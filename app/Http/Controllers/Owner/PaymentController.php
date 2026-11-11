<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Http\Requests\Payment\ReviewPaymentRequest;
use App\Http\Requests\Payment\StorePaymentRequest;
use App\Models\Payment;
use App\Models\Property;
use App\Models\RentalContract;
use App\Services\PaymentService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Carbon\Carbon;
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

    /**
     * Kanban-style board: every payment for this owner's/manager's contracts,
     * grouped by what needs attention. Month filter applies to due date, but
     * unpaid past-due payments always show so nothing late is hidden.
     */
    public function board(Request $request): View
    {
        $user = $request->user();

        $month = $request->string('month')->value();
        $month = preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month) ? $month : now()->format('Y-m');
        $start = Carbon::createFromFormat('Y-m-d', $month.'-01')->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $search = trim($request->string('q')->value());
        $propertyId = $request->input('property_id');

        $scope = fn ($q) => $q->when($user->isOwner(), fn ($q2) => $q2->where('owner_id', $user->id))
            ->when($user->isManager(), fn ($q2) => $q2->whereHas('property.managers', fn ($q3) => $q3->where('users.id', $user->id)))
            ->when($propertyId, fn ($q2) => $q2->where('property_id', $propertyId));

        $all = Payment::query()
            ->with(['contract.property', 'contract.rentalSpace', 'tenant'])
            ->whereHas('contract', $scope)
            ->when($search !== '', fn ($q) => $q->whereHas('tenant', fn ($t) => $t
                ->where('first_name', 'like', "%{$search}%")
                ->orWhere('last_name', 'like', "%{$search}%")))
            ->where(fn ($q) => $q
                ->whereBetween('due_date', [$start->toDateString(), $end->toDateString()])
                ->orWhere(fn ($late) => $late->whereIn('status', ['pending', 'overdue'])->where('due_date', '<', today())))
            ->orderBy('due_date')
            ->get();

        $isLate = fn (Payment $p) => $p->status === 'overdue' || $p->isPastDue();

        $columns = [
            'overdue' => $all->filter($isLate)->values(),
            'to_collect' => $all->filter(fn ($p) => $p->status === 'pending' && ! $isLate($p))->values(),
            'review' => $all->where('status', 'submitted')->values(),
            'paid' => $all->where('status', 'paid')->values(),
            'rejected' => $all->where('status', 'failed')->values(),
        ];

        $inMonth = $all->filter(fn ($p) => $p->due_date->betweenIncluded($start, $end) && $p->status !== 'failed');
        $expected = (float) $inMonth->sum('amount');
        $collected = (float) $inMonth->where('status', 'paid')->sum('amount');

        return view('owner.payments.board', [
            'columns' => $columns,
            'month' => $month,
            'search' => $search,
            'propertyId' => $propertyId,
            'stats' => [
                'expected' => $expected,
                'collected' => $collected,
                'rate' => $expected > 0 ? (int) round($collected / $expected * 100) : 0,
            ],
            'properties' => Property::query()
                ->when($user->isOwner(), fn ($q) => $q->where('owner_id', $user->id))
                ->when($user->isManager(), fn ($q) => $q->whereHas('managers', fn ($m) => $m->where('users.id', $user->id)))
                ->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function show(Payment $payment): View|RedirectResponse
    {
        $this->authorize('view', $payment);

        if ($payment->rental_application_id) {
            return redirect()->route('owner.applications.show', $payment->rental_application_id);
        }

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

    public function markReceived(Request $request, Payment $payment): RedirectResponse
    {
        $this->authorize('markReceived', $payment);

        $data = $request->validate([
            'payment_method' => ['required', 'in:gcash'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'payment_date' => ['required', 'date', 'before_or_equal:today'],
        ]);

        $this->payments->markReceived($payment, $request->user(), $data);

        return back()->with('status', $payment->typeLabel().' marked as received.');
    }

    public function receipt(Payment $payment)
    {
        $this->authorize('view', $payment);

        abort_unless($payment->status === 'paid', 404, 'A receipt is only available for an approved payment.');

        $payment->load(['contract.property', 'contract.tenant', 'application.property', 'tenant', 'reviewer']);

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

        $payment = $contract->payments()->create($data);
        if ($payment->status === 'paid') {
            $payment->update([
                'payment_date' => $payment->payment_date ?? today(),
                'paid_at' => now(),
                'receipt_number' => $payment->receipt_number ?: 'RCT-'.now()->format('Ymd').'-'.str_pad((string) $payment->id, 6, '0', STR_PAD_LEFT),
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
            ]);
        }

        return back()->with('status', 'Payment recorded.');
    }
}
