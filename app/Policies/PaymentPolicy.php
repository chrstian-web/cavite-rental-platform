<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    public function view(User $user, Payment $payment): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->isTenant()) {
            return $payment->user_id === $user->id;
        }

        return $user->isOwner()
            ? $payment->contract->owner_id === $user->id
            : $payment->contract->property->managers()->where('users.id', $user->id)->exists();
    }

    /**
     * Tenant submitting method/reference/proof against a due payment record.
     */
    public function submit(User $user, Payment $payment): bool
    {
        return $user->isTenant()
            && $payment->user_id === $user->id
            && $payment->canBeSubmittedByTenant();
    }

    /**
     * Owner/manager/super admin reviewing a tenant's submitted payment.
     */
    public function review(User $user, Payment $payment): bool
    {
        if (! $payment->canBeReviewed()) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->isOwner()
            ? $payment->contract->owner_id === $user->id
            : ($user->isManager() && $payment->contract->property->managers()->where('users.id', $user->id)->exists());
    }
}
