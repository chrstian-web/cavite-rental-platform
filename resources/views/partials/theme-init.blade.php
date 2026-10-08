{{-- First thing inside <body>: apply a saved "light" choice before first paint (no flash). --}}
<script>try { if (localStorage.getItem('renter-theme') === 'light') document.body.classList.remove('renter-dark'); } catch (e) {}</script>
