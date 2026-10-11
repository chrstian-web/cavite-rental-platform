@extends('layouts.app')

@section('title', 'Owner Verification')

@push('head')
<style>
    .upload-tile{display:flex;align-items:center;gap:.75rem;width:100%;min-height:64px;padding:.75rem .9rem;
        border:2px dashed rgba(148,163,184,.55);border-radius:.85rem;background:rgba(148,163,184,.08);
        cursor:pointer;transition:border-color .15s,background .15s,transform .1s;position:relative;
        -webkit-tap-highlight-color:transparent}
    .upload-tile:hover,.upload-tile:focus-within{border-color:#3b82f6;background:rgba(59,130,246,.12)}
    .upload-tile:active{transform:scale(.99)}
    .upload-tile__input{position:absolute;inset:0;width:100%;height:100%;opacity:0;cursor:pointer;font-size:0}
    .upload-tile__icon{flex:none;display:flex;align-items:center;justify-content:center;width:42px;height:42px;
        border-radius:999px;background:rgba(59,130,246,.18);color:#3b82f6}
    .upload-tile__text{flex:1;min-width:0;display:flex;flex-direction:column;gap:2px}
    .upload-tile__title{font-size:.9rem;font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
    .upload-tile__hint{font-size:.75rem;opacity:.6}
    .upload-tile__btn{flex:none;font-size:.8rem;font-weight:600;color:#fff;background:#2563eb;border-radius:.6rem;padding:.5rem .9rem}
    .upload-tile.has-file{border-style:solid;border-color:#22c55e;background:rgba(34,197,94,.10)}
    .upload-tile.has-file .upload-tile__icon{background:rgba(34,197,94,.2);color:#22c55e}
    .upload-tile.has-file .upload-tile__btn{background:#475569}
</style>
@endpush

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
                    <label class="upload-tile" data-upload-tile>
                        <input type="file" name="documents[{{ $type }}]" accept=".jpg,.jpeg,.png,.pdf" class="upload-tile__input">
                        <span class="upload-tile__icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                        </span>
                        <span class="upload-tile__text">
                            <span class="upload-tile__title" data-upload-title>Tap to upload file</span>
                            <span class="upload-tile__hint" data-upload-hint>PDF, JPG or PNG · max 5MB</span>
                        </span>
                        <span class="upload-tile__btn" data-upload-btn>Browse</span>
                    </label>
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
            <div class="mb-4">
                @include('partials.document-viewer', ['items' => \App\Support\DocumentViewerItems::fromVerification($verification->documents, $documentTypes), 'id' => 'verification-docs', 'trigger' => true])
            </div>
            <ul class="text-sm space-y-2">
                @foreach ($verification->documents as $doc)
                    <li class="flex items-center justify-between gap-3">
                        <span class="min-w-0">{{ $documentTypes[$doc->document_type]['label'] ?? $doc->document_type }}</span>
                        <span class="shrink-0 text-xs px-2 py-0.5 rounded-full
                            {{ $doc->status === 'verified' ? 'bg-green-50 text-green-700' : ($doc->status === 'rejected' ? 'bg-red-50 text-red-700' : 'bg-amber-50 text-amber-700') }}">
                            {{ str($doc->status)->title() }}
                        </span>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
@endsection

@push('scripts')
<script>
    document.querySelectorAll('[data-upload-tile]').forEach(function (tile) {
        var input = tile.querySelector('input[type=file]');
        input.addEventListener('change', function () {
            var f = input.files && input.files[0];
            var title = tile.querySelector('[data-upload-title]');
            var hint = tile.querySelector('[data-upload-hint]');
            var btn = tile.querySelector('[data-upload-btn]');
            if (f) {
                tile.classList.add('has-file');
                title.textContent = '✓ ' + f.name;
                hint.textContent = (f.size / 1048576).toFixed(2) + ' MB · tap to change';
                btn.textContent = 'Change';
            } else {
                tile.classList.remove('has-file');
                title.textContent = 'Tap to upload file';
                hint.textContent = 'PDF, JPG or PNG · max 5MB';
                btn.textContent = 'Browse';
            }
        });
    });
</script>
@endpush
