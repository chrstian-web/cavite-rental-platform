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
