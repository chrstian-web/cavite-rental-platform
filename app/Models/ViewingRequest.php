<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ViewingRequest extends Model
{
    protected $fillable = [
        'user_id', 'property_id', 'rental_space_id', 'preferred_date',
        'preferred_time', 'message', 'status',
    ];

    protected function casts(): array
    {
        return ['preferred_date' => 'date'];
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
