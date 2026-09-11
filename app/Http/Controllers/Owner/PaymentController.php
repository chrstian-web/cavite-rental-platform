<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Http\Requests\Payment\ReviewPaymentRequest;
use App\Http\Requests\Payment\StorePaymentRequest;
use App\Models\Payment;
use App\Models\RentalContract;
use App\Services\PaymentService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function __construct(protected PaymentService $payments)
    {
    }

    /**
     * Review queue across every contract this owner/manager is responsible
     * for. Defaults to what actually needs attention (submitted), but can be
     * filtered to see the full payment history too.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $status = $request->string('status')->value() ?: 'submitted';

        $payments = Payment::query()
            ->with(['contract.property', 'tenant'])
            ->whereHas('contract', function ($q) use ($user) {
                $q->when($user->isOwner(), fn ($q2) => $q2->where('owner_id', $user->id))
                  ->when($user->isManager(), fn ($q2) => $q2->whereHas('property.managers', fn ($q3) => $q3->where('users.id', $user->id)));
            })
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->orderByDesc('submitted_at')
            ->orderBy('due_date')
            ->paginate(15)
            ->withQueryString();

        $pendingReviewCount = Payment::query()
            ->whereHas('contract', function ($q) use ($user) {
                $q->when($user->isOwner(), fn ($q2) => $q2->where('owner_id', $user->id))
                  ->when($user->isManager(), fn ($q2) => $q2->whereHas('property.managers', fn ($q3) => $q3->where('users.id', $user->id)));
            })
            ->where('status', 'submitted')
            ->count();

        return view('owner.payments.index', compact('payments', 'status', 'pendingReviewCount'));
    }

    public function show(Payment $payment): View
    {
        $this->authorize('view', $payment);

        $payment->load(['contract.property', 'tenant', 'reviewer', 'statusHistories.actor']);

        return view('owner.payments.show', compact('payment'));
    }

    public function review(ReviewPaymentRequest $request, Payment $payment): RedirectResponse
    {
        $reviewer = $request->user();
        $reason = $request->input('reason');

        match ($request->string('action')->value()) {
            'approve' => $this->payments->approve($payment, $reviewer),
            'reject' => $this->payments->reject($payment, $reviewer, $reason),
            'request_correction' => $this->payments->requestCorrection($payment, $reviewer, $reason),
        };

        return redirect()
            ->route('owner.payments.index')
            ->with('status', 'Payment reviewed.');
    }

    public function receipt(Payment $payment)
    {
        $this->authorize('view', $payment);

        abort_unless($payment->status === 'paid', 404, 'A receipt is only available for an approved payment.');

        $payment->load(['contract.property', 'contract.tenant', 'reviewer']);

        $pdf = Pdf::loadView('pdf.payment-receipt', ['payment' => $payment]);

        return $pdf->download("receipt-payment-{$payment->id}.pdf");
    }

    /**
     * Existing manual recording flow (owner enters a fully-formed payment
     * record directly, e.g. for cash collected in person). Unchanged.
     */
    public function store(StorePaymentRequest $request, RentalContract $contract): RedirectResponse
    {
        $data = $request->validated();
        $data['user_id'] = $contract->user_id;

        $contract->payments()->create($data);

        return back()->with('status', 'Payment recorded.');
    }
}
