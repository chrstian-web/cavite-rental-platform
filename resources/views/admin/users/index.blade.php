@extends('layouts.app')

@section('title', 'Manage Users')

@section('content')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
        <div>
            <h1 class="text-xl font-semibold text-slate-900">Manage users</h1>
            <p class="text-sm text-slate-500 mt-1">Reset a user’s password or remove accounts that are no longer members.</p>
        </div>
        <a href="{{ route('admin.users.deleted') }}" class="text-sm text-slate-600 hover:underline">Deleted users &rarr;</a>
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
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($users as $user)
                        <tr>
                            <td class="px-4 py-3 font-medium text-slate-900">{{ $user->first_name }} {{ $user->last_name }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $user->email }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ str($user->role?->name ?? 'Unknown')->title() }}</td>
                            <td class="px-4 py-3">
                                <span class="text-xs px-2 py-1 rounded-full {{ $user->status === 'active' ? 'bg-green-50 text-green-700' : 'bg-slate-100 text-slate-600' }}">
                                    {{ str($user->status)->title() }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <a href="{{ route('admin.users.password.edit', $user) }}" class="text-blue-600 hover:underline">
                                    Reset password
                                </a>
                                @if (! $user->isSuperAdmin() && ! $user->is(auth()->user()))
                                    <form method="POST" action="{{ route('admin.users.destroy', $user) }}" class="inline ml-4"
                                          onsubmit="return confirm('Delete {{ addslashes($user->first_name.' '.$user->last_name) }}? They will no longer be able to log in. Their contracts and payment history are kept.')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:underline">Delete</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-slate-500">No users found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-6">{{ $users->links() }}</div>
@endsection
