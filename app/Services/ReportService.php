<?php

namespace App\Services;

use App\Models\MaintenanceRequest;
use App\Models\Payment;
use App\Models\Property;
use App\Models\RentalApplication;
use App\Models\RentalContract;
use App\Models\SearchLog;
use Carbon\Carbon;

class ReportService
{
    /**
     * Every report returns the same shape: ['title' => ..., 'headers' => [...],
     * 'rows' => [[...], [...]]] — one generic CSV/PDF export path handles all
     * eleven report types instead of one bespoke export per report.
     */
    public const REPORTS = [
        'property_inventory' => 'Property Inventory',
        'occupancy_rate' => 'Occupancy Rate',
        'rental_revenue' => 'Rental Revenue',
        'active_tenants' => 'Active Tenants',
        'rental_applications' => 'Rental Applications',
        'property_popularity' => 'Property Popularity',
        'most_searched_locations' => 'Most Searched Locations',
        'property_type_distribution' => 'Property Type Distribution',
        'payment_status' => 'Payment Status',
        'maintenance_requests' => 'Maintenance Requests',
        'dss_recommendations' => 'DSS Recommendations',
    ];

    public function generate(string $type, array $filters): array
    {
        abort_unless(array_key_exists($type, self::REPORTS), 404);

        return $this->{'report'.str_replace('_', '', ucwords($type, '_'))}($filters);
    }

    protected function dateRange(array $filters, string $column, $query)
    {
        return $query
            ->when($filters['date_from'] ?? null, fn ($q) => $q->whereDate($column, '>=', $filters['date_from']))
            ->when($filters['date_to'] ?? null, fn ($q) => $q->whereDate($column, '<=', $filters['date_to']));
    }

    protected function propertyFilters(array $filters, $query)
    {
        return $query
            ->when($filters['property_id'] ?? null, fn ($q) => $q->where('property_id', $filters['property_id']))
            ->when($filters['property_type'] ?? null, fn ($q) => $q->whereHas('property', fn ($q2) => $q2->where('property_type', $filters['property_type'])));
    }

    protected function reportPropertyInventory(array $filters): array
    {
        $properties = Property::query()
            ->with(['owner', 'location'])
            ->withCount('rentalSpaces')
            ->when($filters['property_id'] ?? null, fn ($q) => $q->where('id', $filters['property_id']))
            ->when($filters['property_type'] ?? null, fn ($q) => $q->where('property_type', $filters['property_type']))
            ->when($filters['date_from'] ?? null, fn ($q) => $q->whereDate('created_at', '>=', $filters['date_from']))
            ->when($filters['date_to'] ?? null, fn ($q) => $q->whereDate('created_at', '<=', $filters['date_to']))
            ->get();

        return [
            'title' => 'Property Inventory',
            'headers' => ['ID', 'Name', 'Type', 'Location', 'Owner', 'Units', 'Verification', 'Availability', 'Listed On'],
            'rows' => $properties->map(fn ($p) => [
                $p->id, $p->name, str($p->property_type)->replace('_', ' ')->title(),
                $p->location->city_municipality, $p->owner->first_name.' '.$p->owner->last_name,
                $p->rental_spaces_count, str($p->verification_status)->title(),
                str($p->availability_status)->replace('_', ' ')->title(), $p->created_at->format('Y-m-d'),
            ])->all(),
        ];
    }

    protected function reportOccupancyRate(array $filters): array
    {
        $properties = Property::query()
            ->with('rentalSpaces')
            ->when($filters['property_id'] ?? null, fn ($q) => $q->where('id', $filters['property_id']))
            ->when($filters['property_type'] ?? null, fn ($q) => $q->where('property_type', $filters['property_type']))
            ->get();

        return [
            'title' => 'Occupancy Rate',
            'headers' => ['Property', 'Total Units', 'Occupied', 'Available', 'Occupancy Rate'],
            'rows' => $properties->map(function ($p) {
                $total = $p->rentalSpaces->count();
                $occupied = $p->rentalSpaces->where('status', 'occupied')->count();
                $rate = $total > 0 ? round(($occupied / $total) * 100, 1) : 0;

                return [$p->name, $total, $occupied, $total - $occupied, $rate.'%'];
            })->all(),
        ];
    }

    protected function reportRentalRevenue(array $filters): array
    {
        $payments = $this->dateRange(
            $filters, 'payment_date',
            Payment::query()->with('contract.property')->where('status', 'paid')
        )
            ->when($filters['property_id'] ?? null, fn ($q) => $q->whereHas('contract', fn ($q2) => $q2->where('property_id', $filters['property_id'])))
            ->when($filters['property_type'] ?? null, fn ($q) => $q->whereHas('contract.property', fn ($q2) => $q2->where('property_type', $filters['property_type'])))
            ->get();

        $total = $payments->sum('amount');

        return [
            'title' => 'Rental Revenue',
            'headers' => ['Property', 'Amount', 'Payment Date', 'Method', 'Reference #'],
            'rows' => $payments->map(fn ($p) => [
                $p->contract->property->name, number_format($p->amount, 2),
                $p->payment_date?->format('Y-m-d'), $p->payment_method ?? '—', $p->reference_number ?? '—',
            ])->all(),
            'summary' => 'Total revenue: ₱'.number_format($total, 2).' across '.$payments->count().' payment(s).',
        ];
    }

    protected function reportActiveTenants(array $filters): array
    {
        $contracts = RentalContract::query()
            ->with(['tenant', 'property', 'rentalSpace'])
            ->where('status', 'active')
            ->when($filters['property_id'] ?? null, fn ($q) => $q->where('property_id', $filters['property_id']))
            ->when($filters['property_type'] ?? null, fn ($q) => $q->whereHas('property', fn ($q2) => $q2->where('property_type', $filters['property_type'])))
            ->get();

        return [
            'title' => 'Active Tenants',
            'headers' => ['Tenant', 'Email', 'Property', 'Unit', 'Monthly Rent', 'Start Date', 'End Date'],
            'rows' => $contracts->map(fn ($c) => [
                $c->tenant->first_name.' '.$c->tenant->last_name, $c->tenant->email,
                $c->property->name, $c->rentalSpace->space_number,
                number_format($c->monthly_rent, 2), $c->start_date->format('Y-m-d'), $c->end_date->format('Y-m-d'),
            ])->all(),
        ];
    }

    protected function reportRentalApplications(array $filters): array
    {
        $applications = $this->dateRange($filters, 'created_at', RentalApplication::query()->with(['user', 'property']))
            ->when($filters['property_id'] ?? null, fn ($q) => $q->where('property_id', $filters['property_id']))
            ->when($filters['property_type'] ?? null, fn ($q) => $q->whereHas('property', fn ($q2) => $q2->where('property_type', $filters['property_type'])))
            ->get();

        return [
            'title' => 'Rental Applications',
            'headers' => ['Applicant', 'Property', 'Status', 'Desired Move-in', 'Submitted On'],
            'rows' => $applications->map(fn ($a) => [
                $a->user->first_name.' '.$a->user->last_name, $a->property->name,
                str($a->status)->replace('_', ' ')->title(),
                $a->desired_move_in_date->format('Y-m-d'), $a->created_at->format('Y-m-d'),
            ])->all(),
            'summary' => 'Total: '.$applications->count().' | Pending: '.$applications->where('status', 'pending')->count()
                .' | Approved: '.$applications->where('status', 'approved')->count()
                .' | Rejected: '.$applications->where('status', 'rejected')->count(),
        ];
    }

    protected function reportPropertyPopularity(array $filters): array
    {
        $properties = Property::query()
            ->withCount('favorites')
            ->when($filters['property_type'] ?? null, fn ($q) => $q->where('property_type', $filters['property_type']))
            ->orderByDesc('views_count')
            ->take(50)
            ->get();

        return [
            'title' => 'Property Popularity',
            'headers' => ['Property', 'Views', 'Favorites'],
            'rows' => $properties->map(fn ($p) => [$p->name, $p->views_count, $p->favorites_count])->all(),
        ];
    }

    protected function reportMostSearchedLocations(array $filters): array
    {
        $logs = $this->dateRange($filters, 'created_at', SearchLog::query())
            ->whereNotNull('location_id')
            ->selectRaw('location_id, count(*) as total')
            ->with('location:id,city_municipality')
            ->groupBy('location_id')
            ->orderByDesc('total')
            ->get();

        return [
            'title' => 'Most Searched Locations',
            'headers' => ['Location', 'Search Count'],
            'rows' => $logs->map(fn ($row) => [$row->location->city_municipality ?? '—', $row->total])->all(),
        ];
    }

    protected function reportPropertyTypeDistribution(array $filters): array
    {
        $rows = Property::query()
            ->selectRaw('property_type, count(*) as total')
            ->groupBy('property_type')
            ->get();

        $grandTotal = $rows->sum('total') ?: 1;

        return [
            'title' => 'Property Type Distribution',
            'headers' => ['Property Type', 'Count', 'Percentage'],
            'rows' => $rows->map(fn ($r) => [
                str($r->property_type)->replace('_', ' ')->title(), $r->total,
                round(($r->total / $grandTotal) * 100, 1).'%',
            ])->all(),
        ];
    }

    protected function reportPaymentStatus(array $filters): array
    {
        $payments = $this->dateRange($filters, 'due_date', Payment::query()->with('contract.property'))
            ->when($filters['property_id'] ?? null, fn ($q) => $q->whereHas('contract', fn ($q2) => $q2->where('property_id', $filters['property_id'])))
            ->get();

        $byStatus = $payments->groupBy('status')->map(fn ($group) => [
            'count' => $group->count(),
            'total' => $group->sum('amount'),
        ]);

        return [
            'title' => 'Payment Status',
            'headers' => ['Property', 'Amount', 'Due Date', 'Status'],
            'rows' => $payments->map(fn ($p) => [
                $p->contract->property->name, number_format($p->amount, 2), $p->due_date->format('Y-m-d'), str($p->status)->title(),
            ])->all(),
            'summary' => collect(['pending', 'paid', 'overdue', 'failed'])
                ->map(fn ($s) => ucfirst($s).': '.($byStatus[$s]['count'] ?? 0).' (₱'.number_format($byStatus[$s]['total'] ?? 0, 2).')')
                ->implode(' | '),
        ];
    }

    protected function reportMaintenanceRequests(array $filters): array
    {
        $requests = $this->dateRange($filters, 'created_at', MaintenanceRequest::query()->with(['tenant', 'property']))
            ->when($filters['property_id'] ?? null, fn ($q) => $q->where('property_id', $filters['property_id']))
            ->when($filters['property_type'] ?? null, fn ($q) => $q->whereHas('property', fn ($q2) => $q2->where('property_type', $filters['property_type'])))
            ->get();

        return [
            'title' => 'Maintenance Requests',
            'headers' => ['Tenant', 'Property', 'Category', 'Priority', 'Status', 'Submitted On'],
            'rows' => $requests->map(fn ($r) => [
                $r->tenant->first_name.' '.$r->tenant->last_name, $r->property->name,
                str($r->category)->replace('_', ' ')->title(), str($r->priority)->title(),
                str($r->status)->replace('_', ' ')->title(), $r->created_at->format('Y-m-d'),
            ])->all(),
        ];
    }

    protected function reportDssRecommendations(array $filters): array
    {
        $scores = $this->dateRange($filters, 'created_at', \App\Models\DssScore::query()->with('property'))
            ->when($filters['property_id'] ?? null, fn ($q) => $q->where('property_id', $filters['property_id']))
            ->get()
            ->groupBy('property_id');

        $rows = $scores->map(function ($group) {
            $property = $group->first()->property;

            return [
                $property->name,
                $group->count(),
                round($group->avg('total_score'), 1),
                round($group->max('total_score'), 1),
            ];
        })->sortByDesc(fn ($r) => $r[2])->values();

        return [
            'title' => 'DSS Recommendations',
            'headers' => ['Property', 'Times Recommended', 'Average Score', 'Highest Score'],
            'rows' => $rows->all(),
        ];
    }
}
