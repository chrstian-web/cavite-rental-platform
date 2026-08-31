@extends('layouts.app')

@section('title', $report['title'])

@section('content')
    <a href="{{ route('admin.reports.index') }}" class="text-sm text-blue-600 hover:underline">&larr; All Reports</a>

    <div class="flex items-center justify-between mt-4 mb-6">
        <h1 class="text-xl font-semibold text-slate-900">{{ $report['title'] }}</h1>
        <div class="flex gap-2">
            <a href="{{ route('admin.reports.export.csv', array_merge(['type' => $type], $filters)) }}"
               class="text-sm bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg px-4 py-2">
                Export CSV
            </a>
            <a href="{{ route('admin.reports.export.pdf', array_merge(['type' => $type], $filters)) }}"
               class="text-sm bg-blue-600 hover:bg-blue-700 text-white rounded-lg px-4 py-2">
                Export PDF
            </a>
        </div>
    </div>

    <form method="GET" class="bg-white border border-slate-200 rounded-xl p-4 mb-6 grid grid-cols-1 md:grid-cols-5 gap-3">
        <div>
            <label class="block text-xs text-slate-500 mb-1">From</label>
            <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="w-full rounded-lg border-slate-300 text-sm">
        </div>
        <div>
            <label class="block text-xs text-slate-500 mb-1">To</label>
            <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="w-full rounded-lg border-slate-300 text-sm">
        </div>
        <div>
            <label class="block text-xs text-slate-500 mb-1">Property</label>
            <select name="property_id" class="w-full rounded-lg border-slate-300 text-sm">
                <option value="">All properties</option>
                @foreach ($properties as $property)
                    <option value="{{ $property->id }}" @selected(($filters['property_id'] ?? null) == $property->id)>{{ $property->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs text-slate-500 mb-1">Property Type</label>
            <select name="property_type" class="w-full rounded-lg border-slate-300 text-sm">
                <option value="">All types</option>
                @foreach (['condominium', 'boarding_house', 'dormitory'] as $ptype)
                    <option value="{{ $ptype }}" @selected(($filters['property_type'] ?? null) === $ptype)>{{ str($ptype)->replace('_',' ')->title() }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex items-end">
            <button type="submit" class="w-full bg-slate-800 hover:bg-slate-900 text-white text-sm rounded-lg py-2">Apply filters</button>
        </div>
    </form>

    @isset($report['summary'])
        <div class="bg-blue-50 border border-blue-100 text-blue-800 text-sm rounded-xl p-4 mb-6">
            {{ $report['summary'] }}
        </div>
    @endisset

    <div class="bg-white border border-slate-200 rounded-xl overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-500 text-left">
                <tr>
                    @foreach ($report['headers'] as $header)
                        <th class="px-4 py-3 whitespace-nowrap">{{ $header }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($report['rows'] as $row)
                    <tr>
                        @foreach ($row as $cell)
                            <td class="px-4 py-3 whitespace-nowrap text-slate-700">{{ $cell }}</td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($report['headers']) }}" class="px-4 py-8 text-center text-slate-400">
                            No data for the selected filters.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
