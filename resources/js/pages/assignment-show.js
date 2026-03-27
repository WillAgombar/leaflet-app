const assignmentRoot = document.querySelector('[data-assignment]');

if (assignmentRoot) {
    const mapElement = document.getElementById('assignment-map');
    const statusElement = document.getElementById('assignment-status');
    const startButton = document.getElementById('start-assignment-button');
    const completeButton = document.getElementById('complete-assignment-button');
    const routeLengthElement = document.getElementById('route-length');

    if (!mapElement || !statusElement || !startButton || !completeButton) {
        throw new Error('Assignment UI is missing required elements.');
    }

    const routeDataRaw = assignmentRoot.dataset.route ?? 'null';
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
    const distanceBetween = (start, end) => {
        const radius = 6371;
        const deltaLat = toRadians(end[1] - start[1]);
        const deltaLng = toRadians(end[0] - start[0]);
        const lat1 = toRadians(start[1]);
        const lat2 = toRadians(end[1]);
        const a = Math.sin(deltaLat / 2) ** 2 + Math.cos(lat1) * Math.cos(lat2) * Math.sin(deltaLng / 2) ** 2;
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));

        return radius * c;
    };

    const calculateRouteLengthKm = (featureCollection) => {
        if (!featureCollection || featureCollection.type !== 'FeatureCollection') {
            return null;
        }

        const coordinates = featureCollection.features?.[0]?.geometry?.coordinates;

        if (!Array.isArray(coordinates) || coordinates.length < 2) {
            return null;
        }

        return coordinates.reduce((total, point, index) => {
            if (index === 0) {
                return total;
            }

            return total + distanceBetween(coordinates[index - 1], point);
        }, 0);
    };

    if (routeData && routeData.type === 'FeatureCollection') {
        const routeLayer = L.geoJSON(routeData, {
            style: {
                color: '#1b5e20',
                weight: 8,
                lineCap: 'round',
                lineJoin: 'round',
                opacity: 0.95,
            },
        }).addTo(map);

        const bounds = routeLayer.getBounds();

        if (bounds.isValid()) {
            map.fitBounds(bounds.pad(0.25));
        }
    } else {
        map.setView([51.0629, -1.3160], 13);
    }

    if (routeLengthElement) {
        const lengthKm = calculateRouteLengthKm(routeData);

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
        if (status === 'assigned') {
            startButton.classList.remove('hidden');
            completeButton.classList.add('hidden');

            return;
        }

        if (status === 'in_progress') {
            startButton.classList.add('hidden');
            completeButton.classList.remove('hidden');

            return;
        }

        startButton.classList.add('hidden');
        completeButton.classList.add('hidden');
    };

    let currentStatus = assignmentRoot.dataset.status ?? 'assigned';
    updateButtons(currentStatus);

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

    startButton.addEventListener('click', async () => {
        startButton.disabled = true;

        const payload = await postStatusUpdate(assignmentRoot.dataset.startUrl);

        startButton.disabled = false;

        if (!payload) {
            return;
        }

        currentStatus = payload.status ?? 'in_progress';
        updateButtons(currentStatus);
        setStatus(payload.message || statusLabels[currentStatus] || 'Route updated.');
    });

    completeButton.addEventListener('click', async () => {
        completeButton.disabled = true;

        const payload = await postStatusUpdate(assignmentRoot.dataset.completeUrl);

        completeButton.disabled = false;

        if (!payload) {
            return;
        }

        currentStatus = payload.status ?? 'completed';
        updateButtons(currentStatus);
        setStatus(payload.message || statusLabels[currentStatus] || 'Route updated.');
    });
}
