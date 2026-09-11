@extends('layouts.app')

@section('title', 'Review Owner Verification')

@section('content')
    <a href="{{ route('admin.owner-verifications.index') }}" class="back-link"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" /></svg><span>Owner Verifications</span></a>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-4">
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white border border-slate-200 rounded-xl p-6">
                <h1 class="text-lg font-semibold text-slate-900 mb-1">
                    {{ $verification->user->first_name }} {{ $verification->user->last_name }}
                </h1>
                <p class="text-sm text-slate-500 mb-4">{{ $verification->user->email }} &middot; Submitted {{ $verification->submitted_at->format('M j, Y g:i A') }}</p>

                <p class="text-sm font-medium text-slate-700 mb-2">Submitted documents</p>
                <div class="space-y-2">
                    @foreach ($verification->documents as $doc)
                        <div class="flex items-center justify-between border border-slate-100 rounded-lg p-3 text-sm">
                            <div>
                                <p class="text-slate-900">{{ str($doc->document_type)->replace('_',' ')->title() }}</p>
                                <p class="text-xs text-slate-400">
                                    {{ $doc->original_filename }}
                                    @if ($doc->expiration_date) &middot; expires {{ $doc->expiration_date->format('M j, Y') }} @endif
                                </p>
                            </div>
                            <a href="{{ route('verification-documents.download', $doc) }}" class="text-blue-600 hover:underline text-xs">View Document</a>
                        </div>
                    @endforeach
                </div>

                @if ($verification->admin_notes)
                    <div class="mt-4 pt-4 border-t border-slate-100">
                        <p class="text-sm font-medium text-slate-700">Notes on this submission</p>
                        <p class="text-sm text-slate-500 mt-1">{{ $verification->admin_notes }}</p>
                    </div>
                @endif
            </div>

            @if ($history->isNotEmpty())
                <div class="bg-white border border-slate-200 rounded-xl p-6">
                    <p class="text-sm font-medium text-slate-700 mb-3">Previous verification attempts</p>
                    <ul class="text-sm space-y-2">
                        @foreach ($history as $past)
                            <li class="flex items-center justify-between text-slate-600">
                                <span>{{ $past->submitted_at->format('M j, Y') }}</span>
                                <span class="text-xs px-2 py-0.5 rounded-full bg-slate-100">{{ str($past->status)->replace('_',' ')->title() }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>

        <div class="bg-white border border-slate-200 rounded-xl p-6">
            <p class="text-sm font-medium text-slate-700 mb-1">Current status</p>
            <span class="text-xs px-2 py-1 rounded-full
                {{ match($verification->status) {
                    'approved' => 'bg-green-50 text-green-700',
                    'rejected' => 'bg-red-50 text-red-700',
                    'needs_additional_documents' => 'bg-amber-50 text-amber-700',
                    default => 'bg-blue-50 text-blue-700',
                } }}">
                {{ str($verification->status)->replace('_',' ')->title() }}
            </span>

            @if ($verification->status === 'needs_review')
                <div class="mt-4 space-y-3">
                    <form method="POST" action="{{ route('admin.owner-verifications.approve', $verification) }}">
                        @csrf @method('PATCH')
                        <textarea name="admin_notes" rows="2" placeholder="Optional note" class="w-full rounded-lg border-slate-300 text-sm mb-2"></textarea>
                        <button class="w-full bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg py-2">Approve</button>
                    </form>

                    <form method="POST" action="{{ route('admin.owner-verifications.request-more', $verification) }}">
                        @csrf @method('PATCH')
                        <textarea name="admin_notes" rows="2" placeholder="What's missing? (required)" required class="w-full rounded-lg border-slate-300 text-sm mb-2"></textarea>
                        <button class="w-full bg-amber-500 hover:bg-amber-600 text-white text-sm font-medium rounded-lg py-2">Request Additional Documents</button>
                    </form>

                    <form method="POST" action="{{ route('admin.owner-verifications.reject', $verification) }}">
                        @csrf @method('PATCH')
                        <textarea name="rejection_reason" rows="2" placeholder="Reason for rejection (required)" required class="w-full rounded-lg border-slate-300 text-sm mb-2"></textarea>
                        <button class="w-full bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg py-2">Reject</button>
                    </form>
                </div>
            @else
                <p class="text-xs text-slate-400 mt-3">
                    This submission has already been reviewed
                    @if ($verification->reviewer) by {{ $verification->reviewer->first_name }} {{ $verification->reviewer->last_name }} @endif
                    on {{ $verification->reviewed_at?->format('M j, Y') }}.
                </p>
            @endif
        </div>
    </div>
@endsection
