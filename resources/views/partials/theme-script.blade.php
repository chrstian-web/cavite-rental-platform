<script>
    (function () {
        var btn = document.getElementById('theme-toggle');
        if (!btn) return;
        function paint() {
            var dark = document.body.classList.contains('renter-dark');
            btn.querySelector('[data-icon="sun"]').style.display = dark ? '' : 'none';
            btn.querySelector('[data-icon="moon"]').style.display = dark ? 'none' : '';
        }
        btn.addEventListener('click', function () {
            var dark = document.body.classList.toggle('renter-dark');
            try { localStorage.setItem('renter-theme', dark ? 'dark' : 'light'); } catch (e) {}
            paint();
        });
        paint();
    })();
</script>
