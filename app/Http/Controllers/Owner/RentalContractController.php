<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Http\Requests\RentalContract\StoreRentalContractRequest;
use App\Models\RentalApplication;
use App\Models\RentalContract;
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
            ->when($user->isOwner(), fn ($q) => $q->where('owner_id', $user->id))
            ->when($user->isManager(), fn ($q) => $q->whereHas('property.managers', fn ($q2) => $q2->where('users.id', $user->id)))
            ->latest()
            ->paginate(10);

        return view('owner.contracts.index', compact('contracts'));
    }

    public function create(RentalApplication $application): View
    {
        $this->authorize('update', $application->property);

        abort_unless($application->status === 'approved', 422, 'Only approved applications can become a contract.');

        $application->load(['rentalSpace', 'property']);

        return view('owner.contracts.create', compact('application'));
    }

    public function store(StoreRentalContractRequest $request, RentalApplication $application): RedirectResponse
    {
        $contract = $this->contracts->createFromApplication($application, $request->validated());

        return redirect()
            ->route('owner.contracts.show', $contract)
            ->with('status', 'Contract created as a draft. Activate it once both parties are ready.');
    }

    public function show(RentalContract $contract): View
    {
        $this->authorize('view', $contract);

        $contract->load(['tenant', 'property', 'rentalSpace', 'payments']);

        return view('owner.contracts.show', compact('contract'));
    }

    public function activate(RentalContract $contract): RedirectResponse
    {
        $this->authorize('view', $contract);
        abort_unless($contract->status === 'draft', 422, 'Only a draft contract can be activated.');

        $this->contracts->activate($contract);

        return back()->with('status', 'Contract activated. The unit is now marked occupied.');
    }

    public function terminate(Request $request, RentalContract $contract): RedirectResponse
    {
        $this->authorize('view', $contract);

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
