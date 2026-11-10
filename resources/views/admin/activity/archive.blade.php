@extends('layouts.app')

@section('title', 'Archived Activity')

@section('content')
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between mb-6">
        <div>
            <a href="{{ route('dashboard') }}" class="text-sm text-blue-600 hover:underline">← Back to dashboard</a>
            <h1 class="mt-2 text-xl font-semibold text-slate-900">Archived Activity</h1>
            <p class="mt-1 text-sm text-slate-500">Items you have already reviewed are kept here instead of being deleted.</p>
        </div>
    </div>

    <div class="max-w-3xl rounded-xl border border-slate-200 bg-white p-5">
        <ul class="space-y-3">
            @forelse ($archivedActivity as $item)
                <li class="flex flex-col gap-3 rounded-lg border border-slate-100 p-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <a href="{{ $item['url'] }}" class="block text-sm text-slate-700 hover:underline">{{ $item['text'] }}</a>
                        <p class="text-xs text-slate-400">{{ $item['at']->diffForHumans() }}</p>
                    </div>
                    <form method="POST" action="{{ route('admin.activity.restore', [$item['archive_type'], $item['archive_id']]) }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:border-slate-500 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-400">Restore</button>
                    </form>
                </li>
            @empty
                <li class="text-sm text-slate-400">No archived activity yet.</li>
            @endforelse
        </ul>
    </div>
@endsection
