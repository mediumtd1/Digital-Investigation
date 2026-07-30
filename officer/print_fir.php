<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
check_auth('Officer');

$fir_id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM firs WHERE id = ?");
$stmt->execute([$fir_id]);
$fir = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$fir) die("FIR record missing.");

$comp_meta = json_decode($fir['complainant_address'], true) ?? [];
$crime_meta = json_decode($fir['description'], true) ?? [];
$accused_meta = json_decode($fir['accused_details'], true) ?? [];

$desc_display = $crime_meta['detailed_desc'] ?? $fir['description'];
$address_display = $comp_meta['address'] ?? $fir['complainant_address'];
$platform = $crime_meta['platform'] ?? 'N/A';
$loss = $crime_meta['financial_loss'] ?? 'N/A';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Print FIR - <?= esc($fir['fir_number']) ?></title>
    
    <style>
        body {
            font-family: 'Times New Roman', Times, serif;
            color: black;
            background: white;
            line-height: 1.5;
            margin: 0;
            padding: 40px;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid black;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h1 {
            font-size: 24px;
            margin: 0 0 5px 0;
            text-transform: uppercase;
        }
        .header h3 {
            font-size: 16px;
            margin: 0;
            font-weight: normal;
        }
        .fir-summary {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            font-size: 14px;
            font-weight: bold;
            padding: 10px;
            background: #f8f8f8;
            border: 1px solid #ccc;
        }
        .section-title {
            text-transform: uppercase;
            font-size: 16px;
            font-weight: bold;
            text-decoration: underline;
            margin-bottom: 10px;
            margin-top: 30px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }
        th, td {
            text-align: left;
            padding: 8px 10px;
            border: 1px solid #000;
        }
        th {
            background-color: #f2f2f2;
            width: 200px;
        }
        .desc-box {
            border: 1px solid #000;
            padding: 15px;
            min-height: 150px;
            font-size: 14px;
            margin-top: 10px;
            background: #fff;
        }
        .footer-sig {
            margin-top: 80px;
            display: flex;
            justify-content: space-between;
        }
        .sig-block {
            text-align: center;
            width: 200px;
        }
        .sig-line {
            border-top: 1px solid black;
            margin-top: 60px;
            padding-top: 5px;
        }
        
        /* Auto-print logic overlay */
        .no-print {
            position: fixed;
            top: 10px;
            right: 10px;
            background: #2563eb;
            color: white;
            border: none;
            padding: 10px 20px;
            font-family: sans-serif;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
        }
        
        @media print {
            body { padding: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body onload="window.print()">

    <button class="no-print" onclick="window.print()">Print Document</button>

    <div class="header">
        <h1>DIGITAL INVESTIGATION</h1>
        <h3>Cyber Investigation & Forensics Division</h3>
        <h3>FIRST INFORMATION REPORT (FIR)</h3>
    </div>

    <div class="fir-summary">
        <div>FIR NUMBER: <?= esc($fir['fir_number']) ?></div>
        <div>TIMESTAMP: <?= date('d M Y, H:i:s', strtotime($fir['created_at'])) ?></div>
    </div>

    <div class="section-title">1. INCIDENT META-DATA</div>
    <table>
        <tr><th>Nature of Offence / Section</th><td><?= esc($fir['crime_type']) ?></td></tr>
        <tr><th>Date & Time of Occurrence</th><td><?= date('d M Y, H:i', strtotime($fir['incident_date'])) ?></td></tr>
        <tr><th>Target Platform / Carrier</th><td><?= esc($platform) ?></td></tr>
        <tr><th>Estimated Financial Loss</th><td><?= $loss === 'N/A' || $loss === '' ? 'N/A' : 'INR ' . esc($loss) ?></td></tr>
    </table>

    <div class="section-title">2. COMPLAINANT / INFORMANT DETAILS</div>
    <table>
        <tr><th>Full Name</th><td><?= esc($fir['complainant_name']) ?></td></tr>
        <tr><th>Age / Gender</th><td><?= esc($comp_meta['age'] ?? '') ?> / <?= esc($comp_meta['gender'] ?? '') ?></td></tr>
        <tr><th>Residential Address</th><td><?= esc($address_display) ?></td></tr>
        <tr><th>District Code / Region</th><td><?= esc($comp_meta['district'] ?? '') ?></td></tr>
        <tr><th>Contact Mobile / Email</th><td><?= esc($fir['complainant_mobile']) ?> / <?= esc($fir['complainant_email'] ?? 'N/A') ?></td></tr>
        <?php if (!empty($comp_meta['aadhaar'])): ?>
        <tr><th>Aadhaar Identifier</th><td><?= esc($comp_meta['aadhaar']) ?></td></tr>
        <?php endif; ?>
    </table>

    <div class="section-title">3. ACCUSED IDENTIFICATION (IF KNOWN)</div>
    <table>
        <tr><th>Name / Handle</th><td><?= esc($accused_meta['name'] ?? 'Unknown / Under Investigation') ?></td></tr>
        <tr><th>Digital Footprint (IP/URL)</th><td><?= esc($accused_meta['ip'] ?? 'N/A') ?> | <?= esc($accused_meta['social'] ?? 'N/A') ?></td></tr>
        <tr><th>Mobile Connectivity</th><td><?= esc($accused_meta['mobile'] ?? 'N/A') ?></td></tr>
    </table>

    <div class="section-title">4. STATEMENT OF INCIDENT (NARRATIVE)</div>
    <div class="desc-box">
        <?= nl2br(esc($desc_display)) ?>
    </div>

    <div class="footer-sig">
        <div class="sig-block">
            <div class="sig-line">Signature of Complainant</div>
        </div>
        <div class="sig-block">
            <div class="sig-line">Signature / Stamp of Investigating Officer<br><small><?= esc($_SESSION['user_name']) ?> (<?= esc($_SESSION['user_mapped_id']) ?>)</small></div>
        </div>
    </div>

</body>
</html>
