@extends('layouts.app')

@section('title', 'My Properties')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl font-semibold text-slate-900">My Properties</h1>
        <a href="{{ route('owner.properties.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg px-4 py-2">
            + Add Property
        </a>
    </div>

    @if ($properties->isEmpty())
        <div class="bg-white border border-slate-200 rounded-xl p-10 text-center text-slate-500">
            You haven't added any properties yet.
        </div>
    @else
        <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-slate-500 text-left">
                    <tr>
                        <th class="px-4 py-3">Property</th>
                        <th class="px-4 py-3">Type</th>
                        <th class="px-4 py-3">Units</th>
                        <th class="px-4 py-3">Verification</th>
                        <th class="px-4 py-3">Availability</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($properties as $property)
                        <tr>
                            <td class="px-4 py-3 font-medium text-slate-900">
                                {{ $property->name }}
                            </td>
                            <td class="px-4 py-3 text-slate-600">{{ str($property->property_type)->replace('_', ' ')->title() }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $property->rental_spaces_count }}</td>
                            <td class="px-4 py-3">
                                <span class="text-xs px-2 py-1 rounded-full
                                    {{ $property->verification_status === 'verified' ? 'bg-green-50 text-green-700' : ($property->verification_status === 'rejected' ? 'bg-red-50 text-red-700' : 'bg-amber-50 text-amber-700') }}">
                                    {{ str($property->verification_status)->title() }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-slate-600">{{ str($property->availability_status)->replace('_', ' ')->title() }}</td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap items-center justify-start gap-2 sm:justify-end">
                                    <a href="{{ route('owner.properties.spaces.index', $property) }}"
                                       class="inline-flex min-h-10 shrink-0 items-center gap-2 whitespace-nowrap rounded-lg border px-3 py-2 text-xs font-semibold transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-400 rt-soft">
                                        <svg aria-hidden="true" focusable="false" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4"><path d="M3 17.5A1.5 1.5 0 0 1 1.5 16V6.25A1.25 1.25 0 0 1 2.75 5h4.1l1.4-1.5h4.5L14.2 5h3.05a1.25 1.25 0 0 1 1.25 1.25V16a1.5 1.5 0 0 1-1.5 1.5H3Zm0-1.5h14V6.5h-3.45l-1.4-1.5H8.9L7.5 6.5H3V16Zm7-1.25a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm0-1.5a2.5 2.5 0 1 1 0-5 2.5 2.5 0 0 1 0 5Z"/></svg>
                                        <span>Units</span>
                                    </a>
                                    <a href="{{ route('owner.properties.tour.show', $property) }}"
                                       class="inline-flex min-h-10 shrink-0 items-center gap-2 whitespace-nowrap rounded-lg border px-3 py-2 text-xs font-semibold transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-violet-400 rt-soft rt-soft-violet">
                                        <svg aria-hidden="true" focusable="false" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4"><path d="M10 2a8 8 0 1 0 0 16 8 8 0 0 0 0-16Zm0 1.5a6.5 6.5 0 1 1 0 13 6.5 6.5 0 0 1 0-13Zm-1.4 2.8a.75.75 0 0 0-1.1.66v6.08a.75.75 0 0 0 1.1.66l5.08-3.04a.75.75 0 0 0 0-1.32L8.6 6.3Z"/></svg>
                                        <span>Virtual Tour</span>
                                    </a>
                                    <a href="{{ route('owner.properties.edit', $property) }}"
                                       class="inline-flex min-h-10 shrink-0 items-center gap-2 whitespace-nowrap rounded-lg bg-amber-500 px-3 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-amber-600 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-400">
                                        <svg aria-hidden="true" focusable="false" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4"><path d="M13.7 2.3a2.4 2.4 0 0 1 3.4 3.4l-9.7 9.7-4.1 1 1-4.1 9.4-9.4ZM4.8 13l-.4 1.6 1.6-.4 8.7-8.7-1.2-1.2L4.8 13Z"/></svg>
                                        <span>Edit</span>
                                    </a>
                                    <form action="{{ route('owner.properties.destroy', $property) }}" method="POST" class="inline-flex shrink-0"
                                      onsubmit="return confirm('Delete this property? This cannot be undone easily.')">
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
        <div class="mt-6">{{ $properties->links() }}</div>
    @endif
@endsection
