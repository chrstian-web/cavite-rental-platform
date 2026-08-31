@extends('layouts.app')

@section('title', 'Recommended for You')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl font-semibold text-slate-900">Your Recommendations</h1>
        <a href="{{ route('tenant.recommendations.create') }}" class="text-sm text-blue-600 hover:underline">Adjust preferences</a>
    </div>

    @if ($results->isEmpty())
        <div class="bg-white border border-slate-200 rounded-xl p-10 text-center text-slate-500">
            No available, verified units to score right now. Check back soon.
        </div>
    @else
        <div class="space-y-4">
            @foreach ($results as $result)
                @php $property = $result['property']; $space = $result['space']; @endphp
                <div class="bg-white border border-slate-200 rounded-xl p-5">
                    <div class="flex flex-col md:flex-row md:items-start justify-between gap-4">
                        <div class="flex-1">
                            <div class="flex items-center gap-3">
                                <a href="{{ route('properties.show', $property->slug) }}" class="font-semibold text-slate-900 hover:underline">
                                    {{ $property->name }}
                                </a>
                                <span class="text-xs bg-slate-100 text-slate-600 rounded-full px-2 py-0.5">
                                    {{ str($property->property_type)->replace('_',' ')->title() }}
                                </span>
                            </div>
                            <p class="text-sm text-slate-500">{{ $property->location->city_municipality }}, Cavite &middot; {{ $space->space_number }}</p>
                            <p class="text-sm text-slate-900 font-medium mt-1">₱{{ number_format($space->monthly_rent) }}/month</p>

                            <ul class="mt-3 space-y-1">
                                @foreach ($result['reasons'] as $reason)
                                    <li class="text-xs text-slate-600 flex items-start gap-1.5">
                                        <span class="text-green-600">✓</span> {{ $reason }}
                                    </li>
                                @endforeach
                            </ul>
                        </div>

                        <div class="text-center shrink-0">
                            <div class="w-20 h-20 rounded-full border-4
                                {{ $result['score'] >= 80 ? 'border-green-500' : ($result['score'] >= 50 ? 'border-amber-500' : 'border-slate-300') }}
                                flex items-center justify-center">
                                <span class="text-lg font-bold text-slate-900">{{ round($result['score']) }}%</span>
                            </div>
                            <p class="text-xs text-slate-400 mt-1">Match Score</p>
                        </div>
                    </div>

                    <details class="mt-3 pt-3 border-t border-slate-100">
                        <summary class="text-xs text-blue-600 cursor-pointer">See full score breakdown</summary>
                        <table class="w-full text-xs mt-2">
                            <thead class="text-slate-400 text-left">
                                <tr><th class="py-1">Criterion</th><th>Raw score</th><th>Weight</th><th>Weighted</th></tr>
                            </thead>
                            <tbody>
                                @foreach ($result['breakdown'] as $key => $b)
                                    <tr class="border-t border-slate-50">
                                        <td class="py-1 text-slate-600">{{ str($key)->replace('_',' ')->title() }}</td>
                                        <td>{{ $b['score'] }}%</td>
                                        <td>{{ $b['weight'] }}%</td>
                                        <td class="font-medium text-slate-900">{{ $b['weighted'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </details>
                </div>
            @endforeach
        </div>
    @endif
@endsection
