const assignmentRoot = document.querySelector('[data-assignment]');

if (assignmentRoot) {
    const mapElement = document.getElementById('assignment-map');
    const statusElement = document.getElementById('assignment-status');
    const startButton = document.getElementById('start-assignment-button');
    const completeButton = document.getElementById('complete-assignment-button');
    const routeLengthElement = document.getElementById('route-length');
    const startLabel = document.getElementById('start-assignment-label');
    const progressValueElement = document.getElementById('route-progress');
    const progressBarElement = document.getElementById('route-progress-bar');
    const trackingStateElement = document.getElementById('tracking-state');
    const trackingNoteElement = document.getElementById('tracking-note');
    const accuracyElement = document.getElementById('gps-accuracy');
    const resumeBanner = document.getElementById('resume-banner');
    const resumeTimeElement = document.getElementById('resume-time');
    const resumeButton = document.getElementById('resume-tracking-button');
    const manualMarkButton = document.getElementById('manual-mark-button');
    const manualMarkLabel = document.getElementById('manual-mark-label');

    if (!mapElement || !statusElement || !startButton || !completeButton) {
        throw new Error('Assignment UI is missing required elements.');
    }

    const routeDataRaw = assignmentRoot.dataset.route ?? 'null';
    const trackingIndexUrl = assignmentRoot.dataset.trackingIndexUrl ?? '';
    const trackingStoreUrl = assignmentRoot.dataset.trackingStoreUrl ?? '';
    const osrmBaseUrl = assignmentRoot.dataset.osrmUrl ?? 'https://router.project-osrm.org';
    let routeData = null;

    try {
        routeData = JSON.parse(routeDataRaw);
    } catch {
        routeData = null;
    }

    const map = L.map(mapElement, {
        zoomControl: false,
        minZoom: 11,
        maxZoom: 19,
    });

    const streetLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors',
        maxZoom: 19,
    });

    streetLayer.addTo(map);

    const toRadians = (value) => (value * Math.PI) / 180;
    const earthRadius = 6371000;

    const distanceBetweenMeters = (start, end) => {
        const deltaLat = toRadians(end[1] - start[1]);
        const deltaLng = toRadians(end[0] - start[0]);
        const lat1 = toRadians(start[1]);
        const lat2 = toRadians(end[1]);
        const a = Math.sin(deltaLat / 2) ** 2 + Math.cos(lat1) * Math.cos(lat2) * Math.sin(deltaLng / 2) ** 2;
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));

        return earthRadius * c;
    };

    const toMeters = (point, refLat) => {
        const latRad = toRadians(point[1]);
        const lngRad = toRadians(point[0]);

        return [earthRadius * lngRad * Math.cos(refLat), earthRadius * latRad];
    };

    const lineStrings = [];

    if (routeData?.type === 'FeatureCollection') {
        (routeData.features ?? []).forEach((feature) => {
            if (!feature?.geometry) {
                return;
            }

            if (feature.geometry.type === 'LineString') {
                const coords = feature.geometry.coordinates;

                if (Array.isArray(coords) && coords.length > 1) {
                    lineStrings.push(coords);
                }
            }

            if (feature.geometry.type === 'MultiLineString') {
                const coords = feature.geometry.coordinates;

                if (Array.isArray(coords)) {
                    coords.forEach((segment) => {
                        if (Array.isArray(segment) && segment.length > 1) {
                            lineStrings.push(segment);
                        }
                    });
                }
            }
        });
    }

    const hasRouteCoordinates = lineStrings.length > 0;

    const buildRouteIndex = (coordinates) => {
        if (!Array.isArray(coordinates) || coordinates.length < 2) {
            return null;
        }

        const segmentLengths = [];
        const cumulativeDistances = [0];
        let total = 0;

        coordinates.forEach((point, index) => {
            if (index === coordinates.length - 1) {
                return;
            }

            const nextPoint = coordinates[index + 1];
            const refLat = toRadians((point[1] + nextPoint[1]) / 2);
            const startMeters = toMeters(point, refLat);
            const endMeters = toMeters(nextPoint, refLat);
            const length = Math.hypot(endMeters[0] - startMeters[0], endMeters[1] - startMeters[1]);

            segmentLengths.push(length);
            total += length;
            cumulativeDistances.push(total);
        });

        return {
            segmentLengths,
            cumulativeDistances,
            totalLength: total,
        };
    };

    const routeIndexes = lineStrings.map((coords) => buildRouteIndex(coords)).filter(Boolean);
    const totalRouteLengthMeters = routeIndexes.reduce((sum, index) => sum + (index?.totalLength ?? 0), 0);

    const calculateRouteLengthKm = () => {
        if (!routeIndexes.length) {
            return null;
        }

        return totalRouteLengthMeters / 1000;
    };

    const projectPointToRoute = (point) => {
        if (!routeIndexes.length || !hasRouteCoordinates) {
            return null;
        }

        let closestDistance = Number.POSITIVE_INFINITY;
        let distanceAlong = 0;
        let matchedIndex = 0;

        routeIndexes.forEach((routeIndex, routeIndexIndex) => {
            if (!routeIndex) {
                return;
            }

            const coordinates = lineStrings[routeIndexIndex] ?? [];

            for (let index = 0; index < coordinates.length - 1; index += 1) {
                const start = coordinates[index];
                const end = coordinates[index + 1];
                const refLat = toRadians((start[1] + end[1]) / 2);
                const startMeters = toMeters(start, refLat);
                const endMeters = toMeters(end, refLat);
                const pointMeters = toMeters(point, refLat);
                const segmentVector = [endMeters[0] - startMeters[0], endMeters[1] - startMeters[1]];
                const segmentLengthSquared = segmentVector[0] ** 2 + segmentVector[1] ** 2;
                const segmentLength = Math.sqrt(segmentLengthSquared);

                let projection = 0;

                if (segmentLengthSquared > 0) {
                    projection =
                        ((pointMeters[0] - startMeters[0]) * segmentVector[0] +
                            (pointMeters[1] - startMeters[1]) * segmentVector[1]) /
                        segmentLengthSquared;
                }

                const clampedProjection = Math.min(Math.max(projection, 0), 1);
                const closestPoint = [
                    startMeters[0] + clampedProjection * segmentVector[0],
                    startMeters[1] + clampedProjection * segmentVector[1],
                ];
                const distanceFromSegment = Math.hypot(
                    pointMeters[0] - closestPoint[0],
                    pointMeters[1] - closestPoint[1]
                );

                if (distanceFromSegment < closestDistance) {
                    closestDistance = distanceFromSegment;
                    distanceAlong = routeIndex.cumulativeDistances[index] + clampedProjection * segmentLength;
                    matchedIndex = routeIndexIndex;
                }
            }
        });

        return {
            distanceAlong,
            distanceFromSegment: closestDistance,
            routeIndex: matchedIndex,
        };
    };

    const updateProgressDisplay = (distanceAlong) => {
        if (!totalRouteLengthMeters || totalRouteLengthMeters <= 0 || !progressValueElement || !progressBarElement) {
            return;
        }

        const percent = Math.min(Math.max((distanceAlong / totalRouteLengthMeters) * 100, 0), 100);
        progressValueElement.textContent = `${Math.round(percent)}%`;
        progressBarElement.style.width = `${percent}%`;
    };

    let routeLayer = null;
    const completedLayers = [];
    let locationMarker = null;
    let accuracyCircle = null;

    if (routeData && routeData.type === 'FeatureCollection') {
        routeLayer = L.geoJSON(routeData, {
            style: {
                color: '#1565c0',
                weight: 9,
                lineCap: 'round',
                lineJoin: 'round',
                opacity: 0.98,
            },
        }).addTo(map);

        const bounds = routeLayer.getBounds();

        if (bounds.isValid()) {
            map.fitBounds(bounds.pad(0.25));
        }
    } else {
        map.setView([51.0629, -1.316], 13);
    }

    if (routeLengthElement) {
        const lengthKm = calculateRouteLengthKm();

        if (lengthKm !== null) {
            routeLengthElement.textContent = `${lengthKm.toFixed(1)} km`;
        }
    }

    const statusLabels = {
        assigned: 'Assigned',
        in_progress: 'In Progress',
        completed: 'Completed',
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

    const updateButtons = (status) => {
        if (status === 'completed') {
            startButton.classList.add('hidden');
            completeButton.classList.add('hidden');

            return;
        }

        startButton.classList.remove('hidden');
        completeButton.classList.toggle('hidden', status !== 'in_progress');
    };

    const updateStartButtonLabel = (status, isTracking) => {
        if (!startLabel) {
            return;
        }

        if (status === 'assigned') {
            startLabel.textContent = 'Start Route';

            return;
        }

        if (isTracking) {
            startLabel.textContent = 'Pause Tracking';

            return;
        }

        startLabel.textContent = 'Resume Tracking';
    };

    let currentStatus = assignmentRoot.dataset.status ?? 'assigned';
    updateButtons(currentStatus);
    updateStartButtonLabel(currentStatus, false);

    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    const postStatusUpdate = async (url) => {
        if (!token) {
            setStatus('Security token is missing. Refresh and try again.', false);

            return null;
        }

        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': token,
                },
            });

            const payload = await response.json();

            if (!response.ok) {
                throw new Error(payload.message || 'Unable to update this route.');
            }

            return payload;
        } catch (error) {
            setStatus(error instanceof Error ? error.message : 'Unable to update this route.', false);

            return null;
        }
    };

    const trackingState = {
        active: false,
        watchId: null,
        lastSnappedPoint: null,
        lastRawPoint: null,
        lastSentAt: 0,
        lastSnapAt: 0,
        snapInFlight: false,
        maxDistance: 0,
        wakeLock: null,
        lastTrackedAt: null,
        startDistanceAlong: Array(routeIndexes.length).fill(null),
        maxProjectionDistance: Array(routeIndexes.length).fill(null),
    };

    const manualState = {
        active: false,
        points: [],
        layer: null,
        verticesLayer: null,
    };

    const updateTrackingUI = () => {
        if (!trackingStateElement) {
            return;
        }

        if (trackingState.active) {
            trackingStateElement.textContent = 'Tracking On';
            trackingStateElement.classList.remove('bg-[#ffdad6]', 'text-[#93000a]');
            trackingStateElement.classList.add('bg-[#e8f5e9]', 'text-[#1b5e20]');
        } else {
            trackingStateElement.textContent = 'Not Tracking';
            trackingStateElement.classList.remove('bg-[#e8f5e9]', 'text-[#1b5e20]');
            trackingStateElement.classList.add('bg-[#ffdad6]', 'text-[#93000a]');
        }

        updateStartButtonLabel(currentStatus, trackingState.active);
        updateResumeBanner();
    };

    const requestWakeLock = async () => {
        if (!('wakeLock' in navigator)) {
            return;
        }

        try {
            trackingState.wakeLock = await navigator.wakeLock.request('screen');
            trackingState.wakeLock.addEventListener('release', () => {
                if (trackingState.active) {
                    requestWakeLock();
                }
            });
        } catch {
            if (trackingNoteElement) {
                trackingNoteElement.textContent = 'Keep this screen open while tracking your route.';
            }
        }
    };

    const releaseWakeLock = () => {
        if (trackingState.wakeLock) {
            trackingState.wakeLock.release();
            trackingState.wakeLock = null;
        }
    };

    const buildRouteSliceBetween = (routeIndex, coordinates, startDistance, endDistance) => {
        if (!routeIndex || !Array.isArray(coordinates) || coordinates.length < 2) {
            return [];
        }

        const clampedStart = Math.min(Math.max(startDistance, 0), routeIndex.totalLength);
        const clampedEnd = Math.min(Math.max(endDistance, 0), routeIndex.totalLength);

        if (clampedEnd <= clampedStart) {
            return [];
        }

        const sliceCoordinates = [];

        for (let index = 0; index < coordinates.length - 1; index += 1) {
            const segmentLength = routeIndex.segmentLengths[index];

            if (!segmentLength) {
                continue;
            }

            const segmentStartDistance = routeIndex.cumulativeDistances[index];
            const segmentEndDistance = routeIndex.cumulativeDistances[index + 1];

            if (clampedEnd < segmentStartDistance || clampedStart > segmentEndDistance) {
                continue;
            }

            const startFraction = Math.max(0, (clampedStart - segmentStartDistance) / segmentLength);
            const endFraction = Math.min(1, (clampedEnd - segmentStartDistance) / segmentLength);
            const startCoord = coordinates[index];
            const endCoord = coordinates[index + 1];
            const interpolate = (fraction) => [
                startCoord[0] + (endCoord[0] - startCoord[0]) * fraction,
                startCoord[1] + (endCoord[1] - startCoord[1]) * fraction,
            ];

            if (!sliceCoordinates.length) {
                sliceCoordinates.push(interpolate(startFraction));
            }

            sliceCoordinates.push(interpolate(endFraction));
        }

        return sliceCoordinates;
    };

    const updateCompletedRoute = (startDistances, endDistances) => {
        if (!hasRouteCoordinates) {
            return;
        }

        routeIndexes.forEach((routeIndex, index) => {
            if (!routeIndex) {
                return;
            }

            const coordinates = lineStrings[index] ?? [];
            const startDistance = startDistances[index] ?? 0;
            const endDistance = endDistances[index] ?? 0;
            const slice = buildRouteSliceBetween(routeIndex, coordinates, startDistance, endDistance);

            if (!slice.length) {
                if (completedLayers[index]) {
                    completedLayers[index].setLatLngs([]);
                }

                return;
            }

            const latLngs = slice.map((coord) => [coord[1], coord[0]]);

            if (!completedLayers[index]) {
                completedLayers[index] = L.polyline(latLngs, {
                    color: '#00c853',
                    weight: 8,
                    lineCap: 'round',
                    lineJoin: 'round',
                    opacity: 0.95,
                }).addTo(map);

                return;
            }

            completedLayers[index].setLatLngs(latLngs);
        });
    };

    const updateLocationMarker = (point, accuracy) => {
        const latLng = [point[1], point[0]];

        if (!locationMarker) {
            locationMarker = L.circleMarker(latLng, {
                radius: 6,
                color: '#1b5e20',
                fillColor: '#1b5e20',
                fillOpacity: 0.9,
            }).addTo(map);
        } else {
            locationMarker.setLatLng(latLng);
        }

        if (accuracy) {
            if (!accuracyCircle) {
                accuracyCircle = L.circle(latLng, {
                    radius: accuracy,
                    color: '#1b5e20',
                    opacity: 0.2,
                    fillOpacity: 0.08,
                    weight: 1,
                }).addTo(map);
            } else {
                accuracyCircle.setLatLng(latLng);
                accuracyCircle.setRadius(accuracy);
            }
        }

        if (accuracyElement) {
            accuracyElement.textContent = accuracy ? `GPS ±${Math.round(accuracy)}m` : 'GPS ±—m';
        }
    };

    const snapToRoad = async (point) => {
        if (!osrmBaseUrl || trackingState.snapInFlight) {
            return point;
        }

        const now = Date.now();

        if (trackingState.lastSnapAt && now - trackingState.lastSnapAt < 2500) {
            return point;
        }

        trackingState.snapInFlight = true;

        try {
            const response = await fetch(`${osrmBaseUrl}/nearest/v1/driving/${point[0]},${point[1]}?number=1`, {
                headers: {
                    Accept: 'application/json',
                },
            });

            if (!response.ok) {
                throw new Error('OSRM snap failed.');
            }

            const payload = await response.json();
            const snapped = payload?.waypoints?.[0]?.location;

            if (Array.isArray(snapped) && snapped.length >= 2) {
                trackingState.lastSnapAt = now;

                return snapped;
            }
        } catch {
            return point;
        } finally {
            trackingState.snapInFlight = false;
        }

        return point;
    };

    const sendTrackingPoint = async (payload) => {
        if (!trackingStoreUrl || !token) {
            return;
        }

        const now = Date.now();

        if (now - trackingState.lastSentAt < 2500) {
            return;
        }

        trackingState.lastSentAt = now;

        try {
            await fetch(trackingStoreUrl, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token,
                },
                body: JSON.stringify(payload),
            });
        } catch {
            setStatus('Tracking point failed to save.', false);
        }
    };

    const defaultTrackingNote = 'Keep this screen open while tracking your route.';

    const resolveRouteThreshold = (accuracy) => {
        const baseline = 35;

        if (typeof accuracy !== 'number' || Number.isNaN(accuracy)) {
            return baseline;
        }

        return Math.max(baseline, Math.min(accuracy * 2, 120));
    };

    const handlePosition = async (position) => {
        const rawPoint = [position.coords.longitude, position.coords.latitude];
        const accuracy = position.coords.accuracy;
        const capturedAt = new Date(position.timestamp).toISOString();

        if (trackingState.lastRawPoint) {
            const distance = distanceBetweenMeters(trackingState.lastRawPoint, rawPoint);

            if (distance < 3) {
                return;
            }
        }

        trackingState.lastRawPoint = rawPoint;
        trackingState.lastTrackedAt = new Date(position.timestamp);

        let snappedPoint = rawPoint;

        if (hasRouteCoordinates) {
            snappedPoint = await snapToRoad(rawPoint);
        }

        trackingState.lastSnappedPoint = snappedPoint;
        updateLocationMarker(snappedPoint, accuracy);

        if (hasRouteCoordinates) {
            const projection = projectPointToRoute(snappedPoint);
            const threshold = resolveRouteThreshold(accuracy);

            if (projection && projection.distanceFromSegment <= threshold) {
                const routeIndex = projection.routeIndex ?? 0;
                const startDistances = trackingState.startDistanceAlong;
                const maxDistances = trackingState.maxProjectionDistance;

                if (startDistances[routeIndex] === null) {
                    startDistances[routeIndex] = projection.distanceAlong;
                    maxDistances[routeIndex] = projection.distanceAlong;
                } else if (projection.distanceAlong > (maxDistances[routeIndex] ?? 0)) {
                    maxDistances[routeIndex] = projection.distanceAlong;
                }

                let traveled = 0;
                startDistances.forEach((startDistance, index) => {
                    if (startDistance === null || maxDistances[index] === null) {
                        return;
                    }

                    traveled += Math.max(maxDistances[index] - startDistance, 0);
                });

                trackingState.maxDistance = traveled;
                updateProgressDisplay(traveled);
                updateCompletedRoute(startDistances, maxDistances);

                if (trackingNoteElement) {
                    trackingNoteElement.textContent = defaultTrackingNote;
                }
            } else if (trackingNoteElement) {
                trackingNoteElement.textContent = 'Move closer to the route to track progress.';
            }
        }

        await sendTrackingPoint({
            latitude: rawPoint[1],
            longitude: rawPoint[0],
            snapped_latitude: snappedPoint[1],
            snapped_longitude: snappedPoint[0],
            accuracy,
            captured_at: capturedAt,
        });

        updateResumeBanner();
    };

    const handlePositionError = (error) => {
        if (!error) {
            setStatus('Unable to read your location. Try again.', false);

            return;
        }

        if (error.code === 1) {
            setStatus('Location permission denied. Enable it to track.', false);
        } else if (error.code === 2) {
            setStatus('Location unavailable. Try again in a moment.', false);
        } else if (error.code === 3) {
            setStatus('Location request timed out. Try again.', false);
        } else {
            setStatus('Unable to read your location. Try again.', false);
        }
    };

    const startTracking = () => {
        if (!('geolocation' in navigator)) {
            setStatus('Geolocation is not supported on this device.', false);

            return;
        }

        if (trackingState.active) {
            return;
        }

        trackingState.active = true;
        updateTrackingUI();
        requestWakeLock();

        trackingState.watchId = navigator.geolocation.watchPosition(handlePosition, handlePositionError, {
            enableHighAccuracy: true,
            maximumAge: 5000,
            timeout: 15000,
        });
    };

    const stopTracking = () => {
        if (trackingState.watchId !== null) {
            navigator.geolocation.clearWatch(trackingState.watchId);
            trackingState.watchId = null;
        }

        trackingState.active = false;
        updateTrackingUI();
        releaseWakeLock();
    };

    const ensureStarted = async () => {
        if (currentStatus !== 'assigned') {
            return true;
        }

        const payload = await postStatusUpdate(assignmentRoot.dataset.startUrl);

        if (!payload) {
            return false;
        }

        currentStatus = payload.status ?? 'in_progress';
        updateButtons(currentStatus);
        setStatus(payload.message || statusLabels[currentStatus] || 'Route updated.');

        return currentStatus === 'in_progress';
    };

    const toggleTracking = async () => {
        if (trackingState.active) {
            stopTracking();

            return;
        }

        startButton.disabled = true;
        const started = await ensureStarted();
        startButton.disabled = false;

        if (!started) {
            return;
        }

        startTracking();
    };

    const loadExistingTracking = async () => {
        if (!trackingIndexUrl || !hasRouteCoordinates) {
            return;
        }

        try {
            const response = await fetch(trackingIndexUrl, {
                headers: {
                    Accept: 'application/json',
                },
            });

            if (!response.ok) {
                return;
            }

            const payload = await response.json();
            const points = payload?.data ?? [];

            if (!points.length) {
                updateProgressDisplay(0);

                return;
            }

            const startProjection = Array(routeIndexes.length).fill(null);
            const maxProjection = Array(routeIndexes.length).fill(null);
            const lastPoint = points[points.length - 1];

            points.forEach((point) => {
                const pointCoordinates = [
                    point.snapped_longitude ?? point.longitude,
                    point.snapped_latitude ?? point.latitude,
                ];
                const projection = projectPointToRoute(pointCoordinates);
                const threshold = resolveRouteThreshold(point.accuracy);

                if (!projection || projection.distanceFromSegment > threshold) {
                    return;
                }

                const routeIndex = projection.routeIndex ?? 0;

                if (startProjection[routeIndex] === null) {
                    startProjection[routeIndex] = projection.distanceAlong;
                    maxProjection[routeIndex] = projection.distanceAlong;

                    return;
                }

                if (projection.distanceAlong > (maxProjection[routeIndex] ?? 0)) {
                    maxProjection[routeIndex] = projection.distanceAlong;
                }
            });

            const hasProgress = startProjection.some((value) => value !== null);

            if (hasProgress) {
                trackingState.startDistanceAlong = startProjection;
                trackingState.maxProjectionDistance = maxProjection;
                let traveled = 0;
                startProjection.forEach((startDistance, index) => {
                    if (startDistance === null || maxProjection[index] === null) {
                        return;
                    }

                    traveled += Math.max(maxProjection[index] - startDistance, 0);
                });
                trackingState.maxDistance = traveled;
                updateProgressDisplay(traveled);
                updateCompletedRoute(startProjection, maxProjection);
            } else {
                updateProgressDisplay(0);
            }

            if (lastPoint) {
                const lastCoordinates = [
                    lastPoint.snapped_longitude ?? lastPoint.longitude,
                    lastPoint.snapped_latitude ?? lastPoint.latitude,
                ];
                updateLocationMarker(lastCoordinates, lastPoint.accuracy);
                trackingState.lastTrackedAt = lastPoint.captured_at ? new Date(lastPoint.captured_at) : null;
            }

            updateResumeBanner();
        } catch {
            updateProgressDisplay(0);
        }
    };

    const formatTrackedTime = (value) => {
        if (!value) {
            return null;
        }

        const dateValue = value instanceof Date ? value : new Date(value);

        if (Number.isNaN(dateValue.getTime())) {
            return null;
        }

        const now = new Date();
        const isSameDay =
            now.getFullYear() === dateValue.getFullYear() &&
            now.getMonth() === dateValue.getMonth() &&
            now.getDate() === dateValue.getDate();
        const timeFormatter = new Intl.DateTimeFormat(undefined, {
            hour: 'numeric',
            minute: '2-digit',
        });
        const dateFormatter = new Intl.DateTimeFormat(undefined, {
            month: 'short',
            day: 'numeric',
            year: 'numeric',
        });

        if (isSameDay) {
            return `Last tracked at ${timeFormatter.format(dateValue)}`;
        }

        return `Last tracked on ${dateFormatter.format(dateValue)} at ${timeFormatter.format(dateValue)}`;
    };

    const updateResumeBanner = () => {
        if (!resumeBanner || !resumeTimeElement) {
            return;
        }

        if (trackingState.active || currentStatus === 'completed' || !trackingState.lastTrackedAt) {
            resumeBanner.classList.add('hidden');

            return;
        }

        const label = formatTrackedTime(trackingState.lastTrackedAt);

        if (!label) {
            resumeBanner.classList.add('hidden');

            return;
        }

        resumeTimeElement.textContent = label;
        resumeBanner.classList.remove('hidden');
    };

    const setManualLabel = () => {
        if (!manualMarkLabel) {
            return;
        }

        manualMarkLabel.textContent = manualState.active ? 'Stop Manual' : 'Mark Manually';
    };

    const handleManualPoint = async (latLng) => {
        const rawPoint = [latLng.lng, latLng.lat];
        const projection = projectPointToRoute(rawPoint);

        const threshold = resolveRouteThreshold(null);

        if (projection && projection.distanceFromSegment <= threshold) {
            const routeIndex = projection.routeIndex ?? 0;
            const startDistances = trackingState.startDistanceAlong;
            const maxDistances = trackingState.maxProjectionDistance;

            if (startDistances[routeIndex] === null) {
                startDistances[routeIndex] = projection.distanceAlong;
                maxDistances[routeIndex] = projection.distanceAlong;
            } else if (projection.distanceAlong > (maxDistances[routeIndex] ?? 0)) {
                maxDistances[routeIndex] = projection.distanceAlong;
            }

            let traveled = 0;
            startDistances.forEach((startDistance, index) => {
                if (startDistance === null || maxDistances[index] === null) {
                    return;
                }

                traveled += Math.max(maxDistances[index] - startDistance, 0);
            });

            trackingState.maxDistance = traveled;
            updateProgressDisplay(traveled);
            updateCompletedRoute(startDistances, maxDistances);
        } else if (trackingNoteElement) {
            trackingNoteElement.textContent = 'Tap closer to the route to mark it manually.';
        }

        updateLocationMarker(rawPoint, null);

        await sendTrackingPoint({
            latitude: rawPoint[1],
            longitude: rawPoint[0],
            snapped_latitude: rawPoint[1],
            snapped_longitude: rawPoint[0],
            accuracy: null,
            captured_at: new Date().toISOString(),
        });
    };

    if (trackingNoteElement) {
        trackingNoteElement.textContent = defaultTrackingNote;
    }

    if (trackingNoteElement && !('geolocation' in navigator)) {
        trackingNoteElement.textContent = 'Geolocation is unavailable in this browser.';
    }

    if (manualMarkButton) {
        manualMarkButton.addEventListener('click', () => {
            manualState.active = !manualState.active;
            setManualLabel();

            if (manualState.active) {
                manualState.points = [];
                if (manualState.layer) {
                    map.removeLayer(manualState.layer);
                    manualState.layer = null;
                }
                if (manualState.verticesLayer) {
                    map.removeLayer(manualState.verticesLayer);
                }
                manualState.verticesLayer = L.layerGroup().addTo(map);
                setStatus('Tap the map to mark roads manually.');
            } else {
                setStatus('Manual marking paused.');
            }
        });
    }

    map.on('click', (event) => {
        if (!manualState.active) {
            return;
        }

        manualState.points.push(event.latlng);

        if (manualState.verticesLayer) {
            L.circleMarker(event.latlng, {
                radius: 4,
                color: '#1b5e20',
                fillColor: '#1b5e20',
                fillOpacity: 0.9,
                weight: 1,
            }).addTo(manualState.verticesLayer);
        }

        if (!manualState.layer) {
            manualState.layer = L.polyline(manualState.points, {
                color: '#1b5e20',
                weight: 4,
                dashArray: '8 8',
                opacity: 0.9,
            }).addTo(map);
        } else {
            manualState.layer.setLatLngs(manualState.points);
        }

        handleManualPoint(event.latlng);
    });

    startButton.addEventListener('click', toggleTracking);

    if (resumeButton) {
        resumeButton.addEventListener('click', toggleTracking);
    }

    completeButton.addEventListener('click', async () => {
        completeButton.disabled = true;

        const payload = await postStatusUpdate(assignmentRoot.dataset.completeUrl);

        completeButton.disabled = false;

        if (!payload) {
            return;
        }

        currentStatus = payload.status ?? 'completed';
        stopTracking();
        updateButtons(currentStatus);
        setStatus(payload.message || statusLabels[currentStatus] || 'Route updated.');
    });

    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible' && trackingState.active) {
            requestWakeLock();
        }
    });

    setManualLabel();
    updateTrackingUI();
    loadExistingTracking();
}
