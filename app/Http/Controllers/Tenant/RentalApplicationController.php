<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\RentalApplication\StoreRentalApplicationRequest;
use App\Models\RentalApplication;
use App\Models\RentalSpace;
use App\Services\RentalApplicationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RentalApplicationController extends Controller
{
    public function __construct(protected RentalApplicationService $applications)
    {
    }

    public function index(Request $request): View
    {
        $applications = $request->user()
            ->rentalApplications()
            ->with(['property', 'rentalSpace'])
            ->latest()
            ->paginate(10);

        return view('tenant.applications.index', compact('applications'));
    }

    public function create(RentalSpace $space): View|RedirectResponse
    {
        if (! $space->acceptsApplications()) {
            return $this->closedResponse($space);
        }

        $space->load(['property', 'images']);

        return view('tenant.applications.create', ['space' => $space, 'property' => $space->property]);
    }

    public function store(StoreRentalApplicationRequest $request, RentalSpace $space): RedirectResponse
    {
        if (! $space->acceptsApplications()) {
            return $this->closedResponse($space);
        }

        $data = $request->safe()->except('documents');

        $this->applications->submit(
            $request->user(),
            $space,
            $data,
            $request->safe()->only(['documents'])['documents'] ?? []
        );

        return redirect()
            ->route('tenant.applications.index')
            ->with('status', 'Application submitted. The property owner will review it soon.');
    }

    public function show(RentalApplication $application): View
    {
        $this->authorize('view', $application);

        $application->load(['property', 'rentalSpace', 'documents']);

        return view('tenant.applications.show', compact('application'));
    }

    /** A full or unavailable space cannot be applied to, even through a direct link. */
    protected function closedResponse(RentalSpace $space): RedirectResponse
    {
        return redirect()
            ->route('properties.show', $space->property->slug)
            ->withErrors(['space' => "{$space->space_number} is no longer open for applications."]);
    }
}
