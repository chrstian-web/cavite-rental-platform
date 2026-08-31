<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\ViewingRequest\StoreViewingRequestRequest;
use App\Models\Property;
use App\Models\RentalSpace;
use App\Services\ViewingRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ViewingRequestController extends Controller
{
    public function __construct(protected ViewingRequestService $viewings)
    {
    }

    public function index(Request $request): View
    {
        $viewings = $request->user()
            ->viewingRequests()
            ->with('property')
            ->latest()
            ->paginate(10);

        return view('tenant.viewings.index', compact('viewings'));
    }

    public function create(Property $property, ?RentalSpace $space = null): View
    {
        return view('tenant.viewings.create', compact('property', 'space'));
    }

    public function store(StoreViewingRequestRequest $request, Property $property): RedirectResponse
    {
        $spaceId = $request->input('rental_space_id');
        $space = $spaceId ? RentalSpace::where('property_id', $property->id)->findOrFail($spaceId) : null;

        $this->viewings->request($request->user(), $property, $space, $request->validated());

        return redirect()
            ->route('tenant.viewings.index')
            ->with('status', 'Viewing request sent. You will be notified once it is confirmed.');
    }
}
