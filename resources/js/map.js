document.addEventListener('DOMContentLoaded', function () {
    const mapElement = document.getElementById('map');
    if (!mapElement) return;

    // Initialize Leaflet Map
    const map = L.map('map', {
        zoomControl: true,
        scrollWheelZoom: false
    }).setView([14.1676, 120.9329], 10);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    // Force size recalculation after render
    setTimeout(() => {
        map.invalidateSize();
    }, 200);
});
