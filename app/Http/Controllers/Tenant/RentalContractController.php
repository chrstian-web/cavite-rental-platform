<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\RentalContract;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Barryvdh\DomPDF\Facade\Pdf;

class RentalContractController extends Controller
{
    public function index(Request $request): View
    {
        $contracts = $request->user()
            ->rentalContracts()
            ->with(['property', 'rentalSpace'])
            ->latest()
            ->paginate(10);

        return view('tenant.contracts.index', compact('contracts'));
    }

    public function show(RentalContract $contract): View
    {
        $this->authorize('view', $contract);

        $contract->load(['property', 'rentalSpace', 'payments', 'review']);

        return view('tenant.contracts.show', compact('contract'));
    }

    public function downloadPdf(RentalContract $contract)
    {
        $this->authorize('view', $contract);

        $pdf = Pdf::loadView('pdf.rental-contract', ['contract' => $contract->load(['tenant', 'owner', 'property', 'rentalSpace'])]);

        return $pdf->download("rental-contract-{$contract->id}.pdf");
    }
}
