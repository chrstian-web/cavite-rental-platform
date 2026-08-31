<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class RentalApplication extends Model
{
    protected $fillable = [
        'user_id', 'property_id', 'rental_space_id', 'desired_move_in_date',
        'length_of_stay_months', 'number_of_occupants', 'employment_status',
        'monthly_income', 'emergency_contact_name', 'emergency_contact_number',
        'emergency_contact_relationship', 'notes', 'status', 'decision_reason',
        'reviewed_by', 'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'desired_move_in_date' => 'date',
            'monthly_income' => 'decimal:2',
            'reviewed_at' => 'datetime',
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

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ApplicationDocument::class);
    }

    public function contract(): HasOne
    {
        return $this->hasOne(RentalContract::class);
    }
}
