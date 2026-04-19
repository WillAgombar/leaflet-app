const trackerRoot = document.querySelector('[data-leaflet-tracker]');

if (trackerRoot) {
    const mapElement = document.getElementById('tracker-map');
    const nameInput = document.getElementById('name-input');
    const nameLabel = document.getElementById('name-label');
    const statusElement = document.getElementById('tracker-status');
    const undoButton = document.getElementById('undo-button');
    const resetButton = document.getElementById('reset-button');
    const saveButton = document.getElementById('save-route-button');
    const saveButtonLabel = document.getElementById('save-route-label');
    const locateButton = document.getElementById('locate-button');
    const layersButton = document.getElementById('layers-button');
    const areaSelectButton = document.getElementById('area-select-button');
    const areaSelectLabel = document.getElementById('area-select-label');
    const areaFinishButton = document.getElementById('area-finish-button');
    const areaFinishLabel = document.getElementById('area-finish-label');
    const routeEditSelect = document.getElementById('route-edit-select');

    if (!mapElement || !nameInput || !statusElement || !undoButton || !resetButton || !saveButton || !saveButtonLabel || !locateButton || !layersButton) {
        throw new Error('Leaflet tracker UI is missing required elements.');
    }

    const mode = trackerRoot.dataset.mode ?? 'volunteer';
    const isTemplateMode = mode === 'template';
    const areaGenerateUrl = trackerRoot.dataset.areaGenerateUrl ?? '';
    const routeLabelState = isTemplateMode ? 'Template' : 'Completed';
    const updateUrlBase = trackerRoot.dataset.updateUrlBase ?? '';

    if (nameLabel) {
        nameLabel.textContent = isTemplateMode ? 'Enter route name' : 'Enter your name';
    }

    nameInput.placeholder = isTemplateMode ? 'e.g. Downtown Loop' : 'e.g. Michael Scott';
    let editingRoute = null;

    const getSaveButtonLabel = (isSaving) => {
        if (isTemplateMode) {
            if (editingRoute) {
                return isSaving ? 'Adding to Route...' : 'Add to Route';
            }

            return isSaving ? 'Saving Template...' : 'Save Route Template';
        }

        return isSaving ? 'Saving Route...' : 'Finish and Save Route';
    };

    saveButtonLabel.textContent = getSaveButtonLabel(false);

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
    const assignedRoutesLayer = L.featureGroup().addTo(map);
    const routeLabelsLayer = L.layerGroup().addTo(map);
    const inProgressRouteLayer = L.layerGroup().addTo(map);
    const inProgressVertexLayer = L.layerGroup().addTo(map);

    let usingDetailedLayer = false;
    let userLocationMarker = null;
    let draftPoints = [];
    let snappedSegments = [];
    let routingQueue = Promise.resolve();
    let draftVersion = 0;
    let areaSelection = {
        active: false,
        points: [],
        polygon: null,
        outline: null,
        verticesLayer: null,
        inFlight: false,
    };

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
        saveButtonLabel.textContent = getSaveButtonLabel(isSaving);
    };

    const setAreaSelectionLabel = (label) => {
        if (!areaSelectLabel) {
            return;
        }

        areaSelectLabel.textContent = label;
    };

    const setAreaFinishLabel = (label) => {
        if (!areaFinishLabel) {
            return;
        }

        areaFinishLabel.textContent = label;
    };

    const updateAreaFinishState = () => {
        if (!areaFinishButton) {
            return;
        }

        const isReady = areaSelection.active && areaSelection.points.length >= 3;
        areaFinishButton.disabled = !isReady;
        areaFinishButton.classList.toggle('opacity-50', !isReady);
        areaFinishButton.classList.toggle('cursor-not-allowed', !isReady);
    };

    const resetAreaSelection = (isActive = false) => {
        if (areaSelection.polygon) {
            map.removeLayer(areaSelection.polygon);
        }

        if (areaSelection.outline) {
            map.removeLayer(areaSelection.outline);
        }

        if (areaSelection.verticesLayer) {
            map.removeLayer(areaSelection.verticesLayer);
        }

        areaSelection = {
            active: isActive,
            points: [],
            polygon: null,
            outline: null,
            verticesLayer: isActive ? L.layerGroup().addTo(map) : null,
            inFlight: false,
        };

        map.getContainer().style.cursor = isActive ? 'crosshair' : '';
        setAreaSelectionLabel(isActive ? 'Cancel Area' : 'Select Area');
        setAreaFinishLabel('Finish Area');
        updateAreaFinishState();
    };

    const updateAreaPolygon = () => {
        if (!areaSelection.active) {
            return;
        }

        if (!areaSelection.outline) {
            areaSelection.outline = L.polyline([], {
                color: '#1b5e20',
                weight: 2,
                dashArray: '6 6',
            }).addTo(map);
        }

        areaSelection.outline.setLatLngs(areaSelection.points);

        if (areaSelection.points.length < 3) {
            if (areaSelection.polygon) {
                areaSelection.polygon.setLatLngs([]);
            }

            return;
        }

        if (!areaSelection.polygon) {
            areaSelection.polygon = L.polygon(areaSelection.points, {
                color: '#1b5e20',
                weight: 2,
                fillColor: '#a5d6a7',
                fillOpacity: 0.2,
            }).addTo(map);
        } else {
            areaSelection.polygon.setLatLngs(areaSelection.points);
        }
    };

    const addAreaVertex = (latlng) => {
        areaSelection.points.push(latlng);

        if (areaSelection.verticesLayer) {
            L.circleMarker(latlng, {
                radius: 4,
                color: '#1b5e20',
                fillColor: '#1b5e20',
                fillOpacity: 0.9,
                weight: 1,
            }).addTo(areaSelection.verticesLayer);
        }

        updateAreaPolygon();
        updateAreaFinishState();
    };

    const generateRoutesFromArea = async (points) => {
        if (!isTemplateMode || areaSelection.inFlight) {
            return false;
        }

        const routeName = nameInput?.value.trim();

        if (!routeName) {
            setStatus('Please enter a route name before generating.', false);
            nameInput?.focus();

            return false;
        }

        if (!Array.isArray(points) || points.length < 3) {
            setStatus('Add at least three points to generate routes.', false);

            return false;
        }

        if (!areaGenerateUrl) {
            setStatus('Area generation is unavailable for this campaign.', false);

            return false;
        }

        const maxVertices = 60;

        if (points.length > maxVertices) {
            setStatus(`Too many points (${points.length}). Please keep it under ${maxVertices}.`, false);

            return false;
        }

        areaSelection.inFlight = true;
        setStatus('Fetching roads inside the area...');

        try {
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            if (!token) {
                setStatus('Security token is missing. Refresh and try again.', false);
                areaSelection.inFlight = false;

                return false;
            }

            const response = await fetch(areaGenerateUrl, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token,
                },
                body: JSON.stringify({
                    name: routeName,
                    points: points.map((point) => ({
                        lat: point.lat,
                        lng: point.lng,
                    })),
                }),
            });

            let payload = null;

            try {
                payload = await response.json();
            } catch {
                payload = null;
            }

            if (!response.ok) {
                const message = payload?.message || `Unable to generate routes for that area (status ${response.status}).`;
                throw new Error(message);
            }

            const route = payload?.route ?? null;
            const segments = payload?.segments ?? null;

            if (!route) {
                setStatus('No roads found inside that area.', false);

                return false;
            }

            upsertStoredRoute(route);
            if (segments) {
                setStatus(`Generated 1 area route with ${segments} roads. Assign it from the campaign assignments screen.`);
            } else {
                setStatus('Generated 1 area route. Assign it from the campaign assignments screen.');
            }

            return true;
        } catch (error) {
            setStatus(error instanceof Error ? error.message : 'Unable to fetch roads for that area. Try again.', false);

            return false;
        } finally {
            areaSelection.inFlight = false;
        }
    };

    const addRouteLabel = (layer, name, stateOverride = null) => {
        const bounds = layer.getBounds();

        if (!bounds.isValid()) {
            return;
        }

        const displayName = (name || '').trim() || 'Volunteer';
        const state = stateOverride || routeLabelState;

        const labelIcon = L.divIcon({
            className: 'route-label',
            iconSize: null,
            html: `
                <div class="route-label-chip" title="${escapeHtml(displayName)}">
                    <span class="route-label-chip__dot"></span>
                    <span class="route-label-chip__meta">
                        <span class="route-label-chip__state">${escapeHtml(state)}</span>
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

        const hue = Math.abs(hash) % 360;
        return `hsl(${hue}, 80%, 40%)`;
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

    const statusStyle = (status, seed) => {
        const baseColor = colorFromSeed(seed);

        if (status === 'in_progress') {
            return {
                color: baseColor,
                opacity: 0.9,
                weight: 6,
                dashArray: '6 6',
            };
        }

        if (status === 'completed') {
            return {
                color: baseColor,
                opacity: 0.95,
                weight: 6,
            };
        }

        if (status === 'available') {
            return {
                color: baseColor,
                opacity: 0.6,
                weight: 6,
                dashArray: '2 8',
            };
        }

        return {
            color: baseColor,
            opacity: 0.8,
            weight: 6,
            dashArray: '10 8',
        };
    };

    const addAssignedRoute = (routeData, status, name, userName) => {
        if (!routeData || routeData.type !== 'FeatureCollection') {
            return;
        }

        const routeLayer = L.geoJSON(routeData, {
            style: statusStyle(status, name),
        });

        if (routeLayer.getLayers().length === 0) {
            return;
        }

        routeLayer.addTo(assignedRoutesLayer);
        
        let labelState = '';
        let displayName = '';
        if (status === 'completed') {
            labelState = 'Completed By';
            displayName = userName || 'User';
        } else if (status === 'assigned' || status === 'in_progress') {
            labelState = 'Assigned To';
            displayName = userName || 'User';
        } else {
            labelState = 'Looking for Volunteer';
            displayName = name || 'Available';
        }
        
        addRouteLabel(routeLayer, displayName, labelState);
    };

    const fitMapToSavedRoutes = () => {
        const bounds = completedRoutesLayer.getBounds();
        const assignedBounds = assignedRoutesLayer.getBounds();

        if (!bounds.isValid() && assignedBounds.isValid()) {
            map.fitBounds(assignedBounds.pad(0.25));

            return;
        }

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

    const parseAssignedRoutes = () => {
        const rawRoutes = trackerRoot.dataset.assignedRoutes ?? '[]';

        try {
            return JSON.parse(rawRoutes);
        } catch {
            return [];
        }
    };

    let storedRoutes = parseStoredRoutes();

    const renderStoredRoutes = () => {
        completedRoutesLayer.clearLayers();
        routeLabelsLayer.clearLayers();

        storedRoutes.forEach((route) => {
            if (!route || typeof route.name !== 'string') {
                return;
            }

            addCompletedRoute(route.name, route.route, route.id);
        });
    };

    const upsertStoredRoute = (route) => {
        if (!route) {
            return;
        }

        const matchIndex = storedRoutes.findIndex((item) => String(item.id) === String(route.id));

        if (matchIndex >= 0) {
            storedRoutes[matchIndex] = route;
        } else {
            storedRoutes = [...storedRoutes, route];
        }

        renderStoredRoutes();
        populateEditSelect();
    };

    const populateEditSelect = () => {
        if (!routeEditSelect) {
            return;
        }

        const currentValue = routeEditSelect.value;
        routeEditSelect.innerHTML = '<option value=\"\">Create new route</option>';

        storedRoutes.forEach((route) => {
            if (!route || !route.id) {
                return;
            }

            const option = document.createElement('option');
            option.value = String(route.id);
            option.textContent = route.name || `Route ${route.id}`;
            routeEditSelect.appendChild(option);
        });

        routeEditSelect.value = currentValue;
    };

    const setEditingRoute = (routeId) => {
        if (!routeId) {
            editingRoute = null;
            saveButtonLabel.textContent = getSaveButtonLabel(false);
            return;
        }

        const match = storedRoutes.find((route) => String(route.id) === String(routeId));

        if (!match) {
            editingRoute = null;
            saveButtonLabel.textContent = getSaveButtonLabel(false);

            return;
        }

        editingRoute = match;
        if (nameInput) {
            nameInput.value = match.name || '';
        }
        saveButtonLabel.textContent = getSaveButtonLabel(false);
        setStatus(`Editing route: ${match.name}`);
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

    renderStoredRoutes();
    populateEditSelect();

    parseAssignedRoutes().forEach((route) => {
        if (!route || !route.route) {
            return;
        }

        addAssignedRoute(route.route, route.status, route.name, route.userName);
    });

    fitMapToSavedRoutes();

    map.on('click', (event) => {
        if (areaSelection.active) {
            addAreaVertex(event.latlng);
            setStatus('Add more points or finish the area.');

            return;
        }

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

    if (areaSelectButton && isTemplateMode) {
        areaSelectButton.addEventListener('click', () => {
            if (areaSelection.active) {
                resetAreaSelection(false);
                setStatus('Area selection cancelled.');

                return;
            }

            resetAreaSelection(true);
            setStatus('Tap map to add area points.');
        });
    }

    if (areaFinishButton && isTemplateMode) {
        areaFinishButton.addEventListener('click', async () => {
            if (!areaSelection.active || areaSelection.points.length < 3) {
                setStatus('Add at least three points to finish the area.', false);

                return;
            }

            const success = await generateRoutesFromArea(areaSelection.points);

            if (success) {
                resetAreaSelection(false);
            }
        });
    }

    if (routeEditSelect && isTemplateMode) {
        routeEditSelect.addEventListener('change', () => {
            const selectedId = routeEditSelect.value;

            if (!selectedId) {
                editingRoute = null;
                saveButtonLabel.textContent = getSaveButtonLabel(false);
                setStatus('Creating a new route.');
                resetDraftRoute();

                return;
            }

            setEditingRoute(selectedId);
            resetDraftRoute();
        });
    }

    saveButton.addEventListener('click', async () => {
        const volunteerName = nameInput.value.trim();
        const selectedRouteId = routeEditSelect?.value ?? '';

        if (isTemplateMode && selectedRouteId) {
            const matchedRoute = storedRoutes.find((route) => String(route.id) === String(selectedRouteId));

            if (!matchedRoute) {
                setStatus('Select a valid route to edit before saving.', false);

                return;
            }

            editingRoute = matchedRoute;
        }

        if (!volunteerName) {
            setStatus(isTemplateMode ? 'Please enter a route name before saving.' : 'Please enter your name before saving.', false);

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

            const routePayload = buildRoutePayload();
            const newFeatures = Array.isArray(routePayload.features) ? routePayload.features : [];
            let mergedRouteData = routePayload;
            let requestUrl = trackerRoot.dataset.saveUrl;
            let method = 'POST';

            if (isTemplateMode && editingRoute) {
                if (!updateUrlBase) {
                    throw new Error('Route editing is unavailable right now.');
                }

                const existingFeatures = Array.isArray(editingRoute.route?.features) ? editingRoute.route.features : [];
                mergedRouteData = {
                    type: 'FeatureCollection',
                    features: [...existingFeatures, ...newFeatures],
                };
                requestUrl = updateUrlBase.replace(/\/0$/, `/${editingRoute.id}`);
                method = 'PATCH';
            }

            const response = await fetch(requestUrl, {
                method,
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token,
                },
                body: JSON.stringify({
                    name: volunteerName,
                    route_data: mergedRouteData,
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
                upsertStoredRoute(payload.route);

                if (editingRoute) {
                    editingRoute = payload.route;
                    if (routeEditSelect) {
                        routeEditSelect.value = String(payload.route.id);
                    }
                }
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
