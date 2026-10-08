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

    /**
     * True when a tenant is renting this unit right now (an active contract) or
     * it is flagged occupied. Pass the list through
     * withCount(['rentalContracts as active_contracts_count' => ...]) to avoid
     * one query per unit.
     */
    public function isRented(): bool
    {
        $active = $this->active_contracts_count
            ?? $this->rentalContracts()->where('status', 'active')->count();

        return $active > 0 || $this->status === 'occupied';
    }

    public function scopeAvailable($query)
    {
        return $query->where('status', 'available');
    }

    /**
     * A unit counts as occupied when it is flagged occupied, has any occupied
     * capacity, or has an active contract. Checking all three keeps the dashboard
     * correct even if the status column was never flipped (partly-filled dorm
     * rooms, older data, a status edited by hand).
     */
    public function scopeOccupied($query)
    {
        return $query->where(function ($q) {
            $q->where('status', 'occupied')
                ->orWhere('occupied_capacity', '>', 0)
                ->orWhereHas('rentalContracts', fn ($c) => $c->where('status', 'active'));
        });
    }

    /**
     * Units a tenant can still take: marked available, with room left, and not
     * fully covered by active contracts.
     */
    public function scopeOpenForRent($query)
    {
        return $query->where('status', 'available')
            ->whereColumn('occupied_capacity', '<', 'total_capacity')
            ->whereRaw('(select count(*) from rental_contracts where rental_contracts.rental_space_id = rental_spaces.id and rental_contracts.status = ?) < rental_spaces.total_capacity', ['active']);
    }
}
