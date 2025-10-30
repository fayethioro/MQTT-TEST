<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suivi des chauffeurs - IzyCab</title>

    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.3/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.3/dist/leaflet.js"></script>

    <!-- MQTT.js -->
    <script src="https://unpkg.com/mqtt/dist/mqtt.min.js"></script>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body, html {
            height: 100%;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        #map {
            width: 100%;
            height: 100vh;
        }

        .info-panel {
            position: fixed;
            top: 10px;
            right: 10px;
            background: white;
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
            z-index: 1000;
            max-width: 300px;
            font-size: 14px;
        }

        .status {
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 8px 0;
        }

        .status-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }

        .status-dot.connected {
            background: #22c55e;
        }

        .status-dot.disconnected {
            background: #ef4444;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        .driver-count {
            font-weight: bold;
            color: #3b82f6;
        }

        .leaflet-popup-content {
            font-size: 13px;
            line-height: 1.5;
        }

        .popup-header {
            font-weight: bold;
            color: #1f2937;
            margin-bottom: 5px;
        }

        .popup-info {
            color: #666;
        }

        .popup-speed {
            color: #d97706;
            font-weight: 500;
        }

        /* Personnaliser les marqueurs */
        .driver-marker {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #3b82f6;
            border: 3px solid white;
            box-shadow: 0 2px 8px rgba(0,0,0,0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 16px;
        }
    </style>
</head>

<body>
    <div id="map"></div>

    <!-- Panel d'information -->
    <div class="info-panel">
        <div class="status">
            <div class="status-dot connected" id="statusDot"></div>
            <span id="connectionStatus">Connexion...</span>
        </div>
        <div>Chauffeurs en ligne: <span class="driver-count" id="driverCount">0</span></div>
        <div style="margin-top: 10px; font-size: 12px; color: #999;">
            Topic: <code>izycab/drivers/+/location</code>
        </div>
    </div>

    <script>
        // Configuration MQTT depuis Laravel
        const mqttConfig = {
            host: '{{ $mqttHost ?? "127.0.0.1" }}',
            port: '{{ $mqttPort ?? 9001 }}',
            // Si WebSockets : wss://votre_host:9001
            // Si MQTT direct : mqtt://votre_host:1883
        };

        console.log('🔧 Configuration MQTT:', mqttConfig);

        // Initialisation de la carte Leaflet
        const map = L.map('map').setView([14.6928, -17.4467], 13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors',
            maxZoom: 19,
        }).addTo(map);

        // Stockage des marqueurs et données des chauffeurs
        const drivers = {};
        let mqttConnected = false;

        // Connexion MQTT (WebSocket)
        const client = mqtt.connect(`ws://${mqttConfig.host}:${mqttConfig.port}`, {
            reconnectPeriod: 5000,
            clientId: `izycab_dashboard_${Math.random().toString(36).substr(2, 9)}`,
        });

        // ===== ÉVÉNEMENTS MQTT =====

        client.on('connect', () => {
            console.log('✅ Connecté au broker MQTT');
            mqttConnected = true;
            updateConnectionStatus(true);
            client.subscribe('izycab/drivers/+/location', (err) => {
                if (!err) {
                    console.log('📡 Abonné au topic: izycab/drivers/+/location');
                } else {
                    console.error('❌ Erreur subscribe:', err);
                }
            });
        });

        client.on('message', (topic, message) => {
            try {
                const data = JSON.parse(message.toString());
                const driverId = data.driver_id;
                const lat = parseFloat(data.latitude);
                const lng = parseFloat(data.longitude);
                const speed = data.speed ?? 0;
                const accuracy = data.accuracy ?? null;
                const timestamp = data.timestamp ?? new Date().toISOString();

                // Valider les coordonnées
                if (lat < -90 || lat > 90 || lng < -180 || lng > 180) {
                    console.warn('⚠️ Coordonnées invalides:', { lat, lng });
                    return;
                }

                updateDriverLocation(driverId, lat, lng, speed, accuracy, timestamp);
                updateDriverCount();
            } catch (err) {
                console.error('❌ Erreur parsing MQTT:', err, message.toString());
            }
        });

        client.on('disconnect', () => {
            console.log('❌ Déconnecté du broker MQTT');
            mqttConnected = false;
            updateConnectionStatus(false);
        });

        client.on('offline', () => {
            console.log('⚠️ Client offline');
            mqttConnected = false;
            updateConnectionStatus(false);
        });

        client.on('error', (err) => {
            console.error('❌ Erreur MQTT:', err);
            updateConnectionStatus(false);
        });

        client.on('reconnect', () => {
            console.log('🔄 Tentative de reconnexion MQTT...');
        });

        // ===== FONCTIONS UTILITAIRES =====

        function updateDriverLocation(driverId, lat, lng, speed, accuracy, timestamp) {
            const shortId = driverId.substring(0, 8).toUpperCase();

            if (!drivers[driverId]) {
                // Nouveau chauffeur
                createDriverMarker(driverId, shortId, lat, lng, speed, accuracy, timestamp);
            } else {
                // Mettre à jour le chauffeur existant
                updateExistingMarker(driverId, lat, lng, speed, accuracy, timestamp);
            }
        }

        function createDriverMarker(driverId, shortId, lat, lng, speed, accuracy, timestamp) {
            // Créer une icône personnalisée
            const icon = L.divIcon({
                className: 'driver-marker',
                html: shortId.charAt(0),
            });

            const marker = L.marker([lat, lng], { icon })
                .addTo(map)
                .bindPopup(generatePopupContent(driverId, shortId, lat, lng, speed, accuracy, timestamp), {
                    maxWidth: 300,
                });

            drivers[driverId] = {
                marker: marker,
                lat: lat,
                lng: lng,
                speed: speed,
                accuracy: accuracy,
                timestamp: timestamp,
                lastUpdate: new Date(),
            };

            console.log(`✅ Nouveau chauffeur: ${shortId} (${lat}, ${lng})`);
        }

        function updateExistingMarker(driverId, lat, lng, speed, accuracy, timestamp) {
            const driver = drivers[driverId];
            const shortId = driverId.substring(0, 8).toUpperCase();

            // Animer le déplacement du marqueur
            driver.marker.setLatLng([lat, lng]);
            driver.marker.setPopupContent(generatePopupContent(driverId, shortId, lat, lng, speed, accuracy, timestamp));

            // Mettre à jour les données
            driver.lat = lat;
            driver.lng = lng;
            driver.speed = speed;
            driver.accuracy = accuracy;
            driver.timestamp = timestamp;
            driver.lastUpdate = new Date();
        }

        function generatePopupContent(driverId, shortId, lat, lng, speed, accuracy, timestamp) {
            const speedDisplay = speed ? `${Math.round(speed)} km/h` : '0 km/h';
            const accuracyDisplay = accuracy ? `±${Math.round(accuracy)}m` : 'N/A';
            const timeDisplay = new Date(timestamp).toLocaleTimeString('fr-FR');

            return `
                <div class="popup-header">Chauffeur ${shortId}</div>
                <div class="popup-info">
                    <div>📍 Lat: ${lat.toFixed(4)}</div>
                    <div>📍 Lng: ${lng.toFixed(4)}</div>
                    <div class="popup-speed">⚡ ${speedDisplay}</div>
                    <div>📡 Précision: ${accuracyDisplay}</div>
                    <div>🕐 ${timeDisplay}</div>
                </div>
            `;
        }

        function updateConnectionStatus(connected) {
            const statusDot = document.getElementById('statusDot');
            const statusText = document.getElementById('connectionStatus');

            if (connected) {
                statusDot.classList.remove('disconnected');
                statusDot.classList.add('connected');
                statusText.textContent = '✅ Connecté';
                statusText.style.color = '#22c55e';
            } else {
                statusDot.classList.remove('connected');
                statusDot.classList.add('disconnected');
                statusText.textContent = '❌ Déconnecté';
                statusText.style.color = '#ef4444';
            }
        }

        function updateDriverCount() {
            const count = Object.keys(drivers).length;
            document.getElementById('driverCount').textContent = count;
        }

        // ===== NETTOYAGE =====
        window.addEventListener('beforeunload', () => {
            client.end();
        });
    </script>
</body>
</html>
