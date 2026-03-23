const trackerRoot = document.querySelector('[data-leaflet-tracker]');

if (trackerRoot) {
    const mapElement = document.getElementById('tracker-map');
    const nameInput = document.getElementById('name-input');
    const statusElement = document.getElementById('tracker-status');
    const undoButton = document.getElementById('undo-button');
    const resetButton = document.getElementById('reset-button');
    const saveButton = document.getElementById('save-route-button');
    const saveButtonLabel = document.getElementById('save-route-label');
    const locateButton = document.getElementById('locate-button');
    const layersButton = document.getElementById('layers-button');

    if (!mapElement || !nameInput || !statusElement || !undoButton || !resetButton || !saveButton || !saveButtonLabel || !locateButton || !layersButton) {
        throw new Error('Leaflet tracker UI is missing required elements.');
    }

    const mapCenter = [51.0629, -1.3160];
    const map = L.map(mapElement, {
        zoomControl: false,
        minZoom: 11,
        maxZoom: 19,
    }).setView(mapCenter, 13);

    const streetLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors',
        maxZoom: 19,
    });

    const detailLayer = L.tileLayer('https://{s}.tile.openstreetmap.fr/hot/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors',
        maxZoom: 19,
    });

    streetLayer.addTo(map);

    const completedRoutesLayer = L.featureGroup().addTo(map);
    const routeLabelsLayer = L.layerGroup().addTo(map);
    const inProgressRouteLayer = L.layerGroup().addTo(map);
    const inProgressVertexLayer = L.layerGroup().addTo(map);

    let usingDetailedLayer = false;
    let userLocationMarker = null;
    let draftPoints = [];

    const escapeHtml = (value) => {
        const replacements = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;',
        };

        return value.replace(/[&<>"']/g, (character) => replacements[character]);
    };

    const setStatus = (message, isSuccess = true) => {
        statusElement.textContent = message;
        statusElement.classList.remove('hidden', 'bg-[#ffdad6]', 'text-[#93000a]', 'bg-[#e8f5e9]', 'text-[#1b5e20]');

        if (isSuccess) {
            statusElement.classList.add('bg-[#e8f5e9]', 'text-[#1b5e20]');

            return;
        }

        statusElement.classList.add('bg-[#ffdad6]', 'text-[#93000a]');
    };

    const setSavingState = (isSaving) => {
        saveButton.disabled = isSaving;
        saveButton.classList.toggle('opacity-70', isSaving);
        saveButton.classList.toggle('cursor-not-allowed', isSaving);
        saveButtonLabel.textContent = isSaving ? 'Saving Route...' : 'Finish and Save Route';
    };

    const addRouteLabel = (layer, name) => {
        const bounds = layer.getBounds();

        if (!bounds.isValid()) {
            return;
        }

        const displayName = name.trim() || 'Volunteer';

        const labelIcon = L.divIcon({
            className: 'route-label',
            iconSize: null,
            html: `
                <div class="route-label-chip" title="${escapeHtml(displayName)}">
                    <span class="route-label-chip__dot"></span>
                    <span class="route-label-chip__meta">
                        <span class="route-label-chip__state">Completed</span>
                        <span class="route-label-chip__name">${escapeHtml(displayName)}</span>
                    </span>
                </div>
            `,
        });

        L.marker(bounds.getCenter(), { icon: labelIcon, keyboard: false }).addTo(routeLabelsLayer);
    };

    const addCompletedRoute = (name, routeData) => {
        if (!routeData || routeData.type !== 'FeatureCollection') {
            return;
        }

        const routeLayer = L.geoJSON(routeData, {
            style: {
                color: '#2e7d32',
                weight: 8,
                lineCap: 'round',
                lineJoin: 'round',
                opacity: 0.95,
            },
        });

        if (routeLayer.getLayers().length === 0) {
            return;
        }

        routeLayer.addTo(completedRoutesLayer);
        addRouteLabel(routeLayer, name);
    };

    const fitMapToSavedRoutes = () => {
        const bounds = completedRoutesLayer.getBounds();

        if (!bounds.isValid()) {
            map.setView(mapCenter, 13);

            return;
        }

        map.fitBounds(bounds.pad(0.25));
    };

    const parseStoredRoutes = () => {
        const rawRoutes = trackerRoot.dataset.routes ?? '[]';

        try {
            return JSON.parse(rawRoutes);
        } catch {
            return [];
        }
    };

    const renderDraftRoute = () => {
        inProgressRouteLayer.clearLayers();
        inProgressVertexLayer.clearLayers();

        draftPoints.forEach((point) => {
            L.circleMarker(point, {
                radius: 4,
                color: '#121212',
                fillColor: '#121212',
                fillOpacity: 1,
                weight: 0,
            }).addTo(inProgressVertexLayer);
        });

        if (draftPoints.length < 2) {
            return;
        }

        L.polyline(draftPoints, {
            color: '#121212',
            weight: 6,
            dashArray: '10 10',
            lineCap: 'round',
            lineJoin: 'round',
            opacity: 0.9,
        }).addTo(inProgressRouteLayer);
    };

    const resetDraftRoute = () => {
        draftPoints = [];
        renderDraftRoute();
    };

    const buildRoutePayload = () => {
        return {
            type: 'FeatureCollection',
            features: [
                {
                    type: 'Feature',
                    properties: {},
                    geometry: {
                        type: 'LineString',
                        coordinates: draftPoints.map((point) => [point.lng, point.lat]),
                    },
                },
            ],
        };
    };

    parseStoredRoutes().forEach((route) => {
        if (!route || typeof route.name !== 'string') {
            return;
        }

        addCompletedRoute(route.name, route.route);
    });

    fitMapToSavedRoutes();

    map.on('click', (event) => {
        draftPoints.push(event.latlng);
        renderDraftRoute();
    });

    undoButton.addEventListener('click', () => {
        if (draftPoints.length === 0) {
            setStatus('No route segment to undo.', false);

            return;
        }

        draftPoints.pop();
        renderDraftRoute();
        setStatus('Last route segment removed.');
    });

    resetButton.addEventListener('click', () => {
        resetDraftRoute();
        setStatus('Current route reset.');
    });

    locateButton.addEventListener('click', () => {
        if (!navigator.geolocation) {
            setStatus('Location services are not available on this device.', false);

            return;
        }

        map.locate({ setView: true, maxZoom: 16 });
    });

    map.on('locationfound', (event) => {
        if (userLocationMarker) {
            userLocationMarker.setLatLng(event.latlng);

            return;
        }

        userLocationMarker = L.circleMarker(event.latlng, {
            radius: 8,
            color: '#1b5e20',
            fillColor: '#a5d6a7',
            fillOpacity: 0.95,
            weight: 2,
        }).addTo(map);
    });

    map.on('locationerror', () => {
        setStatus('Unable to access your location right now.', false);
    });

    layersButton.addEventListener('click', () => {
        if (usingDetailedLayer) {
            map.removeLayer(detailLayer);
            streetLayer.addTo(map);
            usingDetailedLayer = false;
            setStatus('Standard map layer enabled.');

            return;
        }

        map.removeLayer(streetLayer);
        detailLayer.addTo(map);
        usingDetailedLayer = true;
        setStatus('Detailed map layer enabled.');
    });

    saveButton.addEventListener('click', async () => {
        const volunteerName = nameInput.value.trim();

        if (!volunteerName) {
            setStatus('Please enter your name before saving.', false);

            return;
        }

        if (draftPoints.length < 2) {
            setStatus('Add at least two points before saving.', false);

            return;
        }

        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        if (!token) {
            setStatus('Security token is missing. Refresh and try again.', false);

            return;
        }

        setSavingState(true);

        try {
            const response = await fetch(trackerRoot.dataset.saveUrl, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token,
                },
                body: JSON.stringify({
                    name: volunteerName,
                    route_data: buildRoutePayload(),
                }),
            });

            const payload = await response.json();

            if (!response.ok) {
                if (payload.errors && typeof payload.errors === 'object') {
                    const errorMessages = Object.values(payload.errors).flat();
                    throw new Error(errorMessages[0] || 'Unable to save this route.');
                }

                throw new Error(payload.message || 'Unable to save this route.');
            }

            if (payload.route) {
                addCompletedRoute(payload.route.name, payload.route.route);
            }

            resetDraftRoute();
            setStatus(payload.message || 'Route saved successfully.');
        } catch (error) {
            setStatus(error instanceof Error ? error.message : 'Unable to save this route.', false);
        } finally {
            setSavingState(false);
        }
    });
}
