<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\RentalContract;
use App\Services\RentScheduleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RentInstallmentController extends Controller
{
    public function __construct(protected RentScheduleService $schedule)
    {
    }

    /**
     * "Pay now" on a schedule row: makes sure there is a pending monthly-rent
     * payment for that month (never a duplicate), then opens it so the tenant
     * can pay online or submit proof.
     */
    public function start(Request $request, RentalContract $contract, string $month): RedirectResponse
    {
        abort_unless($contract->user_id === $request->user()->id && $contract->status === 'active', 403);

        $installment = $this->schedule->forContract($contract)['rows']->firstWhere('month', $month);

        abort_if($installment === null, 404);
        abort_unless(in_array($installment['action'], ['start', 'view'], true), 403, 'This month is not open for payment yet.');

        $payment = $installment['payment'] ?? $contract->payments()->firstOrCreate(
            [
                'payment_type' => Payment::TYPE_MONTHLY_RENT,
                'due_date' => $installment['due_date']->toDateString(),
            ],
            [
                'user_id' => $contract->user_id,
                'amount' => $contract->monthly_rent,
                'currency' => 'PHP',
                'status' => 'pending',
                'payment_source' => 'manual',
                'notes' => 'Monthly rent',
            ]
        );

        return redirect()->route('tenant.payments.show', $payment);
    }
}
