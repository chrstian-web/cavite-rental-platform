<?php

namespace App\Policies;

use App\Models\User;
use App\Models\ViewingRequest;

class ViewingRequestPolicy
{
    public function view(User $user, ViewingRequest $viewing): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->isTenant()) {
            return $viewing->user_id === $user->id;
        }

        $property = $viewing->property;

        return $user->isOwner()
            ? $property->owner_id === $user->id
            : $property->managers()->where('users.id', $user->id)->exists();
    }

    public function manage(User $user, ViewingRequest $viewing): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        $property = $viewing->property;

        return $user->isOwner()
            ? $property->owner_id === $user->id
            : ($user->isManager() && $property->managers()->where('users.id', $user->id)->exists());
    }
}
