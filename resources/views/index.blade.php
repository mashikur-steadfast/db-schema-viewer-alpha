<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Real-Time Location Tracking</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
        }
        #map {
            height: 400px;
            width: 100%;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>

<h2>Real-Time Location Tracking</h2>
<div id="map"></div>

<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<script>

    const orgId = 1; 
    const eventSource = new EventSource(`/sse/${orgId}`);

    const map = L.map('map').setView([51.505, -0.09], 13); 
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '© OpenStreetMap'
    }).addTo(map);

    const userMarker = L.marker([51.505, -0.09]).addTo(map);

    eventSource.onmessage = (event) => {
        const data = JSON.parse(event.data);
        if (data.message && data.message.lat && data.message.lon) {
            const lat = data.message.lat;
            const lon = data.message.lon;
            userMarker.setLatLng([lat, lon]);
            map.setView([lat, lon], 13);
        }
    };

    eventSource.onerror = (error) => {
        console.error("Error with SSE:", error);
    };

   
</script>

</body>
</html>
