@extends('layouts.app')

@section('title', 'My Viewing Requests')

@section('content')
    <h1 class="text-xl font-semibold text-slate-900 mb-6">My Viewing Requests</h1>

    @if ($viewings->isEmpty())
        <div class="bg-white border border-slate-200 rounded-xl p-10 text-center text-slate-500">
            No viewing requests yet.
        </div>
    @else
        <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-slate-500 text-left">
                    <tr>
                        <th class="px-4 py-3">Property</th>
                        <th class="px-4 py-3">Date & time</th>
                        <th class="px-4 py-3">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($viewings as $viewing)
                        <tr>
                            <td class="px-4 py-3 font-medium text-slate-900">{{ $viewing->property->name ?? 'Property unavailable' }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $viewing->preferred_date->format('M j, Y') }} at {{ $viewing->preferred_time }}</td>
                            <td class="px-4 py-3">
                                <div class="flex flex-col items-end md:items-start gap-2 min-w-0">
                                <span class="text-xs px-2 py-1 rounded-full whitespace-nowrap
                                    {{ match($viewing->status) {
                                        'confirmed' => 'bg-green-50 text-green-700',
                                        'cancelled' => 'bg-red-50 text-red-700',
                                        'completed' => 'bg-slate-100 text-slate-600',
                                        default => 'bg-amber-50 text-amber-700',
                                    } }}">
                                    {{ str($viewing->status)->title() }}
                                </span>
                                @if ($viewing->status === 'rescheduled' && $viewing->reschedule_reason)
                                    <p class="text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2 max-w-xs text-left">
                                        <span class="font-semibold">Reason from the owner:</span> {{ $viewing->reschedule_reason }}
                                    </p>
                                @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-6">{{ $viewings->links() }}</div>
    @endif
@endsection
