<?php

namespace Tests\Feature\RentalApplication;

use App\Models\Property;
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

        $response = $this->actingAs($tenant)->post("/tenant/rental-spaces/{$space->id}/apply", [
            'desired_move_in_date' => now()->addWeek()->toDateString(),
            'number_of_occupants' => 1,
            'employment_status' => 'employed',
            'documents' => [
                'valid_id_1' => UploadedFile::fake()->image('id1.jpg'),
                'valid_id_2' => UploadedFile::fake()->image('id2.jpg'),
            ],
        ]);

        $response->assertRedirect(route('tenant.applications.index'));
        $this->assertDatabaseHas('rental_applications', [
            'user_id' => $tenant->id,
            'rental_space_id' => $space->id,
            'status' => 'pending',
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
        $space = RentalSpace::factory()->create(['property_id' => $property->id]);

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
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('rental_applications', [
            'id' => $application->id,
            'status' => 'approved',
            'reviewed_by' => $owner->id,
        ]);
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

    public function test_a_student_must_upload_a_student_id_and_both_parents_ids(): void
    {
        Storage::fake('local');
        $tenant = User::factory()->tenant()->create();
        $space = RentalSpace::factory()->create();

        $payload = [
            'desired_move_in_date' => now()->addWeek()->toDateString(),
            'number_of_occupants' => 1,
            'employment_status' => 'student',
            'documents' => ['student_id' => UploadedFile::fake()->image('student.jpg')],
        ];

        $this->actingAs($tenant)->post("/tenant/rental-spaces/{$space->id}/apply", $payload)
            ->assertSessionHasErrors(['documents.parent_id_1', 'documents.parent_id_2']);

        $payload['documents']['parent_id_1'] = UploadedFile::fake()->image('p1.jpg');
        $payload['documents']['parent_id_2'] = UploadedFile::fake()->image('p2.jpg');

        $this->actingAs($tenant)->post("/tenant/rental-spaces/{$space->id}/apply", $payload)
            ->assertRedirect(route('tenant.applications.index'));

        $this->assertDatabaseHas('application_documents', ['document_type' => 'student_id']);
        $this->assertDatabaseHas('application_documents', ['document_type' => 'parent_id_2']);
    }

    public function test_non_students_must_upload_two_valid_ids(): void
    {
        Storage::fake('local');
        $tenant = User::factory()->tenant()->create();
        $space = RentalSpace::factory()->create();

        $this->actingAs($tenant)->post("/tenant/rental-spaces/{$space->id}/apply", [
            'desired_move_in_date' => now()->addWeek()->toDateString(),
            'number_of_occupants' => 1,
            'employment_status' => 'self_employed',
            'documents' => ['valid_id_1' => UploadedFile::fake()->image('id1.jpg')],
        ])->assertSessionHasErrors(['documents.valid_id_2']);
    }
}
