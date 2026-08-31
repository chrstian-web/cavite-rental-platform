@extends('layouts.app')

@section('title', 'Notifications')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl font-semibold text-slate-900">Notifications</h1>
        <form method="POST" action="{{ route('notifications.readAll') }}">
            @csrf
            @method('PATCH')
            <button class="text-sm text-blue-600 hover:underline">Mark all read</button>
        </form>
    </div>

    <div class="bg-white border border-slate-200 rounded-xl divide-y divide-slate-100">
        @forelse ($notifications as $notification)
            @include('partials.notification-item', ['notification' => $notification])
        @empty
            <p class="px-4 py-10 text-sm text-slate-400 text-center">No notifications yet.</p>
        @endforelse
    </div>

    <div class="mt-6">{{ $notifications->links() }}</div>
@endsection
