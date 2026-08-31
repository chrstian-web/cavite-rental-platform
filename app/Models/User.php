<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'role_id', 'first_name', 'last_name', 'email', 'phone',
        'password', 'avatar', 'status', 'owner_verification_status',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // ── Relationships ────────────────────────────────────────────

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function properties(): HasMany
    {
        return $this->hasMany(Property::class, 'owner_id');
    }

    public function managedProperties(): BelongsToMany
    {
        return $this->belongsToMany(Property::class, 'property_manager_assignments', 'manager_id', 'property_id');
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    public function comparisons(): HasMany
    {
        return $this->hasMany(Comparison::class);
    }

    public function rentalApplications(): HasMany
    {
        return $this->hasMany(RentalApplication::class);
    }

    public function viewingRequests(): HasMany
    {
        return $this->hasMany(ViewingRequest::class);
    }

    public function rentalContracts(): HasMany
    {
        return $this->hasMany(RentalContract::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function maintenanceRequests(): HasMany
    {
        return $this->hasMany(MaintenanceRequest::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function dssScores(): HasMany
    {
        return $this->hasMany(DssScore::class);
    }

    // ── Role helpers ─────────────────────────────────────────────

    public function isSuperAdmin(): bool
    {
        return $this->role?->slug === 'super_admin';
    }

    public function isOwner(): bool
    {
        return $this->role?->slug === 'owner';
    }

    public function isTenant(): bool
    {
        return $this->role?->slug === 'tenant';
    }

    public function isManager(): bool
    {
        return $this->role?->slug === 'manager';
    }

    /**
     * Used to light up a specific nav link (e.g. "Applications") only when
     * there's an unread notification relevant to THAT section, rather than
     * a single generic "you have notifications" dot everywhere.
     */
    public function hasUnreadNotificationOfType(string|array $types): bool
    {
        $types = (array) $types;

        return $this->unreadNotifications
            ->contains(fn ($n) => in_array($n->data['type'] ?? null, $types, true));
    }

    public function ownerVerifications(): HasMany
    {
        return $this->hasMany(OwnerVerification::class);
    }

    public function latestOwnerVerification(): ?OwnerVerification
    {
        return $this->ownerVerifications()->latest('submitted_at')->first();
    }

    /**
     * The gate every owner-only route checks. Only meaningful for the
     * 'owner' role — managers and Super Admin were never subject to this
     * pipeline, so they're always treated as passing.
     */
    public function isOwnerVerified(): bool
    {
        if (! $this->isOwner()) {
            return true;
        }

        return $this->owner_verification_status === 'verified';
    }
}
