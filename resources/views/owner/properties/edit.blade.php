@extends('layouts.app')

@section('title', 'Edit Property')

@section('content')
    <h1 class="text-xl font-semibold text-slate-900 mb-6">Edit {{ $property->name }}</h1>

    @include('partials.validation-errors')

    <form method="POST" action="{{ route('owner.properties.update', $property) }}" enctype="multipart/form-data"
          class="bg-white border border-slate-200 rounded-xl p-6">
        @csrf
        @method('PUT')
        @include('owner.properties._form')

        <button type="submit" class="mt-6 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg px-5 py-2.5">
            Save changes
        </button>
    </form>
@endsection
