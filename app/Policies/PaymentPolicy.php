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

        $property = $payment->application?->property ?? $payment->contract?->property;
        if (! $property) {
            return false;
        }

        return $user->isOwner()
            ? $property->owner_id === $user->id
            : ($user->isManager() && $property->managers()->where('users.id', $user->id)->exists());
    }

    /**
     * Tenant submitting method/reference/proof against a due payment record.
     */
    public function submit(User $user, Payment $payment): bool
    {
        return $user->isTenant()
            && $payment->user_id === $user->id
            && $payment->rental_application_id === null
            && $payment->canBeSubmittedByTenant();
    }

    /**
     * Tenant adding their reference number + screenshot to a payment they
     * already paid online (the gateway confirmed it; this is for the owner's records).
     */
    public function addDetails(User $user, Payment $payment): bool
    {
        return $user->isTenant()
            && $payment->user_id === $user->id
            && $payment->rental_application_id === null
            && $payment->isOnline()
            && $payment->status === 'paid';
    }

    /**
     * Owner/manager/super admin recording a payment received in person.
     */
    public function markReceived(User $user, Payment $payment): bool
    {
        if ($user->isTenant() || $payment->rental_application_id !== null || ! in_array($payment->status, ['pending', 'overdue'], true)) {
            return false;
        }

        return $this->view($user, $payment);
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

        $property = $payment->application?->property ?? $payment->contract?->property;

        return $property && ($user->isOwner()
            ? $property->owner_id === $user->id
            : ($user->isManager() && $property->managers()->where('users.id', $user->id)->exists()));
    }
}
