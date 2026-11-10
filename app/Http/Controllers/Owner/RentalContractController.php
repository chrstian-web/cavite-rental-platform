<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Http\Requests\RentalContract\StoreRentalContractRequest;
use App\Models\RentalApplication;
use App\Models\RentalContract;
use App\Notifications\MoveInMessageNotification;
use App\Services\RentalContractService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Barryvdh\DomPDF\Facade\Pdf;

class RentalContractController extends Controller
{
    public function __construct(protected RentalContractService $contracts)
    {
    }

    public function index(Request $request): View
    {
        $user = $request->user();

        $contracts = RentalContract::query()
            ->with(['property', 'rentalSpace', 'tenant'])
            ->when($user->isManager(), fn ($q) => $q->whereHas('property.managers', fn ($q2) => $q2->where('users.id', $user->id)))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('owner.contracts.index', compact('contracts'));
    }

    public function create(RentalApplication $application): View
    {
        $this->authorize('update', $application->property);

        abort_unless($application->status === 'approved', 422, 'Only approved applications can become a contract.');
        abort_unless($application->hasPaidReservationFee(), 422, 'The reservation fee must be paid before creating a rental contract.');

        $application->load(['rentalSpace', 'property']);

        return view('owner.contracts.create', compact('application'));
    }

    public function store(StoreRentalContractRequest $request, RentalApplication $application): RedirectResponse
    {
        $contract = $this->contracts->createFromApplication($application, $request->validated());

        return redirect()
            ->route('owner.contracts.show', $contract)
            ->with('status', 'Contract created as a draft. The tenant must pay the down payment before you can activate it.');
    }

    public function show(RentalContract $contract): View
    {
        $this->authorize('view', $contract);

        // Older drafts may predate the down payment requirement; this only creates what is missing.
        if ($contract->status === 'draft') {
            $this->contracts->ensureDownPayments($contract);
        }

        $contract->load(['tenant', 'property', 'rentalSpace', 'payments']);

        return view('owner.contracts.show', compact('contract'));
    }

    public function activate(RentalContract $contract): RedirectResponse
    {
        $this->authorize('view', $contract);
        abort_unless($contract->status === 'draft', 422, 'Only a draft contract can be activated.');

        if (! $contract->hasSettledDownPayments()) {
            return back()->withErrors(['activate' => $contract->downPaymentBlockedMessage()]);
        }

        $this->contracts->activate($contract);

        return back()->with('status', 'Contract activated. The unit is now marked occupied.');
    }

    /**
     * Final remarks from the owner, available once every payment is settled and before the lease ends.
     */
    public function sendMoveInMessage(Request $request, RentalContract $contract): RedirectResponse
    {
        $this->authorize('view', $contract);

        abort_unless(in_array($contract->status, ['draft', 'active'], true), 422, 'A message can only be sent for a draft or active contract.');
        abort_unless($contract->application?->hasPaidReservationFee() ?? true, 422, 'The reservation fee must be paid first.');
        abort_unless($contract->hasSettledDownPayments(), 422, 'The message can be sent once all payments are made.');

        $data = $request->validate([
            'move_in_message' => ['required', 'string', 'max:2000'],
        ]);

        $contract->update([
            'move_in_message' => $data['move_in_message'],
            'move_in_message_sent_at' => now(),
        ]);

        $contract->tenant->notify(new MoveInMessageNotification($contract->fresh(['property'])));

        return back()->with('status', 'Your message was sent to the renter.');
    }

    public function terminate(Request $request, RentalContract $contract): RedirectResponse
    {
        $this->authorize('view', $contract);

        if ($contract->status !== 'active') {
            return back()->withErrors(['terminate' => 'Only an active contract can be terminated.']);
        }

        $this->contracts->end($contract, 'terminated');

        return back()->with('status', 'Contract terminated.');
    }

    public function downloadPdf(RentalContract $contract)
    {
        $this->authorize('view', $contract);

        $pdf = Pdf::loadView('pdf.rental-contract', ['contract' => $contract->load(['tenant', 'owner', 'property', 'rentalSpace'])]);

        return $pdf->download("rental-contract-{$contract->id}.pdf");
    }
}
