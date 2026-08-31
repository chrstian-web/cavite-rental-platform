<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VirtualTour extends Model
{
    protected $fillable = ['property_id', 'rental_space_id', 'title', 'description', 'thumbnail', 'status'];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function rentalSpace(): BelongsTo
    {
        return $this->belongsTo(RentalSpace::class);
    }

    public function scenes(): HasMany
    {
        return $this->hasMany(VirtualTourScene::class)->orderBy('sort_order');
    }
}
