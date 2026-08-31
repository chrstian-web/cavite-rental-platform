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

    public function create(RentalSpace $space): View
    {
        $space->load('property');

        return view('tenant.applications.create', ['space' => $space, 'property' => $space->property]);
    }

    public function store(StoreRentalApplicationRequest $request, RentalSpace $space): RedirectResponse
    {
        $data = $request->safe()->except('documents');

        $this->applications->submit(
            $request->user(),
            $space,
            $data,
            $request->file('documents', [])
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
}
