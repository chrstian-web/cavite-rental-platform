<?php

namespace Tests\Feature\RentalApplication;

use App\Models\ApplicationDocument;
use App\Models\Property;
use App\Models\RentalApplication;
use App\Models\RentalSpace;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ApplicationDocumentDownloadTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Regression test for a bug where the download endpoint declared a
     * return type (Illuminate\Http\Response) that Storage::download()'s
     * actual return value (a Symfony BinaryFileResponse) doesn't satisfy,
     * causing a fatal TypeError on every single document download.
     */
    public function test_the_applicant_can_download_their_own_document_without_error(): void
    {
        Storage::fake('local');

        $tenant = User::factory()->tenant()->create();
        $space = RentalSpace::factory()->create();

        $application = RentalApplication::create([
            'user_id' => $tenant->id,
            'property_id' => $space->property_id,
            'rental_space_id' => $space->id,
            'desired_move_in_date' => now()->addWeek(),
            'number_of_occupants' => 1,
            'status' => 'pending',
        ]);

        $file = UploadedFile::fake()->create('valid_id.jpg', 100, 'image/jpeg');
        $path = $file->store('applications/'.$application->id.'/documents', 'local');

        $document = $application->documents()->create([
            'document_type' => 'valid_id',
            'path' => $path,
            'original_filename' => 'valid_id.jpg',
            'mime_type' => 'image/jpeg',
            'size_bytes' => $file->getSize(),
        ]);

        $response = $this->actingAs($tenant)->get("/documents/{$document->id}/download");

        $response->assertOk();
    }

    public function test_a_different_tenant_cannot_download_someone_elses_document(): void
    {
        Storage::fake('local');

        $owner_tenant = User::factory()->tenant()->create();
        $other_tenant = User::factory()->tenant()->create();
        $space = RentalSpace::factory()->create();

        $application = RentalApplication::create([
            'user_id' => $owner_tenant->id,
            'property_id' => $space->property_id,
            'rental_space_id' => $space->id,
            'desired_move_in_date' => now()->addWeek(),
            'number_of_occupants' => 1,
            'status' => 'pending',
        ]);

        $file = UploadedFile::fake()->create('valid_id.jpg', 100, 'image/jpeg');
        $path = $file->store('applications/'.$application->id.'/documents', 'local');

        $document = $application->documents()->create([
            'document_type' => 'valid_id',
            'path' => $path,
            'original_filename' => 'valid_id.jpg',
            'mime_type' => 'image/jpeg',
            'size_bytes' => $file->getSize(),
        ]);

        $response = $this->actingAs($other_tenant)->get("/documents/{$document->id}/download");

        $response->assertForbidden();
    }
}
