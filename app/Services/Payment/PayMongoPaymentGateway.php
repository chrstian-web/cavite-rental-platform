<?php

namespace App\Services\Payment;

use App\Models\Payment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class PayMongoPaymentGateway implements PaymentGatewayInterface
{
    public function __construct(
        private readonly string $secretKey,
        private readonly string $webhookSecret,
        private readonly string $baseUrl = 'https://api.paymongo.com',
    ) {
    }

    public function createCheckout(Payment $payment, string $successUrl, string $cancelUrl): array
    {
        abort_if($this->secretKey === '', 503, 'PayMongo is not configured.');

        $reference = $payment->gateway_reference ?: 'PAY-'.$payment->id.'-'.Str::upper(Str::random(8));
        $attributes = [
            'line_items' => [[
                'name' => str($payment->payment_type ?: 'Rental payment')->replace('_', ' ')->title()->toString(),
                'amount' => (int) round(((float) $payment->amount) * 100),
                'currency' => $payment->currency ?: 'PHP',
                'quantity' => 1,
            ]],
            'payment_method_types' => config('services.paymongo.payment_methods', ['card', 'gcash', 'qrph']),
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'reference_number' => $reference,
            'send_email_receipt' => false,
            'metadata' => [
                'payment_id' => (string) $payment->id,
                'contract_id' => (string) $payment->rental_contract_id,
            ],
        ];

        $response = Http::withBasicAuth($this->secretKey, '')
            ->acceptJson()
            ->asJson()
            ->post(rtrim($this->baseUrl, '/').'/v2/checkout_sessions', ['data' => ['attributes' => $attributes]])
            ->throw();

        $data = $response->json('data', []);
        $checkoutId = $data['id'] ?? null;
        $paymentUrl = data_get($data, 'attributes.checkout_url');

        if (! $checkoutId || ! $paymentUrl) {
            throw new RuntimeException('PayMongo did not return a checkout URL.');
        }

        return [
            'checkout_id' => $checkoutId,
            'payment_url' => $paymentUrl,
            'reference' => $reference,
            'raw' => $response->json(),
        ];
    }

    public function validateWebhook(string $payload, ?string $signature): array
    {
        if ($this->webhookSecret === '' || ! $signature) {
            return ['valid' => false, 'event_id' => null, 'event_type' => null, 'payload' => []];
        }

        $parts = collect(explode(',', $signature))->mapWithKeys(function (string $part) {
            [$key, $value] = array_pad(explode('=', trim($part), 2), 2, null);
            return [$key => $value];
        });
        $timestamp = $parts->get('t');
        $provided = $parts->get('te') ?: $parts->get('li') ?: $parts->get('v1');
        $livemode = data_get(json_decode($payload, true), 'data.livemode', false);
        $provided = $livemode ? ($parts->get('li') ?: $provided) : ($parts->get('te') ?: $provided);
        $expected = hash_hmac('sha256', $timestamp.'.'.$payload, $this->webhookSecret);

        $decoded = json_decode($payload, true) ?: [];
        return [
            'valid' => is_string($timestamp) && is_string($provided) && hash_equals($expected, $provided),
            'event_id' => data_get($decoded, 'data.id'),
            'event_type' => data_get($decoded, 'data.type'),
            'payload' => $decoded,
        ];
    }

    public function refund(Payment $payment, ?int $amountMinor = null): array
    {
        abort_if($this->secretKey === '', 503, 'PayMongo is not configured.');
        abort_if(! $payment->gateway_payment_id, 422, 'No gateway payment is available to refund.');

        $attributes = ['payment_id' => $payment->gateway_payment_id, 'reason' => 'requested_by_merchant'];
        if ($amountMinor !== null) {
            $attributes['amount'] = $amountMinor;
        }
        $response = Http::withBasicAuth($this->secretKey, '')
            ->acceptJson()->asJson()
            ->post(rtrim($this->baseUrl, '/').'/refunds', ['data' => ['attributes' => $attributes]])
            ->throw();
        $data = $response->json('data', []);

        return [
            'refund_id' => $data['id'] ?? '',
            'status' => data_get($data, 'attributes.status', 'pending'),
            'raw' => $response->json(),
        ];
    }
}

