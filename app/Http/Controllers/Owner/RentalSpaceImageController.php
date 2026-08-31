<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\RentalSpace;
use App\Models\RentalSpaceImage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;

class RentalSpaceImageController extends Controller
{
    public function destroy(Property $property, RentalSpace $space, RentalSpaceImage $image): RedirectResponse
    {
        $this->authorize('update', $property);
        abort_unless($space->property_id === $property->id && $image->rental_space_id === $space->id, 404);

        Storage::disk('public')->delete($image->path);
        $image->delete();

        return back()->with('status', 'Unit photo removed.');
    }

    public function makeCover(Property $property, RentalSpace $space, RentalSpaceImage $image): RedirectResponse
    {
        $this->authorize('update', $property);
        abort_unless($space->property_id === $property->id && $image->rental_space_id === $space->id, 404);

        $space->images()->update(['is_cover' => false]);
        $image->update(['is_cover' => true]);

        return back()->with('status', 'Cover photo updated.');
    }
}
