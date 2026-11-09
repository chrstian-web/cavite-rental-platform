<?php

namespace Tests\Feature\Admin;

use App\Models\OwnerVerification;
use App\Models\User;
use App\Models\VerificationDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OwnerVerificationDocumentButtonsTest extends TestCase
{
    use RefreshDatabase;

    public function test_each_document_view_button_has_its_own_alpine_scope_so_the_click_works(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $owner = User::factory()->owner()->create(['owner_verification_status' => 'needs_review']);
        $verification = OwnerVerification::create([
            'user_id' => $owner->id, 'status' => 'needs_review', 'submitted_at' => now(),
        ]);

        foreach (['government_id', 'business_permit'] as $type) {
            VerificationDocument::create([
                'owner_verification_id' => $verification->id,
                'document_type' => $type,
                'file_path' => "verification-documents/{$type}.png",
                'original_filename' => "{$type}.png",
                'mime_type' => 'image/png',
                'size_bytes' => 1024,
                'status' => 'pending',
            ]);
        }

        $html = $this->actingAs($admin)
            ->get(route('admin.owner-verifications.show', $verification))
            ->assertOk()
            ->getContent();

        // One "View" button per document, each carrying x-data (otherwise Alpine never binds @click)
        // and dispatching to the right viewer item.
        foreach ([0, 1] as $index) {
            $this->assertMatchesRegularExpression(
                '/<button[^>]*\bx-data\b[^>]*open-docs[^>]*owner-docs[^>]*index: '.$index.'\b/s',
                $html,
                "View button #{$index} must have its own x-data scope."
            );
        }
    }
}
