{{--
    Upload box used on the application form.
    Params: $key (document type), $label, $when (JS expression: show/require only when true)
--}}
@php $inputId = 'doc_'.$key; @endphp
<div x-data="{ file: '' }" class="min-w-0">
    <label for="{{ $inputId }}" class="block text-sm font-medium text-slate-700 mb-1.5">
        {{ $label }} <span class="text-rose-600" aria-hidden="true">*</span>
    </label>

    <label for="{{ $inputId }}" class="rt-drop flex items-center gap-3 cursor-pointer rounded-xl border-2 border-dashed border-slate-300 px-4 py-3.5 transition">
        <svg class="w-6 h-6 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" />
        </svg>
        <span class="min-w-0 flex-1">
            <span class="block text-sm text-slate-700 truncate" x-text="file || 'Click to upload'">Click to upload</span>
            <span class="block text-xs text-slate-400">JPG, PNG or PDF · max 5 MB</span>
        </span>
        <span x-show="file" x-cloak class="text-xs font-bold text-green-600 shrink-0">&#10003; Attached</span>
    </label>

    <input id="{{ $inputId }}" type="file" name="documents[{{ $key }}]" class="sr-only"
           accept=".jpg,.jpeg,.png,.pdf,image/jpeg,image/png,application/pdf"
           :disabled="!({{ $when }})" :required="{{ $when }}"
           @change="
               const f = $event.target.files[0];
               if (f && f.size > 5 * 1024 * 1024) { alert('That file is larger than 5 MB. Please choose a smaller one.'); $event.target.value = ''; file = ''; return; }
               file = f ? f.name : '';
           ">

    @error('documents.'.$key)
        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
    @enderror
</div>
