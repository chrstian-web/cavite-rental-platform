<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class RentalSpace extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'property_id', 'space_number', 'space_type', 'bedrooms', 'bathrooms',
        'total_capacity', 'occupied_capacity', 'floor_area_sqm', 'is_furnished',
        'monthly_rent', 'security_deposit', 'advance_payment', 'status',
        'attributes', 'utilities_included',
    ];

    protected function casts(): array
    {
        return [
            'attributes' => 'array',
            'utilities_included' => 'array',
            'is_furnished' => 'boolean',
            'monthly_rent' => 'decimal:2',
            'security_deposit' => 'decimal:2',
            'advance_payment' => 'decimal:2',
        ];
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(RentalSpaceImage::class);
    }

    public function rentalApplications(): HasMany
    {
        return $this->hasMany(RentalApplication::class);
    }

    public function rentalContracts(): HasMany
    {
        return $this->hasMany(RentalContract::class);
    }

    public function getAvailableCapacityAttribute(): int
    {
        return max(0, $this->total_capacity - $this->occupied_capacity);
    }

    public function scopeAvailable($query)
    {
        return $query->where('status', 'available');
    }
}
