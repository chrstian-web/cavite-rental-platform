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
use App\Services\AdminActivityService;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\View\View;

class DashboardController extends Controller
{
    use FiltersAndSortsProperties;

    /** Approximate municipality centers used when listings have no geocoded coordinates. */
    private const CAVITE_CITY_CENTERS = [
        'alfonso' => [14.1393, 120.8530],
        'amadeo' => [14.1701, 120.9230],
        'bacoor' => [14.4590, 120.9590],
        'carmona' => [14.3132, 121.0570],
        'dasmarinas' => [14.3294, 120.9367],
        'general-emilio-aguinaldo' => [14.1837, 120.7950],
        'gen-emilio-aguinaldo' => [14.1837, 120.7950],
        'general-mariano-alvarez' => [14.3006, 121.0190],
        'general-trias' => [14.3869, 120.8816],
        'imus' => [14.4297, 120.9367],
        'indang' => [14.1953, 120.8760],
        'kawit' => [14.4453, 120.9004],
        'magallanes' => [14.1875, 120.7450],
        'maragondon' => [14.2731, 120.7350],
        'mendez' => [14.1281, 120.9060],
        'naic' => [14.3181, 120.7667],
        'rosario' => [14.4146, 120.8560],
        'silang' => [14.2303, 120.9744],
        'tagaytay' => [14.1153, 120.9621],
        'tanza' => [14.3987, 120.8530],
        'trece-martires' => [14.2814, 120.8678],
    ];

    public function __invoke(Request $request): View
    {
        $user = $request->user();

        return match ($user->role->slug) {
            'super_admin' => view('dashboard.admin', $this->adminStats($user)),
            'owner', 'manager' => view('dashboard.owner', $this->ownerStats($user)),
            default => view('dashboard.tenant', $this->tenantStats($user)),
        };
    }

    protected function adminStats(User $admin): array
    {
        $properties = Property::query();
        $activeOwnerVerifications = OwnerVerification::query()
            ->active()
            ->whereHas('user', fn ($query) => $query->withoutTrashed());
        $owners = User::query()
            ->whereHas('role', fn ($query) => $query->where('slug', 'owner'))
            ->with(['ownerVerifications' => fn ($query) => $query->latest('submitted_at')])
            ->withCount('properties')
            ->orderByDesc('properties_count')
            ->orderBy('first_name')
            ->take(10)
            ->get();

        $owners->each(function (User $owner): void {
            $propertyIds = fn () => Property::query()->where('owner_id', $owner->id)->select('id');
            $owner->setAttribute('units_count', RentalSpace::query()->whereIn('property_id', $propertyIds())->count());
            $owner->setAttribute('occupied_count', RentalSpace::query()
                ->whereIn('property_id', $propertyIds())
                ->where('status', 'occupied')
                ->count());
            $owner->setAttribute('active_tenants_count', RentalContract::query()
                ->whereIn('property_id', $propertyIds())
                ->where('status', 'active')
                ->count());
            $owner->setAttribute('latest_verification', $owner->ownerVerifications->first());
        });

        return [
            'totalProperties' => (clone $properties)->count(),
            'totalUnits' => RentalSpace::count(),
            'availableUnits' => RentalSpace::where('status', 'available')->count(),
            'occupiedUnits' => RentalSpace::where('status', 'occupied')->count(),
            'totalOwners' => User::whereHas('role', fn ($query) => $query->where('slug', 'owner'))->count(),
            'totalManagers' => User::whereHas('role', fn ($query) => $query->where('slug', 'manager'))->count(),
            'totalTenants' => User::whereHas('role', fn ($q) => $q->where('slug', 'tenant'))->count(),
            'deletedUsers' => User::onlyTrashed()->count(),
            'pendingOwnerVerifications' => (clone $activeOwnerVerifications)->where('status', 'needs_review')->count(),
            'pendingMoreDocuments' => (clone $activeOwnerVerifications)->where('status', 'needs_additional_documents')->count(),
            'pendingPropertyVerifications' => Property::where('verification_status', 'pending')->count(),
            'owners' => $owners,
            'recentActivity' => Schema::hasTable('admin_activity_archives')
                ? app(AdminActivityService::class)->recent($admin, 6)
                : collect(),
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
        ];
    }

    protected function ownerStats(User $user): array
    {
        $propertyIds = Property::query()
            ->when($user->isOwner(), fn ($q) => $q->where('owner_id', $user->id))
            ->when($user->isManager(), fn ($q) => $q->whereHas('managers', fn ($q2) => $q2->where('users.id', $user->id)))
            ->pluck('id');

        // Units live under a property, so link straight to the spaces list when there is only one property.
        $unitsUrl = $propertyIds->count() === 1
            ? route('owner.properties.spaces.index', Property::findOrFail($propertyIds->first()))
            : route('owner.properties.index');

        return [
            'unitsUrl' => $unitsUrl,
            'totalProperties' => $propertyIds->count(),
            'totalUnits' => RentalSpace::whereIn('property_id', $propertyIds)->count(),
            'availableUnits' => RentalSpace::whereIn('property_id', $propertyIds)->where('status', 'available')->count(),
            'occupiedUnits' => RentalSpace::whereIn('property_id', $propertyIds)->where('status', 'occupied')->count(),
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
        $activeContract = RentalContract::where('user_id', $user->id)->where('status', 'active')->with(['property', 'rentalSpace'])->first();
        $mapProperties = $this->baseVerifiedAvailableQuery()
            ->with('location')
            ->when($activeContract, fn ($q) => $q->whereKeyNot($activeContract->property_id))
            ->get(['id', 'name', 'slug', 'location_id', 'latitude', 'longitude', 'min_monthly_rent', 'max_monthly_rent']);

        $rentMapLocations = $mapProperties
            ->groupBy('location_id')
            ->map(function ($properties) {
                $location = $properties->first()->location;
                $propertyWithCoordinates = $properties->first(fn ($property) => $property->latitude !== null && $property->longitude !== null);
                $locationCoordinates = $location && $location->latitude !== null && $location->longitude !== null
                    ? [$location->latitude, $location->longitude]
                    : null;
                $cityCenter = self::CAVITE_CITY_CENTERS[Str::slug($location?->city_municipality ?? '')] ?? null;
                $latitude = $propertyWithCoordinates?->latitude ?? $locationCoordinates[0] ?? $cityCenter[0] ?? null;
                $longitude = $propertyWithCoordinates?->longitude ?? $locationCoordinates[1] ?? $cityCenter[1] ?? null;

                return [
                    'city' => $location?->city_municipality ?: 'Cavite',
                    'latitude' => $latitude ? (float) $latitude : null,
                    'longitude' => $longitude ? (float) $longitude : null,
                    'count' => $properties->count(),
                    'min_rent' => $properties->pluck('min_monthly_rent')->filter()->min(),
                    'properties' => $properties->take(4)->map(fn ($property) => [
                        'name' => $property->name,
                        'url' => route('properties.show', $property->slug),
                        'min_rent' => $property->min_monthly_rent ? (float) $property->min_monthly_rent : null,
                    ])->values()->all(),
                ];
            })
            ->filter(fn ($location) => $location['latitude'] !== null && $location['longitude'] !== null)
            ->sortByDesc('count')
            ->values();

        return [
            'activeContract' => $activeContract,
            'recentApplications' => RentalApplication::where('user_id', $user->id)->with('property')->latest()->take(3)->get(),
            'upcomingPayment' => Payment::where('user_id', $user->id)->where('status', 'pending')->orderBy('due_date')->first(),
            'upcomingViewings' => $user->viewingRequests()->whereIn('status', ['pending', 'confirmed'])->with('property')->orderBy('preferred_date')->take(3)->get(),
            'favoritesCount' => $user->favorites()->count(),
            'rentMapLocations' => $rentMapLocations,
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
