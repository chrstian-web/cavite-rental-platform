<?php

namespace App\Http\Controllers;

use App\Http\Concerns\FiltersAndSortsProperties;
use App\Models\MaintenanceRequest;
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
        $properties = Property::query();

        return [
            'totalProperties' => (clone $properties)->count(),
            // Only units whose property still exists (soft-deleted properties are ignored).
            'totalUnits' => RentalSpace::whereHas('property')->count(),
            'availableUnits' => RentalSpace::whereHas('property')->openForRent()->count(),
            'occupiedUnits' => RentalSpace::whereHas('property')->occupied()->count(),
            'totalTenants' => User::whereHas('role', fn ($q) => $q->where('slug', 'tenant'))->count(),
            'pendingApplications' => RentalApplication::where('status', 'pending')->count(),
            'monthlyRevenue' => Payment::where('status', 'paid')->whereMonth('payment_date', now()->month)->whereYear('payment_date', now()->year)->sum('amount'),
            'overduePayments' => Payment::where('status', 'overdue')->count(),
            'pendingMaintenance' => MaintenanceRequest::whereIn('status', ['submitted', 'in_progress'])->count(),
            'mostViewed' => Property::orderByDesc('views_count')->take(5)->get(['id', 'name', 'views_count']),
            'mostFavorited' => Property::withCount('favorites')->orderByDesc('favorites_count')->take(5)->get(['id', 'name']),
            'popularLocations' => Property::selectRaw('location_id, count(*) as total')
                ->with('location:id,city_municipality')
                ->groupBy('location_id')
                ->orderByDesc('total')
                ->take(5)
                ->get(),
            'typeDistribution' => Property::selectRaw('property_type, count(*) as total')->groupBy('property_type')->get(),
            'totalOwners' => User::whereHas('role', fn ($q) => $q->where('slug', 'owner'))->count(),
            'owners' => $this->ownerSummaries(),
        ];
    }

    /**
     * Every property owner (most properties first) with their portfolio numbers,
     * for the admin dashboard list.
     */
    protected function ownerSummaries(int $limit = 10)
    {
        $owners = User::query()
            ->whereHas('role', fn ($q) => $q->where('slug', 'owner'))
            ->withCount('properties')
            ->orderByDesc('properties_count')
            ->orderBy('first_name')
            ->take($limit)
            ->get();

        return $owners->each(function (User $owner) {
            $propertyIds = $owner->properties()->pluck('id');

            $owner->units_count = RentalSpace::whereIn('property_id', $propertyIds)->count();
            $owner->occupied_count = RentalSpace::whereIn('property_id', $propertyIds)->occupied()->count();
            $owner->active_tenants_count = RentalContract::whereIn('property_id', $propertyIds)->where('status', 'active')->count();
        });
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
