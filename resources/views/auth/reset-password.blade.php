@extends('layouts.guest')

@section('title', 'Reset password')

@section('content')
    <h1 class="text-xl font-semibold text-slate-900 mb-6">Reset password</h1>

    <form method="POST" action="{{ route('password.store') }}" class="space-y-4">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
            <input type="email" name="email" value="{{ old('email', $request->email) }}" required autofocus
                class="w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500">
            @error('email') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">New password</label>
            <input type="password" name="password" required
                class="w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500">
            @error('password') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Confirm new password</label>
            <input type="password" name="password_confirmation" required
                class="w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500">
        </div>

        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg py-2.5">
            Reset password
        </button>
    </form>
@endsection
