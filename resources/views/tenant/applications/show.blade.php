@extends('layouts.app')

@section('title', 'Application — '.$application->property->name)

@section('content')
    <a href="{{ route('tenant.applications.index') }}" class="text-sm text-blue-600 hover:underline">&larr; My Applications</a>

    <div class="bg-white border border-slate-200 rounded-xl p-6 mt-4 max-w-2xl">
        <div class="flex items-center justify-between mb-4">
            <h1 class="text-lg font-semibold text-slate-900">{{ $application->property->name }} — {{ $application->rentalSpace->space_number }}</h1>
            <span class="text-xs px-2 py-1 rounded-full
                {{ match($application->status) {
                    'approved' => 'bg-green-50 text-green-700',
                    'rejected', 'cancelled' => 'bg-red-50 text-red-700',
                    'under_review' => 'bg-blue-50 text-blue-700',
                    default => 'bg-amber-50 text-amber-700',
                } }}">
                {{ str($application->status)->replace('_', ' ')->title() }}
            </span>
        </div>

        @if ($application->decision_reason)
            <div class="mb-4 text-sm bg-slate-50 border border-slate-200 rounded-lg p-3 text-slate-600">
                <strong>Note from the owner:</strong> {{ $application->decision_reason }}
            </div>
        @endif

        <dl class="grid grid-cols-2 gap-y-2 text-sm">
            <dt class="text-slate-500">Desired move-in</dt>
            <dd class="text-slate-900">{{ $application->desired_move_in_date->format('M j, Y') }}</dd>
            <dt class="text-slate-500">Occupants</dt>
            <dd class="text-slate-900">{{ $application->number_of_occupants }}</dd>
            <dt class="text-slate-500">Employment</dt>
            <dd class="text-slate-900">{{ $application->employment_status ? str($application->employment_status)->replace('_',' ')->title() : '—' }}</dd>
        </dl>

        @if ($application->documents->isNotEmpty())
            <div class="mt-4 pt-4 border-t border-slate-100">
                <p class="text-sm font-medium text-slate-700 mb-2">Your documents</p>
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
        @endif
    </div>
@endsection
