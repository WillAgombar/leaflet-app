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

    const routeCoordinates = routeData?.features?.[0]?.geometry?.coordinates ?? [];
    const hasRouteCoordinates = Array.isArray(routeCoordinates) && routeCoordinates.length > 1;

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

    const routeIndex = buildRouteIndex(routeCoordinates);
    const totalRouteLengthMeters = routeIndex?.totalLength ?? null;

    const calculateRouteLengthKm = () => {
        if (!routeIndex) {
            return null;
        }

        return routeIndex.totalLength / 1000;
    };

    const projectPointToRoute = (point) => {
        if (!routeIndex || !hasRouteCoordinates) {
            return null;
        }

        let closestDistance = Number.POSITIVE_INFINITY;
        let distanceAlong = 0;

        for (let index = 0; index < routeCoordinates.length - 1; index += 1) {
            const start = routeCoordinates[index];
            const end = routeCoordinates[index + 1];
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
            }
        }

        return {
            distanceAlong,
            distanceFromSegment: closestDistance,
        };
    };

    const buildRouteSlice = (distanceAlong) => {
        if (!routeIndex || !hasRouteCoordinates) {
            return [];
        }

        const clampedDistance = Math.min(Math.max(distanceAlong, 0), routeIndex.totalLength);
        const sliceCoordinates = [routeCoordinates[0]];
        let remaining = clampedDistance;

        for (let index = 0; index < routeCoordinates.length - 1; index += 1) {
            if (remaining <= 0) {
                break;
            }

            const segmentLength = routeIndex.segmentLengths[index];
            const start = routeCoordinates[index];
            const end = routeCoordinates[index + 1];

            if (segmentLength <= 0) {
                continue;
            }

            if (remaining >= segmentLength) {
                sliceCoordinates.push(end);
                remaining -= segmentLength;

                continue;
            }

            const fraction = remaining / segmentLength;
            sliceCoordinates.push([
                start[0] + (end[0] - start[0]) * fraction,
                start[1] + (end[1] - start[1]) * fraction,
            ]);
            remaining = 0;
        }

        return sliceCoordinates;
    };

    const updateProgressDisplay = (distanceAlong) => {
        if (!totalRouteLengthMeters || !progressValueElement || !progressBarElement) {
            return;
        }

        const percent = Math.min(Math.max((distanceAlong / totalRouteLengthMeters) * 100, 0), 100);
        progressValueElement.textContent = `${Math.round(percent)}%`;
        progressBarElement.style.width = `${percent}%`;
    };

    let routeLayer = null;
    let completedLayer = null;
    let locationMarker = null;
    let accuracyCircle = null;

    if (routeData && routeData.type === 'FeatureCollection') {
        routeLayer = L.geoJSON(routeData, {
            style: {
                color: '#b7e1bf',
                weight: 8,
                lineCap: 'round',
                lineJoin: 'round',
                opacity: 0.9,
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

        if (lengthKm) {
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

    const updateCompletedRoute = (distanceAlong) => {
        if (!hasRouteCoordinates) {
            return;
        }

        const slice = buildRouteSlice(distanceAlong);

        if (!slice.length) {
            return;
        }

        if (!completedLayer) {
            completedLayer = L.polyline(slice.map((coord) => [coord[1], coord[0]]), {
                color: '#1b5e20',
                weight: 8,
                lineCap: 'round',
                lineJoin: 'round',
                opacity: 0.95,
            }).addTo(map);

            return;
        }

        completedLayer.setLatLngs(slice.map((coord) => [coord[1], coord[0]]));
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

            if (projection && projection.distanceAlong > trackingState.maxDistance) {
                trackingState.maxDistance = projection.distanceAlong;
                updateProgressDisplay(trackingState.maxDistance);
                updateCompletedRoute(trackingState.maxDistance);
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

            let maxDistance = 0;
            const lastPoint = points[points.length - 1];

            points.forEach((point) => {
                const pointCoordinates = [
                    point.snapped_longitude ?? point.longitude,
                    point.snapped_latitude ?? point.latitude,
                ];
                const projection = projectPointToRoute(pointCoordinates);

                if (projection && projection.distanceAlong > maxDistance) {
                    maxDistance = projection.distanceAlong;
                }
            });

            trackingState.maxDistance = maxDistance;
            updateProgressDisplay(maxDistance);
            updateCompletedRoute(maxDistance);

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

    if (trackingNoteElement && !('geolocation' in navigator)) {
        trackingNoteElement.textContent = 'Geolocation is unavailable in this browser.';
    }

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

    updateTrackingUI();
    loadExistingTracking();
}
