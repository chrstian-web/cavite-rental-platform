@extends('layouts.app')

@section('title', 'My Contracts')

@section('content')
    <h1 class="text-xl font-semibold text-slate-900 mb-6">My Contracts</h1>

    @if ($contracts->isEmpty())
        <div class="bg-white border border-slate-200 rounded-xl p-10 text-center text-slate-500">No contracts yet.</div>
    @else
        <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-slate-500 text-left">
                    <tr>
                        <th class="px-4 py-3">Property / Unit</th>
                        <th class="px-4 py-3">Term</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($contracts as $contract)
                        <tr>
                            <td class="px-4 py-3 font-medium text-slate-900">{{ $contract->property->name }} — {{ $contract->rentalSpace->space_number }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $contract->start_date->format('M j, Y') }} – {{ $contract->end_date->format('M j, Y') }}</td>
                            <td class="px-4 py-3">
                                <span class="text-xs px-2 py-1 rounded-full
                                    {{ match($contract->status) {
                                        'active' => 'bg-green-50 text-green-700',
                                        'terminated' => 'bg-red-50 text-red-700',
                                        'expired' => 'bg-slate-100 text-slate-600',
                                        default => 'bg-amber-50 text-amber-700',
                                    } }}">
                                    {{ str($contract->status)->title() }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('tenant.contracts.show', $contract) }}" class="text-blue-600 hover:underline">View</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-6">{{ $contracts->links() }}</div>
    @endif
@endsection
