@extends('layouts.app')

@section('title', 'My Favorites')

@section('content')
    <h1 class="text-xl font-semibold text-slate-900 mb-6">My Favorites</h1>

    @if ($favorites->isEmpty())
        <div class="bg-white border border-slate-200 rounded-xl p-10 text-center text-slate-500">
            No favorites yet. <a href="{{ route('properties.index') }}" class="text-blue-600 hover:underline">Browse listings</a> and tap the heart on any property.
        </div>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach ($favorites as $favorite)
                @php $property = $favorite->property; @endphp
                <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
                    <a href="{{ route('properties.show', $property->slug) }}">
                        <div class="aspect-video bg-slate-100 flex items-center justify-center text-slate-400 text-sm">
                            @if ($property->images->first())
                                <img src="{{ asset('storage/'.$property->images->first()->path) }}" class="w-full h-full object-cover">
                            @else
                                No photo yet
                            @endif
                        </div>
                        <div class="p-4">
                            <h3 class="font-semibold text-slate-900">{{ $property->name }}</h3>
                            <p class="text-sm text-slate-500">{{ $property->location->city_municipality }}, Cavite</p>
                        </div>
                    </a>
                    <div class="px-4 pb-4">
                        <form method="POST" action="{{ route('tenant.favorites.toggle', $property) }}">
                            @csrf
                            <button class="text-xs text-red-500 hover:underline">Remove from favorites</button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="mt-6">{{ $favorites->links() }}</div>
    @endif
@endsection
