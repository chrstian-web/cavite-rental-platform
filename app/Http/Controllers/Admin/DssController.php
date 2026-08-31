<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DssCriteria;
use App\Models\DssWeight;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DssController extends Controller
{
    public function index(): View
    {
        $criteria = DssCriteria::with(['weights' => fn ($q) => $q->where('is_active', true)])
            ->orderByDesc('id')
            ->get();

        $totalWeight = $criteria->sum(fn ($c) => optional($c->weights->first())->weight_percentage ?? 0);

        return view('admin.dss.index', compact('criteria', 'totalWeight'));
    }

    /**
     * Saves a NEW weight row per criterion and deactivates the old one,
     * rather than editing in place — this keeps historical dss_scores
     * explainable against the weight configuration that was active
     * when they were computed.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'weights' => ['required', 'array'],
            'weights.*' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        DB::transaction(function () use ($validated, $request) {
            foreach ($validated['weights'] as $criteriaId => $newWeight) {
                $criteria = DssCriteria::findOrFail($criteriaId);

                $criteria->weights()->where('is_active', true)->update(['is_active' => false]);

                DssWeight::create([
                    'dss_criteria_id' => $criteria->id,
                    'weight_percentage' => $newWeight,
                    'is_active' => true,
                    'updated_by' => $request->user()->id,
                ]);
            }
        });

        return back()->with('status', 'DSS weights updated. New recommendation requests will use the updated configuration.');
    }
}
