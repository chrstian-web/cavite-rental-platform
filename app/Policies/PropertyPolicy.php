<?php

namespace App\Policies;

use App\Models\Property;
use App\Models\User;

class PropertyPolicy
{
    public function viewAny(?User $user): bool
    {
        return true; // public listings
    }

    public function view(?User $user, Property $property): bool
    {
        return true; // public listing details
    }

    public function create(User $user): bool
    {
        return in_array($user->role->slug, ['owner', 'super_admin'], true);
    }

    public function update(User $user, Property $property): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->isOwner()) {
            return $property->owner_id === $user->id;
        }

        if ($user->isManager()) {
            return $property->managers()->where('users.id', $user->id)->exists();
        }

        return false;
    }

    public function delete(User $user, Property $property): bool
    {
        return $user->isSuperAdmin() || ($user->isOwner() && $property->owner_id === $user->id);
    }

    public function verify(User $user, Property $property): bool
    {
        return $user->isSuperAdmin();
    }
}
