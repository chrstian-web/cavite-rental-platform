<?php

namespace App\Services;

use App\Models\Property;
use App\Models\RentalApplication;
use App\Models\RentalSpace;
use App\Models\User;
use App\Notifications\NewRentalApplicationNotification;
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

    public function review(RentalApplication $application, string $status, ?string $reason, User $reviewer): RentalApplication
    {
        $application->update([
            'status' => $status,
            'decision_reason' => $reason,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
        ]);

        if (in_array($status, ['approved', 'rejected'], true)) {
            $application->user->notify(new RentalApplicationStatusNotification($application));
        }

        return $application->fresh();
    }
}
