<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OwnerVerification;
use App\Services\OwnerVerificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OwnerVerificationController extends Controller
{
    public function __construct(protected OwnerVerificationService $verifications)
    {
    }

    public function index(Request $request): View
    {
        $verifications = OwnerVerification::query()
            ->with('user')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->latest('submitted_at')
            ->paginate(15);

        return view('admin.owner-verifications.index', compact('verifications'));
    }

    public function show(OwnerVerification $ownerVerification): View
    {
        $ownerVerification->load(['user', 'documents', 'reviewer']);

        // Previous attempts by the same owner, for the audit trail the brief asks for.
        $history = $ownerVerification->user->ownerVerifications()
            ->where('id', '!=', $ownerVerification->id)
            ->latest('submitted_at')
            ->get();

        return view('admin.owner-verifications.show', [
            'verification' => $ownerVerification,
            'history' => $history,
        ]);
    }

    public function approve(Request $request, OwnerVerification $ownerVerification): RedirectResponse
    {
        $request->validate(['admin_notes' => ['nullable', 'string', 'max:2000']]);

        $this->verifications->approve($ownerVerification, $request->user(), $request->input('admin_notes'));

        return back()->with('status', 'Owner verification approved.');
    }

    public function reject(Request $request, OwnerVerification $ownerVerification): RedirectResponse
    {
        $request->validate(['rejection_reason' => ['required', 'string', 'max:2000']]);

        $this->verifications->reject($ownerVerification, $request->user(), $request->string('rejection_reason'));

        return back()->with('status', 'Owner verification rejected.');
    }

    public function requestMoreDocuments(Request $request, OwnerVerification $ownerVerification): RedirectResponse
    {
        $request->validate(['admin_notes' => ['required', 'string', 'max:2000']]);

        $this->verifications->requestMoreDocuments($ownerVerification, $request->user(), $request->string('admin_notes'));

        return back()->with('status', 'Requested additional documents from the owner.');
    }
}
