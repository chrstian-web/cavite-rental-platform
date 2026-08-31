@extends('layouts.app')

@section('title', 'Apply — '.$property->name)

@section('content')
    <a href="{{ route('properties.show', $property->slug) }}" class="text-sm text-blue-600 hover:underline">&larr; {{ $property->name }}</a>

    <h1 class="text-xl font-semibold text-slate-900 mt-4 mb-2">Apply for {{ $space->space_number }}</h1>
    <p class="text-sm text-slate-500 mb-6">{{ $property->name }} &middot; ₱{{ number_format($space->monthly_rent) }}/month</p>

    <form method="POST" action="{{ route('tenant.applications.store', $space) }}" enctype="multipart/form-data"
          class="bg-white border border-slate-200 rounded-xl p-6 space-y-4 max-w-2xl">
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Desired move-in date</label>
                <input type="date" name="desired_move_in_date" required
                    class="w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Intended length of stay (months)</label>
                <input type="number" name="length_of_stay_months" min="1"
                    class="w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500">
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Number of occupants</label>
            <input type="number" name="number_of_occupants" min="1" value="1" required
                class="w-full md:w-40 rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500">
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Employment status</label>
                <select name="employment_status" class="w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">Prefer not to say</option>
                    <option value="employed">Employed</option>
                    <option value="self_employed">Self-employed</option>
                    <option value="student">Student</option>
                    <option value="unemployed">Unemployed</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Monthly income (₱, optional)</label>
                <input type="number" step="0.01" name="monthly_income"
                    class="w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500">
            </div>
        </div>

        <div class="pt-2 border-t border-slate-100">
            <p class="text-sm font-medium text-slate-700 mb-2">Emergency contact</p>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <input type="text" name="emergency_contact_name" placeholder="Name" class="rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500">
                <input type="text" name="emergency_contact_number" placeholder="09171234567 (11 digits)"
                    inputmode="numeric" maxlength="11" pattern="[0-9]{11}"
                    class="rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500">
                <input type="text" name="emergency_contact_relationship" placeholder="Relationship" class="rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500">
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Notes to the owner (optional)</label>
            <textarea name="notes" rows="3" class="w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500"></textarea>
        </div>

        <div class="pt-2 border-t border-slate-100">
            <p class="text-sm font-medium text-slate-700 mb-2">Supporting documents (optional, but speeds up approval)</p>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                @foreach ([
                    'valid_id' => 'Valid ID',
                    'proof_of_income' => 'Proof of income',
                    'school_id' => 'School ID',
                    'coe' => 'Certificate of enrollment',
                    'other' => 'Other document',
                ] as $key => $label)
                    <div>
                        <label class="block text-xs text-slate-500 mb-1">{{ $label }}</label>
                        <input type="file" name="documents[{{ $key }}]" accept="image/*,.pdf" class="w-full text-sm">
                    </div>
                @endforeach
            </div>
        </div>

        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg px-5 py-2.5">
            Submit application
        </button>
    </form>
@endsection
