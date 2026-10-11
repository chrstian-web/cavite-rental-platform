{{-- Makes the site installable as the "Cavite Rentals" app and registers its service worker. Include inside <head>. --}}
<link rel="manifest" href="{{ asset('manifest.webmanifest') }}" crossorigin="use-credentials">
<meta name="theme-color" content="#1d4ed8">
<meta name="application-name" content="Cavite Rentals">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="Cavite Rentals">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<link rel="apple-touch-icon" href="{{ asset('icons/apple-touch-icon.png') }}">
<link rel="icon" type="image/png" sizes="64x64" href="{{ asset('icons/favicon-64.png') }}">
@auth
    <script>
        // Installed app + already logged in: skip the marketing home page and go straight to the dashboard.
        (function () {
            var installed = (window.matchMedia && window.matchMedia('(display-mode: standalone)').matches) || window.navigator.standalone;
            if (installed && location.pathname.replace(/\/+$/, '') === @json(rtrim(parse_url(route('home'), PHP_URL_PATH), '/'))) {
                location.replace(@json(route('dashboard')));
            }
        })();
    </script>
@endauth
<script>
    // Service workers need https (or localhost); on plain http the site just keeps working as a normal website.
    if ('serviceWorker' in navigator && (location.protocol === 'https:' || location.hostname === 'localhost')) {
        window.addEventListener('load', function () {
            navigator.serviceWorker.register(@json(asset('sw.js'))).catch(function () {});
        });
    }

    // Small "Install app" button when the browser says the app can be installed (Android / desktop Chrome).
    (function () {
        var deferred = null;
        function standalone() { return window.matchMedia('(display-mode: standalone)').matches || navigator.standalone; }
        window.addEventListener('beforeinstallprompt', function (e) {
            e.preventDefault();
            if (standalone() || sessionStorage.getItem('pwa-dismissed')) return;
            deferred = e;
            var bar = document.createElement('div');
            bar.setAttribute('style', 'position:fixed;left:12px;right:12px;bottom:calc(12px + env(safe-area-inset-bottom,0px));z-index:150;display:flex;align-items:center;gap:10px;' +
                'max-width:420px;margin:0 auto;padding:10px 12px;border-radius:16px;background:#0d1117;color:#fff;box-shadow:0 10px 30px rgba(0,0,0,.35);font:600 14px system-ui,sans-serif;');
            bar.innerHTML = '<img src="{{ asset('icons/icon-96.png') }}" width="36" height="36" style="border-radius:9px" alt="">' +
                '<span style="flex:1">Install Cavite Rentals</span>' +
                '<button id="pwa-install" style="background:#2563eb;color:#fff;border:0;border-radius:10px;padding:8px 14px;font-weight:700">Install</button>' +
                '<button id="pwa-close" aria-label="Dismiss" style="background:transparent;color:#9ca3af;border:0;font-size:20px;line-height:1;padding:4px 6px">&times;</button>';
            document.body.appendChild(bar);
            bar.querySelector('#pwa-install').onclick = function () {
                deferred.prompt();
                deferred.userChoice.then(function () { bar.remove(); deferred = null; });
            };
            bar.querySelector('#pwa-close').onclick = function () { sessionStorage.setItem('pwa-dismissed', '1'); bar.remove(); };
        });
        window.addEventListener('appinstalled', function () { var b = document.getElementById('pwa-install'); if (b) b.parentNode.remove(); });
    })();
</script>
