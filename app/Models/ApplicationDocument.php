<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicationDocument extends Model
{
    protected $fillable = [
        'rental_application_id', 'document_type', 'path', 'original_filename',
        'mime_type', 'size_bytes', 'status',
    ];

    /** Document types requested on the application form, per employment status. */
    public const LABELS = [
        'valid_id_1' => 'Valid ID (1 of 2)',
        'valid_id_2' => 'Valid ID (2 of 2)',
        'student_id' => 'Student ID',
        'parent_id_1' => "Parent/guardian's valid ID (1 of 2)",
        'parent_id_2' => "Parent/guardian's valid ID (2 of 2)",
        // Older applications
        'valid_id' => 'Valid ID',
        'proof_of_income' => 'Proof of income',
        'school_id' => 'School ID',
        'coe' => 'Certificate of enrollment',
        'other' => 'Other document',
    ];

    public function getLabelAttribute(): string
    {
        return self::LABELS[$this->document_type]
            ?? str($this->document_type)->replace('_', ' ')->title()->toString();
    }

    public function rentalApplication(): BelongsTo
    {
        return $this->belongsTo(RentalApplication::class);
    }
}
