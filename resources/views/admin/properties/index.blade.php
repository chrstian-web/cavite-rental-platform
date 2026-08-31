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
                        <td class="px-4 py-3 text-right space-x-2">
                            <form action="{{ route('admin.properties.verify', $property) }}" method="POST" class="inline">
                                @csrf @method('PATCH')
                                <input type="hidden" name="decision" value="verified">
                                <button class="text-green-600 hover:underline">Verify</button>
                            </form>
                            <form action="{{ route('admin.properties.verify', $property) }}" method="POST" class="inline">
                                @csrf @method('PATCH')
                                <input type="hidden" name="decision" value="rejected">
                                <button class="text-red-600 hover:underline">Reject</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-6">{{ $properties->links() }}</div>
@endsection
