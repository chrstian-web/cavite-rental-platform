<?php

namespace App\Services\Payment;

use App\Models\Payment;

interface PaymentGatewayInterface
{
    /** @return array{checkout_id:string, payment_url:string, reference:string|null, raw:array} */
    public function createCheckout(Payment $payment, string $successUrl, string $cancelUrl): array;

    /** @return array{valid:bool,event_id:string|null,event_type:string|null,payload:array} */
    public function validateWebhook(string $payload, ?string $signature): array;

    /** @return array{refund_id:string, status:string, raw:array} */
    public function refund(Payment $payment, ?int $amountMinor = null): array;
}

