<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DssScore extends Model
{
    protected $fillable = [
        'user_id', 'property_id', 'rental_space_id', 'total_score',
        'criteria_breakdown', 'reasons', 'preferences_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'total_score' => 'decimal:2',
            'criteria_breakdown' => 'array',
            'reasons' => 'array',
            'preferences_snapshot' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function rentalSpace(): BelongsTo
    {
        return $this->belongsTo(RentalSpace::class);
    }
}
