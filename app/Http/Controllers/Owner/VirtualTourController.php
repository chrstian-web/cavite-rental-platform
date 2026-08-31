<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Http\Requests\VirtualTour\StoreVirtualTourRequest;
use App\Models\Property;
use App\Services\VirtualTourService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VirtualTourController extends Controller
{
    public function __construct(protected VirtualTourService $tours)
    {
    }

    /**
     * A property may only have one "primary" tour in this step (rental-space-level
     * tours use the same table/service and can be added the same way later).
     * This screen creates it if missing, otherwise shows the scene manager.
     */
    public function show(Property $property): View
    {
        $this->authorize('update', $property);

        $tour = $property->virtualTours()->with('scenes.hotspots')->first();

        return view('owner.properties.tour.index', compact('property', 'tour'));
    }

    public function store(StoreVirtualTourRequest $request, Property $property): RedirectResponse
    {
        $this->tours->createTour($property, $request->validated(), $request->file('thumbnail'));

        return redirect()
            ->route('owner.properties.tour.show', $property)
            ->with('status', 'Virtual tour created. Now add 360° scenes.');
    }

    public function publish(Request $request, Property $property): RedirectResponse
    {
        $this->authorize('update', $property);

        $tour = $property->virtualTours()->firstOrFail();

        try {
            $this->tours->publish($tour);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['tour' => $e->getMessage()]);
        }

        return back()->with('status', 'Virtual tour published — it is now visible on the public listing.');
    }
}
