/*
 * Cavite Rentals service worker.
 *  - Pages: always network first, so the app shows your latest code the moment you're online.
 *    Pages are NEVER cached (they contain private data: applications, IDs, payments).
 *  - Static files (CSS/JS/icons/fonts): network first, cached copy only as an offline fallback.
 *  - Offline: shows a friendly offline page instead of the browser's error.
 * Bump VERSION to force every phone to drop its old cache.
 */
const VERSION = 'v2';
const CACHE = 'cavite-rentals-' + VERSION;
const OFFLINE_URL = new URL('offline.html', self.registration.scope).href;
const PRECACHE = [OFFLINE_URL, new URL('icons/icon-192.png', self.registration.scope).href];

self.addEventListener('install', (event) => {
    event.waitUntil(caches.open(CACHE).then((c) => c.addAll(PRECACHE)).then(() => self.skipWaiting()));
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(keys.filter((k) => k.startsWith('cavite-rentals-') && k !== CACHE).map((k) => caches.delete(k))))
            .then(() => self.clients.claim())
    );
});

const isStatic = (url) => /\.(?:css|js|png|jpg|jpeg|svg|webp|ico|woff2?)$/i.test(url.pathname) && !/\/storage\//.test(url.pathname);

self.addEventListener('fetch', (event) => {
    const req = event.request;
    if (req.method !== 'GET') return;

    const url = new URL(req.url);
    if (url.origin !== self.location.origin) return; // CDNs etc. go straight to the network

    // Private documents and payment proofs: never touch the cache.
    if (/\/documents\//.test(url.pathname) || /\/proof/.test(url.pathname)) return;

    if (req.mode === 'navigate') {
        event.respondWith(fetch(req).catch(() => caches.match(OFFLINE_URL)));
        return;
    }

    if (isStatic(url)) {
        event.respondWith(
            fetch(req)
                .then((res) => {
                    if (res.ok) { const copy = res.clone(); caches.open(CACHE).then((c) => c.put(req, copy)); }
                    return res;
                })
                .catch(() => caches.match(req))
        );
    }
});
