<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Dss\RecommendationPreferencesRequest;
use App\Services\DssScoringService;
use Illuminate\Http\JsonResponse;

class RecommendationController extends ApiController
{
    public function __construct(protected DssScoringService $dss)
    {
    }

    public function store(RecommendationPreferencesRequest $request): JsonResponse
    {
        $results = $this->dss->recommend($request->user(), $request->validated())->take(20);

        $payload = $results->map(fn ($r) => [
            'property_id' => $r['property']->id,
            'property_name' => $r['property']->name,
            'property_slug' => $r['property']->slug,
            'rental_space_id' => $r['space']->id,
            'space_number' => $r['space']->space_number,
            'monthly_rent' => (float) $r['space']->monthly_rent,
            'score' => $r['score'],
            'breakdown' => $r['breakdown'],
            'reasons' => $r['reasons'],
        ])->values();

        return $this->success($payload, 'Recommendations calculated successfully.');
    }
}
