<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Property extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'owner_id', 'location_id', 'barangay_id', 'name', 'slug', 'property_type',
        'description', 'address_line', 'latitude', 'longitude',
        'contact_person', 'contact_number', 'contact_email',
        'min_monthly_rent', 'max_monthly_rent', 'house_rules',
        'availability_status', 'verification_status', 'views_count', 'is_featured',
    ];

    protected function casts(): array
    {
        return [
            'house_rules' => 'array',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'min_monthly_rent' => 'decimal:2',
            'max_monthly_rent' => 'decimal:2',
            'is_featured' => 'boolean',
        ];
    }

    // ── Relationships ────────────────────────────────────────────

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function managers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'property_manager_assignments', 'property_id', 'manager_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function barangay(): BelongsTo
    {
        return $this->belongsTo(Barangay::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(PropertyImage::class);
    }

    public function amenities(): BelongsToMany
    {
        return $this->belongsToMany(Amenity::class, 'amenity_property');
    }

    public function rentalSpaces(): HasMany
    {
        return $this->hasMany(RentalSpace::class);
    }

    public function virtualTour(): HasOne
    {
        return $this->hasOne(VirtualTour::class);
    }

    public function virtualTours(): HasMany
    {
        return $this->hasMany(VirtualTour::class);
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    public function rentalApplications(): HasMany
    {
        return $this->hasMany(RentalApplication::class);
    }

    public function viewingRequests(): HasMany
    {
        return $this->hasMany(ViewingRequest::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function dssScores(): HasMany
    {
        return $this->hasMany(DssScore::class);
    }

    // ── Scopes ───────────────────────────────────────────────────

    public function scopeOfType($query, string $type)
    {
        return $query->where('property_type', $type);
    }

    public function scopeAvailable($query)
    {
        return $query->where('availability_status', 'available');
    }

    public function scopeInLocation($query, int $locationId)
    {
        return $query->where('location_id', $locationId);
    }

    public function scopeWithinBudget($query, ?float $min, ?float $max)
    {
        return $query
            ->when($min, fn ($q) => $q->where('max_monthly_rent', '>=', $min))
            ->when($max, fn ($q) => $q->where('min_monthly_rent', '<=', $max));
    }
}
