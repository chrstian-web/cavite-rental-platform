<?php

namespace Tests\Feature\VirtualTour;

use App\Models\Property;
use App\Models\SceneHotspot;
use App\Models\VirtualTour;
use App\Models\VirtualTourScene;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VirtualTourViewerTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_published_scene_can_be_loaded_by_a_listing_visitor(): void
    {
        Storage::fake('public');
        [$property, $tour, $scene] = $this->createTour('published');
        Storage::disk('public')->put($scene->panorama_image, 'fake panorama');

        $response = $this->get(route('virtual-tour.panorama', [$property, $tour, $scene]));

        $response->assertOk();
        $response->assertHeader('Cache-Control', 'max-age=3600, public');
    }

    public function test_an_unpublished_scene_is_not_available_to_a_guest(): void
    {
        Storage::fake('public');
        [$property, $tour, $scene] = $this->createTour('draft');
        Storage::disk('public')->put($scene->panorama_image, 'private panorama');

        $response = $this->get(route('virtual-tour.panorama', [$property, $tour, $scene]));

        $response->assertNotFound();
    }

    public function test_an_owner_can_preview_an_unpublished_scene(): void
    {
        Storage::fake('public');
        [$property, $tour, $scene] = $this->createTour('draft');
        Storage::disk('public')->put($scene->panorama_image, 'owner panorama');

        $response = $this->actingAs($property->owner)->get(
            route('virtual-tour.panorama', [$property, $tour, $scene])
        );

        $response->assertOk();
    }

    public function test_a_scene_from_another_tour_cannot_be_loaded(): void
    {
        Storage::fake('public');
        [$property, $tour, $scene] = $this->createTour('published');
        $otherTour = VirtualTour::create([
            'property_id' => $property->id,
            'title' => 'Other Tour',
            'status' => 'published',
        ]);
        $otherScene = VirtualTourScene::create([
            'virtual_tour_id' => $otherTour->id,
            'title' => 'Other Scene',
            'panorama_image' => 'properties/other/scene.jpg',
            'sort_order' => 1,
        ]);

        $response = $this->get(route('virtual-tour.panorama', [$property, $tour, $otherScene]));

        $response->assertNotFound();
    }

    public function test_property_page_contains_scene_and_hotspot_data_for_the_viewer(): void
    {
        [$property, $tour, $scene] = $this->createTour('published');
        $targetScene = VirtualTourScene::create([
            'virtual_tour_id' => $tour->id,
            'title' => 'Kitchen',
            'panorama_image' => 'properties/'.$property->id.'/tours/'.$tour->id.'/kitchen.jpg',
            'sort_order' => 2,
        ]);
        SceneHotspot::create([
            'scene_id' => $scene->id,
            'target_scene_id' => $targetScene->id,
            'position_x' => 20,
            'position_y' => -5,
            'label' => 'Go to kitchen',
            'type' => 'navigation',
        ]);

        $response = $this->get(route('properties.show', $property->slug));

        $response->assertOk();
        $response->assertSee('Living Room');
        $response->assertSee('Kitchen');
        $response->assertSee('Go to kitchen');
        // The route is embedded in JSON, where slashes are escaped safely.
        $response->assertSee('virtual-tour', false);
    }

    /** @return array{0: Property, 1: VirtualTour, 2: VirtualTourScene} */
    private function createTour(string $status): array
    {
        $property = Property::factory()->create();
        $tour = VirtualTour::create([
            'property_id' => $property->id,
            'title' => 'Sample Tour',
            'status' => $status,
        ]);
        $scene = VirtualTourScene::create([
            'virtual_tour_id' => $tour->id,
            'title' => 'Living Room',
            'panorama_image' => 'properties/'.$property->id.'/tours/'.$tour->id.'/living-room.jpg',
            'sort_order' => 1,
        ]);

        return [$property, $tour, $scene];
    }
}
