<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <title>Leaflet Draw Test</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet-draw/dist/leaflet.draw.css" />

    <style>
        #map { height: 90vh; width: 100%; }
        body { margin: 0; padding: 0; font-family: Arial, sans-serif; }
        #controls { padding: 10px; background: #f4f4f4; }
        #controls input { padding: 5px; font-size: 16px; }
        #controls button { padding: 6px 10px; font-size: 16px; margin-left: 5px; }
    </style>
</head>
<body>

<div id="controls">
    <input type="text" id="name" placeholder="Enter your name" />
    <button id="saveDraw">Finish & Save</button>
</div>

<div id="map"></div>

<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet-draw/dist/leaflet.draw.js"></script>

<script>
    // Initialize map
    const map = L.map('map').setView([51.505, -0.09], 13); // Default to London coords

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '©️ OpenStreetMap'
    }).addTo(map);

    // Feature group to store drawn lines
    const drawnItems = new L.FeatureGroup();
    map.addLayer(drawnItems);

    // Leaflet Draw control (always visible)
    const drawControl = new L.Control.Draw({
        draw: {
            polyline: true,
            polygon: false,
            rectangle: false,
            circle: false,
            marker: false,
            circlemarker: false
        },
        edit: {
            featureGroup: drawnItems,
            edit: false,
            remove: true
        }
    });
    map.addControl(drawControl);

    // Automatically start polyline drawing
    let polylineDrawer = new L.Draw.Polyline(map, drawControl.options.draw.polyline);
    polylineDrawer.enable();

    // Handle creation of lines
    map.on(L.Draw.Event.CREATED, function (event) {
        const layer = event.layer;
        drawnItems.addLayer(layer);
        // Keep drawing mode active after each line
        polylineDrawer.enable();
    });

    // Save function (logs GeoJSON for testing)
    document.getElementById('saveDraw').onclick = function() {
        const name = document.getElementById('name').value.trim();
        if (!name) {
            alert('Please enter your name before saving.');
            return;
        }
        const data = drawnItems.toGeoJSON();
        console.log('Name:', name);
        console.log('GeoJSON data:', JSON.stringify(data, null, 2));
        alert('Drawing saved! Check console for GeoJSON data.');
    };
</script>

</body>
</html>