<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\RentalContractResource;
use App\Models\RentalContract;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RentalContractController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $contracts = $request->user()->rentalContracts()->with(['property', 'rentalSpace'])->latest()->paginate($request->integer('per_page', 15));

        return $this->success(RentalContractResource::collection($contracts), 'Contracts retrieved successfully.');
    }

    public function show(Request $request, RentalContract $contract): JsonResponse
    {
        if (! $request->user()->can('view', $contract)) {
            return $this->error('Forbidden.', 403);
        }

        return $this->success(new RentalContractResource($contract->load(['property', 'rentalSpace'])));
    }
}
