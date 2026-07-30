<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

$is_admin = (strpos($_SERVER['PHP_SELF'], '/admin/') !== false);
check_auth('Intern'); 
$portal = 'intern';

$last_results = $_SESSION['last_results'] ?? null;
unset($_SESSION['last_results']);
$error_msg = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($_POST['single_ip'])) {
    $ip = trim($_POST['single_ip']);
    if (filter_var($ip, FILTER_VALIDATE_IP)) {
        
        
        $url = "http://ip-api.com/json/" . $ip . "?fields=66846719"; 
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        $response = curl_exec($ch);
        curl_close($ch);
        
        $data = json_decode($response, true);
        
        if ($data && $data['status'] === 'success') {
            $_SESSION['last_results'] = [$data];
            try {
                $stmt = $pdo->prepare("INSERT INTO ip_search_history (ip, country, region, city, zip, isp, lat, lon, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $data['query'], 
                    $data['country'] ?? 'Unknown', 
                    $data['regionName'] ?? '',
                    $data['city'] ?? '', 
                    $data['zip'] ?? '', 
                    $data['isp'] ?? '',
                    $data['lat'] ?? 0, 
                    $data['lon'] ?? 0, 
                    'success'
                ]);
            } catch (Exception $e) {}
            
            header("Location: ip_lookup.php?success=1");
            exit;
        } else { $error_msg = "API Error: " . ($data['message'] ?? 'Node Unreachable'); }
    } else { $error_msg = "Invalid IP Address format."; }
}

$history = [];
try { $history = $pdo->query("SELECT * FROM ip_search_history ORDER BY search_date DESC LIMIT 12")->fetchAll(); } catch (Exception $e) {}

$page_title = 'IP Geolocation Lab | ' . SITE_NAME;
?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.css" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.js"></script>

<style>
    :root { --cyber-accent: #00f3ff; --panel-bg: rgba(15, 23, 42, 0.9); }
    .ip-wrapper { padding: 30px; animation: slideUp 0.4s ease; }
    
    .glass-card {
        background: var(--panel-bg);
        border: 1px solid rgba(0, 243, 255, 0.15);
        border-radius: 16px;
        padding: 25px;
        backdrop-filter: blur(15px);
        margin-bottom: 25px;
        box-shadow: 0 10px 40px rgba(0,0,0,0.4);
    }

    .search-bar {
        display: flex; gap: 15px; margin-bottom: 30px;
    }
    .ip-input {
        flex: 1; background: rgba(0,0,0,0.4); border: 1px solid rgba(255,255,255,0.1);
        padding: 15px 25px; border-radius: 12px; color: #fff; font-family: 'Share Tech Mono', monospace;
        font-size: 1.2rem; outline: none; transition: 0.3s;
    }
    .ip-input:focus { border-color: var(--cyber-accent); box-shadow: 0 0 20px rgba(0,243,255,0.2); }
    
    .btn-pulse {
        background: var(--cyber-accent); color: #000; border: none; padding: 0 40px;
        border-radius: 12px; font-weight: 800; text-transform: uppercase; letter-spacing: 2px;
        cursor: pointer; transition: 0.3s;
    }
    .btn-pulse:hover { transform: translateY(-2px); box-shadow: 0 5px 20px rgba(0,243,255,0.4); }

    #map {
        height: 550px; width: 100%; border-radius: 12px;
        border: 1px solid rgba(0, 243, 255, 0.2); background: #020617;
    }

    .info-grid {
        display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; margin-top: 30px;
    }
    .info-box {
        background: rgba(0,0,0,0.3); padding: 20px; border-radius: 12px;
        border-left: 4px solid var(--cyber-accent);
    }
    .info-label { font-size: 0.7rem; color: #64748b; text-transform: uppercase; letter-spacing: 2px; margin-bottom: 8px; }
    .info-value { font-size: 1.1rem; color: #e2e8f0; font-weight: 600; font-family: 'Rajdhani', sans-serif; }

    .history-card { padding: 15px; border-bottom: 1px solid rgba(255,255,255,0.05); }
    .history-ip { color: var(--cyber-accent); font-family: 'Share Tech Mono', monospace; }

    @keyframes slideUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
</style>

<div class="portal-container">
    <?php include '_sidebar.php'; ?>

    <main class="main-content">
        <header class="topbar">
            <div class="topbar-left">
                <h2 style="font-family: 'Rajdhani', sans-serif; letter-spacing: 2px; text-transform: uppercase;">
                    <i class="fa-solid fa-satellite-dish" style="color: var(--cyber-accent);"></i> IP Geolocation Lab
                </h2>
            </div>
            <?php if($last_results): ?>
            <div class="topbar-right">
                <button onclick="window.print()" class="btn-primary no-print" style="font-size: 0.75rem; padding: 7px 18px; clip-path: polygon(5% 0, 100% 0, 95% 100%, 0% 100%); border: none; cursor: pointer; color: #000;">
                    <i class="fa-solid fa-file-pdf"></i> Export PDF
                </button>
            </div>
            <?php endif; ?>
        </header>

        <div class="ip-wrapper">
            <?php if($error_msg): ?>
                <div style="background: rgba(239, 68, 68, 0.1); color: #ef4444; padding: 15px; border-radius: 8px; margin-bottom: 25px; border: 1px solid #ef4444;">
                    <i class="fa-solid fa-circle-exclamation"></i> <?= esc($error_msg) ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="search-bar no-print">
                <input type="text" name="single_ip" class="ip-input" placeholder="> Enter Target IP Address (e.g. 152.59.4.0)" required>
                <button type="submit" class="btn-pulse">Lock & Trace</button>
            </form>

            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="glass-card" style="padding: 10px;">
                        <div id="map">
                            <div style="height: 100%; display: flex; align-items: center; justify-content: center; color: #64748b;">
                                <i class="fa-solid fa-radar fa-spin fa-3x"></i>
                            </div>
                        </div>
                    </div>
                    
                    <?php if($last_results): $r = $last_results[0]; ?>
                        <div class="info-grid">
                            <div class="info-box">
                                <div class="info-label">Geographic Location</div>
                                <div class="info-value"><?= esc($r['city']) ?>, <?= esc($r['regionName']) ?>, <?= esc($r['country']) ?></div>
                            </div>
                            <div class="info-box">
                                <div class="info-label">Network Provider (ISP)</div>
                                <div class="info-value"><?= esc($r['isp']) ?></div>
                            </div>
                            <div class="info-box">
                                <div class="info-label">Organization / ASN</div>
                                <div class="info-value"><?= esc($r['org'] ?: $r['as']) ?></div>
                            </div>
                            <div class="info-box">
                                <div class="info-label">Coordinates</div>
                                <div class="info-value"><?= $r['lat'] ?>, <?= $r['lon'] ?></div>
                            </div>
                            <div class="info-box">
                                <div class="info-label">Timezone & Local Time</div>
                                <div class="info-value"><?= esc($r['timezone']) ?></div>
                            </div>
                            <div class="info-box">
                                <div class="info-label">Proxy / VPN Detection</div>
                                <div class="info-value" style="color: <?= ($r['proxy']??0) ? '#ef4444' : '#10b981' ?>;">
                                    <?= ($r['proxy']??0) ? 'DETECTION POSITIVE' : 'SIGNAL CLEAN' ?>
                                </div>
                            </div>
                        </div>

                        <script>
                            document.addEventListener('DOMContentLoaded', function() {
                                // Define Base Layers
                                var cyberNight = L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
                                    attribution: '&copy; CartoDB'
                                });
                                
                                var tacticalDay = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                                    attribution: '&copy; OpenStreetMap'
                                });

                                var satellite = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
                                    attribution: 'Tiles &copy; Esri &mdash; Source: Esri, i-cubed, USDA, USGS, AEX, GeoEye, Getmapping, Aerogrid, IGN, IGP, UPR-EGP, and the GIS User Community'
                                });

                                var map = L.map('map', { 
                                    zoomControl: false,
                                    layers: [cyberNight] // Default
                                }).setView([<?= $r['lat'] ?>, <?= $r['lon'] ?>], 11);
                                
                                var baseMaps = {
                                    "<i class='fa-solid fa-moon'></i> Cyber Night": cyberNight,
                                    "<i class='fa-solid fa-sun'></i> Tactical Day": tacticalDay,
                                    "<i class='fa-solid fa-satellite'></i> Satellite View": satellite
                                };

                                L.control.layers(baseMaps, null, { position: 'topright', collapsed: false }).addTo(map);
                                L.control.zoom({ position: 'bottomright' }).addTo(map);

                                var markerIcon = L.divIcon({
                                    className: 'custom-div-icon',
                                    html: "<div style='width: 30px; height: 30px; background: rgba(0,243,255,0.3); border: 2px solid #00f3ff; border-radius: 50%; box-shadow: 0 0 20px #00f3ff; display:flex; align-items:center; justify-content:center;'><div style='width:8px; height:8px; background:#fff; border-radius:50%;'></div></div>",
                                    iconSize: [30, 30], iconAnchor: [15, 15]
                                });

                                L.marker([<?= $r['lat'] ?>, <?= $r['lon'] ?>], {icon: markerIcon}).addTo(map)
                                 .bindPopup("<div style='color:#000; font-family:Rajdhani; font-weight:bold;'>TRACED SIGNAL<br><?= esc($r['query']) ?></div>").openPopup();
                                 
                                L.circle([<?= $r['lat'] ?>, <?= $r['lon'] ?>], {
                                    color: '#00f3ff', fillColor: '#00f3ff', fillOpacity: 0.1, radius: 5000
                                }).addTo(map);
                            });
                        </script>
                    <?php endif; ?>
                </div>

                <div class="col-lg-4 no-print">
                    <div class="glass-card">
                        <h3 style="font-family: 'Rajdhani', sans-serif; font-size: 1.2rem; margin-bottom: 20px; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 10px;">
                            <i class="fa-solid fa-clock-rotate-left"></i> Signal History
                        </h3>
                        <div style="max-height: 700px; overflow-y: auto;">
                            <?php foreach($history as $h): ?>
                                <div class="history-card">
                                    <div class="history-ip"><?= esc($h['ip']) ?></div>
                                    <div style="font-size: 0.75rem; color: #64748b; margin-top: 5px;">
                                        <?= esc($h['city']) ?>, <?= esc($h['country']) ?>
                                    </div>
                                    <div style="font-size: 0.65rem; color: #475569; margin-top: 3px;">
                                        <?= date('M d, H:i', strtotime($h['search_date'])) ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>