<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Review\StoreReviewRequest;
use App\Models\RentalContract;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ReviewController extends Controller
{
    public function create(RentalContract $contract): View
    {
        $this->authorize('view', $contract);
        abort_unless(
            in_array($contract->status, ['expired', 'terminated'], true) && ! $contract->review()->exists(),
            422,
            'You can only review a completed tenancy once.'
        );

        return view('tenant.contracts.review', compact('contract'));
    }

    public function store(StoreReviewRequest $request, RentalContract $contract): RedirectResponse
    {
        $contract->review()->create([
            ...$request->validated(),
            'user_id' => $request->user()->id,
            'property_id' => $contract->property_id,
        ]);

        return redirect()
            ->route('tenant.contracts.show', $contract)
            ->with('status', 'Thanks for your review!');
    }
}
