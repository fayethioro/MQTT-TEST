<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>IzyCab Tracking (simple)</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.3/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.3/dist/leaflet.js"></script>
    <style>
        body, html { margin: 0; padding: 0; height: 100%; }
        #map { width: 100%; height: 100vh; }
    </style>
</head>
<body>
    <div id="map"></div>
    <script>
        const map = L.map('map').setView([14.6928, -17.4467], 13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
        }).addTo(map);

        const drivers = {};

        async function fetchDrivers() {
            try {
                const response = await fetch('/api/drivers');
                const data = await response.json();

                data.forEach(d => {
                    const id = d.driver_id;
                    const lat = d.latitude;
                    const lng = d.longitude;

                    if (!drivers[id]) {
                        const marker = L.marker([lat, lng]).addTo(map)
                            .bindPopup(`<b>Chauffeur ${id}</b><br>Lat: ${lat}<br>Lng: ${lng}`);
                        drivers[id] = marker;
                    } else {
                        drivers[id].setLatLng([lat, lng]);
                        drivers[id].setPopupContent(`<b>Chauffeur ${id}</b><br>Lat: ${lat}<br>Lng: ${lng}`);
                    }
                });
            } catch (e) {
                console.error('Erreur API:', e);
            }
        }

        // ⏱️ Rafraîchissement toutes les 3 secondes
        setInterval(fetchDrivers, 10000);
        fetchDrivers();
    </script>
</body>
</html>
