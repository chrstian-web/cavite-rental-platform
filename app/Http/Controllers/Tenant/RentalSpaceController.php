<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\RentalSpace;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class RentalSpaceController extends Controller
{
    /**
     * The page a renter lands on after pressing "Apply" on a unit/room: every
     * detail the owner entered, a viewing request box and the location map.
     * The actual application form is one click further (tenant.applications.create).
     */
    public function show(RentalSpace $space): View|RedirectResponse
    {
        $space->load([
            'property.location',
            'property.barangay',
            'property.owner',
            'images' => fn ($q) => $q->orderByDesc('is_cover')->orderBy('sort_order')->orderBy('id'),
        ]);

        // A full or unavailable room cannot be applied to, even through a direct link.
        if (! $space->acceptsApplications()) {
            return redirect()
                ->route('properties.show', $space->property->slug)
                ->withErrors(['space' => "{$space->space_number} is no longer open for applications."]);
        }

        return view('tenant.spaces.show', [
            'space' => $space,
            'property' => $space->property,
        ]);
    }
}
