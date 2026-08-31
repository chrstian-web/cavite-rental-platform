@extends('layouts.app')

@section('title', 'DSS Configuration')

@section('content')
    <h1 class="text-xl font-semibold text-slate-900 mb-1">Decision Support System Configuration</h1>
    <p class="text-sm text-slate-500 mb-6">
        Adjust how much each criterion contributes to a property's recommendation score.
        Changes apply to new recommendation requests immediately — past results stay tied
        to the weights that were active when they were calculated.
    </p>

    <div class="bg-white border border-slate-200 rounded-xl p-6 max-w-2xl">
        <div class="flex items-center justify-between mb-4">
            <p class="text-sm font-medium text-slate-700">Criteria weights</p>
            <span class="text-sm {{ $totalWeight == 100 ? 'text-green-600' : 'text-amber-600' }}">
                Total: {{ $totalWeight }}% {{ $totalWeight == 100 ? '✓' : '(should total 100%)' }}
            </span>
        </div>

        <form method="POST" action="{{ route('admin.dss.update') }}" class="space-y-4">
            @csrf @method('PATCH')

            @foreach ($criteria as $criterion)
                @php $currentWeight = optional($criterion->weights->first())->weight_percentage ?? 0; @endphp
                <div class="flex items-center justify-between gap-4 border-b border-slate-100 pb-3">
                    <div>
                        <p class="text-sm font-medium text-slate-900">{{ $criterion->label }}</p>
                        <p class="text-xs text-slate-400">{{ $criterion->description }}</p>
                    </div>
                    <div class="flex items-center gap-1 shrink-0">
                        <input type="number" step="0.01" min="0" max="100" name="weights[{{ $criterion->id }}]"
                            value="{{ old('weights.'.$criterion->id, $currentWeight) }}"
                            class="w-20 rounded-lg border-slate-300 text-sm text-right">
                        <span class="text-sm text-slate-500">%</span>
                    </div>
                </div>
            @endforeach

            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg px-5 py-2.5">
                Save weights
            </button>
        </form>
    </div>
@endsection
