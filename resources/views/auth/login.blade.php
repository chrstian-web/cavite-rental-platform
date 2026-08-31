@extends('layouts.guest')

@section('title', 'Log in')

@section('content')
    <h1 class="text-xl font-semibold text-slate-900 mb-6">Log in</h1>

    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
            <input type="email" name="email" value="{{ old('email') }}" required autofocus
                class="w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500">
            @error('email') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Password</label>
            <input type="password" name="password" required
                class="w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500">
            @error('password') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>

        <div class="flex items-center justify-between text-sm">
            <label class="flex items-center gap-2">
                <input type="checkbox" name="remember" class="rounded border-slate-300">
                Remember me
            </label>
            <a href="{{ route('password.request') }}" class="text-blue-600 hover:underline">Forgot password?</a>
        </div>

        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg py-2.5">
            Log in
        </button>

        <p class="text-center text-sm text-slate-500">
            Don't have an account? <a href="{{ route('register') }}" class="text-blue-600 hover:underline">Register</a>
        </p>
    </form>
@endsection
