<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Suivi des chauffeurs - IzyCab</title>

    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.3/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.3/dist/leaflet.js"></script>

    <!-- MQTT.js -->
    <script src="https://unpkg.com/mqtt/dist/mqtt.min.js"></script>

    <style>
        body, html { margin: 0; padding: 0; height: 100%; }
        #map { width: 100%; height: 100vh; }
        .leaflet-popup-content { font-size: 14px; }
    </style>
</head>

<body>
    <div id="map"></div>

    <script>
        // Initialisation de la carte Leaflet
        const map = L.map('map').setView([5.345, -4.012], 13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
        }).addTo(map);

        // Stocke les marqueurs des chauffeurs
        const drivers = {};

        // Connexion au broker MQTT (Mosquitto local)
        const client = mqtt.connect('ws://localhost:9001'); // port WebSocket

        client.on('connect', () => {
            console.log('✅ Connecté au broker MQTT');
            client.subscribe('izycab/drivers/+/location');
        });

        client.on('message', (topic, message) => {
            const data = JSON.parse(message.toString());
            const id = data.driver_id;
            const lat = data.latitude;
            const lng = data.longitude;

            if (!drivers[id]) {
                // Nouveau chauffeur → créer un marqueur
                const marker = L.marker([lat, lng]).addTo(map)
                    .bindPopup(`<b>Chauffeur ${id}</b><br>Lat: ${lat}<br>Lng: ${lng}`);
                drivers[id] = marker;
            } else {
                // Déplacer le marqueur existant
                drivers[id].setLatLng([lat, lng]);
                drivers[id].setPopupContent(`<b>Chauffeur ${id}</b><br>Lat: ${lat}<br>Lng: ${lng}`);
            }
        });

        client.on('error', (err) => {
            console.error('❌ Erreur MQTT:', err);
        });
    </script>
</body>
</html>
