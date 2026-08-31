<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Http\Requests\Payment\StorePaymentRequest;
use App\Models\RentalContract;
use Illuminate\Http\RedirectResponse;

class PaymentController extends Controller
{
    public function store(StorePaymentRequest $request, RentalContract $contract): RedirectResponse
    {
        $data = $request->validated();
        $data['user_id'] = $contract->user_id;

        $contract->payments()->create($data);

        return back()->with('status', 'Payment recorded.');
    }
}
