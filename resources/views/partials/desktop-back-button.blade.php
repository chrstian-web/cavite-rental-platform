@php
    $fallbackUrl = $fallbackUrl ?? url('/');
@endphp

<div class="hidden md:block max-w-6xl mx-auto px-4 pt-5">
    <button
        type="button"
        onclick="if (window.history.length > 1) { window.history.back(); } else { window.location.href = @js($fallbackUrl); }"
        class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-600 shadow-sm transition hover:border-blue-300 hover:bg-blue-50 hover:text-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
        aria-label="Go back to the previous page"
    >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path fill-rule="evenodd" d="M17 10a.75.75 0 01-.75.75H5.56l3.22 3.22a.75.75 0 11-1.06 1.06l-4.5-4.5a.75.75 0 010-1.06l4.5-4.5a.75.75 0 111.06 1.06l-3.22 3.22h10.69A.75.75 0 0117 10z" clip-rule="evenodd" />
        </svg>
        Back
    </button>
</div>
