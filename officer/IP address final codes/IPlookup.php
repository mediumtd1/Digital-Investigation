<?php
require_once __DIR__ . '/../../config/db.php';

$last_results = $_SESSION['last_results'] ?? null;
unset($_SESSION['last_results']);
$error_msg = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($_POST['single_ip'])) {
    $ip = trim($_POST['single_ip']);
    if (filter_var($ip, FILTER_VALIDATE_IP) && $pdo) {
        $url = "https://ipapi.co/" . $ip . "/json/";
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        $response = curl_exec($ch);
        $curl_error = curl_error($ch);
        curl_close($ch);

        if ($response) {
            $data = json_decode($response, true);
            if (isset($data['ip'])) {
                $_SESSION['last_results'] = [$data];
                try {
                    $stmt = $pdo->prepare("INSERT INTO ip_search_history (ip, country, region, city, zip, isp, lat, lon, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $data['ip'], 
                        $data['country_name'] ?? 'Unknown', 
                        $data['region'] ?? '',
                        $data['city'] ?? '', 
                        $data['postal'] ?? '', 
                        $data['org'] ?? '',
                        $data['latitude'] ?? 0, 
                        $data['longitude'] ?? 0, 
                        'success'
                    ]);
                } catch (Exception $e) { $error_msg = "DB Error: " . $e->getMessage(); }
                
                if (empty($error_msg)) {
                    header("Location: IPlookup.php?success=1");
                    exit;
                }
            } else { $error_msg = "API Error: " . ($data['reason'] ?? 'Unknown response'); }
        } else { $error_msg = "Connection Error: " . $curl_error; }
    } else { $error_msg = "Invalid IP Address format."; }
}

$history = [];
if ($pdo) {
    try { $history = $pdo->query("SELECT * FROM ip_search_history ORDER BY search_date DESC LIMIT 10")->fetchAll(); } catch (Exception $e) {}
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>SECURE IP INVESTIGATION</title>
    <link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@600&family=Inter:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        :root { --accent: #00f3ff; --bg: #0b1120; }
        body { background: transparent; color: #fff; font-family: 'Inter', sans-serif; padding: 20px; margin: 0; overflow-x: hidden; }
        .box { background: rgba(30, 41, 59, 0.9); border: 1px solid #334155; border-radius: 12px; padding: 25px; margin-bottom: 20px; box-shadow: 0 10px 40px rgba(0,0,0,0.6); }
        h2 { font-family: 'Rajdhani', sans-serif; color: var(--accent); margin-top: 0; text-transform: uppercase; border-bottom: 1px solid #334155; padding-bottom: 10px; }
        input { width: 100%; padding: 15px; background: #0f172a; border: 1px solid #334155; color: #fff; border-radius: 8px; margin: 15px 0; font-size: 1rem; box-sizing: border-box; }
        .btn { background: #3b82f6; color: #fff; border: none; padding: 15px; border-radius: 8px; cursor: pointer; font-weight: 700; width: 100%; text-transform: uppercase; }
        #map { height: 450px; border-radius: 12px; border: 2px solid var(--accent); margin: 20px 0; background: #111; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 15px; border-bottom: 1px solid rgba(255,255,255,0.1); }
        th { color: var(--accent); font-size: 0.8rem; }
        .error-banner { background: #ef4444; color: white; padding: 15px; border-radius: 8px; margin-bottom: 20px; font-weight: bold; }
    </style>
</head>
<body>
    <?php if ($error_msg): ?>
        <div class="error-banner"><?= esc($error_msg) ?></div>
    <?php endif; ?>

    <div class="box">
        <h2>SECURE IP TRACKER</h2>
        <form method="POST" action="">
            <input type="text" name="single_ip" placeholder="ENTER TARGET IP (e.g. 8.8.8.8)" required>
            <button type="submit" class="btn">INITIALIZE TRACE</button>
        </form>
    </div>

    <?php if ($last_results): ?>
        <div id="map"></div>
        <div class="box">
            <h2>LOCATION DATA</h2>
            <table>
                <thead><tr><th>IP</th><th>LOCATION</th><th>ISP/ORG</th><th>COORDS</th></tr></thead>
                <tbody>
                <?php foreach ($last_results as $r): ?>
                    <tr>
                        <td><b style="color:var(--accent)"><?= esc($r['ip']) ?></b></td>
                        <td><?= esc(($r['city']??'Unknown').', '.($r['country_name']??'Unknown')) ?></td>
                        <td><?= esc($r['org']??'N/A') ?></td>
                        <td><?= ($r['latitude']??0).', '.($r['longitude']??0) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
        <script>
            var map = L.map('map').setView([0, 0], 2);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
            <?php foreach ($last_results as $r): if (isset($r['latitude'])): ?>
                L.marker([<?= $r['latitude'] ?>, <?= $r['longitude'] ?>]).addTo(map)
                 .bindPopup("<b>LOCATION LOCKED</b><br><?= esc($r['ip']) ?>").openPopup();
                map.flyTo([<?= $r['latitude'] ?>, <?= $r['longitude'] ?>], 13);
            <?php endif; endforeach; ?>
        </script>
    <?php endif; ?>

    <div class="box">
        <h2>RECENT TRACES</h2>
        <table>
            <tbody>
            <?php foreach ($history as $h): ?>
                <tr><td><?= esc($h['ip']) ?></td><td><?= esc($h['country']) ?></td><td><?= $h['search_date'] ?></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>