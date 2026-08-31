<?php

namespace Tests\Feature\Payment;

use App\Models\Property;
use App\Models\RentalContract;
use App\Models\RentalSpace;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    protected function makeActiveContract(User $owner, User $tenant): RentalContract
    {
        $property = Property::factory()->create(['owner_id' => $owner->id]);
        $space = RentalSpace::factory()->create(['property_id' => $property->id]);

        return RentalContract::create([
            'user_id' => $tenant->id,
            'owner_id' => $owner->id,
            'property_id' => $property->id,
            'rental_space_id' => $space->id,
            'monthly_rent' => 8000,
            'start_date' => now(),
            'end_date' => now()->addYear(),
            'status' => 'active',
        ]);
    }

    public function test_a_property_owner_can_record_a_payment_for_their_contract(): void
    {
        $owner = User::factory()->owner()->create();
        $tenant = User::factory()->tenant()->create();
        $contract = $this->makeActiveContract($owner, $tenant);

        $response = $this->actingAs($owner)->post("/owner/contracts/{$contract->id}/payments", [
            'amount' => 8000,
            'due_date' => now()->addMonth()->toDateString(),
            'status' => 'paid',
            'payment_date' => now()->toDateString(),
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('payments', [
            'rental_contract_id' => $contract->id,
            'amount' => 8000,
            'status' => 'paid',
        ]);
    }

    public function test_an_owner_cannot_record_a_payment_for_another_owners_contract(): void
    {
        $ownerA = User::factory()->owner()->create();
        $ownerB = User::factory()->owner()->create();
        $tenant = User::factory()->tenant()->create();
        $contract = $this->makeActiveContract($ownerA, $tenant);

        $response = $this->actingAs($ownerB)->post("/owner/contracts/{$contract->id}/payments", [
            'amount' => 8000,
            'due_date' => now()->addMonth()->toDateString(),
            'status' => 'paid',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_a_tenant_can_view_their_own_payment_history(): void
    {
        $owner = User::factory()->owner()->create();
        $tenant = User::factory()->tenant()->create();
        $contract = $this->makeActiveContract($owner, $tenant);

        $contract->payments()->create([
            'user_id' => $tenant->id,
            'amount' => 8000,
            'due_date' => now(),
            'status' => 'paid',
        ]);

        $response = $this->actingAs($tenant)->get('/tenant/payments');

        $response->assertOk();
        $response->assertSee('8,000.00');
    }
}
