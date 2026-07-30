<?php
require_once __DIR__ . '/db.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    $allowedTypes = ['text/csv', 'application/vnd.ms-excel', 'text/plain'];
    $file = $_FILES['csv_file'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error = 'Upload failed. Please try again.';
    } else {
        $tmpName = $file['tmp_name'];
        $handle = fopen($tmpName, 'r');

        if ($handle === false) {
            $error = 'Unable to open uploaded file.';
        } else {
            $headers = fgetcsv($handle);

            if (!is_array($headers)) {
                $error = 'Invalid CSV format.';
            } else {
                $headers = array_map('trim', $headers);
                if (!empty($headers) && substr($headers[0], 0, 3) === "\xEF\xBB\xBF") {
                    $headers[0] = substr($headers[0], 3);
                }

                $normalizedHeaders = array_map(function ($header) {
                    $header = trim($header);
                    $header = trim($header, '"\'');
                    $header = preg_replace('/[\s\-]+/', '_', $header);
                    return strtolower($header);
                }, $headers);

                $aliases = [
                    'state' => ['state', 'state_name'],
                    'district' => ['district'],
                    'police_station' => ['police_station', 'police station', 'station', 'police station name', 'station name'],
                    'contact' => ['contact', 'phone', 'phone_number', 'phone number', 'mobile'],
                    'location' => ['location', 'address', 'place'],
                    'latitude' => ['latitude', 'lat'],
                    'longitude' => ['longitude', 'lng', 'long'],
                ];

                $map = [];
                foreach ($aliases as $field => $names) {
                    foreach ($names as $name) {
                        $index = array_search($name, $normalizedHeaders, true);
                        if ($index !== false) {
                            $map[$field] = $index;
                            break;
                        }
                    }
                }

                $required = ['state', 'district', 'police_station', 'contact', 'location'];
                $missing = array_filter($required, function ($field) use ($map) {
                    return !isset($map[$field]);
                });

                if (!empty($missing)) {
                    $error = 'CSV missing required columns: ' . implode(', ', $missing) . '. Please include State, District, Police Station, Contact, and Location.';
                } else {
                    $pdo = getDbConnection();
                    $statement = $pdo->prepare('INSERT INTO police_stations (state, district, police_station, contact, location, latitude, longitude) VALUES (?, ?, ?, ?, ?, ?, ?)');
                    $imported = 0;

                    while (($row = fgetcsv($handle)) !== false) {
                        if (count($row) === 0 || (count($row) === 1 && trim($row[0]) === '')) {
                            continue;
                        }

                        $state = $row[$map['state']] ?? '';
                        $district = $row[$map['district']] ?? '';
                        $policeStation = $row[$map['police_station']] ?? '';
                        $contact = $row[$map['contact']] ?? '';
                        $location = $row[$map['location']] ?? '';
                        $latitude = $row[$map['latitude']] ?? null;
                        $longitude = $row[$map['longitude']] ?? null;

                        if ($state === '' && $district === '' && $policeStation === '' && $contact === '' && $location === '') {
                            continue;
                        }

                        $statement->execute([$state, $district, $policeStation, $contact, $location, $latitude, $longitude]);
                        $imported++;
                    }

                    $message = "Imported $imported rows successfully.";
                }
            }

            fclose($handle);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Import Police Stations</title>
    <style>
        body { margin: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #0F172A; color: #E2E8F0; }
        .page { max-width: 760px; margin: 0 auto; padding: 32px; }
        h1 { color: #E0F2FE; margin-bottom: 8px; }
        p a { color: #93C5FD; text-decoration: none; }
        .card { background: #111827; border: 1px solid #1E293B; border-radius: 18px; padding: 28px; box-shadow: 0 20px 40px rgba(15, 23, 42, 0.35); }
        form { margin-top: 24px; }
        label { display: block; margin: 18px 0 8px; color: #CBD5E1; }
        input[type=file] { width: 100%; padding: 12px 14px; background: #0F172A; border: 1px solid #334155; border-radius: 12px; color: #E2E8F0; }
        button { margin-top: 20px; padding: 14px 20px; background: #2563EB; color: #fff; border: none; border-radius: 12px; cursor: pointer; }
        button:hover { background: #1D4ED8; }
        .message { padding: 16px; border-radius: 14px; margin-bottom: 20px; }
        .success { background: #0F766E; color: #ECFDF5; border: 1px solid #2DD4BF; }
        .error { background: #7F1D1D; color: #FECACA; border: 1px solid #FCA5A5; }
        a.back-link { color: #93C5FD; text-decoration: none; }
    </style>
</head>
<body>
    <div class="page">
        <h1>Import Police Stations from CSV</h1>
        <p>Save your Excel file as CSV, then upload it here. Latitude and Longitude can be stored in the Location field.</p>
        <div class="card">
            <p>The file must include these columns in the first row:</p>
            <ul>
                <li><strong>State</strong></li>
                <li><strong>District</strong></li>
                <li><strong>Police Station</strong></li>
                <li><strong>Contact</strong></li>
                <li><strong>Location</strong></li>
            </ul>

            <?php if ($message): ?>
                <div class="message success"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="message error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="post" enctype="multipart/form-data">
                <label for="csv_file">CSV file</label>
                <input type="file" id="csv_file" name="csv_file" accept=".csv" required>
                <button type="submit">Import Data</button>
            </form>

            <p><a class="back-link" href="index.php">Back to station list</a></p>
        </div>
    </div>
</body>
</html>
