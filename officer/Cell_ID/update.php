<?php
require_once __DIR__ . '/db.php';

$pdo = getDbConnection();
$error = '';
$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    header('Location: index.php');
    exit;
}

$statement = $pdo->prepare('SELECT * FROM police_stations WHERE id = ?');
$statement->execute([$id]);
$station = $statement->fetch();

if (!$station) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $state = trim($_POST['state'] ?? '');
    $district = trim($_POST['district'] ?? '');
    $policeStation = trim($_POST['police_station'] ?? '');
    $contact = trim($_POST['contact'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $latitude = trim($_POST['latitude'] ?? '');
    $longitude = trim($_POST['longitude'] ?? '');

    if ($state === '' || $district === '' || $policeStation === '' || $contact === '' || $location === '') {
        $error = 'State, District, Police Station, Contact, and Location are required.';
    } else {
        $statement = $pdo->prepare('UPDATE police_stations SET state = ?, district = ?, police_station = ?, contact = ?, location = ?, latitude = ?, longitude = ? WHERE id = ?');
        $statement->execute([$state, $district, $policeStation, $contact, $location, $latitude ?: null, $longitude ?: null, $id]);
        header('Location: index.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Police Station</title>
    <style>
        body { margin: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #0F172A; color: #E2E8F0; }
        .page { max-width: 760px; margin: 0 auto; padding: 32px; }
        h1 { margin-bottom: 8px; color: #E0F2FE; }
        p a { color: #93C5FD; text-decoration: none; }
        .card { background: #111827; border: 1px solid #1E293B; border-radius: 18px; padding: 28px; box-shadow: 0 20px 40px rgba(15, 23, 42, 0.35); }
        form { max-width: 100%; }
        label { display: block; margin: 18px 0 8px; color: #CBD5E1; }
        input[type=text] { width: 100%; padding: 12px 14px; border: 1px solid #334155; border-radius: 10px; background: #0F172A; color: #E2E8F0; }
        input[type=text]::placeholder { color: #94A3B8; }
        button { margin-top: 24px; padding: 14px 20px; background: #2563EB; color: #fff; border: none; border-radius: 12px; cursor: pointer; transition: background 0.2s ease; }
        button:hover { background: #1D4ED8; }
        .error { color: #FECACA; background: #7F1D1D; padding: 12px 14px; border-radius: 10px; }
        .field-note { color: #94A3B8; font-size: 0.95rem; margin-top: 4px; }
    </style>
</head>
<body>
    <div class="page">
        <h1>Edit Police Station</h1>
        <p><a href="index.php">Back to list</a></p>
        <div class="card">
            <?php if ($error): ?><p class="error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
            <form method="post" action="">
        <label for="state">State</label>
        <input type="text" id="state" name="state" value="<?= htmlspecialchars($_POST['state'] ?? $station['state']) ?>">
        <label for="district">District</label>
        <input type="text" id="district" name="district" value="<?= htmlspecialchars($_POST['district'] ?? $station['district']) ?>">
        <label for="police_station">Police Station</label>
        <input type="text" id="police_station" name="police_station" value="<?= htmlspecialchars($_POST['police_station'] ?? $station['police_station']) ?>">
        <label for="contact">Contact</label>
        <input type="text" id="contact" name="contact" value="<?= htmlspecialchars($_POST['contact'] ?? $station['contact']) ?>">
        <label for="location">Location</label>
        <input type="text" id="location" name="location" placeholder="e.g. 25.12345,72.12345" value="<?= htmlspecialchars($_POST['location'] ?? $station['location']) ?>">
        <span class="field-note">Location can contain latitude and longitude separated by a comma.</span>
        <label for="latitude">Latitude</label>
        <input type="text" id="latitude" name="latitude" value="<?= htmlspecialchars($_POST['latitude'] ?? $station['latitude']) ?>">
        <label for="longitude">Longitude</label>
        <input type="text" id="longitude" name="longitude" value="<?= htmlspecialchars($_POST['longitude'] ?? $station['longitude']) ?>">
        <button type="submit">Save Changes</button>
    </form>
        </div>
    </div>
</body>
</html>
