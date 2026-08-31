@extends('layouts.guest')

@section('title', 'Forgot password')

@section('content')
    <h1 class="text-xl font-semibold text-slate-900 mb-2">Forgot your password?</h1>
    <p class="text-sm text-slate-500 mb-6">We'll email you a link to reset it.</p>

    @if (session('status'))
        <div class="mb-4 rounded-lg bg-green-50 border border-green-200 text-green-700 px-4 py-3 text-sm">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
        @csrf
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
            <input type="email" name="email" value="{{ old('email') }}" required autofocus
                class="w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500">
            @error('email') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>
        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg py-2.5">
            Email password reset link
        </button>
    </form>
@endsection
