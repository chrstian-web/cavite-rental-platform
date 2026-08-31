<?php

namespace App\Policies;

use App\Models\RentalApplication;
use App\Models\User;

class RentalApplicationPolicy
{
    public function view(User $user, RentalApplication $application): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->isTenant()) {
            return $application->user_id === $user->id;
        }

        // Owners/Managers may view applications for properties they control.
        $property = $application->property;

        return $user->isOwner()
            ? $property->owner_id === $user->id
            : $property->managers()->where('users.id', $user->id)->exists();
    }

    public function review(User $user, RentalApplication $application): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        $property = $application->property;

        return $user->isOwner()
            ? $property->owner_id === $user->id
            : ($user->isManager() && $property->managers()->where('users.id', $user->id)->exists());
    }
}
