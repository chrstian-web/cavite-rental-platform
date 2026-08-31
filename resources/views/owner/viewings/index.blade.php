@extends('layouts.app')

@section('title', 'Viewing Requests')

@section('content')
    <h1 class="text-xl font-semibold text-slate-900 mb-6">Viewing Requests</h1>

    @if ($viewings->isEmpty())
        <div class="bg-white border border-slate-200 rounded-xl p-10 text-center text-slate-500">No viewing requests yet.</div>
    @else
        <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-slate-500 text-left">
                    <tr>
                        <th class="px-4 py-3">Tenant</th>
                        <th class="px-4 py-3">Property</th>
                        <th class="px-4 py-3">Requested</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($viewings as $viewing)
                        <tr>
                            <td class="px-4 py-3 font-medium text-slate-900">{{ $viewing->user->first_name }} {{ $viewing->user->last_name }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $viewing->property->name }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $viewing->preferred_date->format('M j, Y') }} at {{ $viewing->preferred_time }}</td>
                            <td class="px-4 py-3">
                                <span class="text-xs px-2 py-1 rounded-full
                                    {{ match($viewing->status) {
                                        'confirmed' => 'bg-green-50 text-green-700',
                                        'cancelled' => 'bg-red-50 text-red-700',
                                        'completed' => 'bg-slate-100 text-slate-600',
                                        default => 'bg-amber-50 text-amber-700',
                                    } }}">
                                    {{ str($viewing->status)->title() }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                @if (! in_array($viewing->status, ['completed', 'cancelled']))
                                    <form method="POST" action="{{ route('owner.viewings.update', $viewing) }}" class="inline-flex flex-wrap items-center gap-2 justify-end">
                                        @csrf @method('PATCH')
                                        <select name="status" class="rounded-lg border-slate-300 text-xs">
                                            <option value="confirmed">Confirm</option>
                                            <option value="rescheduled">Reschedule (fill date/time)</option>
                                            <option value="completed">Mark Completed</option>
                                            <option value="cancelled">Cancel</option>
                                        </select>
                                        <input type="date" name="preferred_date" class="rounded-lg border-slate-300 text-xs w-32">
                                        <input type="time" name="preferred_time" class="rounded-lg border-slate-300 text-xs w-24">
                                        <button class="text-blue-600 text-xs hover:underline">Update</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-6">{{ $viewings->links() }}</div>
    @endif
@endsection
