/* Cavite Rentals landing page.
   1. Cursor spotlight on any .lp-spot element (same idea as the "Spotlight" component).
   2. Loads the Spline 3D scene into [data-spline] only when it is safe and worthwhile.
      Everyone else keeps the static mascot fallback that is already in the markup. */
(() => {
    'use strict';

    // Pinned on purpose: a release that is at least two weeks old, so the page does not change under you.
    const VIEWER_SRC = 'https://unpkg.com/@splinetool/viewer@2.0.58/build/spline-viewer.js';

    const reduceMotion = matchMedia('(prefers-reduced-motion: reduce)').matches;
    const finePointer  = matchMedia('(hover: hover) and (pointer: fine)').matches;

    /* ── 1. Spotlight ── */
    if (finePointer && !reduceMotion) {
        document.querySelectorAll('.lp-spot').forEach((el) => {
            el.addEventListener('pointermove', (e) => {
                const r = el.getBoundingClientRect();
                el.style.setProperty('--mx', (e.clientX - r.left) + 'px');
                el.style.setProperty('--my', (e.clientY - r.top) + 'px');
            }, { passive: true });
        });
    }

    /* ── 2. 3D scene ── */
    const stage = document.querySelector('[data-spline]');
    if (!stage || !stage.dataset.scene) return;

    const hasWebGL = () => {
        try {
            const c = document.createElement('canvas');
            return !!(c.getContext('webgl2') || c.getContext('webgl'));
        } catch (_) { return false; }
    };
    const saveData = navigator.connection && navigator.connection.saveData;

    // Phones and tablets: skip it. A 3.5 MB WebGL scene is heavy there, and a touch-driven canvas can trap page scrolling.
    const wide = matchMedia('(min-width: 900px)').matches;
    if (!wide || reduceMotion || saveData || !hasWebGL()) return;

    const idle = window.requestIdleCallback || ((fn) => setTimeout(fn, 400));

    const mount = async () => {
        try {
            await import(/* webpackIgnore: true */ VIEWER_SRC);
        } catch (_) {
            return; // blocked or offline: the fallback stays
        }

        const viewer = document.createElement('spline-viewer');
        viewer.className = 'lp-spline';
        viewer.setAttribute('url', stage.dataset.scene);
        viewer.setAttribute('loading', 'eager');

        viewer.addEventListener('load-complete', () => stage.classList.add('is-live'));
        viewer.addEventListener('context-loss', () => {
            stage.classList.remove('is-live');
            viewer.remove();
        });

        stage.appendChild(viewer);
    };

    // Wait until the page itself has finished, then until the browser is idle.
    const start = () => idle(mount, { timeout: 3000 });
    if (document.readyState === 'complete') start();
    else addEventListener('load', start, { once: true });
})();
