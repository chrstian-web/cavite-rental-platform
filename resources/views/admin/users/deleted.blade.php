@extends('layouts.app')

@section('title', 'Deleted Users')

@section('content')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
        <div>
            <h1 class="text-xl font-semibold text-slate-900">Deleted users</h1>
            <p class="text-sm text-slate-500 mt-1">Restore an account that was deleted by mistake. Their contracts and history are still intact.</p>
        </div>
        <a href="{{ route('admin.users.index') }}" class="text-sm text-slate-600 hover:underline">&larr; Back to users</a>
    </div>

    <form method="GET" class="mb-4 flex gap-2">
        <input type="search" name="search" value="{{ request('search') }}" placeholder="Search by name or email"
            class="flex-1 rounded-lg border-slate-300 text-sm focus:ring-blue-500 focus:border-blue-500">
        <button type="submit" class="rounded-lg bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">
            Search
        </button>
    </form>

    <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-slate-500 text-left">
                    <tr>
                        <th class="px-4 py-3">User</th>
                        <th class="px-4 py-3">Email</th>
                        <th class="px-4 py-3">Role</th>
                        <th class="px-4 py-3">Deleted</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($users as $user)
                        <tr>
                            <td class="px-4 py-3 font-medium text-slate-900">{{ $user->first_name }} {{ $user->last_name }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $user->email }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ str($user->role?->name ?? 'Unknown')->title() }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $user->deleted_at->diffForHumans() }}</td>
                            <td class="px-4 py-3 text-right">
                                <form method="POST" action="{{ route('admin.users.restore', $user) }}"
                                      onsubmit="return confirm('Restore {{ addslashes($user->first_name.' '.$user->last_name) }}? They will be able to log in again.')">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="text-green-600 hover:underline">Restore</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-slate-500">No deleted users.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-6">{{ $users->links() }}</div>
@endsection
