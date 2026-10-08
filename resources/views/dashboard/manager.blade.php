@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <div class="rt-hero">
        <p class="eyebrow rt-eyebrow">Manager</p>
        <h1>Welcome, {{ auth()->user()->first_name }}</h1>
        <p>You are logged in as <strong>manager</strong>. Role-specific widgets (properties, applications, payments, DSS recommendations, etc.) ship in later steps as each module is built.</p>
        @include('partials.landing-mascots')
    </div>
    <div class="bg-white border border-slate-200 rounded-xl p-6 text-sm text-slate-500">
        This is a placeholder dashboard confirming RBAC routing works: only a manager account lands here.
    </div>
@endsection
