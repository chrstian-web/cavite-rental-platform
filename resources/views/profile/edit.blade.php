@extends('layouts.app')

@section('title', 'Profile')

@section('content')
    <h1 class="text-xl font-semibold text-slate-900 mb-6">Profile</h1>

    <div class="bg-white border border-slate-200 rounded-xl p-6 max-w-lg mb-6">
        <h2 class="font-medium text-slate-900 mb-4">Account information</h2>
        <form method="POST" action="{{ route('profile.update') }}" class="space-y-4">
            @csrf
            @method('patch')

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">First name</label>
                    <input type="text" name="first_name" value="{{ old('first_name', $user->first_name) }}"
                        class="w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Last name</label>
                    <input type="text" name="last_name" value="{{ old('last_name', $user->last_name) }}"
                        class="w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                <input type="email" name="email" value="{{ old('email', $user->email) }}"
                    class="w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Phone</label>
                <input type="text" name="phone" value="{{ old('phone', $user->phone) }}" inputmode="numeric" maxlength="11" pattern="[0-9]{11}"
                    placeholder="09171234567"
                    class="w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500">
                <p class="text-xs text-slate-400 mt-1">11 digits, numbers only</p>
            </div>

            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg px-4 py-2">
                Save
            </button>
        </form>
    </div>

    <div class="bg-white border border-slate-200 rounded-xl p-6 max-w-lg">
        <h2 class="font-medium text-slate-900 mb-4">Change password</h2>
        <form method="POST" action="{{ route('profile.password.update') }}" class="space-y-4">
            @csrf
            @method('put')

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Current password</label>
                <input type="password" name="current_password"
                    class="w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500">
                @error('current_password', 'updatePassword') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">New password</label>
                <input type="password" name="password"
                    class="w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500">
                @error('password', 'updatePassword') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Confirm new password</label>
                <input type="password" name="password_confirmation"
                    class="w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500">
            </div>

            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg px-4 py-2">
                Update password
            </button>
        </form>
    </div>
@endsection
