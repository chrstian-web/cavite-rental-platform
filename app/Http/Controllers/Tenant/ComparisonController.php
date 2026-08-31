<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Property;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ComparisonController extends Controller
{
    public function show(Request $request): View
    {
        $ids = collect(explode(',', (string) $request->query('ids')))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->take(4) // cap at 4 per the brief
            ->all();

        $properties = Property::query()
            ->with(['images' => fn ($q) => $q->where('is_cover', true), 'amenities', 'location', 'rentalSpaces'])
            ->withAvg('reviews', 'rating')
            ->whereIn('id', $ids)
            ->get()
            ->sortBy(fn ($p) => array_search($p->id, $ids))
            ->values();

        return view('tenant.compare.show', compact('properties'));
    }
}
