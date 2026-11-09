@props(['label' => 'Delete', 'size' => 'md'])
<button {{ $attributes->merge(['type' => 'submit', 'class' => 'delete-btn delete-btn--'.$size]) }}>
    <span class="delete-btn__label">{{ $label }}</span>
    <span class="delete-btn__icon" aria-hidden="true">
        <svg class="icon-x" viewBox="0 0 24 24" focusable="false"><path d="M6 6l12 12M18 6L6 18"/></svg>
        <svg class="icon-check" viewBox="0 0 24 24" focusable="false"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg>
    </span>
</button>
