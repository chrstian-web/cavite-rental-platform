@extends('layouts.app')

@section('title', 'Review Application')

@section('content')
    <a href="{{ route('owner.applications.index') }}" class="text-sm text-blue-600 hover:underline">&larr; Applications</a>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-4">
        <div class="lg:col-span-2 bg-white border border-slate-200 rounded-xl p-6">
            <h1 class="text-lg font-semibold text-slate-900 mb-1">
                {{ $application->user->first_name }} {{ $application->user->last_name }}
            </h1>
            <p class="text-sm text-slate-500 mb-4">
                Applying for {{ $application->property->name }} — {{ $application->rentalSpace->space_number }}
            </p>

            <dl class="grid grid-cols-2 gap-y-3 text-sm">
                <dt class="text-slate-500">Email</dt><dd class="text-slate-900">{{ $application->user->email }}</dd>
                <dt class="text-slate-500">Phone</dt><dd class="text-slate-900">{{ $application->user->phone ?? '—' }}</dd>
                <dt class="text-slate-500">Desired move-in</dt><dd class="text-slate-900">{{ $application->desired_move_in_date->format('M j, Y') }}</dd>
                <dt class="text-slate-500">Length of stay</dt><dd class="text-slate-900">{{ $application->length_of_stay_months ? $application->length_of_stay_months.' months' : '—' }}</dd>
                <dt class="text-slate-500">Occupants</dt><dd class="text-slate-900">{{ $application->number_of_occupants }}</dd>
                <dt class="text-slate-500">Employment</dt><dd class="text-slate-900">{{ $application->employment_status ? str($application->employment_status)->replace('_',' ')->title() : '—' }}</dd>
                <dt class="text-slate-500">Monthly income</dt><dd class="text-slate-900">{{ $application->monthly_income ? '₱'.number_format($application->monthly_income) : '—' }}</dd>
                <dt class="text-slate-500">Emergency contact</dt>
                <dd class="text-slate-900">
                    {{ $application->emergency_contact_name ?? '—' }}
                    @if ($application->emergency_contact_number) ({{ $application->emergency_contact_number }}) @endif
                </dd>
            </dl>

            @if ($application->notes)
                <div class="mt-4 pt-4 border-t border-slate-100">
                    <p class="text-sm font-medium text-slate-700 mb-1">Notes from applicant</p>
                    <p class="text-sm text-slate-600">{{ $application->notes }}</p>
                </div>
            @endif

            @if ($application->documents->isNotEmpty())
                <div class="mt-4 pt-4 border-t border-slate-100">
                    <p class="text-sm font-medium text-slate-700 mb-2">Submitted documents</p>
                    <ul class="text-sm space-y-1">
                        @foreach ($application->documents as $doc)
                            <li>
                                <a href="{{ route('documents.download', $doc) }}" class="text-blue-600 hover:underline">
                                    {{ str($doc->document_type)->replace('_',' ')->title() }} — {{ $doc->original_filename }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @else
                <p class="mt-4 pt-4 border-t border-slate-100 text-sm text-slate-400">No documents submitted.</p>
            @endif
        </div>

        <div class="bg-white border border-slate-200 rounded-xl p-6">
            <p class="text-sm font-medium text-slate-700 mb-1">Current status</p>
            <span class="text-xs px-2 py-1 rounded-full
                {{ match($application->status) {
                    'approved' => 'bg-green-50 text-green-700',
                    'rejected', 'cancelled' => 'bg-red-50 text-red-700',
                    'under_review' => 'bg-blue-50 text-blue-700',
                    default => 'bg-amber-50 text-amber-700',
                } }}">
                {{ str($application->status)->replace('_', ' ')->title() }}
            </span>

            @if (! in_array($application->status, ['approved', 'rejected', 'cancelled']))
                <form method="POST" action="{{ route('owner.applications.review', $application) }}" class="mt-4 space-y-3">
                    @csrf @method('PATCH')
                    <div>
                        <label class="block text-xs text-slate-500 mb-1">Decision</label>
                        <select name="status" class="w-full rounded-lg border-slate-300 text-sm">
                            <option value="under_review">Mark as Under Review</option>
                            <option value="approved">Approve</option>
                            <option value="rejected">Reject</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-slate-500 mb-1">Note to applicant (optional)</label>
                        <textarea name="decision_reason" rows="3" class="w-full rounded-lg border-slate-300 text-sm"></textarea>
                    </div>
                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg py-2">
                        Save decision
                    </button>
                </form>
            @elseif ($application->status === 'approved')
                <div class="mt-4">
                    @if ($application->contract)
                        <a href="{{ route('owner.contracts.show', $application->contract) }}"
                           class="block text-center bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-medium rounded-lg py-2.5">
                            View contract
                        </a>
                    @else
                        <a href="{{ route('owner.applications.contract.create', $application) }}"
                           class="block text-center bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg py-2.5">
                            Create rental contract
                        </a>
                    @endif
                </div>
            @endif
        </div>
    </div>
@endsection
