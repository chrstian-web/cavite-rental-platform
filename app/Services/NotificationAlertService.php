<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;

/**
 * Turns a user's unread notifications (admin, owner/manager or renter) into
 * the cards shown in the floating notification panel.
 *
 * Card shape (every card has these keys):
 *   id, kind, category, initials, headline, sub, chips[{text,tone}], reference,
 *   when, url, cta
 * Payment cards also keep: payment_id, tenant_name, amount, type_label, place,
 * method, has_proof, status.
 */
class NotificationAlertService
{
    /** @return array<int, array<string, mixed>> */
    public function forUser(User $user, int $limit = 15): array
    {
        $notifications = $user->unreadNotifications()->latest()->limit(100)->get()->take($limit)->values();

        if ($notifications->isEmpty()) {
            return [];
        }

        $payments = Payment::query()
            ->with(['tenant', 'contract.property', 'contract.rentalSpace'])
            ->whereIn('id', $notifications->map(fn ($n) => $n->data['payment_id'] ?? null)->filter()->unique())
            ->get()
            ->keyBy('id');

        return $notifications->map(function (DatabaseNotification $n) use ($payments) {
            $type = $n->data['type'] ?? null;

            if (in_array($type, ['online_payment_received', 'payment_submitted', 'payment_details_added'], true)) {
                $payment = $payments->get($n->data['payment_id'] ?? 0);

                return $payment ? $this->paymentCard($n->id, $type, $n->created_at, $payment) : null;
            }

            return $this->genericCard($n, $payments);
        })->filter()->values()->all();
    }

    /** Mark every unread notification as read. */
    public function dismissAll(User $user): void
    {
        $user->unreadNotifications->markAsRead();
    }

    protected function paymentCard(string $id, string $type, $createdAt, Payment $payment): array
    {
        $tenant = $payment->tenant;
        $name = trim(($tenant->first_name ?? '').' '.($tenant->last_name ?? '')) ?: 'A renter';
        $amount = '₱'.number_format((float) $payment->amount, 2);
        $contract = $payment->contract;
        $method = $payment->payment_method ?: ($payment->isOnline() ? 'online' : null);
        $methodLabel = $method ? (string) str($method)->replace('_', ' ')->title() : null;

        [$kind, $headline, $status] = match ($type) {
            'online_payment_received' => ['online', "{$name} paid {$amount}", 'Paid online · confirmed'],
            'payment_submitted' => ['review', "{$name} submitted {$amount}", $payment->status === 'submitted' ? 'Needs your review' : ucfirst(str_replace('_', ' ', $payment->status))],
            default => ['details', "{$name} added payment proof", 'Proof added'],
        };

        $place = trim(($contract?->property?->name ?? '').' — '.($contract?->rentalSpace?->space_number ?? ''), ' —');

        $chips = [['text' => $status, 'tone' => $kind === 'review' ? 'warn' : 'ok']];
        if ($methodLabel) {
            $chips[] = ['text' => $methodLabel, 'tone' => 'neutral'];
        }
        if ($payment->proof_path) {
            $chips[] = ['text' => 'Screenshot attached', 'tone' => 'neutral'];
        }

        return [
            'id' => $id,
            'kind' => $kind,
            'category' => 'Payment',
            'initials' => mb_strtoupper(mb_substr($tenant->first_name ?? '?', 0, 1).mb_substr($tenant->last_name ?? '', 0, 1)),
            'headline' => $headline,
            'sub' => $payment->typeLabel().($place ? ' · '.$place : ''),
            'chips' => $chips,
            'reference' => $payment->reference_number ?: $payment->gateway_reference,
            'when' => $createdAt->diffForHumans(),
            'url' => route('owner.payments.show', $payment),
            'cta' => $kind === 'review' ? 'Review' : 'View',
            // payment-specific extras
            'payment_id' => $payment->id,
            'tenant_name' => $name,
            'amount' => $amount,
            'type_label' => $payment->typeLabel(),
            'place' => $place,
            'method' => $methodLabel,
            'has_proof' => (bool) $payment->proof_path,
            'status' => $status,
        ];
    }

    protected function genericCard(DatabaseNotification $n, $payments): ?array
    {
        $d = $n->data;
        $type = $d['type'] ?? 'unknown';
        $property = $d['property_name'] ?? null;
        $status = isset($d['status']) ? ucfirst(str_replace('_', ' ', (string) $d['status'])) : null;
        $isGood = in_array($d['status'] ?? null, ['approved', 'verified', 'confirmed', 'completed', 'paid', 'resolved', 'accepted'], true);
        $isBad = in_array($d['status'] ?? null, ['rejected', 'cancelled', 'declined', 'failed', 'unverified'], true);
        $tone = $isGood ? 'ok' : ($isBad ? 'bad' : 'info');

        // [category, headline, sub, chips, url, cta, kind]
        $card = match ($type) {
            'new_viewing_request' => [
                'Viewing', ($d['requester_name'] ?? 'A renter').' requested a viewing',
                $property, array_filter([
                    isset($d['preferred_date']) ? ['text' => $d['preferred_date'].(isset($d['preferred_time']) ? ' · '.substr((string) $d['preferred_time'], 0, 5) : ''), 'tone' => 'neutral'] : null,
                    ['text' => 'Needs your response', 'tone' => 'warn'],
                ]), route('owner.viewings.index'), 'Respond', 'review',
            ],
            'viewing_request_status' => [
                'Viewing', match ($d['status'] ?? null) {
                    'confirmed' => 'Your viewing request was accepted',
                    'rescheduled' => 'Your viewing was rescheduled',
                    'cancelled' => 'Your viewing request was cancelled',
                    'completed' => 'Your viewing was marked completed',
                    default => 'Your viewing request was updated',
                }, $property, array_filter([
                    $status ? ['text' => $status, 'tone' => $tone] : null,
                    ! empty($d['preferred_date']) ? ['text' => $d['preferred_date'].(! empty($d['preferred_time']) ? ' · '.$d['preferred_time'] : ''), 'tone' => 'neutral'] : null,
                ]),
                route('tenant.viewings.index'), 'View', $isBad ? 'bad' : 'success',
            ],
            'new_rental_application' => [
                'Application', ($d['applicant_name'] ?? 'A renter').' applied to rent', $property,
                [['text' => 'Needs your review', 'tone' => 'warn']],
                route('owner.applications.show', $d['application_id']), 'Review', 'review',
            ],
            'rental_application_status' => [
                'Application', 'Your application was '.str($d['status'] ?? 'updated')->replace('_', ' '), $property,
                array_filter([$status ? ['text' => $status, 'tone' => $tone] : null]),
                route('tenant.applications.show', $d['application_id']), 'View', $isBad ? 'bad' : 'success',
            ],
            'new_property_submitted' => [
                'Verification', 'New property needs verification', $property,
                [['text' => 'Awaiting admin review', 'tone' => 'warn']],
                route('admin.properties.index'), 'Verify', 'review',
            ],
            'property_verification' => [
                'Property', ($d['status'] ?? null) === 'verified' ? 'Your property is verified and live' : 'Your property was not approved',
                $property, array_filter([$status ? ['text' => $status, 'tone' => $tone] : null]),
                route('owner.properties.index'), 'View', ($d['status'] ?? null) === 'verified' ? 'success' : 'bad',
            ],
            'new_owner_verification_submitted' => [
                'Verification', ($d['owner_name'] ?? 'An owner').' submitted verification documents', null,
                [['text' => 'Needs verification', 'tone' => 'warn']],
                route('admin.owner-verifications.show', $d['owner_verification_id']), 'Verify', 'review',
            ],
            'owner_verification_result' => [
                'Verification', match ($d['status'] ?? null) {
                    'approved' => 'Your owner verification was approved',
                    'rejected' => 'Your owner verification was not approved',
                    'needs_additional_documents' => 'Additional documents are needed',
                    default => 'Your owner verification was updated',
                }, null, array_filter([$status ? ['text' => $status, 'tone' => $tone] : null]),
                route('owner.verification.show'), 'View', $isGood ? 'success' : ($isBad ? 'bad' : 'review'),
            ],
            'new_maintenance_request' => [
                'Maintenance', 'New maintenance request', $property,
                [['text' => 'Needs attention', 'tone' => 'warn']],
                route('owner.maintenance.index'), 'View', 'review',
            ],
            'maintenance_request_status' => [
                'Maintenance', 'Your maintenance request is '.str($d['status'] ?? 'updated')->replace('_', ' '), null,
                array_filter([$status ? ['text' => $status, 'tone' => $tone] : null]),
                route('tenant.maintenance.index'), 'View', $isGood ? 'success' : 'info',
            ],
            'contract_activated' => [
                'Contract', 'Your contract is now active', $property,
                [['text' => 'Active', 'tone' => 'ok']],
                route('tenant.contracts.show', $d['contract_id']), 'View', 'success',
            ],
            'down_payment_requested' => [
                'Payment', 'Down payment requested', $property, array_filter([
                    isset($d['security_deposit']) ? ['text' => 'Deposit ₱'.number_format((float) $d['security_deposit'], 2), 'tone' => 'neutral'] : null,
                    isset($d['advance_payment']) ? ['text' => 'Advance ₱'.number_format((float) $d['advance_payment'], 2), 'tone' => 'neutral'] : null,
                ]), route('tenant.contracts.show', $d['contract_id']), 'Pay now', 'review',
            ],
            'payment_reviewed' => [
                'Payment', 'Your payment was '.str($d['status'] ?? 'updated')->replace('_', ' '), null,
                array_filter([$status ? ['text' => $status, 'tone' => $tone] : null]),
                route('tenant.payments.show', $d['payment_id']), 'View', $isGood ? 'success' : ($isBad ? 'bad' : 'info'),
            ],
            default => null,
        };

        if (! $card) {
            return null;
        }

        [$category, $headline, $sub, $chips, $url, $cta, $kind] = $card;

        return [
            'id' => $n->id,
            'kind' => $kind,
            'category' => $category,
            'initials' => mb_strtoupper(mb_substr($category, 0, 2)),
            'headline' => $headline,
            'sub' => $sub,
            'chips' => array_values(array_filter($chips)),
            'note' => ! empty($d['reason']) ? 'Reason: '.$d['reason'] : null,
            'reference' => null,
            'when' => $n->created_at->diffForHumans(),
            'url' => $url,
            'cta' => $cta,
        ];
    }
}
