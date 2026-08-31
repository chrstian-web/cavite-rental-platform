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
                            <td class="px-4 py-3 font-medium text-slate-900">{{ $property->name }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ str($property->property_type)->replace('_', ' ')->title() }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $property->rental_spaces_count }}</td>
                            <td class="px-4 py-3">
                                <span class="text-xs px-2 py-1 rounded-full
                                    {{ $property->verification_status === 'verified' ? 'bg-green-50 text-green-700' : ($property->verification_status === 'rejected' ? 'bg-red-50 text-red-700' : 'bg-amber-50 text-amber-700') }}">
                                    {{ str($property->verification_status)->title() }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-slate-600">{{ str($property->availability_status)->replace('_', ' ')->title() }}</td>
                            <td class="px-4 py-3 text-right space-x-3">
                                <a href="{{ route('owner.properties.spaces.index', $property) }}" class="text-blue-600 hover:underline">Units</a>
                                <a href="{{ route('owner.properties.tour.show', $property) }}" class="text-blue-600 hover:underline">Virtual Tour</a>
                                <a href="{{ route('owner.properties.edit', $property) }}" class="text-blue-600 hover:underline">Edit</a>
                                <form action="{{ route('owner.properties.destroy', $property) }}" method="POST" class="inline"
                                      onsubmit="return confirm('Delete this property? This cannot be undone easily.')">
                                    @csrf @method('DELETE')
                                    <button class="text-red-600 hover:underline">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-6">{{ $properties->links() }}</div>
    @endif
@endsection
