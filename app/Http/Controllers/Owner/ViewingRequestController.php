<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Http\Requests\ViewingRequest\UpdateViewingRequestRequest;
use App\Models\ViewingRequest;
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
        $user = $request->user();

        $viewings = ViewingRequest::query()
            ->with(['property', 'user', 'rentalSpace'])
            ->whereHas('property', function ($q) use ($user) {
                $q->when($user->isOwner(), fn ($q2) => $q2->where('owner_id', $user->id))
                  ->when($user->isManager(), fn ($q2) => $q2->whereHas('managers', fn ($q3) => $q3->where('users.id', $user->id)));
            })
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->latest()
            ->paginate(10);

        return view('owner.viewings.index', compact('viewings'));
    }

    public function update(UpdateViewingRequestRequest $request, ViewingRequest $viewing): RedirectResponse
    {
        $this->viewings->updateStatus($viewing, $request->validated());

        return back()->with('status', 'Viewing request updated.');
    }
}
