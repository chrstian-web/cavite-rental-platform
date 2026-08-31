@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <h1 class="text-xl font-semibold text-slate-900 mb-2">Welcome, {{ auth()->user()->first_name }}</h1>
    <p class="text-sm text-slate-500 mb-6">
        You are logged in as <strong>manager</strong>. Role-specific widgets (properties, applications,
        payments, DSS recommendations, etc.) ship in later steps as each module is built.
    </p>
    <div class="bg-white border border-slate-200 rounded-xl p-6 text-sm text-slate-500">
        This is a placeholder dashboard confirming RBAC routing works: only a manager account lands here.
    </div>
@endsection
