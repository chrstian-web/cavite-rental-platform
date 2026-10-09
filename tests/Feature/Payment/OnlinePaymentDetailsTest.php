<?php

namespace Tests\Feature\Payment;

use App\Models\Payment;
use App\Models\Property;
use App\Models\RentalContract;
use App\Models\RentalSpace;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OnlinePaymentDetailsTest extends TestCase
{
    use RefreshDatabase;

    private function onlinePaidPayment(): array
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
            'currency' => 'PHP', 'due_date' => now()->addMonth(), 'status' => 'paid',
            'payment_source' => 'online', 'gateway' => 'fake', 'payment_date' => today(), 'paid_at' => now(),
        ]);

        return [$tenant, $payment];
    }

    public function test_tenant_can_add_reference_and_screenshot_after_paying_online(): void
    {
        Storage::fake('local');
        [$tenant, $payment] = $this->onlinePaidPayment();

        $this->actingAs($tenant)->post(route('tenant.payments.details', $payment), [
            'reference_number' => 'GC-123456',
            'proof' => UploadedFile::fake()->create('shot.jpg', 100, 'image/jpeg'),
        ])->assertRedirect(route('tenant.payments.show', $payment));

        $payment->refresh();
        $this->assertSame('GC-123456', $payment->reference_number);
        $this->assertNotNull($payment->proof_path);
        $this->assertSame('paid', $payment->status);
    }

    public function test_reference_and_screenshot_are_required(): void
    {
        [$tenant, $payment] = $this->onlinePaidPayment();

        $this->actingAs($tenant)->post(route('tenant.payments.details', $payment), [])
            ->assertSessionHasErrors(['reference_number', 'proof']);
    }

    public function test_another_tenant_cannot_add_details(): void
    {
        [, $payment] = $this->onlinePaidPayment();
        $other = User::factory()->tenant()->create();

        $this->actingAs($other)->post(route('tenant.payments.details', $payment), [
            'reference_number' => 'X',
            'proof' => UploadedFile::fake()->create('shot.jpg', 100, 'image/jpeg'),
        ])->assertForbidden();
    }

    public function test_details_cannot_be_added_to_an_unpaid_payment(): void
    {
        [$tenant, $payment] = $this->onlinePaidPayment();
        $payment->update(['status' => 'pending']);

        $this->actingAs($tenant)->post(route('tenant.payments.details', $payment), [
            'reference_number' => 'X',
            'proof' => UploadedFile::fake()->create('shot.jpg', 100, 'image/jpeg'),
        ])->assertForbidden();
    }
}
