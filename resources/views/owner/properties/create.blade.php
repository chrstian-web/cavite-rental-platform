@extends('layouts.app')

@section('title', 'Add Property')

@section('content')
    <h1 class="text-xl font-semibold text-slate-900 mb-6">Add a new property</h1>

    @include('partials.validation-errors')

    <form method="POST" action="{{ route('owner.properties.store') }}" enctype="multipart/form-data"
          class="bg-white border border-slate-200 rounded-xl p-6">
        @csrf
        @include('owner.properties._form')

        <div class="sticky bottom-0 z-10 -mx-6 -mb-6 mt-6 border-t border-slate-200 bg-white/95 px-6 py-4 backdrop-blur md:static md:mx-0 md:mb-0 md:border-0 md:bg-transparent md:px-0 md:py-0">
            <button type="submit" class="w-full rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-blue-700 md:w-auto">
                Create property
            </button>
        </div>
    </form>
@endsection
