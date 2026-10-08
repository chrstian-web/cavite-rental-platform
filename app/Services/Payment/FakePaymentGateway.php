<?php

namespace App\Services\Payment;

use App\Models\Payment;
use Illuminate\Support\Str;

class FakePaymentGateway implements PaymentGatewayInterface
{
    public function createCheckout(Payment $payment, string $successUrl, string $cancelUrl): array
    {
        $checkoutId = 'fake_cs_'.$payment->id.'_'.Str::lower(Str::random(8));
        return [
            'checkout_id' => $checkoutId,
            'payment_url' => url('/fake-payments/'.$checkoutId),
            'reference' => 'FAKE-'.$payment->id,
            'raw' => ['id' => $checkoutId, 'status' => 'pending'],
        ];
    }

    public function validateWebhook(string $payload, ?string $signature): array
    {
        $decoded = json_decode($payload, true) ?: [];
        return [
            'valid' => hash_equals((string) config('services.paymongo.webhook_secret'), (string) $signature),
            'event_id' => data_get($decoded, 'data.id'),
            'event_type' => data_get($decoded, 'data.type'),
            'payload' => $decoded,
        ];
    }

    public function refund(Payment $payment, ?int $amountMinor = null): array
    {
        return ['refund_id' => 'fake_ref_'.$payment->id, 'status' => 'succeeded', 'raw' => []];
    }
}

