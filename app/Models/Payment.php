<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payment extends Model
{
    /**
     * pending    — due, tenant hasn't submitted anything yet (or was asked to correct/resubmit)
     * submitted  — tenant submitted method/reference/proof, awaiting owner or admin review
     * paid       — reviewer approved the submission (or owner recorded it directly)
     * overdue    — manually flagged overdue by an owner (see isPastDue() for the computed version)
     * failed     — reviewer rejected the submission
     */
    public const STATUSES = ['pending', 'submitted', 'paid', 'overdue', 'failed'];

    protected $fillable = [
        'rental_contract_id', 'user_id', 'amount', 'due_date', 'payment_date',
        'payment_method', 'reference_number', 'status', 'notes',
        'proof_path', 'proof_original_filename', 'submitted_at',
        'reviewed_by', 'reviewed_at', 'review_reason',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'due_date' => 'date',
            'payment_date' => 'date',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(RentalContract::class, 'rental_contract_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(PaymentStatusHistory::class)->latest('created_at');
    }

    /**
     * Computed lateness, independent of the stored 'overdue' status (which an
     * owner can still set manually for cash-recorded payments). Used to group
     * "Due Soon" vs "Overdue" in the tenant payments view.
     */
    public function isPastDue(): bool
    {
        return in_array($this->status, ['pending', 'overdue'], true)
            && $this->due_date->isPast();
    }

    public function canBeSubmittedByTenant(): bool
    {
        return $this->status === 'pending';
    }

    public function canBeReviewed(): bool
    {
        return $this->status === 'submitted';
    }
}
