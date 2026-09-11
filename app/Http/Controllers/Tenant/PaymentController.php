<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Payment\SubmitPaymentRequest;
use App\Models\Payment;
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
     * Grouped into the buckets requested in the build prompt: Due Soon,
     * Pending Review, Paid, Overdue, Failed. "Overdue" is computed from the
     * due date rather than relying solely on the stored status, so a payment
     * an owner never manually flagged still surfaces once it's late.
     */
    public function index(Request $request): View
    {
        $all = Payment::query()
            ->with(['contract.property'])
            ->where('user_id', $request->user()->id)
            ->orderBy('due_date')
            ->get();

        $overdue = $all->filter(fn (Payment $p) => $p->isPastDue());
        $dueSoon = $all->filter(fn (Payment $p) => $p->status === 'pending' && ! $p->isPastDue());
        $pendingReview = $all->where('status', 'submitted');
        $paid = $all->where('status', 'paid')->sortByDesc('payment_date');
        $failed = $all->where('status', 'failed');

        return view('tenant.payments.index', compact('dueSoon', 'pendingReview', 'paid', 'overdue', 'failed'));
    }

    public function show(Payment $payment): View
    {
        $this->authorize('view', $payment);

        $payment->load(['contract.property', 'reviewer', 'statusHistories.actor']);

        return view('tenant.payments.show', compact('payment'));
    }

    public function store(SubmitPaymentRequest $request, Payment $payment): RedirectResponse
    {
        $this->payments->submit(
            $payment,
            $request->user(),
            $request->validated(),
            $request->file('proof')
        );

        return redirect()
            ->route('tenant.payments.show', $payment)
            ->with('status', 'Payment submitted for review.');
    }

    public function receipt(Payment $payment)
    {
        $this->authorize('view', $payment);

        abort_unless($payment->status === 'paid', 404, 'A receipt is only available for an approved payment.');

        $payment->load(['contract.property', 'contract.tenant', 'reviewer']);

        $pdf = Pdf::loadView('pdf.payment-receipt', ['payment' => $payment]);

        return $pdf->download("receipt-payment-{$payment->id}.pdf");
    }
}
