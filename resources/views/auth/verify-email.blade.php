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

    <div class="flex flex-wrap items-center justify-between gap-3">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit" class="inline-flex min-h-10 items-center justify-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-400">Resend verification email</button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-slate-400">Log out</button>
        </form>
    </div>
@endsection
