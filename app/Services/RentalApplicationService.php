<?php

namespace App\Services;

use App\Models\Property;
use App\Models\RentalApplication;
use App\Models\RentalSpace;
use App\Models\User;
use App\Notifications\NewRentalApplicationNotification;
use App\Notifications\ReservationFeeOfferedNotification;
use App\Notifications\RentalApplicationStatusNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class RentalApplicationService
{
    /**
     * @param  array<string, UploadedFile|null>  $documents  keyed by document_type
     */
    public function submit(User $tenant, RentalSpace $space, array $data, array $documents): RentalApplication
    {
        return DB::transaction(function () use ($tenant, $space, $data, $documents) {
            $application = RentalApplication::create([
                ...$data,
                'user_id' => $tenant->id,
                'property_id' => $space->property_id,
                'rental_space_id' => $space->id,
                'status' => 'pending',
                // Snapshot of the property's reservation fee, so later changes to the listing don't alter this application.
                'reservation_fee' => $space->property->reservation_fee,
            ]);

            foreach (array_filter($documents) as $type => $file) {
                /** @var UploadedFile $file */
                $path = $file->store("applications/{$application->id}/documents", 'local');

                $application->documents()->create([
                    'document_type' => $type,
                    'path' => $path,
                    'original_filename' => $file->getClientOriginalName(),
                    'mime_type' => $file->getClientMimeType(),
                    'size_bytes' => $file->getSize(),
                ]);
            }

            // Notify everyone responsible for this property: the owner, plus any assigned managers.
            $recipients = collect([$application->property->owner])
                ->merge($application->property->managers)
                ->filter();

            foreach ($recipients as $recipient) {
                $recipient->notify(new NewRentalApplicationNotification($application));
            }

            return $application->fresh(['documents']);
        });
    }

    public function review(RentalApplication $application, string $status, ?string $reason, User $reviewer, array $offer = []): RentalApplication
    {
        [$application, $alreadyApproved] = DB::transaction(function () use ($application, $status, $reason, $reviewer, $offer) {
            $application = RentalApplication::whereKey($application->id)->lockForUpdate()->firstOrFail();
            $alreadyApproved = $application->status === 'approved';
            abort_if($alreadyApproved && ($status !== 'approved' || $application->reservation_terms_accepted_at), 422, 'An accepted reservation offer cannot be changed.');
            abort_if($alreadyApproved && $application->hasPaidReservationFee(), 422, 'A paid reservation offer cannot be changed.');

            $application->update([
                'status' => $status,
                'decision_reason' => $reason ?? $application->decision_reason,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                ...($status === 'approved' ? [
                    'reservation_fee' => $offer['reservation_fee'],
                    'reservation_terms' => $offer['reservation_terms'],
                    'reservation_offer_sent_at' => now(),
                ] : []),
            ]);

            return [$application->fresh(['property', 'rentalSpace', 'user']), $alreadyApproved];
        });

        if (in_array($status, ['approved', 'rejected'], true)) {
            if (! $alreadyApproved) {
                $application->user->notify(new RentalApplicationStatusNotification($application));
            }

            if ($status === 'approved') {
                $application->user->notify(new ReservationFeeOfferedNotification($application));
            }
        }

        return $application->fresh();
    }
}
