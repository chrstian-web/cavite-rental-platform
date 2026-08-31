@extends('layouts.app')

@section('title', 'Request a Viewing — '.$property->name)

@section('content')
    <a href="{{ route('properties.show', $property->slug) }}" class="text-sm text-blue-600 hover:underline">&larr; {{ $property->name }}</a>

    <h1 class="text-xl font-semibold text-slate-900 mt-4 mb-6">Request a viewing — {{ $property->name }}</h1>

    <form method="POST" action="{{ route('tenant.viewings.store', $property) }}" class="bg-white border border-slate-200 rounded-xl p-6 space-y-4 max-w-md">
        @csrf
        @if ($space ?? null)
            <input type="hidden" name="rental_space_id" value="{{ $space->id }}">
            <p class="text-sm text-slate-500">For unit: <strong>{{ $space->space_number }}</strong></p>
        @endif

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Preferred date</label>
            <input type="date" name="preferred_date" required class="w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Preferred time</label>
            <input type="time" name="preferred_time" required class="w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Message (optional)</label>
            <textarea name="message" rows="3" class="w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500"></textarea>
        </div>

        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg px-5 py-2.5">
            Send request
        </button>
    </form>
@endsection
