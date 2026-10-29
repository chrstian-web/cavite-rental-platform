{{-- Button effects, included once per layout. (1) Every POST/PATCH/PUT submit button shows a spinner and blocks double-submits.
     (2) Forms marked data-fav-toggle play a heart pop before submitting. Opt out of (1) with data-no-loading on the button. --}}
<style>
    .is-loading { color: transparent !important; text-shadow: none !important; pointer-events: none; cursor: progress; }
    .is-loading::after {
        content: "";
        position: absolute;
        top: 50%;
        left: 50%;
        width: 1.1em;
        height: 1.1em;
        margin: -0.55em 0 0 -0.55em;
        border: 2px solid var(--ld-color, currentColor);
        border-right-color: transparent;
        border-radius: 50%;
        animation: ld-spin 0.7s linear infinite;
    }
    @keyframes ld-spin { to { transform: rotate(360deg); } }

    .apply-btn {
        position: relative;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        margin-top: 8px;
        padding: 8px 14px;
        border-radius: 9999px;
        background: #2563eb;
        color: #fff !important;
        font-size: 12px;
        font-weight: 600;
        line-height: 1;
        text-decoration: none !important;
        overflow: hidden;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.2);
        transition: transform 0.2s cubic-bezier(0.31, -0.105, 0.43, 1.4), box-shadow 0.2s ease, background-color 0.2s ease;
    }
    .apply-btn::before {
        content: "";
        position: absolute;
        top: 0;
        left: -60%;
        width: 40%;
        height: 100%;
        background: linear-gradient(100deg, transparent, rgba(255, 255, 255, 0.4), transparent);
        transform: skewX(-20deg);
        transition: left 0.55s ease;
    }
    .apply-btn__arrow {
        width: 14px;
        height: 14px;
        fill: none;
        stroke: currentColor;
        stroke-width: 2.5;
        stroke-linecap: round;
        stroke-linejoin: round;
        transition: transform 0.25s cubic-bezier(0.31, -0.105, 0.43, 1.4);
    }
    .apply-btn:active { transform: translateY(0) scale(0.97); box-shadow: 0 1px 2px rgba(15, 23, 42, 0.2); }
    .apply-btn:focus-visible { outline: 3px solid rgba(37, 99, 235, 0.45); outline-offset: 2px; }
    @media (hover: hover) {
        .apply-btn:hover { background: #1d4ed8; transform: translateY(-1px); box-shadow: 0 6px 14px rgba(37, 99, 235, 0.4); }
        .apply-btn:hover::before { left: 130%; }
        .apply-btn:hover .apply-btn__arrow { transform: translateX(4px); }
    }

    .fav-heart { display: inline-block; transform-origin: center; }
    .fav-heart.is-popping { animation: fav-pop 0.32s cubic-bezier(0.31, -0.105, 0.43, 1.4); }
    @keyframes fav-pop { 0% { transform: scale(1); } 35% { transform: scale(1.5); } 65% { transform: scale(0.9); } 100% { transform: scale(1); } }

    @media (prefers-reduced-motion: reduce) {
        .is-loading::after { animation-duration: 2s; }
        .fav-heart.is-popping { animation: none; }
        .apply-btn, .apply-btn::before, .apply-btn__arrow { transition: none; }
    }
</style>
<script>
    (function () {
        var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        document.addEventListener('submit', function (e) {
            if (e.defaultPrevented) return;
            var form = e.target;
            if (!form || form.tagName !== 'FORM') return;

            if (form.hasAttribute('data-fav-toggle')) {
                var heart = form.querySelector('.fav-heart');
                if (!heart || reduce || form.dataset.popping) return;
                e.preventDefault();
                form.dataset.popping = '1';
                var glyph = heart.textContent.trim();
                if (glyph === '\u2661') { heart.textContent = '\u2665'; heart.classList.remove('text-slate-400'); heart.classList.add('text-red-500'); }
                else if (glyph === '\u2665') { heart.textContent = '\u2661'; heart.classList.remove('text-red-500'); heart.classList.add('text-slate-400'); }
                heart.classList.add('is-popping');
                setTimeout(function () { form.submit(); }, 320);
                return;
            }

            if ((form.method || '').toLowerCase() === 'get') return;
            if (form.dataset.submitting) { e.preventDefault(); return; }

            var btn = e.submitter;
            if (!btn || btn.tagName !== 'BUTTON' || btn.classList.contains('delete-btn') || btn.hasAttribute('data-no-loading')) return;

            form.dataset.submitting = '1';
            var cs = getComputedStyle(btn);
            btn.style.setProperty('--ld-color', cs.color);
            if (cs.position === 'static') btn.style.position = 'relative';
            btn.setAttribute('aria-busy', 'true');
            btn.classList.add('is-loading');

            setTimeout(reset, 60000);
            function reset() {
                delete form.dataset.submitting;
                btn.classList.remove('is-loading');
                btn.removeAttribute('aria-busy');
            }
        });

        window.addEventListener('pageshow', function (e) {
            if (!e.persisted) return;
            document.querySelectorAll('.is-loading').forEach(function (b) { b.classList.remove('is-loading'); b.removeAttribute('aria-busy'); });
            document.querySelectorAll('form[data-submitting], form[data-popping]').forEach(function (f) { delete f.dataset.submitting; delete f.dataset.popping; });
        });
    })();
</script>
