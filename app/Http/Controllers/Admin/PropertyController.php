<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Notifications\PropertyVerificationNotification;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PropertyController extends Controller
{
    public function index(Request $request): View
    {
        $properties = Property::query()
            ->with(['owner', 'location', 'images' => fn ($q) => $q->where('is_cover', true)])
            ->withCount('rentalSpaces')
            ->when($request->filled('status'), fn ($q) => $q->where('verification_status', $request->string('status')))
            ->latest()
            ->paginate(15);

        return view('admin.properties.index', compact('properties'));
    }

    public function verify(Request $request, Property $property): RedirectResponse
    {
        $this->authorize('verify', $property);

        $request->validate(['decision' => ['required', 'in:verified,rejected']]);

        $property->update(['verification_status' => $request->string('decision')]);

        $property->owner->notify(new PropertyVerificationNotification($property));

        return back()->with('status', "Property marked as {$request->string('decision')}.");
    }
}
