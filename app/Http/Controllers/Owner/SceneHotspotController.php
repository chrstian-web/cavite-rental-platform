<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Http\Requests\VirtualTour\StoreHotspotRequest;
use App\Models\Property;
use App\Models\SceneHotspot;
use App\Models\VirtualTour;
use App\Models\VirtualTourScene;
use App\Services\VirtualTourService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SceneHotspotController extends Controller
{
    public function __construct(protected VirtualTourService $tours)
    {
    }

    public function store(StoreHotspotRequest $request, Property $property, VirtualTour $tour, VirtualTourScene $scene): RedirectResponse
    {
        abort_unless($tour->property_id === $property->id && $scene->virtual_tour_id === $tour->id, 404);

        $this->tours->addHotspot($scene, $request->validated());

        return back()->with('status', 'Hotspot added.');
    }

    /** Change the size of an already-saved arrow. */
    public function update(Request $request, Property $property, VirtualTour $tour, VirtualTourScene $scene, SceneHotspot $hotspot): RedirectResponse
    {
        $this->authorize('update', $property);
        abort_unless(
            $tour->property_id === $property->id
            && $scene->virtual_tour_id === $tour->id
            && $hotspot->scene_id === $scene->id,
            404
        );

        $data = $request->validate([
            'size' => ['required', 'integer', 'between:24,140'],
        ]);

        $hotspot->update($data);

        return back()->with('status', 'Arrow size updated.');
    }

    public function destroy(Property $property, VirtualTour $tour, VirtualTourScene $scene, SceneHotspot $hotspot): RedirectResponse
    {
        $this->authorize('update', $property);
        abort_unless(
            $tour->property_id === $property->id
            && $scene->virtual_tour_id === $tour->id
            && $hotspot->scene_id === $scene->id,
            404
        );

        $this->tours->deleteHotspot($hotspot);

        return back()->with('status', 'Hotspot removed.');
    }
}
