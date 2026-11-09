{{-- Animated delete button: styles + behaviour. Included once in layouts/app.blade.php. Markup lives in components/delete-button.blade.php (<x-delete-button>). --}}
<style>
    .delete-btn {
        --db-bg: #dc2626;
        --db-divider: #991b1b;
        --db-success: #16a34a;
        --db-ease: cubic-bezier(0.31, -0.105, 0.43, 1.4);
        --db-speed: 0.25s;
        --db-w: 96px;
        --db-h: 40px;
        --db-font: 12px;
        --db-icon: 16px;
        --db-icon-big: 24px;

        position: relative;
        display: block;
        flex-shrink: 0;
        width: var(--db-w);
        height: var(--db-h);
        padding: 0;
        border: 0;
        border-radius: 8px;
        background: var(--db-bg);
        color: #fff;
        font: inherit;
        font-size: var(--db-font);
        font-weight: 600;
        overflow: hidden;
        cursor: pointer;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.18);
        transition: background-color var(--db-speed) var(--db-ease), opacity var(--db-speed) var(--db-ease);
    }
    .delete-btn--sm { --db-w: 88px; --db-h: 36px; }
    .delete-btn--block { --db-w: 100%; --db-h: 44px; --db-font: 14px; --db-icon: 18px; --db-icon-big: 28px; }

    .delete-btn__label,
    .delete-btn__icon {
        position: absolute;
        top: 0;
        height: 100%;
        display: grid;
        place-items: center;
        transition: all var(--db-speed) var(--db-ease);
    }
    .delete-btn__label { left: 0; width: 72%; white-space: nowrap; }
    .delete-btn__label::after {
        content: "";
        position: absolute;
        top: 20%;
        right: -1px;
        width: 2px;
        height: 60%;
        background: var(--db-divider);
    }
    .delete-btn__icon { right: 0; width: 28%; }
    .delete-btn__icon svg {
        width: var(--db-icon);
        height: var(--db-icon);
        fill: none;
        stroke: currentColor;
        stroke-width: 2.5;
        stroke-linecap: round;
        stroke-linejoin: round;
        transition: all var(--db-speed) var(--db-ease);
    }
    .delete-btn__icon .icon-check { display: none; }

    .delete-btn:focus-visible { outline: 3px solid rgba(220, 38, 38, 0.45); outline-offset: 2px; }

    .delete-btn.is-done { background: var(--db-success); pointer-events: none; }
    .delete-btn.is-done .delete-btn__label { left: -72%; opacity: 0; }
    .delete-btn.is-done .delete-btn__icon { width: 100%; }
    .delete-btn.is-done .delete-btn__icon svg { width: var(--db-icon-big); height: var(--db-icon-big); }
    .delete-btn.is-done .icon-x { display: none; }
    .delete-btn.is-done .icon-check { display: block; }

    @media (hover: hover) {
        .delete-btn:hover { opacity: 0.92; }
        .delete-btn:hover .delete-btn__label { left: -72%; opacity: 0; }
        .delete-btn:hover .delete-btn__icon { width: 100%; }
        .delete-btn:hover .delete-btn__icon svg { width: var(--db-icon-big); height: var(--db-icon-big); }
        .delete-btn:active { opacity: 1; }
    }

    @media (prefers-reduced-motion: reduce) {
        .delete-btn, .delete-btn__label, .delete-btn__icon, .delete-btn__icon svg { transition: none; }
    }
</style>
<script>
    document.addEventListener('submit', function (e) {
        if (e.defaultPrevented) return;
        var btn = e.target.querySelector && e.target.querySelector('.delete-btn');
        if (btn) btn.classList.add('is-done');
    });
    window.addEventListener('pageshow', function () {
        document.querySelectorAll('.delete-btn.is-done').forEach(function (b) { b.classList.remove('is-done'); });
    });
</script>
