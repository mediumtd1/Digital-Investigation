<?php
require_once __DIR__ . '/db.php';

if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    $pdo = getDbConnection();

    if ($_GET['action'] === 'states') {
        $stmt = $pdo->query('SELECT DISTINCT state FROM police_stations ORDER BY state');
        echo json_encode($stmt->fetchAll(PDO::FETCH_COLUMN));
        exit;
    }

    if ($_GET['action'] === 'districts' && isset($_GET['state'])) {
        $stmt = $pdo->prepare('SELECT DISTINCT district FROM police_stations WHERE state = ? ORDER BY district');
        $stmt->execute([$_GET['state']]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_COLUMN));
        exit;
    }

    if ($_GET['action'] === 'stations' && isset($_GET['state'], $_GET['district'])) {
        $stmt = $pdo->prepare('SELECT id, police_station FROM police_stations WHERE state = ? AND district = ? ORDER BY police_station');
        $stmt->execute([$_GET['state'], $_GET['district']]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    if ($_GET['action'] === 'details' && isset($_GET['id'])) {
        $stmt = $pdo->prepare('SELECT * FROM police_stations WHERE id = ?');
        $stmt->execute([intval($_GET['id'])]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        echo json_encode($row ?: []);
        exit;
    }

    echo json_encode([]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Search Police Stations</title>
    <style>
        body { margin: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #0F172A; color: #E2E8F0; }
        .page { max-width: 980px; margin: 0 auto; padding: 32px; }
        h1 { margin-bottom: 8px; color: #E0F2FE; }
        p { color: #CBD5E1; margin-bottom: 24px; }
        label { display: block; margin-top: 18px; color: #94A3B8; font-weight: 600; }
        select { width: 100%; max-width: 520px; padding: 14px 16px; border-radius: 14px; border: 1px solid #334155; background: #0F172A; color: #E2E8F0; }
        select:disabled { opacity: 0.7; }
        .page-tabs { display: flex; gap: 8px; margin-bottom: 20px; }
        .tab { display: inline-flex; align-items: center; padding: 10px 18px; border-radius: 999px; text-decoration: none; font-weight: 700; color: #94A3B8; background: #111827; border: 1px solid #1E293B; }
        .tab.active, .tab:hover { color: #fff; background: #2563EB; border-color: #2563EB; }
        .details { margin-top: 32px; padding: 0; background: transparent; border: none; box-shadow: none; }
        .station-card { font-family: 'Segoe UI', Arial, sans-serif; max-width: 380px; border-radius: 16px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); overflow: hidden; background: #ffffff; border: 1px solid #eef2f5; margin: 20px auto; color: #2d3748; }
        .station-card-header { background: linear-gradient(135deg, #1a365d 0%, #2a4365 100%); padding: 24px 20px; text-align: center; color: white; }
        .station-card-icon { font-size: 42px; margin-bottom: 8px; }
        .station-card-header h2 { margin: 0; font-size: 22px; font-weight: 700; letter-spacing: 0.5px; text-transform: uppercase; }
        .station-card-header p { margin: 4px 0 0 0; opacity: 0.85; font-size: 13px; font-weight: 600; letter-spacing: 1px; }
        .station-card-body { padding: 24px 20px; }
        .detail-row { display: flex; align-items: center; margin-bottom: 16px; }
        .detail-row:last-child { margin-bottom: 24px; }
        .detail-row-icon { font-size: 20px; width: 35px; text-align: left; }
        .detail-row-info span { display: block; }
        .detail-row-label { font-size: 11px; color: #718096; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
        .detail-row-value { font-size: 15px; color: #2d3748; font-weight: 600; }
        .detail-link { font-size: 15px; color: #2b6cb0; font-weight: 600; text-decoration: none; }
        .direction-button { display: none; width: 100%; background: #3182ce; color: white; text-align: center; padding: 14px; border-radius: 10px; font-weight: 600; font-size: 15px; text-decoration: none; border: none; cursor: pointer; box-shadow: 0 4px 6px rgba(66, 153, 225, 0.2); transition: background 0.2s; }
        .direction-button:hover { background: #2b6cb0; }
        .map-frame-wrapper { display: none; margin-top: 20px; border-radius: 18px; overflow: hidden; border: 1px solid #1E293B; }
        .map-frame-wrapper iframe { width: 100%; min-height: 420px; border: none; }
        .button-link { display: none; }
        @media (max-width: 900px) {
            .details-grid { grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); }
            .detail-card { padding: 16px; }
        }
        @media (max-width: 640px) {
            .details-grid { grid-template-columns: 1fr; }
            .detail-card { padding: 14px; }
        }
        .map-frame-wrapper { display: none; margin-top: 20px; border-radius: 18px; overflow: hidden; border: 1px solid #1E293B; }
        .map-frame-wrapper iframe { width: 100%; min-height: 420px; border: none; }
        .button-link { display: none; align-items: center; justify-content: center; margin-top: 18px; padding: 12px 18px; background: #2563EB; color: #fff; text-decoration: none; border-radius: 12px; }
        .button-link:hover { background: #1D4ED8; }
        .top-actions { display: flex; flex-wrap: wrap; gap: 12px; margin-bottom: 24px; }
        .control-group { display: grid; gap: 16px; }
    </style>
</head>
<body>
    <div class="page">
        <div class="page-tabs">
            <a class="tab active" href="https://digitalinvestigation.in/officer/dashboard.php">Dashboard</a>
        </div>
        <header style="display:flex; align-items:center; justify-content:space-between; gap:16px; margin-bottom:28px;">
            <div>
                <h1 style="margin:0;">Search Police Stations</h1>
                <p style="margin:12px 0 0; color:#CBD5E1;">Select State, District, and Station to view details and map directions.</p>
            </div>
        </header>

    <div class="control-group">
        <label for="state">State</label>
        <select id="state"><option value="">Loading states...</option></select>

        <label for="district">District</label>
        <select id="district" disabled><option value="">Select state first</option></select>

        <label for="station">Police Station</label>
        <select id="station" disabled><option value="">Select district first</option></select>
    </div>

    <div class="details" id="details" style="display:none;">
        <div class="station-card" id="stationCard">
            <div class="station-card-header">
                <div class="station-card-icon">🏛️</div>
                <h2 id="detailTitle">Police Station</h2>
                <p>POLICE STATION</p>
            </div>
            <div class="station-card-body" id="detailList"></div>
        </div>
        <button id="directionsLink" class="direction-button" type="button">🚗 Show directions on map</button>
        <div class="map-frame-wrapper" id="googleMapWrapper">
            <iframe id="googleMapFrame" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
        </div>
    </div>

    </div>

    <script>
        const stateSelect = document.getElementById('state');
        const districtSelect = document.getElementById('district');
        const stationSelect = document.getElementById('station');
        const detailsEl = document.getElementById('details');
        const detailList = document.getElementById('detailList');
        const directionsLink = document.getElementById('directionsLink');
        const googleMapWrapper = document.getElementById('googleMapWrapper');
        const googleMapFrame = document.getElementById('googleMapFrame');
        const apiPath = location.pathname.replace(/\/$/, '');
        let currentLatLng = null;

        function onReady(callback) {
            if (document.readyState !== 'loading') {
                callback();
            } else {
                document.addEventListener('DOMContentLoaded', callback);
            }
        }

        onReady(loadStates);

        directionsLink.addEventListener('click', event => {
            event.preventDefault();
            if (currentLatLng) {
                showDirectionsOnMap(currentLatLng.lat, currentLatLng.lng);
            }
        });

        function fetchJson(url) {
            return fetch(url).then(res => {
                if (!res.ok) {
                    throw new Error('Server error: ' + res.status);
                }
                return res.json();
            });
        }

        function apiUrl(query) {
            const path = apiPath || window.location.pathname.replace(/\/$/, '');
            return path + '?' + query;
        }

        function setOptions(select, items, placeholder) {
            select.innerHTML = '';
            const emptyOption = document.createElement('option');
            emptyOption.value = '';
            emptyOption.textContent = placeholder;
            select.appendChild(emptyOption);
            if (!Array.isArray(items)) {
                return;
            }
            items.forEach(item => {
                const option = document.createElement('option');
                if (typeof item === 'string') {
                    option.value = item;
                    option.textContent = item;
                } else {
                    option.value = item.id;
                    option.textContent = item.police_station;
                }
                select.appendChild(option);
            });
        }

        function loadStates() {
            stateSelect.disabled = true;
            districtSelect.disabled = true;
            stationSelect.disabled = true;

            fetchJson(apiUrl('action=states')).then(states => {
                if (!Array.isArray(states) || states.length === 0) {
                    console.warn('No states returned from server:', states);
                    setOptions(stateSelect, [], 'No states found');
                    stateSelect.disabled = true;
                    districtSelect.disabled = true;
                    stationSelect.disabled = true;
                    return;
                }
                setOptions(stateSelect, states, 'Select state');
                stateSelect.disabled = false;
                districtSelect.innerHTML = '<option value="">Select state first</option>';
                stationSelect.innerHTML = '<option value="">Select district first</option>';
            }).catch(error => {
                console.error('Failed to load states:', error);
                setOptions(stateSelect, [], 'Unable to load states');
                stateSelect.disabled = true;
                districtSelect.disabled = true;
                stationSelect.disabled = true;
            });
        }

        stateSelect.addEventListener('change', () => {
            const state = stateSelect.value;
            detailsEl.style.display = 'none';
            stationSelect.disabled = true;
            if (!state) {
                districtSelect.disabled = true;
                setOptions(districtSelect, [], 'Select state first');
                setOptions(stationSelect, [], 'Select district first');
                return;
            }
            districtSelect.disabled = false;
            fetchJson(apiUrl('action=districts&state=' + encodeURIComponent(state))).then(districts => {
                setOptions(districtSelect, districts, 'Select district');
                stationSelect.disabled = true;
                setOptions(stationSelect, [], 'Select district first');
            });
        });

        districtSelect.addEventListener('change', () => {
            const state = stateSelect.value;
            const district = districtSelect.value;
            detailsEl.style.display = 'none';
            if (!district) {
                stationSelect.disabled = true;
                setOptions(stationSelect, [], 'Select district first');
                return;
            }
            fetchJson(apiUrl('action=stations&state=' + encodeURIComponent(state) + '&district=' + encodeURIComponent(district))).then(stations => {
                setOptions(stationSelect, stations, 'Select police station');
                stationSelect.disabled = false;
            });
        });

        function showGoogleMap(lat, lng) {
            googleMapFrame.src = 'https://maps.google.com/maps?q=' + encodeURIComponent(lat + ',' + lng) + '&z=15&output=embed';
            googleMapWrapper.style.display = 'block';
        }

        function showDirectionsOnMap(lat, lng) {
            const destination = encodeURIComponent(lat + ',' + lng);
            if (navigator.geolocation) {
                directionsLink.textContent = 'Getting your location...';
                directionsLink.disabled = true;
                navigator.geolocation.getCurrentPosition(position => {
                    const origin = position.coords.latitude + ',' + position.coords.longitude;
                    googleMapFrame.src = 'https://maps.google.com/maps?saddr=' + encodeURIComponent(origin) + '&daddr=' + destination + '&output=embed';
                    googleMapWrapper.style.display = 'block';
                    directionsLink.textContent = 'Show directions on map';
                    directionsLink.disabled = false;
                }, () => {
                    googleMapFrame.src = 'https://maps.google.com/maps?saddr=Current+Location&daddr=' + destination + '&output=embed';
                    googleMapWrapper.style.display = 'block';
                    directionsLink.textContent = 'Show directions on map';
                    directionsLink.disabled = false;
                }, { timeout: 10000 });
            } else {
                googleMapFrame.src = 'https://maps.google.com/maps?saddr=Current+Location&daddr=' + destination + '&output=embed';
                googleMapWrapper.style.display = 'block';
            }
        }

        function getFieldIcon(label) {
            switch (label) {
                case 'State': return '🏛️';
                case 'District': return '📍';
                case 'Police Station': return '🚓';
                case 'Contact': return '📞';
                case 'Location': return '🗺️';
                default: return 'ℹ️';
            }
        }

        stationSelect.addEventListener('change', () => {
            const id = stationSelect.value;
            currentLatLng = null;
            if (!id) {
                detailsEl.style.display = 'none';
                googleMapWrapper.style.display = 'none';
                googleMapFrame.src = '';
                directionsLink.style.display = 'none';
                return;
            }

            fetchJson(apiUrl('action=details&id=' + encodeURIComponent(id))).then(data => {
                console.log('Selected station data:', data);
                if (!data || typeof data !== 'object' || Object.keys(data).length === 0) {
                    detailsEl.style.display = 'none';
                    googleMapWrapper.style.display = 'none';
                    googleMapFrame.src = '';
                    directionsLink.style.display = 'none';
                    return;
                }

                function parseCoordinates(value) {
                    if (!value) return null;
                    const parts = value.toString().trim().replace(/\s+/g, ' ').replace(/\s*,\s*/g, ',').split(/[ ,]+/);
                    if (parts.length < 2) return null;
                    const lat = parseFloat(parts[0]);
                    const lng = parseFloat(parts[1]);
                    if (Number.isFinite(lat) && Number.isFinite(lng)) {
                        return { lat, lng };
                    }
                    return null;
                }

                const rawLocation = data.location || '';
                const locationCoords = parseCoordinates(rawLocation);
                const lat = locationCoords ? locationCoords.lat : (data.latitude ? parseFloat(data.latitude) : NaN);
                const lng = locationCoords ? locationCoords.lng : (data.longitude ? parseFloat(data.longitude) : NaN);
                const validCoords = Number.isFinite(lat) && Number.isFinite(lng);

                detailList.innerHTML = '';
                const title = document.getElementById('detailTitle');
                title.textContent = data.police_station || 'Police Station';

                const rows = [
                    ['State', data.state || 'N/A', '🗺️'],
                    ['District', data.district || 'N/A', '📍'],
                    ['Contact', data.contact ? '+91 ' + data.contact : 'N/A', '📞'],
                    ['Location', data.location || 'N/A', '🚓'],
                ];

                rows.forEach(([label, value, icon]) => {
                    const row = document.createElement('div');
                    row.className = 'detail-row';

                    const iconEl = document.createElement('div');
                    iconEl.className = 'detail-row-icon';
                    iconEl.textContent = icon;

                    const info = document.createElement('div');
                    info.className = 'detail-row-info';

                    const labelEl = document.createElement('span');
                    labelEl.className = 'detail-row-label';
                    labelEl.textContent = label;

                    const valueEl = document.createElement('span');
                    valueEl.className = 'detail-row-value';
                    if (label === 'Contact' && data.contact) {
                        const link = document.createElement('a');
                        link.className = 'detail-link';
                        link.href = 'tel:' + data.contact;
                        link.textContent = '+91 ' + data.contact;
                        valueEl.appendChild(link);
                    } else if (label === 'Location' && validCoords) {
                        const locationLink = document.createElement('a');
                        locationLink.className = 'detail-link';
                        locationLink.href = '#';
                        locationLink.textContent = value;
                        locationLink.addEventListener('click', event => {
                            event.preventDefault();
                            showGoogleMap(lat, lng);
                        });
                        valueEl.appendChild(locationLink);
                    } else {
                        valueEl.textContent = value;
                    }

                    info.appendChild(labelEl);
                    info.appendChild(valueEl);
                    row.appendChild(iconEl);
                    row.appendChild(info);
                    detailList.appendChild(row);
                });

                if (validCoords) {
                    currentLatLng = { lat, lng };
                    directionsLink.style.display = 'inline-flex';
                    directionsLink.textContent = 'Show directions on map';
                    directionsLink.disabled = false;
                } else {
                    currentLatLng = null;
                    directionsLink.style.display = 'none';
                }

                googleMapWrapper.style.display = 'none';
                googleMapFrame.src = '';
                detailsEl.style.display = 'block';
            }).catch(error => {
                console.error('Unable to load station details:', error);
                detailsEl.style.display = 'none';
                directionsLink.style.display = 'none';
                googleMapWrapper.style.display = 'none';
                googleMapFrame.src = '';
            });
        });

    </script>
</body>
</html>
