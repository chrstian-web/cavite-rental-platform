<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function index(Request $request): View
    {
        $payments = Payment::query()
            ->with(['contract.property'])
            ->where('user_id', $request->user()->id)
            ->latest('due_date')
            ->paginate(15);

        return view('tenant.payments.index', compact('payments'));
    }
}
