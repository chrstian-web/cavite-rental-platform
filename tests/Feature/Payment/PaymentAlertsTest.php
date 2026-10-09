<?php

namespace Tests\Feature\Payment;

use App\Models\Payment;
use App\Models\Property;
use App\Models\RentalContract;
use App\Models\RentalSpace;
use App\Models\User;
use App\Notifications\OnlinePaymentReceivedNotification;
use App\Notifications\PaymentDetailsAddedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class PaymentAlertsTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0: User, 1: User, 2: Payment, 3: DatabaseNotification} */
    private function scenario(): array
    {
        $owner = User::factory()->owner()->create();
        $tenant = User::factory()->tenant()->create(['first_name' => 'Juan', 'last_name' => 'Dela Cruz']);
        $property = Property::factory()->create(['owner_id' => $owner->id]);
        $space = RentalSpace::factory()->create(['property_id' => $property->id]);
        $contract = RentalContract::create([
            'user_id' => $tenant->id, 'owner_id' => $owner->id, 'property_id' => $property->id,
            'rental_space_id' => $space->id, 'monthly_rent' => 8000, 'start_date' => now(),
            'end_date' => now()->addYear(), 'status' => 'active',
        ]);
        $payment = $contract->payments()->create([
            'user_id' => $tenant->id, 'payment_type' => 'monthly_rent', 'amount' => 8000, 'currency' => 'PHP',
            'due_date' => now()->addMonth(), 'status' => 'paid', 'payment_source' => 'online', 'gateway' => 'fake',
            'payment_method' => 'gcash', 'reference_number' => 'GC-998877', 'payment_date' => today(), 'paid_at' => now(),
        ]);

        $notification = DatabaseNotification::create([
            'id' => (string) Str::uuid(),
            'type' => OnlinePaymentReceivedNotification::class,
            'notifiable_type' => User::class,
            'notifiable_id' => $owner->id,
            'data' => ['type' => 'online_payment_received', 'payment_id' => $payment->id, 'amount' => 8000.0, 'payment_type' => 'monthly_rent'],
        ]);

        return [$owner, $tenant, $payment, $notification];
    }

    public function test_owner_feed_shows_who_paid_and_the_details(): void
    {
        [$owner, , $payment] = $this->scenario();

        $this->actingAs($owner)->getJson(route('payment-alerts.index'))
            ->assertOk()
            ->assertJsonPath('alerts.0.tenant_name', 'Juan Dela Cruz')
            ->assertJsonPath('alerts.0.amount', '₱8,000.00')
            ->assertJsonPath('alerts.0.reference', 'GC-998877')
            ->assertJsonPath('alerts.0.method', 'Gcash')
            ->assertJsonPath('alerts.0.kind', 'online')
            ->assertJsonPath('alerts.0.url', route('owner.payments.show', $payment));
    }

    public function test_dismissing_marks_the_alert_as_read(): void
    {
        [$owner, , , $notification] = $this->scenario();

        $this->actingAs($owner)->postJson(route('payment-alerts.dismiss', $notification->id))->assertOk();

        $this->assertNotNull($notification->fresh()->read_at);
        $this->actingAs($owner)->getJson(route('payment-alerts.index'))->assertJsonPath('alerts', []);
    }

    public function test_dismiss_all_clears_every_payment_alert(): void
    {
        [$owner, , , $notification] = $this->scenario();

        $this->actingAs($owner)->postJson(route('payment-alerts.dismiss-all'))->assertOk();

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_another_user_cannot_dismiss_someone_elses_alert(): void
    {
        [, , , $notification] = $this->scenario();
        $stranger = User::factory()->owner()->create();

        $this->actingAs($stranger)->postJson(route('payment-alerts.dismiss', $notification->id))->assertOk();

        $this->assertNull($notification->fresh()->read_at);
    }

    public function test_tenants_do_not_get_the_feed(): void
    {
        [, $tenant] = $this->scenario();

        $this->actingAs($tenant)->getJson(route('payment-alerts.index'))->assertForbidden();
    }

    public function test_owner_is_notified_when_a_tenant_adds_payment_details(): void
    {
        Storage::fake('local');
        Notification::fake();
        [$owner, $tenant, $payment] = $this->scenario();

        $this->actingAs($tenant)->post(route('tenant.payments.details', $payment), [
            'reference_number' => 'GC-998877',
            'proof' => UploadedFile::fake()->create('shot.jpg', 100, 'image/jpeg'),
        ])->assertRedirect();

        Notification::assertSentTo($owner, PaymentDetailsAddedNotification::class);
    }

    public function test_the_floating_panel_is_rendered_for_owners(): void
    {
        [$owner] = $this->scenario();

        $this->actingAs($owner)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('paymentAlerts(', false)
            ->assertSee('Juan Dela Cruz');
    }
}
