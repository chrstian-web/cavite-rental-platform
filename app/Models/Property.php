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
        'min_monthly_rent', 'max_monthly_rent', 'reservation_fee', 'gcash_number', 'house_rules',
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
            'reservation_fee' => 'decimal:2',
            'is_featured' => 'boolean',
            'subscription_hidden' => 'boolean',
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

    /** Listings the owner's plan allows tenants to see. */
    public function scopeVisibleToTenants($query)
    {
        return $query->where('properties.subscription_hidden', false);
    }

    /**
     * Search boost: listings from owners on a paid plan come first, highest tier first.
     * Tiers and plan names come from config/subscriptions.php, never from user input.
     */
    public function scopeBoostedFirst($query)
    {
        $cases = collect(config('subscriptions.plans'))
            ->filter(fn ($plan) => $plan['boost'] > 0)
            ->map(fn ($plan, $key) => "WHEN s.plan = '{$key}' THEN {$plan['boost']}")
            ->implode(' ');

        if ($cases === '') {
            return $query;
        }

        return $query->orderByRaw(
            "COALESCE((SELECT MAX(CASE {$cases} ELSE 0 END) FROM subscriptions s"
            ." WHERE s.user_id = properties.owner_id AND s.status = 'active'"
            ." AND (s.ends_at IS NULL OR s.ends_at > ?)), 0) DESC",
            [now()]
        );
    }

    /**
     * Adds an `is_boosted` column (true when the owner is on a paid plan) so a result
     * list can show a "Featured" badge without a query per card. Call it before
     * withCount()/withAvg() so those keep selecting the property columns too.
     */
    public function scopeWithBoostFlag($query)
    {
        $plans = collect(config('subscriptions.plans'))->filter(fn ($plan) => $plan['boost'] > 0)->keys();

        if ($plans->isEmpty()) {
            return $query;
        }

        $list = $plans->map(fn ($key) => "'{$key}'")->implode(',');

        return $query->addSelect('properties.*')->selectRaw(
            "EXISTS (SELECT 1 FROM subscriptions s WHERE s.user_id = properties.owner_id AND s.status = 'active'"
            ." AND s.plan IN ({$list}) AND (s.ends_at IS NULL OR s.ends_at > ?)) AS is_boosted",
            [now()]
        );
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
