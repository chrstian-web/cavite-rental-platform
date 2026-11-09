/* Cavite Rentals landing page.
   1. Property type chips (pick one, or none) and the live result count on the search button.
   2. Price map of Cavite (Leaflet, loaded only when the map is needed).
   If anything fails, the server-rendered city list stays usable. */
(() => {
    'use strict';

    // Pinned versions, both older than two weeks, so the page does not change under you.
    const LEAFLET_VERSION = '1.9.4';
    const LEAFLET_BASE = `https://cdnjs.cloudflare.com/ajax/libs/leaflet/${LEAFLET_VERSION}/`;
    // OpenStreetMap's own tiles need no API key. They are light, so landing.css darkens them to fit the page.
    // For heavy traffic, switch to a hosted provider with a key (MapTiler, Stadia, etc.) by changing only this URL.
    const TILES = 'https://tile.openstreetmap.org/{z}/{x}/{y}.png';
    const ATTRIBUTION = '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors';

    const reduceMotion = matchMedia('(prefers-reduced-motion: reduce)').matches;

    /* ── 1. Live count ── */
    const form = document.querySelector('[data-search]');

    // Property type chips behave like one choice that can also be cleared: pick one, or none to see every type.
    if (form) {
        const boxes = form.querySelectorAll('input[name="type"]');
        boxes.forEach((box) => box.addEventListener('change', () => {
            if (box.checked) boxes.forEach((other) => { if (other !== box) other.checked = false; });
        }));
    }

    if (form && form.dataset.countUrl) {
        const countEl  = form.querySelector('[data-count]');
        const nounEl   = form.querySelector('[data-noun]');
        const statusEl = form.querySelector('[data-status]');
        let timer = null;
        let controller = null;

        const refresh = async () => {
            if (!countEl) return;
            if (controller) controller.abort();
            controller = new AbortController();

            const params = new URLSearchParams();
            new FormData(form).forEach((value, key) => { if (value !== '') params.set(key, value); });

            try {
                const res = await fetch(`${form.dataset.countUrl}?${params}`, {
                    headers: { Accept: 'application/json' },
                    signal: controller.signal,
                });
                if (!res.ok) return;
                const { count } = await res.json();
                countEl.textContent = Number(count).toLocaleString();
                if (nounEl) nounEl.textContent = count === 1 ? 'listing' : 'listings';
                if (statusEl) {
                    statusEl.textContent = count === 0
                        ? 'No listings match these filters yet.'
                        : `${Number(count).toLocaleString()} ${count === 1 ? 'listing matches' : 'listings match'}.`;
                }
            } catch (_) { /* aborted or offline: keep the last number */ }
        };

        const schedule = () => { clearTimeout(timer); timer = setTimeout(refresh, 250); };
        form.addEventListener('change', schedule);
        form.addEventListener('input', schedule);
    }

    /* ── 2. Price map ── */
    const card = document.querySelector('[data-map-card]');
    const mapEl = card && card.querySelector('[data-map]');
    if (!card || !mapEl) return;

    let cities;
    try { cities = JSON.parse(mapEl.dataset.cities || '[]').filter((c) => c.lat != null && c.lng != null); }
    catch (_) { cities = []; }

    const fallback = () => card.classList.add('is-fallback');

    // Phones use the city list: it is faster, and a touch-driven map can trap page scrolling.
    if (!cities.length || matchMedia('(max-width: 640px)').matches) return fallback();

    const loadLeaflet = () => new Promise((resolve, reject) => {
        if (window.L) return resolve(window.L);

        const css = document.createElement('link');
        css.rel = 'stylesheet';
        css.href = `${LEAFLET_BASE}leaflet.min.css`;
        document.head.appendChild(css);

        const js = document.createElement('script');
        js.src = `${LEAFLET_BASE}leaflet.min.js`;
        js.onload = () => resolve(window.L);
        js.onerror = reject;
        document.head.appendChild(js);
    });

    const nf = new Intl.NumberFormat('en-PH');
    const meta = (c) => `${nf.format(c.listings)} ${c.listings === 1 ? 'listing' : 'listings'}${c.label ? ` · from ${c.label}` : ''}`;
    const browse = (c) => `${card.dataset.browse}?location_id=${encodeURIComponent(c.id)}`;

    const selName = card.querySelector('[data-sel-name]');
    const selMeta = card.querySelector('[data-sel-meta]');
    const selLink = card.querySelector('[data-sel-link]');

    const mount = async () => {
        let L;
        try { L = await loadLeaflet(); } catch (_) { return fallback(); }
        if (!L) return fallback();

        const map = L.map(mapEl, {
            zoomControl: true, scrollWheelZoom: false, minZoom: 9, maxZoom: 14, attributionControl: true,
            zoomAnimation: !reduceMotion, fadeAnimation: !reduceMotion, markerZoomAnimation: !reduceMotion,
        });
        map.attributionControl.setPrefix(false);
        L.tileLayer(TILES, { attribution: ATTRIBUTION, maxZoom: 19 }).addTo(map);

        // Wheel zoom stays off so the page can scroll past the map; it switches on once the map has focus.
        map.on('focus click', () => map.scrollWheelZoom.enable());
        map.on('blur mouseout', () => map.scrollWheelZoom.disable());

        const markers = new Map();
        let selected = null;

        const select = (c) => {
            if (selected === c.id) return;
            selected = c.id;
            markers.forEach((m, id) => {
                const el = m.getElement();
                if (el) el.classList.toggle('is-sel', id === c.id);
            });
            if (selName) selName.textContent = c.name;
            if (selMeta) selMeta.textContent = meta(c);
            if (selLink) selLink.href = browse(c);
        };

        cities.forEach((c, i) => {
            const icon = L.divIcon({
                className: 'lp-pin-wrap',
                html: `<span class="lp-pin" style="--i:${i}">${c.label || nf.format(c.listings)}</span>`,
                iconSize: [0, 0],
            });
            const marker = L.marker([c.lat, c.lng], {
                icon,
                keyboard: true,
                title: `${c.name}: ${meta(c)}`,
                alt: `${c.name}, ${meta(c)}`,
                riseOnHover: true,
            }).addTo(map);

            marker.bindTooltip(c.name, { direction: 'top', offset: [0, -14] });
            marker.on('click mouseover', () => select(c));
            marker.on('keypress', (e) => {
                const key = e.originalEvent && e.originalEvent.key;
                if (key === 'Enter' || key === ' ') { select(c); window.location.href = browse(c); }
            });
            markers.set(c.id, marker);
        });

        const bounds = L.latLngBounds(cities.map((c) => [c.lat, c.lng]));
        map.fitBounds(bounds, { padding: [48, 48], maxZoom: 11, animate: false });

        // The selected pin needs its DOM element, which exists once the markers are added.
        selected = null;
        select(cities[0]);

        card.classList.add('is-live');
        requestAnimationFrame(() => map.invalidateSize());
    };

    // Start after the page has loaded and the browser is idle, so the hero text paints first.
    const idle = window.requestIdleCallback || ((fn) => setTimeout(fn, 200));
    const start = () => idle(mount, { timeout: 2000 });
    if (document.readyState === 'complete') start();
    else addEventListener('load', start, { once: true });
})();
