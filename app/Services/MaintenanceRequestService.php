<?php

namespace App\Services;

use App\Models\MaintenanceRequest;
use App\Models\Property;
use App\Models\RentalSpace;
use App\Models\User;
use App\Notifications\MaintenanceRequestStatusNotification;
use App\Notifications\NewMaintenanceRequestNotification;
use Illuminate\Support\Facades\DB;

class MaintenanceRequestService
{
    public function submit(User $tenant, Property $property, ?RentalSpace $space, array $data, array $images): MaintenanceRequest
    {
        return DB::transaction(function () use ($tenant, $property, $space, $data, $images) {
            $request = MaintenanceRequest::create([
                ...$data,
                'user_id' => $tenant->id,
                'property_id' => $property->id,
                'rental_space_id' => $space?->id,
                'status' => 'submitted',
            ]);

            foreach ($images as $image) {
                $path = $image->store("maintenance/{$request->id}", 'public');
                $request->images()->create(['path' => $path]);
            }

            $recipients = collect([$property->owner])->merge($property->managers)->filter();
            foreach ($recipients as $recipient) {
                $recipient->notify(new NewMaintenanceRequestNotification($request));
            }

            return $request->fresh(['images']);
        });
    }

    public function updateStatus(MaintenanceRequest $request, string $status): MaintenanceRequest
    {
        $request->update(['status' => $status]);
        $request->tenant->notify(new MaintenanceRequestStatusNotification($request));

        return $request->fresh();
    }
}
