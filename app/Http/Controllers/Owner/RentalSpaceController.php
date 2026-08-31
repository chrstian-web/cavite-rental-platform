<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Http\Requests\RentalSpace\StoreRentalSpaceRequest;
use App\Http\Requests\RentalSpace\UpdateRentalSpaceRequest;
use App\Models\Property;
use App\Models\RentalSpace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class RentalSpaceController extends Controller
{
    public function index(Property $property): View
    {
        $this->authorize('update', $property);

        $spaces = $property->rentalSpaces()
            ->with(['images' => fn ($q) => $q->where('is_cover', true)])
            ->orderBy('space_number')
            ->paginate(15);

        return view('owner.properties.spaces.index', compact('property', 'spaces'));
    }

    public function create(Property $property): View
    {
        $this->authorize('update', $property);

        return view('owner.properties.spaces.create', compact('property'));
    }

    public function store(StoreRentalSpaceRequest $request, Property $property): RedirectResponse
    {
        $data = $request->safe()->except('images');
        $data['attributes'] = array_filter($data['attributes'] ?? []);

        $space = $property->rentalSpaces()->create($data);

        $this->storeImages($space, $request->file('images', []));

        return redirect()
            ->route('owner.properties.spaces.index', $property)
            ->with('status', 'Rental space added.');
    }

    public function edit(Property $property, RentalSpace $space): View
    {
        $this->authorize('update', $property);
        abort_unless($space->property_id === $property->id, 404);

        $space->load('images');

        return view('owner.properties.spaces.edit', compact('property', 'space'));
    }

    public function update(UpdateRentalSpaceRequest $request, Property $property, RentalSpace $space): RedirectResponse
    {
        abort_unless($space->property_id === $property->id, 404);

        $data = $request->safe()->except('images');
        $data['attributes'] = array_filter($data['attributes'] ?? []);

        $space->update($data);

        $this->storeImages($space, $request->file('images', []));

        return redirect()
            ->route('owner.properties.spaces.index', $property)
            ->with('status', 'Rental space updated.');
    }

    public function destroy(Property $property, RentalSpace $space): RedirectResponse
    {
        $this->authorize('update', $property);
        abort_unless($space->property_id === $property->id, 404);

        foreach ($space->images as $image) {
            Storage::disk('public')->delete($image->path);
        }

        $space->delete(); // soft delete

        return back()->with('status', 'Rental space removed.');
    }

    /**
     * @param  UploadedFile[]  $images
     */
    protected function storeImages(RentalSpace $space, array $images): void
    {
        if (empty($images)) {
            return;
        }

        $hasCover = $space->images()->where('is_cover', true)->exists();
        $nextSortOrder = (int) $space->images()->max('sort_order');

        foreach ($images as $index => $file) {
            $path = $file->store("properties/{$space->property_id}/units/{$space->id}", 'public');

            $space->images()->create([
                'path' => $path,
                'is_cover' => ! $hasCover && $index === 0,
                'sort_order' => ++$nextSortOrder,
            ]);
        }
    }
}
