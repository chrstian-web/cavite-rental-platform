@extends('layouts.app')

@section('title', 'Add Unit/Room')

@section('content')
    <a href="{{ route('owner.properties.spaces.index', $property) }}" class="back-link"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" /></svg><span>{{ $property->name }}</span></a>

    <h1 class="text-xl font-semibold text-slate-900 mt-4 mb-6">Add a unit/room to {{ $property->name }}</h1>

    <form method="POST" action="{{ route('owner.properties.spaces.store', $property) }}" enctype="multipart/form-data"
          class="bg-white border border-slate-200 rounded-xl p-6">
        @csrf
        @include('owner.properties.spaces._form')

        <button type="submit" class="mt-6 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg px-5 py-2.5">
            Add unit/room
        </button>
    </form>
@endsection
