<?php

namespace Tests\Feature\Payment;

use App\Models\Payment;
use App\Models\Property;
use App\Models\RentalContract;
use App\Models\RentalSpace;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OnlinePaymentTest extends TestCase
{
    use RefreshDatabase;

    private function payment(): array
    {
        $owner = User::factory()->owner()->create();
        $tenant = User::factory()->tenant()->create();
        $property = Property::factory()->create(['owner_id' => $owner->id]);
        $space = RentalSpace::factory()->create(['property_id' => $property->id]);
        $contract = RentalContract::create([
            'user_id' => $tenant->id, 'owner_id' => $owner->id, 'property_id' => $property->id,
            'rental_space_id' => $space->id, 'monthly_rent' => 8000, 'start_date' => now(),
            'end_date' => now()->addYear(), 'status' => 'active',
        ]);
        $payment = $contract->payments()->create([
            'user_id' => $tenant->id, 'payment_type' => 'monthly_rent', 'amount' => 8000,
            'currency' => 'PHP', 'due_date' => now()->addMonth(), 'status' => 'pending',
            'payment_source' => 'manual',
        ]);
        return [$tenant, $payment];
    }

    public function test_checkout_uses_server_amount_and_enters_processing(): void
    {
        [$tenant, $payment] = $this->payment();

        $response = $this->actingAs($tenant)->post(route('tenant.payments.checkout', $payment), [
            'amount' => 1,
        ]);

        $response->assertRedirect();
        $payment->refresh();
        $this->assertSame('processing', $payment->status);
        $this->assertSame('gateway', $payment->payment_source);
        $this->assertSame('PHP', $payment->currency);
        $this->assertSame(8000.0, (float) $payment->amount);
        $this->assertNotNull($payment->gateway_checkout_id);
    }

    public function test_valid_webhook_marks_payment_paid_and_duplicate_is_idempotent(): void
    {
        config(['services.paymongo.webhook_secret' => 'test-secret']);
        [$tenant, $payment] = $this->payment();
        $this->actingAs($tenant)->post(route('tenant.payments.checkout', $payment));
        $payment->refresh();

        $payload = [
            'data' => [
                'id' => $payment->gateway_checkout_id,
                'type' => 'checkout_session.payment.paid',
                'data' => [
                    'id' => $payment->gateway_checkout_id,
                    'attributes' => [
                        'metadata' => ['payment_id' => (string) $payment->id],
                        'payments' => [[
                            'id' => 'pay_fake_'.$payment->id,
                            'attributes' => ['amount' => 800000, 'source' => ['type' => 'gcash']],
                        ]],
                    ],
                ],
            ],
        ];

        $first = $this->withHeader('Paymongo-Signature', 'test-secret')
            ->postJson('/api/v1/payments/webhook/paymongo', $payload);
        $first->assertOk();
        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'paid']);
        $this->assertDatabaseCount('payment_events', 2);

        $second = $this->withHeader('Paymongo-Signature', 'test-secret')
            ->postJson('/api/v1/payments/webhook/paymongo', $payload);
        $second->assertOk()->assertJsonPath('data.duplicate', true);
        $this->assertDatabaseCount('payment_events', 2);
    }

    public function test_invalid_webhook_cannot_change_payment(): void
    {
        config(['services.paymongo.webhook_secret' => 'test-secret']);
        [$tenant, $payment] = $this->payment();
        $this->actingAs($tenant)->post(route('tenant.payments.checkout', $payment));
        $payment->refresh();

        $response = $this->withHeader('Paymongo-Signature', 'wrong-secret')
            ->postJson('/api/v1/payments/webhook/paymongo', ['data' => ['id' => $payment->gateway_checkout_id]]);
        $response->assertStatus(400);
        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'processing']);
    }
}

