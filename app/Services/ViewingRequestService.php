<?php

namespace App\Services;

use App\Models\Property;
use App\Models\RentalSpace;
use App\Models\User;
use App\Models\ViewingRequest;
use App\Notifications\NewViewingRequestNotification;
use App\Notifications\ViewingRequestStatusNotification;

class ViewingRequestService
{
    public function request(User $tenant, Property $property, ?RentalSpace $space, array $data): ViewingRequest
    {
        $viewing = ViewingRequest::create([
            ...$data,
            'user_id' => $tenant->id,
            'property_id' => $property->id,
            'rental_space_id' => $space?->id,
            'status' => 'pending',
        ]);

        $recipients = collect([$property->owner])->merge($property->managers)->filter();

        foreach ($recipients as $recipient) {
            $recipient->notify(new NewViewingRequestNotification($viewing));
        }

        return $viewing;
    }

    public function updateStatus(ViewingRequest $viewing, array $data): ViewingRequest
    {
        // Only touch preferred_date/preferred_time when actually rescheduling —
        // confirming, cancelling, or completing a viewing should never
        // overwrite its existing date/time with the blank fields the form
        // always submits for those other actions.
        $updateData = ['status' => $data['status']];

        if ($data['status'] === 'rescheduled') {
            $updateData['preferred_date'] = $data['preferred_date'];
            $updateData['preferred_time'] = $data['preferred_time'];
        }

        $viewing->update($updateData);
        $viewing->user->notify(new ViewingRequestStatusNotification($viewing));

        return $viewing->fresh();
    }
}
