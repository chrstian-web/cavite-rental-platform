<?php

namespace App\Services;

use App\Models\Property;
use App\Models\SceneHotspot;
use App\Models\VirtualTour;
use App\Models\VirtualTourScene;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class VirtualTourService
{
    public function createTour(Property $property, array $data, ?UploadedFile $thumbnail): VirtualTour
    {
        return DB::transaction(function () use ($property, $data, $thumbnail) {
            return $property->virtualTours()->create([
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'thumbnail' => $thumbnail ? $thumbnail->store("properties/{$property->id}/tours", 'public') : null,
                'status' => 'draft',
            ]);
        });
    }

    public function updateTour(VirtualTour $tour, array $data, ?UploadedFile $thumbnail): VirtualTour
    {
        if ($thumbnail) {
            if ($tour->thumbnail) {
                Storage::disk('public')->delete($tour->thumbnail);
            }
            $data['thumbnail'] = $thumbnail->store("properties/{$tour->property_id}/tours", 'public');
        }

        $tour->update($data);

        return $tour->fresh();
    }

    public function publish(VirtualTour $tour): void
    {
        // Require at least one scene before a tour can go live.
        if ($tour->scenes()->count() === 0) {
            throw new \RuntimeException('Add at least one scene before publishing the tour.');
        }

        $tour->update(['status' => 'published']);
    }

    public function addScene(VirtualTour $tour, array $data, UploadedFile $panorama): VirtualTourScene
    {
        $nextOrder = $data['sort_order'] ?? (int) $tour->scenes()->max('sort_order') + 1;

        return $tour->scenes()->create([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'panorama_image' => $panorama->store("properties/{$tour->property_id}/tours/{$tour->id}", 'public'),
            'sort_order' => $nextOrder,
        ]);
    }

    public function updateScene(VirtualTourScene $scene, array $data, ?UploadedFile $panorama): VirtualTourScene
    {
        if ($panorama) {
            Storage::disk('public')->delete($scene->panorama_image);
            $data['panorama_image'] = $panorama->store(
                "properties/{$scene->virtualTour->property_id}/tours/{$scene->virtual_tour_id}",
                'public'
            );
        }

        $scene->update($data);

        return $scene->fresh();
    }

    public function deleteScene(VirtualTourScene $scene): void
    {
        DB::transaction(function () use ($scene) {
            Storage::disk('public')->delete($scene->panorama_image);
            // Hotspots that target this scene should no longer point anywhere.
            SceneHotspot::where('target_scene_id', $scene->id)->update(['target_scene_id' => null]);
            $scene->delete();
        });
    }

    public function addHotspot(VirtualTourScene $scene, array $data): SceneHotspot
    {
        return $scene->hotspots()->create($data);
    }

    public function deleteHotspot(SceneHotspot $hotspot): void
    {
        $hotspot->delete();
    }
}
