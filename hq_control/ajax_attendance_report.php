<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
check_auth('Admin');

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Admin') {
    die('<div style="color:red;">Unauthorized Access</div>');
}

$intern_id = (int)($_GET['intern_id'] ?? 0);
$selected_month = $_GET['month'] ?? date('m');
$selected_year = $_GET['year'] ?? date('Y');

if (!$intern_id) {
    die('<div style="color:red;">Invalid Intern ID</div>');
}

$stmt = $pdo->prepare("SELECT name, intern_id, college FROM interns WHERE id = ?");
$stmt->execute([$intern_id]);
$intern = $stmt->fetch();

if (!$intern) {
    die('<div style="color:red;">Intern Not Found</div>');
}

$days_in_month = cal_days_in_month(CAL_GREGORIAN, $selected_month, $selected_year);
$start_date = "$selected_year-$selected_month-01";
$end_date = "$selected_year-$selected_month-$days_in_month";

$stmt = $pdo->prepare("SELECT date, status FROM intern_attendance WHERE intern_id = ? AND date BETWEEN ? AND ?");
$stmt->execute([$intern_id, $start_date, $end_date]);
$records = $stmt->fetchAll();

$attendance_map = [];
$stats = ['Total' => 0, 'Present' => 0, 'Absent' => 0, 'Late' => 0, 'Holiday' => 0];

foreach($records as $rec) {
    $attendance_map[$rec['date']] = $rec['status'];
    $stats[$rec['status']]++;
}

$total_classes = $stats['Present'] + $stats['Absent'] + $stats['Late'];
$attendance_percentage = $total_classes > 0 ? round(($stats['Present'] / $total_classes) * 100, 1) : 100;
?>

<div style="margin-bottom: 20px;">
    <strong><?= esc($intern['name']) ?> (<?= esc($intern['intern_id']) ?>)</strong><br>
    <span style="color: var(--text-muted); font-size: 0.9rem;"><?= esc($intern['college']) ?> — <?= date('F Y', strtotime($start_date)) ?></span>
</div>

<div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin-bottom: 20px;">
    <div style="background: rgba(255,255,255,0.05); padding: 10px; text-align: center; border-radius: 4px;">
        <div style="font-size: 0.8rem; color: var(--text-muted);">Present</div>
        <div style="font-size: 1.5rem; font-weight: bold; color: var(--success);"><?= $stats['Present'] ?></div>
    </div>
    <div style="background: rgba(255,255,255,0.05); padding: 10px; text-align: center; border-radius: 4px;">
        <div style="font-size: 0.8rem; color: var(--text-muted);">Absent</div>
        <div style="font-size: 1.5rem; font-weight: bold; color: var(--danger);"><?= $stats['Absent'] ?></div>
    </div>
    <div style="background: rgba(255,255,255,0.05); padding: 10px; text-align: center; border-radius: 4px;">
        <div style="font-size: 0.8rem; color: var(--text-muted);">Late</div>
        <div style="font-size: 1.5rem; font-weight: bold; color: #f59e0b;"><?= $stats['Late'] ?></div>
    </div>
    <div style="background: rgba(255,255,255,0.05); padding: 10px; text-align: center; border-radius: 4px;">
        <div style="font-size: 0.8rem; color: var(--text-muted);">Percentage</div>
        <div style="font-size: 1.5rem; font-weight: bold; color: <?= $attendance_percentage >= 75 ? 'var(--accent-highlight)' : 'var(--danger)' ?>;"><?= $attendance_percentage ?>%</div>
    </div>
</div>

<div style="display: grid; grid-template-columns: repeat(7, 1fr); text-align: center; font-weight: bold; padding: 5px 0; border-bottom: 1px solid var(--border-color); color: var(--text-muted); font-size: 0.85rem;">
    <div>Sun</div><div>Mon</div><div>Tue</div><div>Wed</div><div>Thu</div><div>Fri</div><div>Sat</div>
</div>
<div style="display: grid; grid-template-columns: repeat(7, 1fr); gap: 5px; padding: 10px 0;">
    <?php
    $first_day_of_month = date('w', strtotime($start_date));
    
    for ($i = 0; $i < $first_day_of_month; $i++) {
        echo "<div></div>";
    }

    for ($d = 1; $d <= $days_in_month; $d++) {
        $current_date = $selected_year . '-' . $selected_month . '-' . str_pad($d, 2, '0', STR_PAD_LEFT);
        
        $bg_color = 'rgba(255,255,255,0.02)';
        $border = '1px solid rgba(255,255,255,0.05)';
        $title = '';

        if (isset($attendance_map[$current_date])) {
            $status = $attendance_map[$current_date];
            if ($status == 'Present') { $border = '1px solid var(--success)'; $bg_color = 'rgba(22, 163, 74, 0.2)'; }
            elseif ($status == 'Absent') { $border = '1px solid var(--danger)'; $bg_color = 'rgba(220, 38, 38, 0.2)';}
            elseif ($status == 'Late') { $border = '1px solid #f59e0b'; $bg_color = 'rgba(245, 158, 11, 0.2)';}
            elseif ($status == 'Holiday') { $border = '1px solid #6b7280';}
            $title = $status;
        }
        
        echo "<div style=\"background: $bg_color; border: $border; padding: 8px 5px; text-align: center; border-radius: 4px; font-size: 0.9rem;\" title=\"$title\">
                $d
              </div>";
    }
    ?>
</div>
