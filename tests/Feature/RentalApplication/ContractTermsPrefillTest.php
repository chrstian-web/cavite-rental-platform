<?php

namespace Tests\Feature\RentalApplication;

use App\Models\Payment;
use App\Models\Property;
use App\Models\RentalApplication;
use App\Models\RentalSpace;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContractTermsPrefillTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_contract_form_is_prefilled_with_the_property_terms_and_conditions(): void
    {
        $owner = User::factory()->owner()->create();
        $tenant = User::factory()->tenant()->create();
        $property = Property::factory()->create([
            'owner_id' => $owner->id,
            'description' => 'No subletting. Quiet hours start at 10 PM.',
        ]);
        $space = RentalSpace::factory()->create(['property_id' => $property->id]);

        $application = RentalApplication::create([
            'user_id' => $tenant->id,
            'property_id' => $property->id,
            'rental_space_id' => $space->id,
            'desired_move_in_date' => now()->addWeek()->toDateString(),
            'lease_end_date' => now()->addYear()->toDateString(),
            'number_of_occupants' => 1,
            'status' => 'approved',
            'reservation_fee' => 1000,
            'reservation_terms' => 'Pay before the contract is created.',
            'reservation_offer_sent_at' => now(),
        ]);

        Payment::create([
            'rental_application_id' => $application->id,
            'user_id' => $tenant->id,
            'payment_type' => Payment::TYPE_RESERVATION_FEE,
            'amount' => 1000,
            'due_date' => today(),
            'status' => 'paid',
        ]);

        $this->actingAs($owner)
            ->get(route('owner.applications.contract.create', $application))
            ->assertOk()
            ->assertSee('No subletting. Quiet hours start at 10 PM.');
    }
}
