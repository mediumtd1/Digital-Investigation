<?php
require_once 'config.php';

// Fetch all tolls from existing table
function getAllTolls() {
    global $pdo;
    $sql = "SELECT 
            `COL 1` as plaza_code, 
            `COL 2` as plaza_name, 
            `COL 3` as plaza_type, 
            `COL 4` as state, 
            `COL 5` as district, 
            `COL 6` as concessionaire,
            SUBSTRING_INDEX(`COL 7`, ',', 1) as lat,
            SUBSTRING_INDEX(`COL 7`, ',', -1) as lng
            FROM toll 
            ORDER BY `COL 2` ASC";
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll();
}


// Search tolls by name or code
function searchTolls($query) {
    global $pdo;
    $sql = "SELECT 
            `COL 1` as plaza_code, 
            `COL 2` as plaza_name, 
            `COL 3` as plaza_type, 
            `COL 4` as state, 
            `COL 5` as district, 
            `COL 6` as concessionaire,
            SUBSTRING_INDEX(`COL 7`, ',', 1) as lat,
            SUBSTRING_INDEX(`COL 7`, ',', -1) as lng
            FROM toll 
            WHERE `COL 2` LIKE ? OR `COL 1` LIKE ? OR `COL 4` LIKE ? 
            ORDER BY `COL 2` ASC";
    $stmt = $pdo->prepare($sql);
    $searchTerm = "%$query%";
    $stmt->execute([$searchTerm, $searchTerm, $searchTerm]);
    return $stmt->fetchAll();
}


// Get single toll detail
function getTollById($code) {
    global $pdo;
    $sql = "SELECT 
            `COL 1` as plaza_code, 
            `COL 2` as plaza_name, 
            `COL 3` as plaza_type, 
            `COL 4` as state, 
            `COL 5` as district, 
            `COL 6` as concessionaire,
            SUBSTRING_INDEX(`COL 7`, ',', 1) as lat,
            SUBSTRING_INDEX(`COL 7`, ',', -1) as lng
            FROM toll 
            WHERE `COL 1` = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$code]);
    return $stmt->fetch();
}


// Add new toll
function addToll($data) {
    global $pdo;
    $sql = "INSERT INTO toll (`COL 1`, `COL 2`, `COL 3`, `COL 4`, `COL 5`, `COL 6`, `COL 7`) 
            VALUES (:code, :name, :type, :state, :district, :concessionaire, :location)";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([
        'code' => $data['plaza_code'],
        'name' => $data['plaza_name'],
        'type' => $data['plaza_type'],
        'state' => $data['state'],
        'district' => $data['district'],
        'concessionaire' => $data['concessionaire'],
        'location' => $data['lat'] . ',' . $data['lng']
    ]);
}


// Delete toll
function deleteToll($code) {
    global $pdo;
    $stmt = $pdo->prepare("DELETE FROM toll WHERE `COL 1` = ?");
    return $stmt->execute([$code]);
}


// Bulk Import from CSV
function importCSV($filename) {
    global $pdo;
    if (($handle = fopen($filename, "r")) !== FALSE) {
        fgetcsv($handle, 1000, ","); // Skip header
        $sql = "INSERT IGNORE INTO toll (`COL 1`, `COL 2`, `COL 3`, `COL 4`, `COL 5`, `COL 6`, `COL 7`) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);

        $count = 0;
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            if (count($data) >= 7) {
                // Split Lat,Lng if it's in the 7th column (COL 7 style)
                $location = $data[6];
                $coords = explode(',', $location);
                $lat = trim($coords[0] ?? '0');
                $lng = trim($coords[1] ?? '0');
                
                $params = [
                    $data[0], // code
                    $data[1], // name
                    $data[2], // type
                    $data[3], // state
                    $data[4], // district
                    $data[5], // concessionaire
                    $location // combined lat/lng
                ];
                
                if ($stmt->execute($params)) {
                    $count++;
                }

            }
        }
        fclose($handle);
        return $count;
    }
    return false;
}
?>
