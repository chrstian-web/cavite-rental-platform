<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VerificationDocument extends Model
{
    protected $fillable = [
        'owner_verification_id', 'document_type', 'file_path', 'original_filename',
        'mime_type', 'size_bytes', 'status', 'expiration_date', 'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'expiration_date' => 'date',
            'verified_at' => 'datetime',
        ];
    }

    public function ownerVerification(): BelongsTo
    {
        return $this->belongsTo(OwnerVerification::class);
    }
}
