{{-- Translates a stored notification's data into a label + link. $notification is a DatabaseNotification. --}}
@php
    $data = $notification->data;
    $type = $data['type'] ?? 'unknown';

    [$label, $url] = match ($type) {
        'new_rental_application' => [
            "New application from {$data['applicant_name']} for {$data['property_name']}",
            route('owner.applications.show', $data['application_id']),
        ],
        'rental_application_status' => [
            "Your application for {$data['property_name']} is now ".str($data['status'])->replace('_', ' '),
            route('tenant.applications.show', $data['application_id']),
        ],
        'new_viewing_request' => [
            "New viewing request for {$data['property_name']}",
            route('owner.viewings.index'),
        ],
        'viewing_request_status' => [
            "Your viewing request for {$data['property_name']} is now ".str($data['status'])->replace('_', ' '),
            route('tenant.viewings.index'),
        ],
        'new_property_submitted' => [
            "{$data['property_name']} is awaiting verification",
            route('admin.properties.index'),
        ],
        'property_verification' => [
            $data['status'] === 'verified'
                ? "{$data['property_name']} has been verified and is now live"
                : "{$data['property_name']} was not approved for listing",
            route('owner.properties.index'),
        ],
        'new_maintenance_request' => [
            "New maintenance request at {$data['property_name']}",
            route('owner.maintenance.index'),
        ],
        'maintenance_request_status' => [
            'Your maintenance request is now '.str($data['status'])->replace('_', ' '),
            route('tenant.maintenance.index'),
        ],
        'contract_activated' => [
            "Your contract for {$data['property_name']} is now active",
            route('tenant.contracts.show', $data['contract_id']),
        ],
        'new_owner_verification_submitted' => [
            "{$data['owner_name']} submitted documents for owner verification",
            route('admin.owner-verifications.show', $data['owner_verification_id']),
        ],
        'owner_verification_result' => [
            match ($data['status']) {
                'approved' => 'Your owner verification was approved',
                'rejected' => 'Your owner verification was not approved',
                'needs_additional_documents' => 'Additional documents are needed for your verification',
                default => 'Your owner verification status was updated',
            },
            route('owner.verification.show'),
        ],
        default => ['You have a new notification', '#'],
    };
@endphp

<a href="{{ $url }}"
   @unless ($notification->read_at)
       onclick="fetch('{{ route('notifications.read', $notification->id) }}', {method: 'PATCH', keepalive: true, headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}'}})"
   @endunless
   class="block px-4 py-3 text-sm hover:bg-slate-50 {{ $notification->read_at ? 'text-slate-500' : 'text-slate-900 font-medium bg-blue-50/40' }}">
    <div class="flex items-start gap-2">
        @unless ($notification->read_at)
            <span class="mt-1.5 w-1.5 h-1.5 rounded-full bg-blue-600 shrink-0"></span>
        @endunless
        <div>
            <p>{{ $label }}</p>
            <p class="text-xs text-slate-400 mt-0.5">{{ $notification->created_at->diffForHumans() }}</p>
        </div>
    </div>
</a>
