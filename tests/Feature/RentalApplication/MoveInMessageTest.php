<?php

namespace Tests\Feature\RentalApplication;

use App\Models\Payment;
use App\Models\Property;
use App\Models\RentalContract;
use App\Models\RentalSpace;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MoveInMessageTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0: User, 1: User, 2: RentalContract} [owner, tenant, contract] */
    private function contract(bool $downPaymentPaid, string $status = 'draft'): array
    {
        $owner = User::factory()->owner()->create();
        $tenant = User::factory()->tenant()->create();
        $property = Property::factory()->create(['owner_id' => $owner->id]);
        $space = RentalSpace::factory()->create(['property_id' => $property->id]);
        $contract = RentalContract::create([
            'user_id' => $tenant->id, 'owner_id' => $owner->id, 'property_id' => $property->id,
            'rental_space_id' => $space->id, 'monthly_rent' => 2000, 'security_deposit' => 1000,
            'advance_payment' => 4000, 'start_date' => now()->addWeek(), 'end_date' => now()->addYear(),
            'status' => $status,
        ]);

        foreach ([Payment::TYPE_SECURITY_DEPOSIT => 1000, Payment::TYPE_ADVANCE_PAYMENT => 4000] as $type => $amount) {
            $contract->payments()->create([
                'user_id' => $tenant->id, 'payment_type' => $type, 'amount' => $amount,
                'currency' => 'PHP', 'due_date' => today(), 'payment_source' => 'manual',
                'status' => $downPaymentPaid ? 'paid' : 'pending',
            ]);
        }

        return [$owner, $tenant, $contract];
    }

    public function test_the_message_box_appears_only_after_all_payments_are_made(): void
    {
        [$owner, , $unpaid] = $this->contract(false);
        $this->actingAs($owner)->get(route('owner.contracts.show', $unpaid))
            ->assertOk()->assertDontSee('Final remarks for the renter');

        [$owner2, , $paid] = $this->contract(true);
        $this->actingAs($owner2)->get(route('owner.contracts.show', $paid))
            ->assertOk()->assertSee('Final remarks for the renter')
            ->assertSee('Send message to renter');
    }

    public function test_the_owner_can_send_a_message_and_the_renter_is_notified_and_sees_it(): void
    {
        [$owner, $tenant, $contract] = $this->contract(true);

        $this->actingAs($owner)
            ->patch(route('owner.contracts.move-in-message', $contract), ['move_in_message' => 'Keys are at the front desk.'])
            ->assertSessionHasNoErrors();

        $contract->refresh();
        $this->assertSame('Keys are at the front desk.', $contract->move_in_message);
        $this->assertNotNull($contract->move_in_message_sent_at);
        $this->assertSame(1, $tenant->notifications()->where('data->type', 'move_in_message')->count());

        $this->actingAs($tenant)->get(route('tenant.contracts.show', $contract))
            ->assertOk()
            ->assertSee('Message from your owner before move-in')
            ->assertSee('Keys are at the front desk.');
    }

    public function test_the_message_is_refused_while_payments_are_still_unpaid(): void
    {
        [$owner, $tenant, $contract] = $this->contract(false);

        $this->actingAs($owner)
            ->patch(route('owner.contracts.move-in-message', $contract), ['move_in_message' => 'Hello'])
            ->assertStatus(422);

        $this->assertNull($contract->fresh()->move_in_message);
        $this->assertSame(0, $tenant->notifications()->count());
    }

    public function test_the_message_is_required_and_limited_in_length(): void
    {
        [$owner, , $contract] = $this->contract(true);

        $this->actingAs($owner)
            ->patch(route('owner.contracts.move-in-message', $contract), ['move_in_message' => ''])
            ->assertSessionHasErrors('move_in_message');
        $this->actingAs($owner)
            ->patch(route('owner.contracts.move-in-message', $contract), ['move_in_message' => str_repeat('a', 2001)])
            ->assertSessionHasErrors('move_in_message');
    }

    public function test_another_owner_cannot_send_a_message_on_this_contract(): void
    {
        [, , $contract] = $this->contract(true);
        $stranger = User::factory()->owner()->create();

        $this->actingAs($stranger)
            ->patch(route('owner.contracts.move-in-message', $contract), ['move_in_message' => 'Hi'])
            ->assertForbidden();
    }
}
