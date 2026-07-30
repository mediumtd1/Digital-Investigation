<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
check_auth('Officer');

$fir_id = (int)($_GET['id'] ?? 0);
$officer_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT * FROM firs WHERE id = ? AND (assigned_officer_id = ? OR filed_by = ?)");
$stmt->execute([$fir_id, $officer_id, $officer_id]);
$fir = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$fir) die("FIR record missing or access violation.");

$stmt = $pdo->prepare("
    SELECT cd.*, o.name as block_officer, o.badge_number
    FROM case_diary_entries cd
    JOIN officers o ON cd.officer_id = o.id
    WHERE cd.fir_id = ? 
    ORDER BY cd.entry_date ASC
");
$stmt->execute([$fir_id]);
$timeline = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Print Case Docket - <?= esc($fir['fir_number']) ?></title>
    
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
            border-bottom: 3px double black;
            padding-bottom: 15px;
            margin-bottom: 30px;
        }
        .header h1 {
            font-size: 26px;
            margin: 0 0 5px 0;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .header h3 {
            font-size: 16px;
            margin: 0;
            font-weight: normal;
        }
        .meta-summary {
            background: #fff;
            border: 2px solid black;
            border-collapse: collapse;
            width: 100%;
            margin-bottom: 40px;
        }
        .meta-summary th, .meta-summary td {
            border: 1px solid black;
            padding: 10px;
            font-size: 14px;
        }
        .meta-summary th {
            text-align: left;
            background: #f0f0f0;
            width: 25%;
        }
        .timeline-box {
            border: 1px solid black;
            margin-bottom: 20px;
            page-break-inside: avoid;
        }
        .timeline-header {
            background: #f0f0f0;
            border-bottom: 1px solid black;
            padding: 8px 10px;
            font-size: 14px;
            font-weight: bold;
            display: flex;
            justify-content: space-between;
        }
        .timeline-content {
            padding: 15px 10px;
            font-size: 13px;
            white-space: pre-wrap;
        }
        .timeline-footer {
            border-top: 1px dotted black;
            padding: 5px 10px;
            font-size: 12px;
            color: #333;
            background: #fafafa;
        }
        
        .no-print {
            position: fixed;
            top: 10px;
            right: 10px;
            background: #f59e0b;
            color: black;
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

    <button class="no-print" onclick="window.print()">Execute PDF Print Map</button>

    <div class="header">
        <h1>DIGITAL INVESTIGATION</h1>
        <h3>Cyber Investigation & Forensics Division</h3>
        <h3>OFFICIAL CASE DIARY LOG / DOCKET</h3>
    </div>

    <table class="meta-summary">
        <tr>
            <th>FIR Protocol Sequence</th>
            <td style="font-family: monospace; font-weight: bold; font-size: 16px;"><?= esc($fir['fir_number']) ?></td>
        </tr>
        <tr>
            <th>Crime Classification Code</th>
            <td><?= esc($fir['crime_type']) ?></td>
        </tr>
        <tr>
            <th>Docket Print Timestamp</th>
            <td><?= date('d M Y, H:i A') ?></td>
        </tr>
        <tr>
            <th>Lead Investigating Officer</th>
            <td><?= esc($_SESSION['user_name']) ?> (BA-<?= esc($_SESSION['user_mapped_id']) ?>)</td>
        </tr>
    </table>

    <h2 style="font-size: 18px; text-transform: uppercase; border-bottom: 1px solid black; padding-bottom: 5px; margin-bottom: 20px;">Chronological Action Ledger</h2>

    <?php foreach ($timeline as $index => $node): ?>
        <div class="timeline-box">
            <div class="timeline-header">
                <span>Entry #<?= $index + 1 ?> — Date: <?= date('d/m/Y - H:i:s', strtotime($node['entry_date'])) ?></span>
            </div>
            <div class="timeline-content"><?= esc($node['notes']) ?></div>
            <?php if (!empty($node['next_action'])): ?>
                <div class="timeline-content" style="border-top: 1px dashed #ccc; background:#fdfdfd;">
                    <strong>Next Procedure Planned:</strong> <?= esc($node['next_action']) ?>
                </div>
            <?php endif; ?>
            <div class="timeline-footer">
                Authenticated by signature ID: <?= esc($node['block_officer']) ?> [<?= esc($node['badge_number']) ?>]
            </div>
        </div>
    <?php endforeach; ?>

    <?php if (empty($timeline)): ?>
        <div style="text-align: center; font-style: italic; padding: 40px; border: 1px dashed black;">
            [ No entries compiled in the ledger frame. ]
        </div>
    <?php endif; ?>

    <div style="margin-top: 50px; text-align: right; padding-right: 50px;">
        <div style="border-top: 1px solid black; display: inline-block; padding-top: 5px; width: 250px; text-align: center;">
            Authorized IO Endorsement<br>
            <small><?= esc($_SESSION['user_name']) ?></small>
        </div>
    </div>

</body>
</html>
