<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentEvent extends Model
{
    protected $fillable = ['payment_id', 'event', 'source', 'gateway_event_id', 'metadata'];

    protected function casts(): array
    {
        return ['metadata' => 'array'];
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}

