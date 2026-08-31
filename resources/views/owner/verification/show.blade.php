@extends('layouts.app')

@section('title', 'Owner Verification')

@section('content')
    <h1 class="text-xl font-semibold text-slate-900 mb-1">Owner Verification</h1>
    <p class="text-sm text-slate-500 mb-6">
        Before you can list and manage properties, we need to verify your ownership/business information.
    </p>

    {{-- Progress checklist --}}
    @php
        $status = $owner->owner_verification_status;
        $steps = [
            'account' => true, // always true if you're seeing this page
            'submitted' => in_array($status, ['needs_review', 'needs_additional_documents', 'rejected', 'approved', 'verified']),
            'review' => in_array($status, ['approved', 'verified']),
            'active' => $status === 'verified' || $status === 'approved',
        ];
    @endphp
    <div class="bg-white border border-slate-200 rounded-xl p-5 mb-6">
        <ul class="space-y-2 text-sm">
            <li class="flex items-center gap-2"><span class="{{ $steps['account'] ? 'text-green-600' : 'text-slate-300' }}">{{ $steps['account'] ? '✓' : '○' }}</span> Account Created</li>
            <li class="flex items-center gap-2"><span class="{{ $steps['submitted'] ? 'text-green-600' : 'text-slate-300' }}">{{ $steps['submitted'] ? '✓' : '○' }}</span> Documents Submitted</li>
            <li class="flex items-center gap-2"><span class="{{ $steps['review'] ? 'text-green-600' : ($steps['submitted'] ? 'text-amber-500' : 'text-slate-300') }}">{{ $steps['review'] ? '✓' : ($steps['submitted'] ? '●' : '○') }}</span> Verification Review</li>
            <li class="flex items-center gap-2"><span class="{{ $steps['active'] ? 'text-green-600' : 'text-slate-300' }}">{{ $steps['active'] ? '✓' : '○' }}</span> Owner Account Activated</li>
        </ul>
    </div>

    {{-- Current status message --}}
    <div class="bg-white border border-slate-200 rounded-xl p-5 mb-6">
        <p class="text-sm font-medium text-slate-700 mb-1">Status</p>
        @switch($status)
            @case('not_submitted')
                <p class="text-sm text-slate-600">You haven't submitted your verification documents yet. Upload them below to get started.</p>
                @break
            @case('needs_review')
                <p class="text-sm text-amber-600">Your documents are currently being reviewed. We'll notify you once verification is complete.</p>
                @break
            @case('needs_additional_documents')
                <p class="text-sm text-amber-600">We need a bit more information before we can verify your account.</p>
                @if ($verification?->admin_notes)
                    <p class="text-sm text-slate-500 mt-1 bg-slate-50 rounded-lg p-3">{{ $verification->admin_notes }}</p>
                @endif
                @break
            @case('rejected')
                <p class="text-sm text-red-600">Your verification was not approved.</p>
                @if ($verification?->rejection_reason)
                    <p class="text-sm text-slate-500 mt-1 bg-slate-50 rounded-lg p-3">{{ $verification->rejection_reason }}</p>
                @endif
                <p class="text-sm text-slate-500 mt-2">You can review the requirements and resubmit below.</p>
                @break
            @case('approved')
            @case('verified')
                <p class="text-sm text-green-600">Your account is fully verified. You have full access to owner features.</p>
                @break
            @default
                <p class="text-sm text-slate-500">—</p>
        @endswitch
    </div>

    @if ($canSubmit)
        <form method="POST" action="{{ route('owner.verification.store') }}" enctype="multipart/form-data"
              class="bg-white border border-slate-200 rounded-xl p-6 space-y-5 max-w-2xl">
            @csrf

            @foreach ($documentTypes as $type => $config)
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">
                        {{ $config['label'] }}
                        @if ($config['required'])
                            <span class="text-red-500">*</span>
                        @else
                            <span class="text-xs text-slate-400 font-normal">(optional)</span>
                        @endif
                    </label>
                    <input type="file" name="documents[{{ $type }}]" accept=".jpg,.jpeg,.png,.pdf" class="w-full text-sm">
                    @error("documents.{$type}") <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
            @endforeach

            <p class="text-xs text-slate-400">Accepted file types: PDF, JPG, PNG. Maximum 5MB per file.</p>

            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg px-5 py-2.5">
                Submit for Verification
            </button>
        </form>
    @elseif ($verification)
        <div class="bg-white border border-slate-200 rounded-xl p-6 max-w-2xl">
            <p class="text-sm font-medium text-slate-700 mb-3">Submitted documents</p>
            <ul class="text-sm space-y-1">
                @foreach ($verification->documents as $doc)
                    <li class="flex items-center justify-between">
                        <span>{{ $documentTypes[$doc->document_type]['label'] ?? $doc->document_type }} — {{ $doc->original_filename }}</span>
                        <span class="text-xs px-2 py-0.5 rounded-full
                            {{ $doc->status === 'verified' ? 'bg-green-50 text-green-700' : ($doc->status === 'rejected' ? 'bg-red-50 text-red-700' : 'bg-amber-50 text-amber-700') }}">
                            {{ str($doc->status)->title() }}
                        </span>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
@endsection
