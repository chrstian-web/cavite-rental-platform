<?php

namespace App\Services;

use App\Models\OwnerVerification;
use App\Models\User;
use App\Models\VerificationDocument;
use App\Notifications\NewOwnerVerificationSubmittedNotification;
use App\Notifications\OwnerVerificationResultNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class OwnerVerificationService
{
    /**
     * Documents an owner may submit. 'required' ones must all be present
     * before submission is even accepted (enforced in the Form Request);
     * everything else is optional supporting evidence.
     */
    public const DOCUMENT_TYPES = [
        'government_id' => ['label' => 'Valid Government ID', 'required' => true],
        'proof_of_ownership' => ['label' => 'Proof of Property Ownership / Authorization', 'required' => true],
        'business_permit' => ['label' => 'Business Permit', 'required' => false],
        'barangay_clearance' => ['label' => 'Barangay Business Clearance', 'required' => false],
        'mayors_permit' => ["label" => "Mayor's Permit", 'required' => false],
        'dti_sec_registration' => ['label' => 'DTI / SEC Registration', 'required' => false],
        'other' => ['label' => 'Other Supporting Document', 'required' => false],
    ];

    /**
     * @param  array<string, UploadedFile>  $documents  keyed by document_type
     * @param  array<string, string>  $expirationDates  keyed by document_type, optional
     */
    public function submit(User $owner, array $documents, array $expirationDates = []): OwnerVerification
    {
        return DB::transaction(function () use ($owner, $documents, $expirationDates) {
            $verification = OwnerVerification::create([
                'user_id' => $owner->id,
                'status' => 'needs_review', // see runAutomatedChecks() for why this is always the outcome
                'submitted_at' => now(),
            ]);

            foreach (array_filter($documents) as $type => $file) {
                /** @var UploadedFile $file */
                $path = $file->store("verification/{$owner->id}/{$verification->id}", 'local');

                $verification->documents()->create([
                    'document_type' => $type,
                    'file_path' => $path,
                    'original_filename' => $file->getClientOriginalName(),
                    'mime_type' => $file->getClientMimeType(),
                    'size_bytes' => $file->getSize(),
                    'status' => 'pending',
                    'expiration_date' => $expirationDates[$type] ?? null,
                ]);
            }

            $this->runAutomatedChecks($verification);

            $owner->update(['owner_verification_status' => $verification->fresh()->status]);

            // Notify every Super Admin that something is waiting in their queue.
            User::whereHas('role', fn ($q) => $q->where('slug', 'super_admin'))
                ->get()
                ->each(fn (User $admin) => $admin->notify(new NewOwnerVerificationSubmittedNotification($verification)));

            return $verification->fresh(['documents']);
        });
    }

    /**
     * Deliberately limited scope: this checks STRUCTURE, not authenticity.
     * File presence/format/size are already enforced by the Form Request
     * before this ever runs. No system can reliably confirm a government ID
     * or business permit is genuine from an uploaded image/PDF alone — claiming
     * otherwise would be dishonest. So the only things checked here are the
     * mechanical facts a computer actually can verify:
     *   - every REQUIRED document type was actually submitted
     *   - if an expiration_date was supplied for a document, it isn't already expired
     *
     * The result is almost always 'needs_review' — this is intentional and
     * matches the brief's own instruction: "If automated verification cannot
     * confidently determine authenticity, send to an administrator for
     * manual review." A fully automatic 'verified' outcome is never produced
     * by this method; only a human admin can move a verification to 'approved'.
     */
    protected function runAutomatedChecks(OwnerVerification $verification): void
    {
        $submittedTypes = $verification->documents()->pluck('document_type')->all();

        $missingRequired = collect(self::DOCUMENT_TYPES)
            ->filter(fn ($config) => $config['required'])
            ->keys()
            ->diff($submittedTypes);

        if ($missingRequired->isNotEmpty()) {
            // Should be unreachable in practice (the Form Request requires
            // these), but kept as a defensive second check.
            $verification->update([
                'status' => 'needs_additional_documents',
                'admin_notes' => 'Automated check: missing required document(s): '.$missingRequired->implode(', '),
            ]);

            return;
        }

        $expiredDocs = $verification->documents()
            ->whereNotNull('expiration_date')
            ->where('expiration_date', '<', now()->toDateString())
            ->pluck('document_type');

        if ($expiredDocs->isNotEmpty()) {
            $verification->update([
                'status' => 'needs_additional_documents',
                'admin_notes' => 'Automated check: expired document(s) submitted: '.$expiredDocs->implode(', ').'. Please upload a current copy.',
            ]);

            return;
        }

        // Structural checks passed. Authenticity is a human judgment call —
        // route to admin review rather than auto-approving.
        $verification->update(['status' => 'needs_review']);
    }

    public function approve(OwnerVerification $verification, User $admin, ?string $notes = null): OwnerVerification
    {
        $verification->update([
            'status' => 'approved',
            'reviewed_at' => now(),
            'reviewed_by' => $admin->id,
            'admin_notes' => $notes,
        ]);

        $verification->documents()->update(['status' => 'verified', 'verified_at' => now()]);

        // NOTE: this used to set 'approved' as an intermediate state, expecting
        // a follow-up OTP/email verification step (Step 20) to promote it to
        // 'verified'. That step was never built, so every approved owner was
        // permanently stuck — locked out of owner features with no path
        // forward. Until Step 20 actually ships, approval goes straight to
        // 'verified' so owners aren't blocked by an unfinished feature.
        $verification->user->update(['owner_verification_status' => 'verified']);
        $verification->user->notify(new OwnerVerificationResultNotification($verification));

        return $verification->fresh();
    }

    public function reject(OwnerVerification $verification, User $admin, string $reason): OwnerVerification
    {
        $verification->update([
            'status' => 'rejected',
            'reviewed_at' => now(),
            'reviewed_by' => $admin->id,
            'rejection_reason' => $reason,
        ]);

        $verification->user->update(['owner_verification_status' => 'rejected']);
        $verification->user->notify(new OwnerVerificationResultNotification($verification));

        return $verification->fresh();
    }

    public function requestMoreDocuments(OwnerVerification $verification, User $admin, string $notes): OwnerVerification
    {
        $verification->update([
            'status' => 'needs_additional_documents',
            'reviewed_at' => now(),
            'reviewed_by' => $admin->id,
            'admin_notes' => $notes,
        ]);

        $verification->user->update(['owner_verification_status' => 'needs_additional_documents']);
        $verification->user->notify(new OwnerVerificationResultNotification($verification));

        return $verification->fresh();
    }
}
