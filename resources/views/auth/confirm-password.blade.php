@extends('layouts.guest')

@section('title', 'Confirm password')

@section('content')
    <h1 class="text-xl font-semibold text-slate-900 mb-2">Confirm password</h1>
    <p class="text-sm text-slate-500 mb-6">This is a secure area. Please confirm your password before continuing.</p>

    <form method="POST" action="{{ route('password.confirm') }}" class="space-y-4">
        @csrf
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Password</label>
            <input type="password" name="password" required autofocus
                class="w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500">
            @error('password') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>
        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg py-2.5">
            Confirm
        </button>
    </form>
@endsection
