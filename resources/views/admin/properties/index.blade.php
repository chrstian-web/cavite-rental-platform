@extends('layouts.app')

@section('title', 'Verify Properties')

@section('content')
    <h1 class="text-xl font-semibold text-slate-900 mb-6">Property verification</h1>

    <form method="GET" class="mb-4">
        <select name="status" onchange="this.form.submit()" class="rounded-lg border-slate-300 text-sm">
            <option value="">All statuses</option>
            @foreach (['pending', 'verified', 'rejected'] as $status)
                <option value="{{ $status }}" @selected(request('status') === $status)>{{ str($status)->title() }}</option>
            @endforeach
        </select>
    </form>

    <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-500 text-left">
                <tr>
                    <th class="px-4 py-3">Property</th>
                    <th class="px-4 py-3">Owner</th>
                    <th class="px-4 py-3">Location</th>
                    <th class="px-4 py-3">Units</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($properties as $property)
                    <tr>
                        <td class="px-4 py-3 font-medium text-slate-900">{{ $property->name }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $property->owner->first_name }} {{ $property->owner->last_name }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $property->location->city_municipality }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $property->rental_spaces_count }}</td>
                        <td class="px-4 py-3">
                            <span class="text-xs px-2 py-1 rounded-full
                                {{ $property->verification_status === 'verified' ? 'bg-green-50 text-green-700' : ($property->verification_status === 'rejected' ? 'bg-red-50 text-red-700' : 'bg-amber-50 text-amber-700') }}">
                                {{ str($property->verification_status)->title() }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap items-center justify-end gap-2">
                            <form action="{{ route('admin.properties.verify', $property) }}" method="POST" class="inline-flex">
                                @csrf @method('PATCH')
                                <input type="hidden" name="decision" value="verified">
                                <button type="submit" class="inline-flex min-h-10 items-center justify-center rounded-lg border px-3 py-2 text-xs font-semibold transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400 rt-soft rt-soft-emerald">Verify</button>
                            </form>
                            <form action="{{ route('admin.properties.verify', $property) }}" method="POST" class="inline-flex">
                                @csrf @method('PATCH')
                                <input type="hidden" name="decision" value="rejected">
                                <button type="submit" class="inline-flex min-h-10 items-center justify-center rounded-lg bg-red-600 px-3 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-red-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-400">Reject</button>
                            </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-6">{{ $properties->links() }}</div>
@endsection
