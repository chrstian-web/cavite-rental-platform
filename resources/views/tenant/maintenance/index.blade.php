@extends('layouts.app')

@section('title', 'My Maintenance Requests')

@section('content')
    <h1 class="text-xl font-semibold text-slate-900 mb-6">My Maintenance Requests</h1>

    @if ($requests->isEmpty())
        <div class="bg-white border border-slate-200 rounded-xl p-10 text-center text-slate-500">
            No maintenance requests yet. Submit one from an active contract.
        </div>
    @else
        <div class="space-y-4">
            @foreach ($requests as $request)
                <div class="bg-white border border-slate-200 rounded-xl p-5">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="font-medium text-slate-900">{{ str($request->category)->replace('_',' ')->title() }}</p>
                            <p class="text-xs text-slate-500 mt-1">{{ $request->property->name }}</p>
                        </div>
                        <span class="text-xs px-2 py-1 rounded-full
                            {{ match($request->status) {
                                'resolved', 'closed' => 'bg-green-50 text-green-700',
                                'in_progress' => 'bg-blue-50 text-blue-700',
                                default => 'bg-amber-50 text-amber-700',
                            } }}">
                            {{ str($request->status)->replace('_',' ')->title() }}
                        </span>
                    </div>
                    <p class="text-sm text-slate-600 mt-2">{{ $request->description }}</p>
                    @if ($request->images->isNotEmpty())
                        <div class="flex gap-2 mt-3">
                            @foreach ($request->images as $image)
                                <img src="{{ asset('storage/'.$image->path) }}" class="w-16 h-16 object-cover rounded-lg">
                            @endforeach
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
        <div class="mt-6">{{ $requests->links() }}</div>
    @endif
@endsection
