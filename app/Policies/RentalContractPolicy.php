<?php

namespace App\Policies;

use App\Models\RentalContract;
use App\Models\User;

class RentalContractPolicy
{
    public function view(User $user, RentalContract $contract): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->isTenant()) {
            return $contract->user_id === $user->id;
        }

        return $user->isOwner()
            ? $contract->owner_id === $user->id
            : $contract->property->managers()->where('users.id', $user->id)->exists();
    }
}
