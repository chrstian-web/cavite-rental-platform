<?php

namespace Tests\Feature\Payment;

use App\Models\Payment;
use App\Models\Property;
use App\Models\RentalApplication;
use App\Models\RentalContract;
use App\Models\RentalSpace;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContractPaymentHistoryTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0: User, 1: User, 2: RentalContract} */
    private function contractWithReservationFee(): array
    {
        $owner = User::factory()->owner()->create();
        $tenant = User::factory()->tenant()->create();
        $property = Property::factory()->create(['owner_id' => $owner->id]);
        $space = RentalSpace::factory()->create(['property_id' => $property->id]);

        $application = RentalApplication::create([
            'user_id' => $tenant->id,
            'property_id' => $property->id,
            'rental_space_id' => $space->id,
            'desired_move_in_date' => now()->addWeek(),
            'lease_end_date' => now()->addYear(),
            'number_of_occupants' => 1,
            'status' => 'approved',
            'reservation_fee' => 2750,
            'reservation_terms' => 'Pay the fee before the contract is prepared.',
            'reservation_offer_sent_at' => now(),
        ]);

        // The reservation fee belongs to the application, not the contract (the contract does not exist yet).
        Payment::create([
            'rental_application_id' => $application->id,
            'user_id' => $tenant->id,
            'payment_type' => Payment::TYPE_RESERVATION_FEE,
            'amount' => 2750,
            'currency' => 'PHP',
            'due_date' => today(),
            'status' => 'paid',
            'payment_source' => 'online',
            'paid_at' => now(),
        ]);

        $contract = RentalContract::create([
            'rental_application_id' => $application->id,
            'user_id' => $tenant->id,
            'owner_id' => $owner->id,
            'property_id' => $property->id,
            'rental_space_id' => $space->id,
            'monthly_rent' => 2500,
            'security_deposit' => 1000,
            'advance_payment' => 5000,
            'start_date' => now()->addWeek(),
            'end_date' => now()->addYear(),
            'status' => 'active',
        ]);

        $contract->payments()->create([
            'user_id' => $tenant->id,
            'payment_type' => Payment::TYPE_SECURITY_DEPOSIT,
            'amount' => 1000,
            'due_date' => today(),
            'status' => 'paid',
        ]);

        return [$owner, $tenant, $contract];
    }

    public function test_tenant_contract_page_lists_the_reservation_fee_in_payment_history(): void
    {
        [, $tenant, $contract] = $this->contractWithReservationFee();

        $this->actingAs($tenant)
            ->get(route('tenant.contracts.show', $contract))
            ->assertOk()
            ->assertSeeInOrder(['Payment history', 'Reservation Fee', '2,750.00', 'Security Deposit', '1,000.00']);
    }

    public function test_owner_contract_page_lists_the_reservation_fee_in_payments(): void
    {
        [$owner, , $contract] = $this->contractWithReservationFee();

        $this->actingAs($owner)
            ->get(route('owner.contracts.show', $contract))
            ->assertOk()
            ->assertSeeInOrder(['Reservation Fee', '2,750.00', 'Security Deposit', '1,000.00']);
    }

    public function test_owner_cannot_mark_the_reservation_fee_as_received_in_person(): void
    {
        [$owner, , $contract] = $this->contractWithReservationFee();
        $reservation = $contract->application->reservationFeePayment;

        $this->actingAs($owner)
            ->patch(route('owner.payments.received', $reservation), [
                'payment_method' => 'gcash',
                'payment_date' => today()->toDateString(),
            ])
            ->assertForbidden();
    }

    public function test_history_without_a_reservation_fee_still_works(): void
    {
        [, $tenant, $contract] = $this->contractWithReservationFee();
        $contract->application->reservationFeePayment()->delete();

        $this->actingAs($tenant)
            ->get(route('tenant.contracts.show', $contract->fresh()))
            ->assertOk()
            ->assertSee('Security Deposit')
            ->assertDontSee('Reservation Fee');
    }

    public function test_payment_history_puts_the_reservation_fee_first_and_does_not_change_contract_payments(): void
    {
        [, , $contract] = $this->contractWithReservationFee();

        $history = $contract->load(['payments', 'application.reservationFeePayment'])->paymentHistory();

        $this->assertSame(
            [Payment::TYPE_RESERVATION_FEE, Payment::TYPE_SECURITY_DEPOSIT],
            $history->pluck('payment_type')->all()
        );
        // Down-payment / activation logic still only sees real contract payments.
        $this->assertCount(1, $contract->payments);
    }
}
