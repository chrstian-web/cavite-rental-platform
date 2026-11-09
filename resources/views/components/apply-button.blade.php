@props(['href'])
<a href="{{ $href }}" {{ $attributes->merge(['class' => 'apply-btn']) }}>
    <span class="apply-btn__label">Apply</span>
    <svg class="apply-btn__arrow" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
</a>
