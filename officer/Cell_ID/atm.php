<?php
require_once __DIR__ . '/db.php';

$pdo = getDbConnection();
$atm = null;
$message = '';
$searchValue = '';

if (isset($_GET['atm_id'])) {
    $searchValue = trim($_GET['atm_id']);
    if ($searchValue === '') {
        $message = 'Please enter an ATM machine ID or number.';
    } else {
        $stmt = $pdo->prepare('SELECT * FROM atms WHERE atm_number = ? OR id = ? LIMIT 1');
        $stmt->execute([$searchValue, is_numeric($searchValue) ? intval($searchValue) : 0]);
        $atm = $stmt->fetch();
        if (!$atm) {
            $message = 'No ATM found with that ID/number.';
        }
    }
}

function formatPhone($phone) {
    return $phone ? '+91 ' . htmlspecialchars($phone) : 'N/A';
}

function sanitize($value) {
    return htmlspecialchars($value ?: 'N/A');
}

function parseCoordinates($location, $latitude, $longitude) {
    if ($latitude && $longitude) {
        $lat = floatval($latitude);
        $lng = floatval($longitude);
        if (is_finite($lat) && is_finite($lng)) {
            return [$lat, $lng];
        }
    }
    if ($location) {
        $parts = preg_split('/[ ,]+/', trim($location));
        if (count($parts) >= 2) {
            $lat = floatval($parts[0]);
            $lng = floatval($parts[1]);
            if (is_finite($lat) && is_finite($lng)) {
                return [$lat, $lng];
            }
        }
    }
    return null;
}

$coords = null;
if ($atm) {
    $coords = parseCoordinates($atm['location'], $atm['latitude'], $atm['longitude']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>ATM Lookup</title>
    <style>
        body { margin: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #0F172A; color: #E2E8F0; }
        .page { max-width: 980px; margin: 0 auto; padding: 32px; }
        .top-actions { display: flex; flex-wrap: wrap; gap: 12px; margin-bottom: 24px; }
        .button { display: inline-flex; align-items: center; justify-content: center; padding: 12px 18px; background: #2563EB; color: #FFFFFF; text-decoration: none; border-radius: 8px; transition: background 0.2s ease; }
        .button:hover { background: #1D4ED8; }
        .card { background: #111827; border: 1px solid #1E293B; border-radius: 18px; padding: 24px; box-shadow: 0 20px 40px rgba(15, 23, 42, 0.35); }
        .form-group { display: grid; gap: 10px; max-width: 520px; margin-bottom: 20px; }
        input[type="text"] { width: 100%; padding: 14px 16px; border-radius: 14px; border: 1px solid #334155; background: #0F172A; color: #E2E8F0; }
        input[type="submit"] { width: fit-content; padding: 12px 18px; background: #2563EB; border: none; border-radius: 12px; color: #fff; cursor: pointer; }
        input[type="submit"]:hover { background: #1D4ED8; }
        .message { margin-bottom: 20px; padding: 16px; border-radius: 14px; background: #1E293B; color: #E2E8F0; }
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
        .direction-button { display: block; width: 100%; background: #3182ce; color: white; text-align: center; padding: 14px; border-radius: 10px; font-weight: 600; font-size: 15px; text-decoration: none; border: none; cursor: pointer; box-shadow: 0 4px 6px rgba(66, 153, 225, 0.2); transition: background 0.2s; }
        .direction-button:hover { background: #2b6cb0; }
        .map-frame-wrapper { display: none; margin-top: 20px; border-radius: 18px; overflow: hidden; border: 1px solid #1E293B; }
        .map-frame-wrapper iframe { width: 100%; min-height: 420px; border: none; }
    </style>
</head>
<body>
    <div class="page">
        <header style="display:flex; align-items:center; justify-content:space-between; gap:16px; margin-bottom:28px;">
            <div>
                <h1 style="margin:0;">ATM Lookup</h1>
                <p style="margin:12px 0 0; color:#CBD5E1;">Search ATM details by entering the machine ID or number.</p>
            </div>
            <div class="top-actions">
                <a class="button" href="index.php">Stations</a>
                <a class="button" href="search.php">Station Search</a>
            </div>
        </header>

        <?php if ($message): ?>
            <div class="message"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <form method="get" action="" class="card">
            <div class="form-group">
                <label for="atm_id" style="color:#E2E8F0;font-weight:600;">ATM Machine ID / Number</label>
                <input id="atm_id" name="atm_id" type="text" value="<?= htmlspecialchars($searchValue) ?>" placeholder="Enter ATM ID or number" />
                <input type="submit" value="Search ATM" />
            </div>
        </form>

        <?php if ($atm): ?>
            <?php $mapCoords = $coords ? ($coords[0] . ',' . $coords[1]) : ''; ?>
            <div class="station-card">
                <div class="station-card-header">
                    <div class="station-card-icon">🏧</div>
                    <h2><?= sanitize($atm['atm_number']) ?></h2>
                    <p>ATM MACHINE</p>
                </div>
                <div class="station-card-body">
                    <div class="detail-row">
                        <div class="detail-row-icon">🏛️</div>
                        <div class="detail-row-info">
                            <span class="detail-row-label">Bank</span>
                            <span class="detail-row-value"><?= sanitize($atm['bank_name']) ?></span>
                        </div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-row-icon">📍</div>
                        <div class="detail-row-info">
                            <span class="detail-row-label">Branch</span>
                            <span class="detail-row-value"><?= sanitize($atm['branch']) ?></span>
                        </div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-row-icon">🌆</div>
                        <div class="detail-row-info">
                            <span class="detail-row-label">City / State</span>
                            <span class="detail-row-value"><?= sanitize($atm['city']) ?>, <?= sanitize($atm['state']) ?></span>
                        </div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-row-icon">📞</div>
                        <div class="detail-row-info">
                            <span class="detail-row-label">Contact</span>
                            <a class="detail-link" href="tel:<?= htmlspecialchars($atm['contact']) ?>"><?= formatPhone($atm['contact']) ?></a>
                        </div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-row-icon">🗺️</div>
                        <div class="detail-row-info">
                            <span class="detail-row-label">Location</span>
                            <span class="detail-row-value"><?= sanitize($atm['location']) ?></span>
                        </div>
                    </div>
                    <?php if ($mapCoords): ?>
                        <button class="direction-button" type="button" onclick="document.getElementById('googleMapFrame').src='https://maps.google.com/maps?q=<?= rawurlencode($mapCoords) ?>&z=15&output=embed'; document.getElementById('googleMapWrapper').style.display='block';">🚗 Show directions on map</button>
                    <?php endif; ?>
                </div>
            </div>
            <div class="map-frame-wrapper" id="googleMapWrapper">
                <iframe id="googleMapFrame" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
