<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Http\Requests\OwnerVerification\StoreOwnerVerificationRequest;
use App\Services\OwnerVerificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OwnerVerificationController extends Controller
{
    public function __construct(protected OwnerVerificationService $verifications)
    {
    }

    public function show(Request $request): View
    {
        $owner = $request->user();
        $latest = $owner->latestOwnerVerification();

        return view('owner.verification.show', [
            'owner' => $owner,
            'verification' => $latest,
            'documentTypes' => OwnerVerificationService::DOCUMENT_TYPES,
            // Only allow (re)submitting when there's nothing pending review yet.
            'canSubmit' => in_array($owner->owner_verification_status, [
                'not_submitted', 'rejected', 'needs_additional_documents', null,
            ], true),
        ]);
    }

    public function store(StoreOwnerVerificationRequest $request): RedirectResponse
    {
        $documents = $request->file('documents', []);

        $this->verifications->submit($request->user(), $documents);

        return redirect()
            ->route('owner.verification.show')
            ->with('status', 'Your documents have been submitted successfully. We will notify you once verification is complete.');
    }
}
