<?php

namespace Tests\Feature\RentalApplication;

use App\Models\Property;
use App\Models\Payment;
use App\Models\RentalApplication;
use App\Models\RentalSpace;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RentalApplicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_tenant_can_submit_a_rental_application(): void
    {
        Storage::fake('local');
        $tenant = User::factory()->tenant()->create();
        $space = RentalSpace::factory()->create();
        $leaseEndDate = now()->addYear()->toDateString();

        $response = $this->actingAs($tenant)->post("/tenant/rental-spaces/{$space->id}/apply", [
            'desired_move_in_date' => now()->addWeek()->toDateString(),
            'lease_end_date' => $leaseEndDate,
            'number_of_occupants' => 1,
            'employment_status' => 'employed',
            'documents' => [
                'valid_id_1' => UploadedFile::fake()->create('id1.jpg', 100, 'image/jpeg'),
                'proof_of_income' => UploadedFile::fake()->create('income.pdf', 100, 'application/pdf'),
            ],
        ]);

        $response->assertRedirect(route('tenant.applications.index'));
        $this->assertDatabaseHas('rental_applications', [
            'user_id' => $tenant->id,
            'rental_space_id' => $space->id,
            'status' => 'pending',
        ]);
        $this->assertSame(
            $leaseEndDate,
            RentalApplication::where('user_id', $tenant->id)->where('rental_space_id', $space->id)->firstOrFail()->lease_end_date->toDateString()
        );
        $this->assertDatabaseHas('application_documents', [
            'document_type' => 'proof_of_income',
            'original_filename' => 'income.pdf',
        ]);
    }

    public function test_a_tenant_cannot_view_another_tenants_application(): void
    {
        $tenantA = User::factory()->tenant()->create();
        $tenantB = User::factory()->tenant()->create();
        $space = RentalSpace::factory()->create();

        $application = RentalApplication::create([
            'user_id' => $tenantA->id,
            'property_id' => $space->property_id,
            'rental_space_id' => $space->id,
            'desired_move_in_date' => now()->addWeek(),
            'number_of_occupants' => 1,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($tenantB)->get("/tenant/applications/{$application->id}");

        $response->assertForbidden();
    }

    public function test_a_property_owner_can_approve_an_application_for_their_property(): void
    {
        $owner = User::factory()->owner()->create();
        $tenant = User::factory()->tenant()->create();
        $property = Property::factory()->create(['owner_id' => $owner->id]);
        $space = RentalSpace::factory()->create(['property_id' => $property->id, 'advance_payment' => 2750]);

        $application = RentalApplication::create([
            'user_id' => $tenant->id,
            'property_id' => $property->id,
            'rental_space_id' => $space->id,
            'desired_move_in_date' => now()->addWeek(),
            'number_of_occupants' => 1,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($owner)->patch("/owner/applications/{$application->id}/review", [
            'status' => 'approved',
            'decision_reason' => 'Looks good!',
            'reservation_fee' => 2750,
            'reservation_terms' => 'Pay the reservation fee before contract preparation.',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('rental_applications', [
            'id' => $application->id,
            'status' => 'approved',
            'reviewed_by' => $owner->id,
        ]);

        $this->assertSame(2, $tenant->notifications()->count());
        $approvalNotification = $tenant->notifications()->where('data->type', 'rental_application_status')->firstOrFail();
        $this->assertSame('approved', $approvalNotification->data['status']);
        $this->assertArrayNotHasKey('reservation_fee', $approvalNotification->data);
        $offerNotification = $tenant->notifications()->where('data->type', 'reservation_fee_offered')->firstOrFail();
        $this->assertSame(2750.0, (float) $offerNotification->data['reservation_fee']);
        $this->assertSame('Pay the reservation fee before contract preparation.', $offerNotification->data['reservation_terms']);
        $alert = collect(app(\App\Services\NotificationAlertService::class)->forUser($tenant))
            ->firstWhere('headline', 'Reservation fee and terms received');
        $this->assertNotNull($alert);
        $this->assertSame(route('tenant.applications.show', $application), $alert['url']);
        $this->assertContains(['text' => 'Reservation fee ₱2,750.00', 'tone' => 'neutral'], $alert['chips']);
    }

    public function test_an_application_cannot_be_approved_without_a_reservation_fee_and_terms(): void
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
            'number_of_occupants' => 1,
            'status' => 'pending',
        ]);

        $this->actingAs($owner)->patch(route('owner.applications.review', $application), [
            'status' => 'approved',
        ])->assertSessionHasErrors(['reservation_fee', 'reservation_terms']);

        $this->assertDatabaseHas('rental_applications', ['id' => $application->id, 'status' => 'pending']);
        $this->assertSame(0, $tenant->notifications()->count());
    }

    public function test_an_owner_cannot_review_an_application_for_a_property_they_do_not_own(): void
    {
        $ownerA = User::factory()->owner()->create();
        $ownerB = User::factory()->owner()->create();
        $tenant = User::factory()->tenant()->create();
        $property = Property::factory()->create(['owner_id' => $ownerA->id]);
        $space = RentalSpace::factory()->create(['property_id' => $property->id]);

        $application = RentalApplication::create([
            'user_id' => $tenant->id,
            'property_id' => $property->id,
            'rental_space_id' => $space->id,
            'desired_move_in_date' => now()->addWeek(),
            'number_of_occupants' => 1,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($ownerB)->patch("/owner/applications/{$application->id}/review", [
            'status' => 'approved',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseHas('rental_applications', ['id' => $application->id, 'status' => 'pending']);
    }

    public function test_a_student_can_upload_any_identification_document_and_must_upload_proof_of_income(): void
    {
        Storage::fake('local');
        $tenant = User::factory()->tenant()->create();
        $space = RentalSpace::factory()->create();

        $payload = [
            'desired_move_in_date' => now()->addWeek()->toDateString(),
            'lease_end_date' => now()->addYear()->toDateString(),
            'number_of_occupants' => 1,
            'employment_status' => 'student',
        ];

        $this->actingAs($tenant)->post("/tenant/rental-spaces/{$space->id}/apply", $payload)
            ->assertSessionHasErrors(['documents.valid_id_1', 'documents.proof_of_income']);

        $payload['documents'] = [
            'valid_id_1' => UploadedFile::fake()->create('school-card.pdf', 100, 'application/pdf'),
            'proof_of_income' => UploadedFile::fake()->create('income.pdf', 100, 'application/pdf'),
        ];

        $this->actingAs($tenant)->post("/tenant/rental-spaces/{$space->id}/apply", $payload)
            ->assertRedirect(route('tenant.applications.index'));

        $this->assertDatabaseHas('application_documents', ['document_type' => 'valid_id_1']);
        $this->assertDatabaseHas('application_documents', ['document_type' => 'proof_of_income']);

        $incomeDocument = \App\Models\ApplicationDocument::where('document_type', 'proof_of_income')->firstOrFail();
        $this->assertSame('Proof of income of parents/guardian', $incomeDocument->label);
    }

    public function test_non_students_must_upload_one_valid_id(): void
    {
        Storage::fake('local');
        $tenant = User::factory()->tenant()->create();
        $space = RentalSpace::factory()->create();

        $this->actingAs($tenant)->post("/tenant/rental-spaces/{$space->id}/apply", [
            'desired_move_in_date' => now()->addWeek()->toDateString(),
            'lease_end_date' => now()->addYear()->toDateString(),
            'number_of_occupants' => 1,
            'employment_status' => 'self_employed',
            'documents' => [
                'proof_of_income' => UploadedFile::fake()->create('income.pdf', 100, 'application/pdf'),
            ],
        ])->assertSessionHasErrors(['documents.valid_id_1']);
    }

    public function test_every_employment_status_requires_proof_of_income(): void
    {
        Storage::fake('local');
        $tenant = User::factory()->tenant()->create();
        $space = RentalSpace::factory()->create();

        foreach (['employed', 'self_employed', 'student', 'unemployed'] as $status) {
            $this->actingAs($tenant)->post("/tenant/rental-spaces/{$space->id}/apply", [
                'desired_move_in_date' => now()->addWeek()->toDateString(),
                'lease_end_date' => now()->addYear()->toDateString(),
                'number_of_occupants' => 1,
                'employment_status' => $status,
                'documents' => [
                    'valid_id_1' => UploadedFile::fake()->create('id1.jpg', 100, 'image/jpeg'),
                ],
            ])->assertSessionHasErrors(['documents.proof_of_income']);
        }
    }

    public function test_owner_contract_form_prefills_the_lease_end_date_requested_by_the_renter(): void
    {
        $owner = User::factory()->owner()->create();
        $tenant = User::factory()->tenant()->create();
        $property = Property::factory()->create(['owner_id' => $owner->id]);
        $space = RentalSpace::factory()->create(['property_id' => $property->id]);
        $leaseEndDate = now()->addYear()->toDateString();

        $application = RentalApplication::create([
            'user_id' => $tenant->id,
            'property_id' => $property->id,
            'rental_space_id' => $space->id,
            'desired_move_in_date' => now()->addWeek()->toDateString(),
            'lease_end_date' => $leaseEndDate,
            'number_of_occupants' => 1,
            'status' => 'approved',
            'reservation_fee' => 3000,
            'reservation_terms' => 'Pay before the contract is created.',
            'reservation_offer_sent_at' => now(),
        ]);

        Payment::create([
            'rental_application_id' => $application->id,
            'user_id' => $tenant->id,
            'payment_type' => Payment::TYPE_RESERVATION_FEE,
            'amount' => 3000,
            'due_date' => today(),
            'status' => 'paid',
        ]);

        $this->actingAs($owner)
            ->get(route('owner.applications.contract.create', $application))
            ->assertOk()
            ->assertSee('name="end_date" value="'.$leaseEndDate.'"', false);
    }
}
