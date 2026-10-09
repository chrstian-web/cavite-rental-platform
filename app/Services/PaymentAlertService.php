<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\User;

/**
 * Turns a user's unread payment notifications into the cards shown in the
 * floating "Payment activity" panel (who paid, how much, method, reference…).
 */
class PaymentAlertService
{
    public const TYPES = ['online_payment_received', 'payment_submitted', 'payment_details_added'];

    /** @return array<int, array<string, mixed>> */
    public function forUser(User $user, int $limit = 15): array
    {
        $notifications = $user->unreadNotifications()
            ->latest()
            ->limit(100)
            ->get()
            ->filter(fn ($n) => in_array($n->data['type'] ?? null, self::TYPES, true))
            ->take($limit)
            ->values();

        if ($notifications->isEmpty()) {
            return [];
        }

        $payments = Payment::query()
            ->with(['tenant', 'contract.property', 'contract.rentalSpace'])
            ->whereIn('id', $notifications->map(fn ($n) => $n->data['payment_id'] ?? null)->filter()->unique())
            ->get()
            ->keyBy('id');

        return $notifications->map(function ($n) use ($payments) {
            $payment = $payments->get($n->data['payment_id'] ?? 0);

            return $payment ? $this->card($n->id, $n->data['type'], $n->created_at, $payment) : null;
        })->filter()->values()->all();
    }

    /** Mark every unread payment alert as read. */
    public function dismissAll(User $user): void
    {
        $user->unreadNotifications
            ->filter(fn ($n) => in_array($n->data['type'] ?? null, self::TYPES, true))
            ->each->markAsRead();
    }

    protected function card(string $id, string $type, $createdAt, Payment $payment): array
    {
        $tenant = $payment->tenant;
        $name = trim(($tenant->first_name ?? '').' '.($tenant->last_name ?? '')) ?: 'A tenant';
        $amount = '₱'.number_format((float) $payment->amount, 2);
        $contract = $payment->contract;

        $method = $payment->payment_method ?: ($payment->isOnline() ? 'online' : null);

        [$kind, $headline, $status] = match ($type) {
            'online_payment_received' => ['online', "{$name} paid {$amount}", 'Paid online · confirmed'],
            'payment_submitted' => ['review', "{$name} submitted {$amount}", $payment->status === 'submitted' ? 'Needs your review' : ucfirst(str_replace('_', ' ', $payment->status))],
            default => ['details', "{$name} added payment proof", 'Proof added'],
        };

        return [
            'id' => $id,
            'payment_id' => $payment->id,
            'kind' => $kind,
            'headline' => $headline,
            'tenant_name' => $name,
            'initials' => mb_strtoupper(mb_substr($tenant->first_name ?? '?', 0, 1).mb_substr($tenant->last_name ?? '', 0, 1)),
            'amount' => $amount,
            'type_label' => $payment->typeLabel(),
            'place' => trim(($contract?->property?->name ?? '').' — '.($contract?->rentalSpace?->space_number ?? ''), ' —'),
            'method' => $method ? (string) str($method)->replace('_', ' ')->title() : null,
            'reference' => $payment->reference_number ?: $payment->gateway_reference,
            'has_proof' => (bool) $payment->proof_path,
            'status' => $status,
            'when' => $createdAt->diffForHumans(),
            'url' => route('owner.payments.show', $payment),
        ];
    }
}
