<?php

namespace Tests\Feature\Payment;

use App\Models\Payment;
use App\Models\Property;
use App\Models\RentalContract;
use App\Models\RentalSpace;
use App\Models\User;
use App\Services\RentScheduleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class RentScheduleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // "Today" is Oct 9, 2026; the lease runs Aug 8, 2026 – Aug 7, 2027 (12 monthly installments on the 8th).
        $this->travelTo(Carbon::parse('2026-10-09 10:00:00'));
    }

    /** @return array{0: User, 1: RentalContract} */
    private function contract(array $overrides = []): array
    {
        $owner = User::factory()->owner()->create();
        $tenant = User::factory()->tenant()->create();
        $property = Property::factory()->create(['owner_id' => $owner->id]);
        $space = RentalSpace::factory()->create(['property_id' => $property->id]);

        $contract = RentalContract::create(array_merge([
            'user_id' => $tenant->id, 'owner_id' => $owner->id, 'property_id' => $property->id,
            'rental_space_id' => $space->id, 'monthly_rent' => 8000,
            'start_date' => '2026-08-08', 'end_date' => '2027-08-07', 'status' => 'active',
        ], $overrides));

        return [$tenant, $contract];
    }

    private function rentPayment(RentalContract $contract, string $dueDate, string $status): Payment
    {
        return $contract->payments()->create([
            'user_id' => $contract->user_id, 'payment_type' => 'monthly_rent', 'amount' => 8000,
            'currency' => 'PHP', 'due_date' => $dueDate, 'status' => $status, 'payment_source' => 'manual',
        ]);
    }

    public function test_the_schedule_has_one_installment_per_month_of_the_lease(): void
    {
        [, $contract] = $this->contract();

        $schedule = app(RentScheduleService::class)->forContract($contract);

        $this->assertSame(12, $schedule['total']);
        $this->assertSame('2026-08-08', $schedule['rows']->first()['due_date']->toDateString());
        $this->assertSame('2027-07-08', $schedule['rows']->last()['due_date']->toDateString());
    }

    public function test_paid_overdue_and_upcoming_months_are_told_apart(): void
    {
        [, $contract] = $this->contract();
        $this->rentPayment($contract, '2026-08-08', 'paid');

        $schedule = app(RentScheduleService::class)->forContract($contract);
        $states = $schedule['rows']->pluck('state', 'month');

        $this->assertSame('paid', $states['2026-08']);
        $this->assertSame('overdue', $states['2026-09']);
        $this->assertSame('overdue', $states['2026-10']);
        $this->assertSame('scheduled', $states['2026-12']);
        $this->assertSame(1, $schedule['paid']);
        $this->assertSame('2026-09', $schedule['next']['month']);   // oldest unpaid month comes first
        $this->assertSame('start', $schedule['next']['action']);
    }

    public function test_a_paid_advance_payment_covers_the_first_month(): void
    {
        [, $contract] = $this->contract(['advance_payment' => 8000]);
        $contract->payments()->create([
            'user_id' => $contract->user_id, 'payment_type' => 'advance_payment', 'amount' => 8000,
            'currency' => 'PHP', 'due_date' => '2026-08-07', 'status' => 'paid', 'payment_source' => 'manual',
        ]);

        $first = app(RentScheduleService::class)->forContract($contract)['rows']->first();

        $this->assertSame('paid', $first['state']);
        $this->assertSame('Covered by your advance payment', $first['note']);
    }

    public function test_a_submitted_payment_waits_for_the_owner_and_the_next_month_moves_on(): void
    {
        [, $contract] = $this->contract(['start_date' => '2026-09-08', 'end_date' => '2027-09-07']);
        $this->rentPayment($contract, '2026-09-08', 'submitted');

        $schedule = app(RentScheduleService::class)->forContract($contract);

        $this->assertSame('submitted', $schedule['rows']->first()['state']);
        $this->assertSame('2026-10', $schedule['next']['month']);
    }

    public function test_pay_now_creates_one_pending_payment_and_opens_it(): void
    {
        [$tenant, $contract] = $this->contract();
        $this->rentPayment($contract, '2026-08-08', 'paid');

        $response = $this->actingAs($tenant)->post(route('tenant.contracts.rent.start', [$contract, '2026-09']));

        $payment = $contract->payments()->where('payment_type', 'monthly_rent')->where('status', 'pending')->first();
        $this->assertNotNull($payment);
        $this->assertSame('2026-09-08', $payment->due_date->toDateString());
        $this->assertSame(8000.0, (float) $payment->amount);
        $response->assertRedirect(route('tenant.payments.show', $payment));

        // Clicking again opens the same payment instead of creating another.
        $this->actingAs($tenant)->post(route('tenant.contracts.rent.start', [$contract, '2026-09']))
            ->assertRedirect(route('tenant.payments.show', $payment));
        $this->assertSame(2, $contract->payments()->where('payment_type', 'monthly_rent')->count());
    }

    public function test_a_month_far_in_the_future_cannot_be_paid_yet(): void
    {
        [$tenant, $contract] = $this->contract();

        $this->actingAs($tenant)->post(route('tenant.contracts.rent.start', [$contract, '2027-03']))->assertForbidden();
        $this->assertSame(0, $contract->payments()->count());
    }

    public function test_another_tenant_cannot_start_a_payment_on_this_contract(): void
    {
        [, $contract] = $this->contract();
        $other = User::factory()->tenant()->create();

        $this->actingAs($other)->post(route('tenant.contracts.rent.start', [$contract, '2026-09']))->assertForbidden();
    }

    public function test_the_dashboard_shows_the_whole_term(): void
    {
        [$tenant, $contract] = $this->contract();
        $this->rentPayment($contract, '2026-08-08', 'paid');

        $this->actingAs($tenant)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Rent schedule')
            ->assertSeeText('1 of 12 months paid')
            ->assertSee('Show all 12 months')
            ->assertSee('Overdue');
    }
}
