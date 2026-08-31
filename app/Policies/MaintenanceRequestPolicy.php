<?php

namespace App\Policies;

use App\Models\MaintenanceRequest;
use App\Models\User;

class MaintenanceRequestPolicy
{
    public function view(User $user, MaintenanceRequest $request): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->isTenant()) {
            return $request->user_id === $user->id;
        }

        $property = $request->property;

        return $user->isOwner()
            ? $property->owner_id === $user->id
            : $property->managers()->where('users.id', $user->id)->exists();
    }
}
