<?php

namespace Tests\Feature\RentalSpace;

use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RentalSpaceImageTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_owner_can_upload_photos_when_creating_a_unit(): void
    {
        Storage::fake('public');

        $owner = User::factory()->owner()->create();
        $property = Property::factory()->create(['owner_id' => $owner->id]);

        $response = $this->actingAs($owner)->post("/owner/properties/{$property->id}/spaces", [
            'space_number' => 'Room 1',
            'bedrooms' => 1,
            'bathrooms' => 1,
            'total_capacity' => 2,
            'monthly_rent' => 5000,
            'status' => 'available',
            'images' => [UploadedFile::fake()->create('room.jpg', 100, 'image/jpeg')],
        ]);

        $response->assertRedirect();
        $space = $property->rentalSpaces()->first();
        $this->assertNotNull($space);
        $this->assertEquals(1, $space->images()->count());
        $this->assertTrue($space->images()->first()->is_cover);
    }

    public function test_an_owner_cannot_upload_images_for_another_owners_unit(): void
    {
        $ownerA = User::factory()->owner()->create();
        $ownerB = User::factory()->owner()->create();
        $property = Property::factory()->create(['owner_id' => $ownerA->id]);

        $response = $this->actingAs($ownerB)->post("/owner/properties/{$property->id}/spaces", [
            'space_number' => 'Room 1',
            'bedrooms' => 1,
            'bathrooms' => 1,
            'total_capacity' => 2,
            'monthly_rent' => 5000,
            'status' => 'available',
        ]);

        $response->assertForbidden();
    }
}
