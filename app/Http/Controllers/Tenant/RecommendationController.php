<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dss\RecommendationPreferencesRequest;
use App\Models\Amenity;
use App\Models\Location;
use App\Services\DssScoringService;
use Illuminate\View\View;

class RecommendationController extends Controller
{
    public function __construct(protected DssScoringService $dss)
    {
    }

    public function create(): View
    {
        return view('tenant.recommendations.create', [
            'locations' => Location::where('is_active', true)->orderBy('city_municipality')->get(),
            'amenities' => Amenity::orderBy('name')->get(),
        ]);
    }

    public function store(RecommendationPreferencesRequest $request): View
    {
        $preferences = $request->validated();

        $results = $this->dss->recommend($request->user(), $preferences)->take(20);

        return view('tenant.recommendations.results', compact('results', 'preferences'));
    }
}
