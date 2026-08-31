<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\MaintenanceRequest\StoreMaintenanceRequestRequest;
use App\Models\MaintenanceRequest;
use App\Models\RentalContract;
use App\Services\MaintenanceRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MaintenanceRequestController extends Controller
{
    public function __construct(protected MaintenanceRequestService $maintenance)
    {
    }

    public function index(Request $request): View
    {
        $requests = MaintenanceRequest::query()
            ->with(['property', 'rentalSpace', 'images'])
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(10);

        return view('tenant.maintenance.index', compact('requests'));
    }

    public function create(RentalContract $contract): View
    {
        $this->authorize('view', $contract);
        abort_unless($contract->status === 'active', 422, 'You can only submit maintenance requests for an active tenancy.');

        return view('tenant.maintenance.create', compact('contract'));
    }

    public function store(StoreMaintenanceRequestRequest $request, RentalContract $contract): RedirectResponse
    {
        $this->authorize('view', $contract);
        abort_unless($contract->status === 'active', 422);

        $this->maintenance->submit(
            $request->user(),
            $contract->property,
            $contract->rentalSpace,
            $request->safe()->except('images'),
            $request->file('images', [])
        );

        return redirect()
            ->route('tenant.maintenance.index')
            ->with('status', 'Maintenance request submitted.');
    }
}
