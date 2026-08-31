@extends('layouts.app')

@section('title', 'Maintenance Requests')

@section('content')
    <h1 class="text-xl font-semibold text-slate-900 mb-6">Maintenance Requests</h1>

    <form method="GET" class="mb-4">
        <select name="status" onchange="this.form.submit()" class="rounded-lg border-slate-300 text-sm">
            <option value="">All statuses</option>
            @foreach (['submitted', 'in_progress', 'resolved', 'closed'] as $status)
                <option value="{{ $status }}" @selected(request('status') === $status)>{{ str($status)->replace('_',' ')->title() }}</option>
            @endforeach
        </select>
    </form>

    @if ($requests->isEmpty())
        <div class="bg-white border border-slate-200 rounded-xl p-10 text-center text-slate-500">No maintenance requests.</div>
    @else
        <div class="space-y-4">
            @foreach ($requests as $request)
                <div class="bg-white border border-slate-200 rounded-xl p-5">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="font-medium text-slate-900">
                                {{ str($request->category)->replace('_',' ')->title() }}
                                <span class="text-xs px-2 py-0.5 rounded-full ml-2
                                    {{ match($request->priority) {
                                        'urgent' => 'bg-red-50 text-red-700',
                                        'high' => 'bg-amber-50 text-amber-700',
                                        default => 'bg-slate-100 text-slate-600',
                                    } }}">
                                    {{ str($request->priority)->title() }}
                                </span>
                            </p>
                            <p class="text-xs text-slate-500 mt-1">
                                {{ $request->tenant->first_name }} {{ $request->tenant->last_name }} &middot;
                                {{ $request->property->name }}
                                @if ($request->rentalSpace) — {{ $request->rentalSpace->space_number }} @endif
                            </p>
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

                    @if (! in_array($request->status, ['resolved', 'closed']))
                        <form method="POST" action="{{ route('owner.maintenance.update', $request) }}" class="mt-3 flex items-center gap-2">
                            @csrf @method('PATCH')
                            <select name="status" class="rounded-lg border-slate-300 text-xs">
                                <option value="in_progress">Mark In Progress</option>
                                <option value="resolved">Mark Resolved</option>
                                <option value="closed">Close</option>
                            </select>
                            <button class="text-blue-600 text-xs hover:underline">Update</button>
                        </form>
                    @endif
                </div>
            @endforeach
        </div>
        <div class="mt-6">{{ $requests->links() }}</div>
    @endif
@endsection
