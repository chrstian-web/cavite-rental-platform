<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\PropertyImage;
use App\Services\PropertyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PropertyImageController extends Controller
{
    public function __construct(protected PropertyService $properties)
    {
    }

    public function destroy(Property $property, PropertyImage $image): RedirectResponse
    {
        $this->authorize('update', $property);

        abort_unless($image->property_id === $property->id, 404);

        $this->properties->deleteImage($property, $image->id);

        return back()->with('status', 'Image removed.');
    }

    public function update(Request $request, Property $property, PropertyImage $image): RedirectResponse
    {
        $this->authorize('update', $property);
        abort_unless($image->property_id === $property->id, 404);

        $data = $request->validate(['image' => ['required', 'image', 'max:5120']]);
        $path = $data['image']->store("properties/{$property->id}", 'public');
        Storage::disk('public')->delete($image->path);
        $image->update(['path' => $path]);

        return back()->with('status', 'Image replaced.');
    }

    public function makeCover(Property $property, PropertyImage $image): RedirectResponse
    {
        $this->authorize('update', $property);

        abort_unless($image->property_id === $property->id, 404);

        $property->images()->update(['is_cover' => false]);
        $image->update(['is_cover' => true]);

        return back()->with('status', 'Cover photo updated.');
    }
}
