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
    let snappedSegments = [];
    let routingQueue = Promise.resolve();
    let draftVersion = 0;

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

    const colorFromSeed = (seed) => {
        const seedString = String(seed ?? '');
        let hash = 0;

        for (let index = 0; index < seedString.length; index += 1) {
            hash = ((hash << 5) - hash) + seedString.charCodeAt(index);
            hash |= 0;
        }

        const palette = [
            '#d32f2f',
            '#fbc02d',
            '#388e3c',
            '#1976d2',
            '#7b1fa2',
            '#f57c00',
            '#00796b',
        ];

        return palette[Math.abs(hash) % palette.length];
    };

    const addCompletedRoute = (name, routeData, seed) => {
        if (!routeData || routeData.type !== 'FeatureCollection') {
            return;
        }

        const routeColor = colorFromSeed(seed ?? name);

        const routeLayer = L.geoJSON(routeData, {
            style: {
                color: routeColor,
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

    const osrmEndpoint = 'https://router.project-osrm.org/route/v1/driving';

    const startNewDraftVersion = () => {
        draftVersion += 1;
        routingQueue = Promise.resolve();
    };

    const buildSnappedPath = () => {
        if (snappedSegments.length === 0) {
            return [];
        }

        const path = [];
        let processedSegments = 0;

        for (const segment of snappedSegments) {
            if (!Array.isArray(segment) || segment.length === 0) {
                break;
            }

            if (processedSegments === 0) {
                path.push(...segment);
            } else {
                const lastPoint = path[path.length - 1];
                const firstPoint = segment[0];

                if (lastPoint && firstPoint && lastPoint.lat === firstPoint.lat && lastPoint.lng === firstPoint.lng) {
                    path.push(...segment.slice(1));
                } else {
                    path.push(...segment);
                }
            }

            processedSegments += 1;
        }

        if (processedSegments === 0) {
            return [...draftPoints];
        }

        if (processedSegments < snappedSegments.length && draftPoints.length > processedSegments + 1) {
            path.push(...draftPoints.slice(processedSegments + 1));
        }

        return path;
    };

    const renderDraftRoute = () => {
        inProgressRouteLayer.clearLayers();
        inProgressVertexLayer.clearLayers();

        if (draftPoints.length === 1) {
            L.circleMarker(draftPoints[0], {
                radius: 4,
                color: '#121212',
                fillColor: '#121212',
                fillOpacity: 1,
                weight: 0,
            }).addTo(inProgressVertexLayer);
        }

        const routeLatLngs = buildSnappedPath();

        if (routeLatLngs.length < 2) {
            return;
        }

        L.polyline(routeLatLngs, {
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
        snappedSegments = [];
        startNewDraftVersion();
        renderDraftRoute();
    };

    const fetchSnappedSegment = async (start, end) => {
        const coordinates = `${start.lng},${start.lat};${end.lng},${end.lat}`;
        const url = `${osrmEndpoint}/${coordinates}?overview=full&geometries=geojson`;

        try {
            const response = await fetch(url, {
                headers: {
                    Accept: 'application/json',
                },
            });

            if (!response.ok) {
                throw new Error('Unable to snap route segment.');
            }

            const payload = await response.json();
            const geometry = payload?.routes?.[0]?.geometry?.coordinates;

            if (!Array.isArray(geometry) || geometry.length === 0) {
                throw new Error('No route geometry returned.');
            }

            return geometry.map(([lng, lat]) => L.latLng(lat, lng));
        } catch {
            return [start, end];
        }
    };

    const queueSnappedSegment = (start, end, segmentIndex) => {
        const version = draftVersion;

        routingQueue = routingQueue.then(async () => {
            const segment = await fetchSnappedSegment(start, end);

            if (version !== draftVersion) {
                return;
            }

            snappedSegments[segmentIndex] = segment;
            renderDraftRoute();
        });
    };

    const buildRoutePayload = () => {
        const snappedPath = buildSnappedPath();
        const pathToSave = snappedPath.length > 0 ? snappedPath : draftPoints;

        return {
            type: 'FeatureCollection',
            features: [
                {
                    type: 'Feature',
                    properties: {},
                    geometry: {
                        type: 'LineString',
                        coordinates: pathToSave.map((point) => [point.lng, point.lat]),
                    },
                },
            ],
        };
    };

    parseStoredRoutes().forEach((route) => {
        if (!route || typeof route.name !== 'string') {
            return;
        }

        addCompletedRoute(route.name, route.route, route.id);
    });

    fitMapToSavedRoutes();

    map.on('click', (event) => {
        const previousPoint = draftPoints[draftPoints.length - 1] ?? null;

        draftPoints.push(event.latlng);
        renderDraftRoute();

        if (previousPoint) {
            const segmentIndex = draftPoints.length - 2;

            snappedSegments[segmentIndex] = null;
            queueSnappedSegment(previousPoint, event.latlng, segmentIndex);
        }
    });

    undoButton.addEventListener('click', () => {
        if (draftPoints.length === 0) {
            setStatus('No route segment to undo.', false);

            return;
        }

        draftPoints.pop();
        snappedSegments.pop();
        startNewDraftVersion();
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
            await routingQueue;

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
                addCompletedRoute(payload.route.name, payload.route.route, payload.route.id);
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
