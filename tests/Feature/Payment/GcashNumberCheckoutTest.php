<?php

namespace Tests\Feature\Payment;

use App\Models\Payment;
use App\Models\Property;
use App\Models\RentalContract;
use App\Models\RentalSpace;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GcashNumberCheckoutTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0: User, 1: Payment} [tenant, payment with a started demo checkout] */
    private function checkout(array $propertyAttributes = []): array
    {
        $owner = User::factory()->owner()->create();
        $tenant = User::factory()->tenant()->create();
        $property = Property::factory()->create($propertyAttributes + ['owner_id' => $owner->id]);
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

        $this->actingAs($tenant)->post(route('tenant.payments.pay-online', $payment));

        return [$tenant, $payment->fresh()];
    }

    public function test_the_owner_can_save_a_gcash_number_on_the_property(): void
    {
        $owner = User::factory()->owner()->create();
        $property = Property::factory()->create(['owner_id' => $owner->id]);
        $payload = [
            'name' => $property->name,
            'property_type' => $property->property_type,
            'location_id' => $property->location_id,
            'address_line' => $property->address_line,
            'availability_status' => 'available',
        ];

        $this->actingAs($owner)->get(route('owner.properties.edit', $property))
            ->assertOk()->assertSee('GCash number');

        $this->actingAs($owner)->put(route('owner.properties.update', $property), $payload + ['gcash_number' => '09171234567'])
            ->assertSessionHasNoErrors();
        $this->assertSame('09171234567', $property->fresh()->gcash_number);

        $this->actingAs($owner)->put(route('owner.properties.update', $property), $payload + ['gcash_number' => '0917'])
            ->assertSessionHasErrors('gcash_number');
    }

    public function test_choosing_gcash_opens_the_gcash_screen_showing_the_owners_number(): void
    {
        [$tenant, $payment] = $this->checkout(['gcash_number' => '09171234567']);

        $this->actingAs($tenant)
            ->post(route('fake-checkout.continue', $payment->gateway_checkout_id), ['payment_method' => 'gcash'])
            ->assertRedirect(route('fake-checkout.gcash', $payment->gateway_checkout_id));

        $this->actingAs($tenant)->get(route('fake-checkout.gcash', $payment->gateway_checkout_id))
            ->assertOk()
            ->assertSee('09171234567')
            ->assertSee('name="amount"', false);

        $this->assertSame('pending', $payment->fresh()->status);
    }

    public function test_the_contact_number_is_used_when_no_gcash_number_is_set(): void
    {
        [$tenant, $payment] = $this->checkout(['gcash_number' => null, 'contact_number' => '09998887777']);

        $this->actingAs($tenant)->get(route('fake-checkout.gcash', $payment->gateway_checkout_id))
            ->assertOk()->assertSee('09998887777');
    }

    public function test_without_any_number_the_renter_is_sent_back_with_a_message(): void
    {
        [$tenant, $payment] = $this->checkout(['gcash_number' => null, 'contact_number' => null]);

        $this->actingAs($tenant)->get(route('fake-checkout.gcash', $payment->gateway_checkout_id))
            ->assertRedirect(route('fake-checkout.show', $payment->gateway_checkout_id))
            ->assertSessionHasErrors('payment_method');
    }

    public function test_the_amount_entered_on_the_gcash_screen_must_match_what_is_due(): void
    {
        [$tenant, $payment] = $this->checkout(['gcash_number' => '09171234567']);
        $checkout = $payment->gateway_checkout_id;

        $this->actingAs($tenant)
            ->post(route('fake-checkout.pay', $checkout), ['payment_method' => 'gcash', 'amount' => 100])
            ->assertSessionHasErrors('amount');
        $this->assertSame('pending', $payment->fresh()->status);

        $this->actingAs($tenant)
            ->post(route('fake-checkout.pay', $checkout), ['payment_method' => 'gcash', 'amount' => 8000])
            ->assertSessionHasNoErrors();
        $this->assertSame('paid', $payment->fresh()->status);
    }

    public function test_bank_transfer_is_no_longer_accepted_at_checkout(): void
    {
        [$tenant, $payment] = $this->checkout();

        $this->actingAs($tenant)
            ->post(route('fake-checkout.continue', $payment->gateway_checkout_id), ['payment_method' => 'bank_transfer'])
            ->assertSessionHasErrors('payment_method');

        $this->assertNotSame('paid', $payment->fresh()->status);
    }

    public function test_the_payment_page_offers_gcash_instead_of_the_paymongo_unavailable_warning(): void
    {
        [$tenant, $payment] = $this->checkout(['gcash_number' => '09171234567']);

        $this->actingAs($tenant)->get(route('tenant.payments.show', $payment))
            ->assertOk()
            ->assertSee('Pay with GCash')
            ->assertSee('Pay by GCash to the owner')
            ->assertDontSee('Secure online payment is unavailable');
    }
}
