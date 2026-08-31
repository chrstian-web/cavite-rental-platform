<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\MaintenanceRequest\StoreMaintenanceRequestRequest;
use App\Http\Resources\MaintenanceRequestResource;
use App\Models\RentalContract;
use App\Services\MaintenanceRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MaintenanceRequestController extends ApiController
{
    public function __construct(protected MaintenanceRequestService $maintenance)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $requests = \App\Models\MaintenanceRequest::with(['property', 'images'])
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return $this->success(MaintenanceRequestResource::collection($requests), 'Maintenance requests retrieved successfully.');
    }

    public function store(StoreMaintenanceRequestRequest $request, RentalContract $contract): JsonResponse
    {
        if (! $request->user()->can('view', $contract) || $contract->status !== 'active') {
            return $this->error('You can only submit maintenance requests for your own active tenancy.', 422);
        }

        $maintenanceRequest = $this->maintenance->submit(
            $request->user(),
            $contract->property,
            $contract->rentalSpace,
            $request->safe()->except('images'),
            $request->file('images', [])
        );

        return $this->success(new MaintenanceRequestResource($maintenanceRequest->load(['property', 'images'])), 'Maintenance request submitted.', 201);
    }
}
