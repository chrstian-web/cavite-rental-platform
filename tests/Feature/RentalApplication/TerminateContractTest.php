<?php

namespace Tests\Feature\RentalApplication;

use App\Models\Payment;
use App\Models\Property;
use App\Models\RentalContract;
use App\Models\RentalSpace;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TerminateContractTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0: User, 1: User, 2: RentalContract, 3: RentalSpace} [owner, tenant, contract, space] */
    private function contract(string $status = 'active', array $space = []): array
    {
        $owner = User::factory()->owner()->create();
        $tenant = User::factory()->tenant()->create();
        $property = Property::factory()->create(['owner_id' => $owner->id]);
        $rentalSpace = RentalSpace::factory()->create($space + [
            'property_id' => $property->id, 'total_capacity' => 1, 'occupied_capacity' => 1, 'status' => 'occupied',
        ]);
        $contract = RentalContract::create([
            'user_id' => $tenant->id, 'owner_id' => $owner->id, 'property_id' => $property->id,
            'rental_space_id' => $rentalSpace->id, 'monthly_rent' => 2000, 'start_date' => now()->subMonth(),
            'end_date' => now()->addYear(), 'status' => $status,
        ]);

        return [$owner, $tenant, $contract, $rentalSpace];
    }

    public function test_terminating_an_active_contract_frees_the_space(): void
    {
        [$owner, , $contract, $space] = $this->contract();

        $this->actingAs($owner)->patch(route('owner.contracts.terminate', $contract))
            ->assertSessionHasNoErrors();

        $this->assertSame('terminated', $contract->fresh()->status);
        $this->assertSame(0, $space->fresh()->occupied_capacity);
        $this->assertSame('available', $space->fresh()->status);
    }

    public function test_terminating_twice_does_not_free_the_space_twice(): void
    {
        [$owner, , $contract, $space] = $this->contract('active', ['total_capacity' => 3, 'occupied_capacity' => 2, 'status' => 'available']);

        $this->actingAs($owner)->patch(route('owner.contracts.terminate', $contract));
        $this->actingAs($owner)->patch(route('owner.contracts.terminate', $contract))
            ->assertSessionHasErrors('terminate');

        $this->assertSame(1, $space->fresh()->occupied_capacity);
    }

    public function test_only_an_active_contract_can_be_terminated(): void
    {
        foreach (['draft', 'expired'] as $status) {
            [$owner, , $contract] = $this->contract($status);

            $this->actingAs($owner)->patch(route('owner.contracts.terminate', $contract))
                ->assertSessionHasErrors('terminate');

            $this->assertSame($status, $contract->fresh()->status);
        }
    }

    public function test_occupancy_never_goes_below_zero(): void
    {
        [$owner, , $contract, $space] = $this->contract('active', ['occupied_capacity' => 0, 'status' => 'available']);

        $this->actingAs($owner)->patch(route('owner.contracts.terminate', $contract));

        $this->assertSame(0, $space->fresh()->occupied_capacity);
    }

    public function test_another_owner_cannot_terminate_the_contract(): void
    {
        [, , $contract] = $this->contract();
        $stranger = User::factory()->owner()->create();

        $this->actingAs($stranger)->patch(route('owner.contracts.terminate', $contract))->assertForbidden();

        $this->assertSame('active', $contract->fresh()->status);
    }

    public function test_the_renter_can_no_longer_pay_online_after_termination(): void
    {
        [$owner, $tenant, $contract] = $this->contract();
        $payment = $contract->payments()->create([
            'user_id' => $tenant->id, 'payment_type' => 'monthly_rent', 'amount' => 2000, 'currency' => 'PHP',
            'due_date' => now()->addWeek(), 'status' => 'pending', 'payment_source' => 'manual',
        ]);

        $this->actingAs($owner)->patch(route('owner.contracts.terminate', $contract));

        $this->actingAs($tenant)->post(route('tenant.payments.pay-online', $payment))->assertForbidden();
        $this->actingAs($tenant)->get(route('tenant.payments.show', $payment))
            ->assertOk()->assertDontSee('Pay with GCash');
    }
}
