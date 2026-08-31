@extends('layouts.app')

@section('title', 'Add Unit/Room')

@section('content')
    <a href="{{ route('owner.properties.spaces.index', $property) }}" class="text-sm text-blue-600 hover:underline">&larr; {{ $property->name }}</a>

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
