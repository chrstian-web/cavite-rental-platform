<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\ViewingRequest\StoreViewingRequestRequest;
use App\Http\Resources\ViewingRequestResource;
use App\Models\Property;
use App\Services\ViewingRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ViewingRequestController extends ApiController
{
    public function __construct(protected ViewingRequestService $viewings)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $viewings = $request->user()->viewingRequests()->with('property')->latest()->paginate($request->integer('per_page', 15));

        return $this->success(ViewingRequestResource::collection($viewings), 'Viewing requests retrieved successfully.');
    }

    public function store(StoreViewingRequestRequest $request, Property $property): JsonResponse
    {
        $viewing = $this->viewings->request($request->user(), $property, null, $request->validated());

        return $this->success(new ViewingRequestResource($viewing->load('property')), 'Viewing request sent.', 201);
    }
}
