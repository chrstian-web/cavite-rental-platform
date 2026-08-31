@extends('layouts.guest')

@section('title', 'Verify email')

@section('content')
    <h1 class="text-xl font-semibold text-slate-900 mb-2">Verify your email</h1>
    <p class="text-sm text-slate-500 mb-6">
        Thanks for signing up! Before getting started, please verify your email by clicking the link we just emailed you.
    </p>

    @if (session('status') === 'verification-link-sent')
        <div class="mb-4 rounded-lg bg-green-50 border border-green-200 text-green-700 px-4 py-3 text-sm">
            A new verification link has been sent to your email address.
        </div>
    @endif

    <div class="flex items-center justify-between">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit" class="text-sm text-blue-600 hover:underline">Resend verification email</button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="text-sm text-slate-500 hover:underline">Log out</button>
        </form>
    </div>
@endsection
