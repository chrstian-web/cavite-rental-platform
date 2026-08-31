<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Http\Requests\VirtualTour\StoreSceneRequest;
use App\Models\Property;
use App\Models\VirtualTour;
use App\Models\VirtualTourScene;
use App\Services\VirtualTourService;
use Illuminate\Http\RedirectResponse;

class VirtualTourSceneController extends Controller
{
    public function __construct(protected VirtualTourService $tours)
    {
    }

    public function store(StoreSceneRequest $request, Property $property, VirtualTour $tour): RedirectResponse
    {
        abort_unless($tour->property_id === $property->id, 404);

        $this->tours->addScene($tour, $request->validated(), $request->file('panorama_image'));

        return redirect()
            ->route('owner.properties.tour.show', $property)
            ->with('status', 'Scene added.');
    }

    public function update(StoreSceneRequest $request, Property $property, VirtualTour $tour, VirtualTourScene $scene): RedirectResponse
    {
        abort_unless($tour->property_id === $property->id && $scene->virtual_tour_id === $tour->id, 404);

        $this->tours->updateScene($scene, $request->validated(), $request->file('panorama_image'));

        return redirect()
            ->route('owner.properties.tour.show', $property)
            ->with('status', 'Scene updated.');
    }

    public function destroy(Property $property, VirtualTour $tour, VirtualTourScene $scene): RedirectResponse
    {
        $this->authorize('update', $property);
        abort_unless($tour->property_id === $property->id && $scene->virtual_tour_id === $tour->id, 404);

        $this->tours->deleteScene($scene);

        return back()->with('status', 'Scene removed.');
    }
}
