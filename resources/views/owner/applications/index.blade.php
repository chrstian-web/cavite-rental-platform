@extends('layouts.app')

@section('title', 'Applications')

@section('content')
    <h1 class="text-xl font-semibold text-slate-900 mb-6">Rental Applications</h1>

    <form method="GET" class="mb-4">
        <select name="status" onchange="this.form.submit()" class="rounded-lg border-slate-300 text-sm">
            <option value="">All statuses</option>
            @foreach (['pending', 'under_review', 'approved', 'rejected', 'cancelled'] as $status)
                <option value="{{ $status }}" @selected(request('status') === $status)>{{ str($status)->replace('_',' ')->title() }}</option>
            @endforeach
        </select>
    </form>

    @if ($applications->isEmpty())
        <div class="bg-white border border-slate-200 rounded-xl p-10 text-center text-slate-500">No applications yet.</div>
    @else
        <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-slate-500 text-left">
                    <tr>
                        <th class="px-4 py-3">Applicant</th>
                        <th class="px-4 py-3">Property / Unit</th>
                        <th class="px-4 py-3">Move-in</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($applications as $application)
                        <tr>
                            <td class="px-4 py-3 font-medium text-slate-900">{{ $application->user->first_name }} {{ $application->user->last_name }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $application->property->name }} — {{ $application->rentalSpace->space_number }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $application->desired_move_in_date->format('M j, Y') }}</td>
                            <td class="px-4 py-3">
                                <span class="text-xs px-2 py-1 rounded-full
                                    {{ match($application->status) {
                                        'approved' => 'bg-green-50 text-green-700',
                                        'rejected', 'cancelled' => 'bg-red-50 text-red-700',
                                        'under_review' => 'bg-blue-50 text-blue-700',
                                        default => 'bg-amber-50 text-amber-700',
                                    } }}">
                                    {{ str($application->status)->replace('_', ' ')->title() }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('owner.applications.show', $application) }}" class="text-blue-600 hover:underline">Review</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-6">{{ $applications->links() }}</div>
    @endif
@endsection
