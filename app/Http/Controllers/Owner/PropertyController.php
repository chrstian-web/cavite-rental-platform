<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Http\Requests\Property\StorePropertyRequest;
use App\Http\Requests\Property\UpdatePropertyRequest;
use App\Models\Amenity;
use App\Models\Location;
use App\Models\Property;
use App\Services\PropertyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PropertyController extends Controller
{
    public function __construct(protected PropertyService $properties)
    {
    }

    /**
     * Properties the current user may manage: their own (owner) or ones
     * they've been explicitly assigned to (manager).
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        $properties = Property::query()
            ->with(['images' => fn ($q) => $q->where('is_cover', true), 'location'])
            ->withCount('rentalSpaces')
            ->when($user->isOwner(), fn ($q) => $q->where('owner_id', $user->id))
            ->when($user->isManager(), fn ($q) => $q->whereHas('managers', fn ($q2) => $q2->where('users.id', $user->id)))
            ->latest()
            ->paginate(10);

        return view('owner.properties.index', compact('properties'));
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Property::class);

        return view('owner.properties.create', $this->formData());
    }

    public function store(StorePropertyRequest $request): RedirectResponse
    {
        $data = $request->safe()->except(['amenities', 'images']);

        $property = $this->properties->create(
            $data,
            $request->input('amenities', []),
            $request->file('images', []),
            $request->user()->id
        );

        return redirect()
            ->route('owner.properties.spaces.index', $property)
            ->with('status', 'Property created. Now add its units/rooms below.');
    }

    public function edit(Property $property): View
    {
        $this->authorize('update', $property);

        $property->load(['images', 'amenities']);

        return view('owner.properties.edit', [...$this->formData(), 'property' => $property]);
    }

    public function update(UpdatePropertyRequest $request, Property $property): RedirectResponse
    {
        $data = $request->safe()->except(['amenities', 'images']);

        $this->properties->update(
            $property,
            $data,
            $request->input('amenities', []),
            $request->file('images', [])
        );

        return back()->with('status', 'Property updated.');
    }

    public function destroy(Property $property): RedirectResponse
    {
        $this->authorize('delete', $property);

        $this->properties->delete($property);

        return redirect()->route('owner.properties.index')->with('status', 'Property deleted.');
    }

    protected function formData(): array
    {
        return [
            'locations' => Location::where('is_active', true)->orderBy('city_municipality')->get(),
            'amenities' => Amenity::orderBy('name')->get(),
        ];
    }
}
