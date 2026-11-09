@extends('layouts.app')

@section('title', $property->name.' — Units')

@section('content')
    <a href="{{ route('owner.properties.index') }}" class="back-link"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" /></svg><span>My Properties</span></a>

    <div class="flex items-center justify-between mt-4 mb-6">
        <h1 class="text-xl font-semibold text-slate-900">{{ $property->name }} — Units / Rooms</h1>
        <a href="{{ route('owner.properties.spaces.create', $property) }}" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg px-4 py-2">
            + Add unit/room
        </a>
    </div>

    @if ($spaces->isEmpty())
        <div class="bg-white border border-slate-200 rounded-xl p-10 text-center text-slate-500">
            No units/rooms added yet. Add at least one so renters can apply.
        </div>
    @else
        <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-slate-500 text-left">
                    <tr>
                        <th class="px-4 py-3">Photo</th>
                        <th class="px-4 py-3">Number</th>
                        <th class="px-4 py-3">Type</th>
                        <th class="px-4 py-3">Bed/Bath</th>
                        <th class="px-4 py-3">Capacity</th>
                        <th class="px-4 py-3">Rent</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($spaces as $space)
                        <tr>
                            <td class="px-4 py-3">
                                @if ($space->images->first())
                                    <img src="{{ asset('storage/'.$space->images->first()->path) }}" class="w-12 h-10 object-cover rounded-lg">
                                @else
                                    <div class="w-12 h-10 rounded-lg bg-slate-100 flex items-center justify-center text-slate-400 text-[9px]">None</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 font-medium text-slate-900">{{ $space->space_number }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $space->space_type ?? '—' }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $space->bedrooms }} / {{ $space->bathrooms }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $space->occupied_capacity }}/{{ $space->total_capacity }}</td>
                            <td class="px-4 py-3 text-slate-600">₱{{ number_format($space->monthly_rent) }}</td>
                            <td class="px-4 py-3">
                                <span class="text-xs px-2 py-1 rounded-full {{ $space->status === 'available' ? 'bg-green-50 text-green-700' : 'bg-slate-100 text-slate-600' }}">
                                    {{ str($space->status)->title() }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap items-center justify-end gap-2">
                                <a href="{{ route('owner.properties.spaces.edit', [$property, $space]) }}" class="inline-flex min-h-10 items-center justify-center rounded-lg bg-amber-500 px-3 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-amber-600 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-400">Edit</a>
                                <form action="{{ route('owner.properties.spaces.destroy', [$property, $space]) }}" method="POST" class="inline-flex"
                                      onsubmit="return confirm('Remove this unit/room?')">
                                    @csrf @method('DELETE')
                                    <x-delete-button label="Delete" />
                                </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-6">{{ $spaces->links() }}</div>
    @endif
@endsection
