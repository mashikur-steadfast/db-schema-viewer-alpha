<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Send Location</title>
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
        #location-details {
            margin-bottom: 20px;
        }
    </style>
</head>
<body>

<h2>Select and Send Location</h2>
<div id="map"></div>


<div id="location-details">
    <p id="selected-location">Click on the map to select a location.</p>
</div>


<a href="/">Back to Map</a>

<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<script>

    const map = L.map('map').setView([51.505, -0.09], 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '© OpenStreetMap'
    }).addTo(map);

    const userMarker = L.marker([51.505, -0.09]).addTo(map);

    map.on('click', function(e) {
        const lat = e.latlng.lat;
        const lon = e.latlng.lng;

 
        userMarker.setLatLng([lat, lon]);
        map.setView([lat, lon], 13);
        document.getElementById('selected-location').textContent = `Selected Location: Latitude: ${lat.toFixed(6)}, Longitude: ${lon.toFixed(6)}`;


        fetch(`/send-location-api?lat=${lat}&lon=${lon}`)
            .then(response => response.json())
            .then(data => {
                console.log("Location sent to backend:", data);
            })
            .catch(err => console.error("Error sending location:", err));
    });
</script>

</body>
</html>
