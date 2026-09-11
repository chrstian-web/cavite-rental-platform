@extends('layouts.app')

@section('title', 'Reset User Password')

@section('content')
    <div class="max-w-xl">
        <a href="{{ route('admin.users.index') }}" class="text-sm text-blue-600 hover:underline">&larr; Back to users</a>

        <div class="mt-4 bg-white border border-slate-200 rounded-xl p-6">
            <h1 class="text-xl font-semibold text-slate-900">Reset user password</h1>
            <p class="text-sm text-slate-500 mt-1 mb-6">
                Set a new password for <strong class="text-slate-700">{{ $user->first_name }} {{ $user->last_name }}</strong> ({{ $user->email }}).
            </p>

            <div class="mb-6 rounded-lg bg-amber-50 border border-amber-200 px-4 py-3 text-sm text-amber-800">
                The password will be securely hashed before it is saved. It will not be visible in the database.
            </div>

            <form method="POST" action="{{ route('admin.users.password.update', $user) }}" class="space-y-5">
                @csrf
                @method('PATCH')

                <div>
                    <label for="password" class="block text-sm font-medium text-slate-700 mb-1">New password</label>
                    <input id="password" type="password" name="password" required autofocus autocomplete="new-password"
                        class="w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500">
                    @error('password') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-slate-700 mb-1">Confirm new password</label>
                    <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password"
                        class="w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500">
                </div>

                <div class="flex items-center justify-end gap-3">
                    <a href="{{ route('admin.users.index') }}" class="text-sm text-slate-600 hover:underline">Cancel</a>
                    <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-blue-700">
                        Update password
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
