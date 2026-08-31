<?php

namespace Tests\Feature\OwnerVerification;

use App\Models\OwnerVerification;
use App\Models\Role;
use App\Models\User;
use App\Models\VerificationDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OwnerVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::factory()->create(['slug' => 'owner', 'name' => 'Property Owner']);
        Role::factory()->create(['slug' => 'tenant', 'name' => 'Tenant']);
    }

    public function test_registering_as_owner_sets_not_submitted_status_and_redirects_to_verification(): void
    {
        $response = $this->post('/register', [
            'first_name' => 'New', 'last_name' => 'Owner',
            'email' => 'newowner@example.com', 'password' => 'Password123!',
            'password_confirmation' => 'Password123!', 'role' => 'owner',
        ]);

        $response->assertRedirect(route('owner.verification.show'));
        $this->assertDatabaseHas('users', ['email' => 'newowner@example.com', 'owner_verification_status' => 'not_submitted']);
    }

    public function test_registering_as_tenant_does_not_set_owner_verification_status(): void
    {
        $this->post('/register', [
            'first_name' => 'New', 'last_name' => 'Tenant',
            'email' => 'newtenant@example.com', 'password' => 'Password123!',
            'password_confirmation' => 'Password123!', 'role' => 'tenant',
        ]);

        $this->assertDatabaseHas('users', ['email' => 'newtenant@example.com', 'owner_verification_status' => null]);
    }

    public function test_an_unverified_owner_is_redirected_away_from_owner_only_features(): void
    {
        $owner = User::factory()->owner()->create(['owner_verification_status' => 'not_submitted']);

        $response = $this->actingAs($owner)->get('/owner/properties');

        $response->assertRedirect(route('owner.verification.show'));
    }

    public function test_an_unverified_owner_can_still_reach_the_verification_page_itself(): void
    {
        $owner = User::factory()->owner()->create(['owner_verification_status' => 'not_submitted']);

        $response = $this->actingAs($owner)->get('/owner/verification');

        $response->assertOk();
    }

    public function test_a_verified_owner_can_access_owner_only_features(): void
    {
        $owner = User::factory()->owner()->create(['owner_verification_status' => 'verified']);

        $response = $this->actingAs($owner)->get('/owner/properties');

        $response->assertOk();
    }

    public function test_an_owner_cannot_submit_verification_without_required_documents(): void
    {
        $owner = User::factory()->owner()->create(['owner_verification_status' => 'not_submitted']);

        $response = $this->actingAs($owner)->post('/owner/verification', []);

        $response->assertSessionHasErrors(['documents.government_id', 'documents.proof_of_ownership']);
    }

    public function test_an_owner_can_submit_verification_with_required_documents(): void
    {
        Storage::fake('local');

        $owner = User::factory()->owner()->create(['owner_verification_status' => 'not_submitted']);

        $response = $this->actingAs($owner)->post('/owner/verification', [
            'documents' => [
                'government_id' => UploadedFile::fake()->create('id.jpg', 100, 'image/jpeg'),
                'proof_of_ownership' => UploadedFile::fake()->create('deed.pdf', 100, 'application/pdf'),
            ],
        ]);

        $response->assertRedirect(route('owner.verification.show'));
        $owner->refresh();
        // Automated check never auto-approves — it always routes to human review.
        $this->assertEquals('needs_review', $owner->owner_verification_status);
        $this->assertDatabaseCount('verification_documents', 2);
    }

    public function test_a_verification_with_an_expired_document_is_flagged_for_more_documents_automatically(): void
    {
        Storage::fake('local');

        $owner = User::factory()->owner()->create(['owner_verification_status' => 'not_submitted']);

        $this->actingAs($owner)->post('/owner/verification', [
            'documents' => [
                'government_id' => UploadedFile::fake()->create('id.jpg', 100, 'image/jpeg'),
                'proof_of_ownership' => UploadedFile::fake()->create('deed.pdf', 100, 'application/pdf'),
            ],
            'expiration_dates' => [
                'government_id' => now()->subDay()->toDateString(), // already expired
            ],
        ]);

        $owner->refresh();
        $this->assertEquals('needs_additional_documents', $owner->owner_verification_status);
    }

    public function test_super_admin_can_approve_a_verification(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $owner = User::factory()->owner()->create(['owner_verification_status' => 'needs_review']);
        $verification = OwnerVerification::create([
            'user_id' => $owner->id, 'status' => 'needs_review', 'submitted_at' => now(),
        ]);

        $response = $this->actingAs($admin)->patch("/admin/owner-verifications/{$verification->id}/approve", [
            'admin_notes' => 'Looks good.',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('owner_verifications', ['id' => $verification->id, 'status' => 'approved']);
        $this->assertEquals('approved', $owner->fresh()->owner_verification_status);

        // Approved is NOT the same as verified — OTP (Step 20) still gates full access.
        $this->assertFalse($owner->fresh()->isOwnerVerified());
    }

    public function test_super_admin_can_reject_a_verification_with_a_reason(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $owner = User::factory()->owner()->create(['owner_verification_status' => 'needs_review']);
        $verification = OwnerVerification::create([
            'user_id' => $owner->id, 'status' => 'needs_review', 'submitted_at' => now(),
        ]);

        $response = $this->actingAs($admin)->patch("/admin/owner-verifications/{$verification->id}/reject", [
            'rejection_reason' => 'ID photo is not legible.',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('owner_verifications', ['id' => $verification->id, 'status' => 'rejected']);
        $this->assertEquals('rejected', $owner->fresh()->owner_verification_status);
    }

    public function test_rejection_requires_a_reason(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $owner = User::factory()->owner()->create(['owner_verification_status' => 'needs_review']);
        $verification = OwnerVerification::create([
            'user_id' => $owner->id, 'status' => 'needs_review', 'submitted_at' => now(),
        ]);

        $response = $this->actingAs($admin)->patch("/admin/owner-verifications/{$verification->id}/reject", []);

        $response->assertSessionHasErrors('rejection_reason');
    }

    public function test_a_non_admin_cannot_review_verifications(): void
    {
        $owner = User::factory()->owner()->create(['owner_verification_status' => 'needs_review']);
        $verification = OwnerVerification::create([
            'user_id' => $owner->id, 'status' => 'needs_review', 'submitted_at' => now(),
        ]);

        $response = $this->actingAs($owner)->patch("/admin/owner-verifications/{$verification->id}/approve", []);

        $response->assertForbidden();
    }

    public function test_only_the_owner_or_an_admin_can_download_a_verification_document(): void
    {
        Storage::fake('local');

        $owner = User::factory()->owner()->create();
        $otherOwner = User::factory()->owner()->create();
        $verification = OwnerVerification::create([
            'user_id' => $owner->id, 'status' => 'needs_review', 'submitted_at' => now(),
        ]);
        $document = VerificationDocument::create([
            'owner_verification_id' => $verification->id,
            'document_type' => 'government_id',
            'file_path' => 'verification/1/1/id.jpg',
            'original_filename' => 'id.jpg',
            'mime_type' => 'image/jpeg',
            'size_bytes' => 100,
        ]);
        Storage::disk('local')->put($document->file_path, 'fake content');

        $this->actingAs($otherOwner)
            ->get("/documents/verification/{$document->id}/download")
            ->assertForbidden();

        $this->actingAs($owner)
            ->get("/documents/verification/{$document->id}/download")
            ->assertOk();
    }
}
