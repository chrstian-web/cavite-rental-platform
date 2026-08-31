<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Http\Requests\RentalApplication\ReviewRentalApplicationRequest;
use App\Models\Property;
use App\Models\RentalApplication;
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
        $user = $request->user();

        $applications = RentalApplication::query()
            ->with(['property', 'rentalSpace', 'user'])
            ->whereHas('property', function ($q) use ($user) {
                $q->when($user->isOwner(), fn ($q2) => $q2->where('owner_id', $user->id))
                  ->when($user->isManager(), fn ($q2) => $q2->whereHas('managers', fn ($q3) => $q3->where('users.id', $user->id)));
            })
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->latest()
            ->paginate(10);

        return view('owner.applications.index', compact('applications'));
    }

    public function show(RentalApplication $application): View
    {
        $this->authorize('view', $application);

        $application->load(['property', 'rentalSpace', 'documents', 'user']);

        return view('owner.applications.show', compact('application'));
    }

    public function review(ReviewRentalApplicationRequest $request, RentalApplication $application): RedirectResponse
    {
        $this->applications->review(
            $application,
            $request->string('status'),
            $request->input('decision_reason'),
            $request->user()
        );

        return back()->with('status', 'Application updated.');
    }
}
