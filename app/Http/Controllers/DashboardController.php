<?php

namespace App\Http\Controllers;

use App\Http\Concerns\FiltersAndSortsProperties;
use App\Models\MaintenanceRequest;
use App\Models\OwnerVerification;
use App\Models\Payment;
use App\Models\Property;
use App\Models\RentalApplication;
use App\Models\RentalContract;
use App\Models\RentalSpace;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    use FiltersAndSortsProperties;

    public function __invoke(Request $request): View
    {
        $user = $request->user();

        return match ($user->role->slug) {
            'super_admin' => view('dashboard.admin', $this->adminStats()),
            'owner', 'manager' => view('dashboard.owner', $this->ownerStats($user)),
            default => view('dashboard.tenant', $this->tenantStats($user)),
        };
    }

    protected function adminStats(): array
    {
        $roleCount = fn (string $slug) => User::whereHas('role', fn ($q) => $q->where('slug', $slug))->count();

        return [
            // Needs attention: the admin's real work queue.
            'pendingOwnerVerifications' => OwnerVerification::active()->where('status', 'needs_review')->whereHas('user', fn ($q) => $q->withoutTrashed())->count(),
            'pendingMoreDocuments' => OwnerVerification::active()->where('status', 'needs_additional_documents')->whereHas('user', fn ($q) => $q->withoutTrashed())->count(),
            'pendingPropertyVerifications' => Property::where('verification_status', 'pending')->whereHas('owner', fn ($q) => $q->withoutTrashed())->count(),

            // Platform totals. Units only count those whose property still exists.
            'totalProperties' => Property::count(),
            'totalUnits' => RentalSpace::whereHas('property')->count(),
            'occupiedUnits' => RentalSpace::whereHas('property')->occupied()->count(),
            'monthlyRevenue' => Payment::where('status', 'paid')
                ->whereMonth('payment_date', now()->month)
                ->whereYear('payment_date', now()->year)
                ->sum('amount'),

            // Users by role.
            'totalOwners' => $roleCount('owner'),
            'totalManagers' => $roleCount('manager'),
            'totalTenants' => $roleCount('tenant'),
            'deletedUsers' => User::onlyTrashed()->count(),

            'owners' => $this->ownerSummaries(),
            'recentActivity' => $this->recentActivity(),
        ];
    }

    /**
     * Owners (most properties first) with their portfolio numbers. Uses a few
     * grouped queries instead of several queries per owner.
     */
    protected function ownerSummaries(int $limit = 10)
    {
        $owners = User::query()
            ->whereHas('role', fn ($q) => $q->where('slug', 'owner'))
            ->withCount('properties')
            ->with(['ownerVerifications' => fn ($q) => $q->latest('submitted_at')])
            ->orderByDesc('properties_count')
            ->orderBy('first_name')
            ->take($limit)
            ->get();

        $ownerIds = $owners->pluck('id');

        $propertyOwner = Property::whereIn('owner_id', $ownerIds)->pluck('owner_id', 'id');
        $propertyIds = $propertyOwner->keys();

        $units = RentalSpace::whereIn('property_id', $propertyIds)->get(['id', 'property_id'])
            ->countBy(fn ($u) => $propertyOwner[$u->property_id]);
        $occupied = RentalSpace::whereIn('property_id', $propertyIds)->occupied()->get(['id', 'property_id'])
            ->countBy(fn ($u) => $propertyOwner[$u->property_id]);
        $tenants = RentalContract::whereIn('property_id', $propertyIds)->where('status', 'active')->get(['id', 'property_id'])
            ->countBy(fn ($c) => $propertyOwner[$c->property_id]);

        return $owners->each(function (User $owner) use ($units, $occupied, $tenants) {
            $owner->units_count = $units[$owner->id] ?? 0;
            $owner->occupied_count = $occupied[$owner->id] ?? 0;
            $owner->active_tenants_count = $tenants[$owner->id] ?? 0;
            $owner->latest_verification = $owner->ownerVerifications->first();
        });
    }

    /**
     * Latest platform events for the admin feed: new sign-ups, property
     * submissions and owner verification submissions, newest first.
     */
    protected function recentActivity(int $limit = 8)
    {
        $signups = User::with('role')->latest()->take($limit)->get()
            ->filter(fn ($u) => $u->role?->slug !== 'super_admin')
            ->map(fn ($u) => [
                'at' => $u->created_at,
                'text' => trim($u->first_name.' '.$u->last_name).' registered as '.str($u->role?->slug ?? 'user')->replace('_', ' ')->lower(),
                'url' => route('admin.users.index', ['search' => $u->email]),
            ]);

        $properties = Property::with('owner')->latest()->take($limit)->get()
            ->map(fn ($p) => [
                'at' => $p->created_at,
                'text' => '"'.$p->name.'" submitted by '.trim(($p->owner->first_name ?? '').' '.($p->owner->last_name ?? '')),
                'url' => route('admin.properties.index'),
            ]);

        $verifications = OwnerVerification::active()->with('user')->whereNotNull('submitted_at')->latest('submitted_at')->take($limit)->get()
            ->map(fn ($v) => [
                'at' => $v->submitted_at,
                'text' => trim(($v->user->first_name ?? '').' '.($v->user->last_name ?? '')).' submitted owner verification',
                'url' => route('admin.owner-verifications.show', $v),
            ]);

        return $signups->concat($properties)->concat($verifications)
            ->sortByDesc('at')
            ->take($limit)
            ->values();
    }

    protected function ownerStats(User $user): array
    {
        $propertyIds = Property::query()
            ->when($user->isOwner(), fn ($q) => $q->where('owner_id', $user->id))
            ->when($user->isManager(), fn ($q) => $q->whereHas('managers', fn ($q2) => $q2->where('users.id', $user->id)))
            ->pluck('id');

        return [
            'totalProperties' => $propertyIds->count(),
            'totalUnits' => RentalSpace::whereIn('property_id', $propertyIds)->count(),
            'availableUnits' => RentalSpace::whereIn('property_id', $propertyIds)->openForRent()->count(),
            'occupiedUnits' => RentalSpace::whereIn('property_id', $propertyIds)->occupied()->count(),
            'pendingApplications' => RentalApplication::whereIn('property_id', $propertyIds)->where('status', 'pending')->count(),
            'activeTenants' => RentalContract::whereIn('property_id', $propertyIds)->where('status', 'active')->count(),
            'monthlyRevenue' => Payment::whereHas('contract', fn ($q) => $q->whereIn('property_id', $propertyIds))
                ->where('status', 'paid')->whereMonth('payment_date', now()->month)->whereYear('payment_date', now()->year)->sum('amount'),
            'outstandingPayments' => Payment::whereHas('contract', fn ($q) => $q->whereIn('property_id', $propertyIds))
                ->whereIn('status', ['pending', 'overdue'])->sum('amount'),
            'pendingMaintenance' => MaintenanceRequest::whereIn('property_id', $propertyIds)->whereIn('status', ['submitted', 'in_progress'])->count(),
            'totalViews' => Property::whereIn('id', $propertyIds)->sum('views_count'),
        ];
    }

    protected function tenantStats(User $user): array
    {
        $activeContract = RentalContract::query()
            ->where('user_id', $user->id)
            ->where('status', 'active')
            // A soft-deleted property or rental space can leave an old
            // contract row behind; it is no longer a current rental.
            ->whereHas('property', fn ($q) => $q->withoutTrashed())
            ->whereHas('rentalSpace')
            ->with(['property', 'rentalSpace'])
            ->first();

        return [
            'activeContract' => $activeContract,
            'recentApplications' => RentalApplication::where('user_id', $user->id)->with('property')->latest()->take(3)->get(),
            'upcomingPayment' => Payment::where('user_id', $user->id)->where('status', 'pending')->orderBy('due_date')->first(),
            'rentSchedule' => $activeContract ? app(\App\Services\RentScheduleService::class)->forContract($activeContract) : null,
            'upcomingViewings' => $user->viewingRequests()->whereIn('status', ['pending', 'confirmed'])->with('property')->orderBy('preferred_date')->take(3)->get(),
            'favoritesCount' => $user->favorites()->count(),
            'availableProperties' => $this->baseVerifiedAvailableQuery()
                ->when($activeContract, fn ($q) => $q->whereKeyNot($activeContract->property_id))
                ->with(['images' => fn ($q) => $q->where('is_cover', true), 'location'])
                ->withCount('rentalSpaces')
                ->withAvg('reviews', 'rating')
                ->latest()
                ->take(8)
                ->get(),
        ];
    }
}
