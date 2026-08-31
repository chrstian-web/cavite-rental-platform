<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\RentalApplication\StoreRentalApplicationRequest;
use App\Http\Resources\RentalApplicationResource;
use App\Models\RentalSpace;
use App\Services\RentalApplicationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RentalApplicationController extends ApiController
{
    public function __construct(protected RentalApplicationService $applications)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $applications = $request->user()
            ->rentalApplications()
            ->with(['property', 'rentalSpace'])
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return $this->success(RentalApplicationResource::collection($applications), 'Applications retrieved successfully.');
    }

    public function store(StoreRentalApplicationRequest $request, RentalSpace $space): JsonResponse
    {
        $application = $this->applications->submit(
            $request->user(),
            $space,
            $request->safe()->except('documents'),
            $request->file('documents', [])
        );

        return $this->success(
            new RentalApplicationResource($application->load(['property', 'rentalSpace'])),
            'Application submitted successfully.',
            201
        );
    }

    public function show(Request $request, \App\Models\RentalApplication $application): JsonResponse
    {
        if (! $request->user()->can('view', $application)) {
            return $this->error('Forbidden.', 403);
        }

        return $this->success(new RentalApplicationResource($application->load(['property', 'rentalSpace'])));
    }
}
