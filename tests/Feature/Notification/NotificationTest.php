<?php

namespace Tests\Feature\Notification;

use App\Models\Location;
use App\Models\Property;
use App\Models\RentalApplication;
use App\Models\RentalContract;
use App\Models\RentalSpace;
use App\Models\User;
use App\Notifications\ContractActivatedNotification;
use App\Notifications\NewPropertySubmittedNotification;
use App\Services\RentalContractService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_is_notified_when_a_new_property_is_submitted(): void
    {
        Notification::fake();

        $admin = User::factory()->superAdmin()->create();
        $owner = User::factory()->owner()->create();
        $location = Location::factory()->create();

        $this->actingAs($owner)->post('/owner/properties', [
            'name' => 'Notify Me Place',
            'property_type' => 'dormitory',
            'location_id' => $location->id,
            'address_line' => '1 Test St',
        ]);

        Notification::assertSentTo($admin, NewPropertySubmittedNotification::class);
    }

    public function test_tenant_is_notified_when_their_contract_is_activated(): void
    {
        Notification::fake();

        $owner = User::factory()->owner()->create();
        $tenant = User::factory()->tenant()->create();
        $property = Property::factory()->create(['owner_id' => $owner->id]);
        $space = RentalSpace::factory()->create(['property_id' => $property->id]);

        $contract = RentalContract::create([
            'user_id' => $tenant->id,
            'owner_id' => $owner->id,
            'property_id' => $property->id,
            'rental_space_id' => $space->id,
            'monthly_rent' => 8000,
            'start_date' => now(),
            'end_date' => now()->addYear(),
            'status' => 'draft',
        ]);

        app(RentalContractService::class)->activate($contract);

        Notification::assertSentTo($tenant, ContractActivatedNotification::class);
    }

    public function test_the_notification_center_page_loads_for_an_authenticated_user(): void
    {
        $tenant = User::factory()->tenant()->create();

        $response = $this->actingAs($tenant)->get('/notifications');

        $response->assertOk();
    }

    public function test_mark_all_read_actually_marks_notifications_as_read(): void
    {
        $tenant = User::factory()->tenant()->create();

        // Insert a notification row directly — this test only cares about
        // the mark-all-read mechanism, not any specific notification's content.
        $tenant->notifications()->create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'type' => 'App\\Notifications\\ContractActivatedNotification',
            'data' => ['type' => 'contract_activated', 'property_name' => 'Test'],
        ]);

        $this->assertEquals(1, $tenant->fresh()->unreadNotifications()->count());

        $response = $this->actingAs($tenant)->patch('/notifications/read-all');

        $response->assertRedirect();
        $this->assertEquals(0, $tenant->fresh()->unreadNotifications()->count());
    }
}
