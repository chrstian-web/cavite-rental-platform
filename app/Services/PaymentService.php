<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\User;
use App\Notifications\PaymentReviewedNotification;
use App\Notifications\PaymentSubmittedNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    /**
     * Tenant submits method/reference/date and optional proof against an
     * existing due payment record. Guarded against duplicate submission and
     * mismatched/overpayment amounts by the Form Request + Policy before
     * this ever runs; this method just performs the transition.
     */
    public function submit(Payment $payment, User $tenant, array $data, ?UploadedFile $proof = null): Payment
    {
        return DB::transaction(function () use ($payment, $tenant, $data, $proof) {
            $fromStatus = $payment->status;

            $update = [
                'payment_method' => $data['payment_method'],
                'reference_number' => $data['reference_number'],
                'payment_date' => $data['payment_date'],
                'status' => 'submitted',
                'submitted_at' => now(),
                // Clear any prior reviewer decision — this is a fresh submission.
                'reviewed_by' => null,
                'reviewed_at' => null,
                'review_reason' => null,
            ];

            if ($proof) {
                $path = $proof->store("payments/{$payment->rental_contract_id}/{$payment->id}", 'local');
                $update['proof_path'] = $path;
                $update['proof_original_filename'] = $proof->getClientOriginalName();
            }

            $payment->update($update);

            $payment->statusHistories()->create([
                'actor_id' => $tenant->id,
                'from_status' => $fromStatus,
                'to_status' => 'submitted',
                'reason' => 'Tenant submitted payment for review.',
            ]);

            $this->notifyReviewers($payment->fresh());

            return $payment->fresh();
        });
    }

    /**
     * For a payment already confirmed online: stores the tenant's reference
     * number and payment screenshot so the owner has them on record.
     * The payment's status does not change.
     */
    public function addOnlineDetails(Payment $payment, User $tenant, string $referenceNumber, UploadedFile $proof): Payment
    {
        return DB::transaction(function () use ($payment, $tenant, $referenceNumber, $proof) {
            $path = $proof->store("payments/{$payment->rental_contract_id}/{$payment->id}", 'local');

            $payment->update([
                'reference_number' => $referenceNumber,
                'proof_path' => $path,
                'proof_original_filename' => $proof->getClientOriginalName(),
            ]);

            $payment->statusHistories()->create([
                'actor_id' => $tenant->id,
                'from_status' => $payment->status,
                'to_status' => $payment->status,
                'reason' => 'Tenant added reference number and payment screenshot.',
            ]);

            return $payment->fresh();
        });
    }

    /**
     * Owner/manager records that a still-pending payment was received in
     * person (cash, over-the-counter, etc.) without the tenant submitting proof.
     *
     * @param  array{payment_method: string, reference_number?: ?string, payment_date: string}  $data
     */
    public function markReceived(Payment $payment, User $reviewer, array $data): Payment
    {
        return DB::transaction(function () use ($payment, $reviewer, $data) {
            $fromStatus = $payment->status;

            $payment->update([
                'payment_method' => $data['payment_method'],
                'reference_number' => $data['reference_number'] ?? null,
                'payment_date' => $data['payment_date'],
                'status' => 'paid',
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'review_reason' => null,
            ]);

            $payment->statusHistories()->create([
                'actor_id' => $reviewer->id,
                'from_status' => $fromStatus,
                'to_status' => 'paid',
                'reason' => 'Payment received in person and recorded by the owner.',
            ]);

            $payment->tenant->notify(new PaymentReviewedNotification($payment->fresh()));

            return $payment->fresh();
        });
    }

    public function approve(Payment $payment, User $reviewer): Payment
    {
        return $this->transition($payment, $reviewer, 'paid', null);
    }

    public function reject(Payment $payment, User $reviewer, string $reason): Payment
    {
        return $this->transition($payment, $reviewer, 'failed', $reason);
    }

    /**
     * Sends the submission back to the tenant with a note on what to fix,
     * rather than a hard rejection — the tenant can resubmit against the
     * same payment record.
     */
    public function requestCorrection(Payment $payment, User $reviewer, string $reason): Payment
    {
        return $this->transition($payment, $reviewer, 'pending', $reason);
    }

    protected function transition(Payment $payment, User $reviewer, string $toStatus, ?string $reason): Payment
    {
        return DB::transaction(function () use ($payment, $reviewer, $toStatus, $reason) {
            $fromStatus = $payment->status;

            $payment->update([
                'status' => $toStatus,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'review_reason' => $reason,
            ]);

            $payment->statusHistories()->create([
                'actor_id' => $reviewer->id,
                'from_status' => $fromStatus,
                'to_status' => $toStatus,
                'reason' => $reason,
            ]);

            $payment->tenant->notify(new PaymentReviewedNotification($payment->fresh()));

            return $payment->fresh();
        });
    }

    protected function notifyReviewers(Payment $payment): void
    {
        $contract = $payment->contract()->with('property.managers', 'owner')->first();

        $recipients = collect([$contract->owner])
            ->merge($contract->property->managers)
            ->filter()
            ->unique('id');

        foreach ($recipients as $recipient) {
            $recipient->notify(new PaymentSubmittedNotification($payment));
        }
    }
}
