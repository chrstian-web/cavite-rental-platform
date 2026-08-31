@extends('layouts.app')

@section('title', 'Reports')

@section('content')
    <h1 class="text-xl font-semibold text-slate-900 mb-1">Reports</h1>
    <p class="text-sm text-slate-500 mb-6">Filter by date, property, or property type, then export to CSV or PDF.</p>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach ($reportTypes as $slug => $label)
            <a href="{{ route('admin.reports.show', $slug) }}"
               class="bg-white border border-slate-200 rounded-xl p-5 hover:shadow-md transition">
                <p class="font-medium text-slate-900">{{ $label }}</p>
                <p class="text-xs text-slate-400 mt-1">View, filter, and export</p>
            </a>
        @endforeach
    </div>
@endsection
