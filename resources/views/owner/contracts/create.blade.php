@extends('layouts.app')

@section('title', 'Create Contract')

@section('content')
    <a href="{{ route('owner.applications.show', $application) }}" class="back-link"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" /></svg><span>Application</span></a>

    <h1 class="text-xl font-semibold text-slate-900 mt-4 mb-6">
        Create contract — {{ $application->user->first_name }} {{ $application->user->last_name }}
    </h1>

    <form method="POST" action="{{ route('owner.applications.contract.store', $application) }}"
          class="bg-white border border-slate-200 rounded-xl p-6 space-y-4 max-w-2xl">
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Monthly rent (₱)</label>
                <input type="number" step="0.01" name="monthly_rent" value="{{ old('monthly_rent', $application->rentalSpace->monthly_rent) }}" required
                    class="w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Security deposit (₱)</label>
                <input type="number" step="0.01" name="security_deposit" value="{{ old('security_deposit', $application->rentalSpace->security_deposit) }}"
                    class="w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Advance payment (₱)</label>
                <input type="number" step="0.01" name="advance_payment" value="{{ old('advance_payment', $application->rentalSpace->advance_payment) }}"
                    class="w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Lease start date</label>
                <input type="date" name="start_date" value="{{ old('start_date', $application->desired_move_in_date->format('Y-m-d')) }}" required
                    class="w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Lease end date</label>
                <input type="date" name="end_date" required
                    class="w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500">
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Terms &amp; conditions</label>
            <textarea name="terms_and_conditions" rows="6" placeholder="House rules, penalties, renewal terms, etc."
                class="w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500">{{ old('terms_and_conditions') }}</textarea>
        </div>

        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg px-5 py-2.5">
            Create contract (draft)
        </button>
    </form>
@endsection
