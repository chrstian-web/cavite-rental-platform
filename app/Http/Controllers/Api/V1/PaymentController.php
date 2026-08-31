<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\PaymentResource;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $payments = Payment::query()
            ->with('contract.property')
            ->where('user_id', $request->user()->id)
            ->latest('due_date')
            ->paginate($request->integer('per_page', 15));

        return $this->success(PaymentResource::collection($payments), 'Payments retrieved successfully.');
    }
}
