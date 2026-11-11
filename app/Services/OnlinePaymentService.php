<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\PaymentEvent;
use App\Notifications\OnlinePaymentReceivedNotification;
use App\Notifications\PaymentReviewedNotification;
use App\Services\Payment\FakePaymentGateway;
use App\Services\Payment\PayMongoPaymentGateway;
use App\Services\Payment\PaymentGatewayInterface;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OnlinePaymentService
{
    public function gatewayName(): string
    {
        return config('services.payment_gateway', 'paymongo') === 'fake' ? 'fake' : 'paymongo';
    }

    public function isFake(): bool
    {
        return $this->gatewayName() === 'fake';
    }

    /** The fake gateway has no real settlement, so it is limited to local development and tests. */
    public function fakeAllowed(): bool
    {
        return $this->isFake() && app()->environment(['local', 'testing']);
    }

    public function gateway(): PaymentGatewayInterface
    {
        if ($this->gatewayName() === 'paymongo') {
            return new PayMongoPaymentGateway(
                (string) config('services.paymongo.secret_key', ''),
                (string) config('services.paymongo.webhook_secret', ''),
            );
        }

        return new FakePaymentGateway();
    }

    /**
     * Creates a checkout session for a pending payment and returns the URL
     * the tenant must be sent to.
     */
    public function startCheckout(Payment $payment, string $successUrl, string $cancelUrl): string
    {
        abort_unless($payment->canBeSubmittedByTenant(), 422, 'This payment cannot be paid online right now.');
        if ($payment->payment_type === Payment::TYPE_RESERVATION_FEE) {
            $application = $payment->application;
            abort_unless(
                $application
                    && $application->status === 'approved'
                    && (float) $payment->amount === (float) $application->reservation_fee
                    && $application->reservation_terms_accepted_at
                    && $application->reservation_terms_accepted_hash
                    && hash_equals($application->reservation_terms_accepted_hash, hash('sha256', (string) $application->reservation_terms)),
                422,
                'You must agree to the owner’s current reservation terms before payment.'
            );
        }
        abort_if($this->isFake() && ! $this->fakeAllowed(), 503, 'Online payments are not configured.');
        $existingUrl = data_get($payment->metadata, 'gateway_checkout_url');
        if ($payment->gateway === $this->gatewayName() && $payment->gateway_checkout_id && $existingUrl) {
            return $existingUrl;
        }

        $result = $this->gateway()->createCheckout($payment, $successUrl, $cancelUrl);

        $payment->update([
            'payment_source' => 'online',
            'gateway' => $this->gatewayName(),
            'gateway_checkout_id' => $result['checkout_id'],
            'gateway_reference' => $result['reference'] ?? null,
            'metadata' => array_merge($payment->metadata ?? [], ['gateway_checkout_url' => $result['payment_url']]),
        ]);

        PaymentEvent::create([
            'payment_id' => $payment->id,
            'event' => 'checkout_created',
            'source' => $this->gatewayName(),
            'metadata' => ['checkout_id' => $result['checkout_id']],
        ]);

        return $result['payment_url'];
    }

    /**
     * Verifies and processes a PayMongo webhook. Safe to receive the same
     * event more than once (PayMongo retries): duplicates are ignored.
     */
    public function handlePaymongoWebhook(string $payload, ?string $signature): void
    {
        $check = $this->gateway()->validateWebhook($payload, $signature);
        abort_unless($check['valid'], 400, 'Invalid webhook signature.');

        $decoded = $check['payload'];
        $eventId = $check['event_id'];
        $type = data_get($decoded, 'data.attributes.type');

        // Only a confirmed payment changes anything. Other events are acknowledged and ignored.
        if ($type !== 'checkout_session.payment.paid') {
            return;
        }

        $session = data_get($decoded, 'data.attributes.data', []);
        $checkoutId = data_get($session, 'id');

        $payment = $checkoutId
            ? Payment::where('gateway', 'paymongo')->where('gateway_checkout_id', $checkoutId)->first()
            : null;

        if (! $payment) {
            Log::warning('PayMongo webhook for an unknown checkout.', ['checkout_id' => $checkoutId, 'event_id' => $eventId]);

            return;
        }

        $paidMinor = data_get($session, 'attributes.payments.0.attributes.amount');
        $expectedMinor = (int) round(((float) $payment->amount) * 100);

        if ($paidMinor === null || (int) $paidMinor !== $expectedMinor) {
            Log::warning('PayMongo amount missing or mismatched; payment not marked paid.', [
                'payment_id' => $payment->id, 'expected' => $expectedMinor, 'received' => $paidMinor,
            ]);

            return;
        }

        try {
            DB::transaction(function () use ($payment, $eventId, $type, $session, $checkoutId) {
                PaymentEvent::create([
                    'payment_id' => $payment->id,
                    'event' => $type,
                    'source' => 'paymongo',
                    'gateway_event_id' => $eventId,
                    'metadata' => ['checkout_id' => $checkoutId],
                ]);

                $this->markPaid(
                    $payment,
                    'paymongo',
                    data_get($session, 'attributes.payments.0.id'),
                    data_get($session, 'attributes.payment_method_used') ?: data_get($session, 'attributes.payments.0.attributes.source.type'),
                );
            });
        } catch (UniqueConstraintViolationException) {
            // This exact event was already processed.
        }
    }

    /**
     * Marks a payment as paid because the gateway confirmed it. Idempotent.
     */
    public function markPaid(Payment $payment, string $gateway, ?string $gatewayPaymentId, ?string $method): Payment
    {
        return DB::transaction(function () use ($payment, $gateway, $gatewayPaymentId, $method) {
            $locked = Payment::whereKey($payment->id)->lockForUpdate()->firstOrFail();

            if ($locked->status === 'paid') {
                return $locked;
            }

            if ($locked->payment_type === Payment::TYPE_RESERVATION_FEE) {
                $application = $locked->application;
                abort_unless(
                    $application
                        && $application->status === 'approved'
                        && (float) $locked->amount === (float) $application->reservation_fee
                        && $application->reservation_terms_accepted_at
                        && $application->reservation_terms_accepted_hash
                        && hash_equals($application->reservation_terms_accepted_hash, hash('sha256', (string) $application->reservation_terms)),
                    422,
                    'The reservation terms or fee changed after acceptance; payment cannot be confirmed.'
                );
            }

            $fromStatus = $locked->status;

            $locked->update([
                'status' => 'paid',
                'payment_source' => 'online',
                'gateway' => $gateway,
                'gateway_payment_id' => $gatewayPaymentId,
                // GCash is the only supported payment method.
                'payment_method' => 'gcash',
                'reference_number' => $locked->gateway_reference ?: $locked->reference_number,
                'payment_date' => today(),
                'paid_at' => now(),
                'receipt_number' => $locked->receipt_number ?: 'RCT-'.now()->format('Ymd').'-'.str_pad((string) $locked->id, 6, '0', STR_PAD_LEFT),
                'review_reason' => null,
                'metadata' => array_merge($locked->metadata ?? [], ['paid_via' => $method]),
            ]);

            $locked->statusHistories()->create([
                'actor_id' => null,
                'from_status' => $fromStatus,
                'to_status' => 'paid',
                'reason' => $gateway === 'fake'
                    ? 'Payment recorded via '.str($method)->replace('_', ' ')->title().'.'
                    : 'Paid online ('.$gateway.'); confirmed automatically.',
            ]);

            $fresh = $locked->fresh();

            $fresh->tenant->notify(new PaymentReviewedNotification($fresh));

            if ($fresh->rental_application_id) {
                $application = $fresh->application()->with('property.managers', 'property.owner')->first();
                $recipients = collect([$application?->property?->owner])->merge($application?->property?->managers ?? collect());
            } else {
                $contract = $fresh->contract()->with('property.managers', 'owner')->first();
                $recipients = collect([$contract?->owner])->merge($contract?->property?->managers ?? collect());
            }
            $recipients = $recipients->filter()->unique('id');
            foreach ($recipients as $recipient) {
                $recipient->notify(new OnlinePaymentReceivedNotification($fresh));
            }

            return $fresh;
        });
    }
}
