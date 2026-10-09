<?php

namespace Tests\Feature\Payment;

use App\Models\Payment;
use App\Models\Property;
use App\Models\RentalContract;
use App\Models\RentalSpace;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OnlinePaymentTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0: User, 1: Payment, 2: User} [tenant, payment, owner] */
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

        return [$tenant, $payment, $owner];
    }

    private function usePaymongo(): void
    {
        config([
            'services.payment_gateway' => 'paymongo',
            'services.paymongo.secret_key' => 'sk_test_example',
            'services.paymongo.webhook_secret' => 'whsec_test',
        ]);
    }

    /** Builds the raw body + signature header PayMongo would send. */
    private function signedWebhook(Payment $payment, string $eventId = 'evt_1', string $secret = 'whsec_test'): array
    {
        $body = json_encode(['data' => [
            'id' => $eventId,
            'type' => 'event',
            'attributes' => [
                'type' => 'checkout_session.payment.paid',
                'livemode' => false,
                'data' => [
                    'id' => $payment->gateway_checkout_id,
                    'attributes' => [
                        'payments' => [[
                            'id' => 'pay_1',
                            'attributes' => ['amount' => 800000, 'source' => ['type' => 'gcash']],
                        ]],
                    ],
                ],
            ],
        ]]);

        $timestamp = time();
        $signature = 't='.$timestamp.',te='.hash_hmac('sha256', $timestamp.'.'.$body, $secret);

        return [$body, $signature];
    }

    private function sendWebhook(string $body, string $signature)
    {
        return $this->call('POST', '/api/webhooks/paymongo', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_PAYMONGO_SIGNATURE' => $signature,
        ], $body);
    }

    // ── Test (fake) checkout ─────────────────────────────────────

    public function test_pay_online_uses_the_server_amount_and_starts_a_checkout(): void
    {
        [$tenant, $payment] = $this->payment();

        $response = $this->actingAs($tenant)->post(route('tenant.payments.pay-online', $payment), ['amount' => 1]);

        $response->assertRedirect();
        $this->assertStringContainsString('/fake-payments/', (string) $response->headers->get('Location'));

        $payment->refresh();
        $this->assertSame('pending', $payment->status);   // not paid until the gateway confirms
        $this->assertSame('online', $payment->payment_source);
        $this->assertSame('fake', $payment->gateway);
        $this->assertSame(8000.0, (float) $payment->amount); // the amount sent by the browser is ignored
        $this->assertNotNull($payment->gateway_checkout_id);
    }

    public function test_finishing_the_test_checkout_marks_it_paid_and_returns_to_the_payment_page(): void
    {
        [$tenant, $payment, $owner] = $this->payment();
        $this->actingAs($tenant)->post(route('tenant.payments.pay-online', $payment));
        $payment->refresh();

        $this->actingAs($tenant)
            ->post(route('fake-checkout.pay', $payment->gateway_checkout_id))
            ->assertRedirect(route('tenant.payments.show', $payment).'#payment-details');

        $payment->refresh();
        $this->assertSame('paid', $payment->status);
        $this->assertNotNull($payment->paid_at);
        $this->assertNotNull($payment->receipt_number);
        $this->assertSame(1, $owner->notifications()->count());

        // Paying again changes nothing and does not notify the owner twice.
        $this->actingAs($tenant)->post(route('fake-checkout.pay', $payment->gateway_checkout_id));
        $this->assertSame(1, $owner->fresh()->notifications()->count());
    }

    public function test_another_tenant_cannot_start_checkout_for_this_payment(): void
    {
        [, $payment] = $this->payment();
        $other = User::factory()->tenant()->create();

        $this->actingAs($other)->post(route('tenant.payments.pay-online', $payment))->assertForbidden();
    }

    // ── PayMongo webhook ─────────────────────────────────────────

    private function startPaymongoCheckout(User $tenant, Payment $payment): Payment
    {
        $this->usePaymongo();
        Http::fake(['https://api.paymongo.com/*' => Http::response([
            'data' => ['id' => 'cs_123', 'attributes' => ['checkout_url' => 'https://checkout.paymongo.com/cs_123']],
        ])]);

        $this->actingAs($tenant)->post(route('tenant.payments.pay-online', $payment))
            ->assertRedirect('https://checkout.paymongo.com/cs_123');

        return $payment->fresh();
    }

    public function test_valid_webhook_marks_payment_paid_and_duplicate_is_idempotent(): void
    {
        [$tenant, $payment, $owner] = $this->payment();
        $payment = $this->startPaymongoCheckout($tenant, $payment);
        [$body, $signature] = $this->signedWebhook($payment);

        $this->sendWebhook($body, $signature)->assertOk();

        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'paid']);
        $this->assertDatabaseCount('payment_events', 2); // checkout_created + paid
        $this->assertSame(1, $owner->notifications()->count());

        // PayMongo retries the same event: acknowledged, but nothing changes.
        $this->sendWebhook($body, $signature)->assertOk();
        $this->assertDatabaseCount('payment_events', 2);
        $this->assertSame(1, $owner->fresh()->notifications()->count());
    }

    public function test_webhook_with_a_wrong_signature_cannot_change_a_payment(): void
    {
        [$tenant, $payment] = $this->payment();
        $payment = $this->startPaymongoCheckout($tenant, $payment);
        [$body, $signature] = $this->signedWebhook($payment, 'evt_2', 'not-the-real-secret');

        $this->sendWebhook($body, $signature)->assertStatus(400);

        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'pending']);
    }

    public function test_webhook_with_the_wrong_amount_does_not_mark_the_payment_paid(): void
    {
        [$tenant, $payment] = $this->payment();
        $payment = $this->startPaymongoCheckout($tenant, $payment);

        $body = json_encode(['data' => [
            'id' => 'evt_3', 'type' => 'event',
            'attributes' => [
                'type' => 'checkout_session.payment.paid', 'livemode' => false,
                'data' => ['id' => 'cs_123', 'attributes' => ['payments' => [['id' => 'pay_9', 'attributes' => ['amount' => 100, 'source' => ['type' => 'gcash']]]]]],
            ],
        ]]);
        $timestamp = time();
        $signature = 't='.$timestamp.',te='.hash_hmac('sha256', $timestamp.'.'.$body, 'whsec_test');

        $this->sendWebhook($body, $signature)->assertOk();

        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'pending']);
    }

    public function test_webhook_is_refused_while_the_fake_gateway_is_active(): void
    {
        config(['services.payment_gateway' => 'fake']);

        $this->sendWebhook('{}', 't=1,te=abc')->assertNotFound();
    }
}
