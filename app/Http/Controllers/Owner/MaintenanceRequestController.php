<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Http\Requests\MaintenanceRequest\UpdateMaintenanceStatusRequest;
use App\Models\MaintenanceRequest;
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
        $user = $request->user();

        $requests = MaintenanceRequest::query()
            ->with(['property', 'rentalSpace', 'tenant', 'images'])
            ->whereHas('property', function ($q) use ($user) {
                $q->when($user->isOwner(), fn ($q2) => $q2->where('owner_id', $user->id))
                  ->when($user->isManager(), fn ($q2) => $q2->whereHas('managers', fn ($q3) => $q3->where('users.id', $user->id)));
            })
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->latest()
            ->paginate(10);

        return view('owner.maintenance.index', compact('requests'));
    }

    public function updateStatus(UpdateMaintenanceStatusRequest $request, MaintenanceRequest $maintenanceRequest): RedirectResponse
    {
        $this->maintenance->updateStatus($maintenanceRequest, $request->string('status'));

        return back()->with('status', 'Maintenance request updated.');
    }
}
