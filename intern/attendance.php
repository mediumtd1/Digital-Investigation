<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

check_auth('Intern');

$portal = 'intern';
$page_title = 'My Attendance | ' . SITE_NAME;

$intern_id = $_SESSION['user_id'];

$selected_month = $_GET['month'] ?? date('m');
$selected_year = $_GET['year'] ?? date('Y');

$selected_month = str_pad((int)$selected_month, 2, '0', STR_PAD_LEFT);
$selected_year = (int)$selected_year;

$days_in_month = cal_days_in_month(CAL_GREGORIAN, $selected_month, $selected_year);
$start_date = "$selected_year-$selected_month-01";
$end_date = "$selected_year-$selected_month-$days_in_month";

$stmt = $pdo->prepare("SELECT date, status, remarks FROM intern_attendance WHERE intern_id = ? AND date BETWEEN ? AND ? ORDER BY date DESC");
$stmt->execute([$intern_id, $start_date, $end_date]);
$attendance_records = $stmt->fetchAll();

$attendance_map = [];
$stats = ['Total' => 0, 'Present' => 0, 'Absent' => 0, 'Late' => 0, 'Holiday' => 0];

foreach($attendance_records as $rec) {
    $attendance_map[$rec['date']] = $rec;
    $stats[$rec['status']]++;
}

$stmt = $pdo->prepare("SELECT date, description FROM holidays WHERE date BETWEEN ? AND ?");
$stmt->execute([$start_date, $end_date]);
$holidays = $stmt->fetchAll(PDO::FETCH_ASSOC);
$holiday_map = [];
foreach($holidays as $h) {
    if (!isset($attendance_map[$h['date']])) {
        
        $stats['Holiday']++;
    }
    $holiday_map[$h['date']] = $h['description'];
}

$today_str = date('Y-m-d');
$final_records = [];
for ($d = 1; $d <= $days_in_month; $d++) {
    $current_date = $selected_year . '-' . $selected_month . '-' . str_pad($d, 2, '0', STR_PAD_LEFT);
    if ($current_date > $today_str) {
        continue; 
    }
    
    
    if (isset($attendance_map[$current_date])) {
        $final_records[] = $attendance_map[$current_date];
    } elseif (isset($holiday_map[$current_date])) {
        
        $final_records[] = [
            'date' => $current_date,
            'status' => 'Holiday',
            'remarks' => $holiday_map[$current_date]
        ];
    } else {
        
        $absent_rec = [
            'date' => $current_date,
            'status' => 'Absent',
            'remarks' => 'Auto-marked Absent (Module/Quiz not completed)'
        ];
        $final_records[] = $absent_rec;
        $attendance_map[$current_date] = $absent_rec;
        $stats['Absent']++;
    }
}

usort($final_records, function($a, $b) {
    return strcmp($b['date'], $a['date']);
});

$attendance_records = $final_records;

$total_classes = $stats['Present'] + $stats['Absent'] + $stats['Late'];
$attendance_percentage = $total_classes > 0 ? round(($stats['Present'] / $total_classes) * 100, 1) : 100;

$limit = 20;
$page = isset($_GET['p']) ? max(1, (int)$_GET['p']) : 1;
$offset = ($page - 1) * $limit;
$total_rows = count($attendance_records);
$total_pages = ceil($total_rows / $limit);
$paginated_records = array_slice($attendance_records, $offset, $limit);

?>

<?php require_once __DIR__ . '/../includes/header.php'; ?>

<div class="dashboard-wrapper">
    
    <?php include_once __DIR__ . '/_sidebar.php'; ?>

    <main class="main-content">
        <header class="topbar">
            <div class="topbar-left">
                <h2>Attendance Record</h2>
            </div>
            <div class="topbar-right">
                <div class="user-profile">
                    <img src="../<?= esc($_SESSION['user_photo'] ?? 'assets/images/default-avatar.png') ?>" alt="Profile" class="profile-pic">
                    <div class="user-info">
                        <span class="user-name"><?= esc($_SESSION['user_name']) ?></span>
                        <span class="user-role">Intern • <?= esc($_SESSION['user_mapped_id']) ?></span>
                    </div>
                </div>
            </div>
        </header>

        <div class="dashboard-content">
            
            
            <?php if ($attendance_percentage < 75 && $total_classes > 5): ?>
            <div style="background: rgba(220, 38, 38, 0.2); border-left: 4px solid var(--danger); padding: 15px 20px; border-radius: 4px; margin-bottom: 25px; display: flex; align-items: center; gap: 15px;">
                <i class="fa-solid fa-triangle-exclamation" style="color: var(--danger); font-size: 1.5rem;"></i>
                <div>
                    <h4 style="color: var(--danger); margin: 0 0 5px 0;">Low Attendance Warning</h4>
                    <p style="margin: 0; font-size: 0.9rem; color: #fca5a5;">Your attendance is currently <?= $attendance_percentage ?>%, which is below the mandatory 75% threshold. Please ensure regular attendance to avoid disciplinary action.</p>
                </div>
            </div>
            <?php endif; ?>

            
            <div class="glass-panel" style="margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center;">
                <form method="GET" style="display: flex; gap: 15px; align-items: center;">
                    <select name="month" class="form-control" style="width: auto;" onchange="this.form.submit()">
                        <?php
                        for ($m = 1; $m <= 12; $m++) {
                            $m_padded = str_pad($m, 2, '0', STR_PAD_LEFT);
                            $m_name = date('F', mktime(0, 0, 0, $m, 1));
                            $sel = ($selected_month == $m_padded) ? 'selected' : '';
                            echo "<option value=\"$m_padded\" $sel>$m_name</option>";
                        }
                        ?>
                    </select>
                    <select name="year" class="form-control" style="width: auto;" onchange="this.form.submit()">
                        <?php
                        $start_y = date('Y') - 2;
                        for ($y = $start_y; $y <= date('Y'); $y++) {
                            $sel = ($selected_year == $y) ? 'selected' : '';
                            echo "<option value=\"$y\" $sel>$y</option>";
                        }
                        ?>
                    </select>
                </form>

                <div style="font-size: 1.2rem; font-weight: 600; color: var(--accent-highlight);">
                    <?= date('F Y', strtotime($start_date)) ?>
                </div>
            </div>

            
            <div class="stats-grid" style="grid-template-columns: repeat(5, 1fr); margin-bottom: 25px;">
                <div class="glass-panel" style="text-align: center; padding: 15px;">
                    <div style="font-size: 0.85rem; color: var(--text-muted);">Total Classes</div>
                    <div style="font-size: 1.8rem; font-weight: bold; color: var(--text-primary);"><?= $total_classes ?></div>
                </div>
                <div class="glass-panel" style="text-align: center; padding: 15px; border-bottom: 3px solid var(--success);">
                    <div style="font-size: 0.85rem; color: var(--text-muted);">Present</div>
                    <div style="font-size: 1.8rem; font-weight: bold; color: var(--success);"><?= $stats['Present'] ?></div>
                </div>
                <div class="glass-panel" style="text-align: center; padding: 15px; border-bottom: 3px solid var(--danger);">
                    <div style="font-size: 0.85rem; color: var(--text-muted);">Absent</div>
                    <div style="font-size: 1.8rem; font-weight: bold; color: var(--danger);"><?= $stats['Absent'] ?></div>
                </div>
                <div class="glass-panel" style="text-align: center; padding: 15px; border-bottom: 3px solid #f59e0b;">
                    <div style="font-size: 0.85rem; color: var(--text-muted);">Late</div>
                    <div style="font-size: 1.8rem; font-weight: bold; color: #f59e0b;"><?= $stats['Late'] ?></div>
                </div>
                <div class="glass-panel" style="text-align: center; padding: 15px;">
                    <div style="font-size: 0.85rem; color: var(--text-muted);">Attendance %</div>
                    <div style="font-size: 1.8rem; font-weight: bold; color: <?= $attendance_percentage >= 75 ? 'var(--accent-highlight)' : 'var(--danger)' ?>;"><?= $attendance_percentage ?>%</div>
                </div>
            </div>

            
            <div class="glass-panel" style="margin-bottom: 30px; border-radius: 8px;">
                <h3 style="margin-bottom: 15px; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">Monthly Calendar</h3>
                
                <div style="display: grid; grid-template-columns: repeat(7, 1fr); text-align: center; font-weight: bold; padding: 10px 0; border-bottom: 1px solid var(--border-color); color: var(--text-muted);">
                    <div>Sun</div><div>Mon</div><div>Tue</div><div>Wed</div><div>Thu</div><div>Fri</div><div>Sat</div>
                </div>

                <div style="display: grid; grid-template-columns: repeat(7, 1fr); gap: 10px; padding: 15px 0;">
                    <?php
                    $first_day_of_month = date('w', strtotime($start_date));
                    
                    
                    for ($i = 0; $i < $first_day_of_month; $i++) {
                        echo "<div></div>";
                    }

                    
                    for ($d = 1; $d <= $days_in_month; $d++) {
                        $current_date = $selected_year . '-' . $selected_month . '-' . str_pad($d, 2, '0', STR_PAD_LEFT);
                        
                        $bg_color = 'rgba(255,255,255,0.02)';
                        $text_color = 'var(--text-primary)';
                        $border = '1px solid rgba(255,255,255,0.05)';
                        $title = '';
                        $dot = '';

                        if (isset($attendance_map[$current_date])) {
                            $status = $attendance_map[$current_date]['status'];
                            if ($status == 'Present') { $border = '1px solid var(--success)'; $bg_color = 'rgba(22, 163, 74, 0.1)'; $dot = '<i class="fa-solid fa-circle" style="color:var(--success); font-size:6px; margin-top:5px; display:block;"></i>'; }
                            elseif ($status == 'Absent') { $border = '1px solid var(--danger)'; $bg_color = 'rgba(220, 38, 38, 0.1)'; $dot = '<i class="fa-solid fa-circle" style="color:var(--danger); font-size:6px; margin-top:5px; display:block;"></i>';}
                            elseif ($status == 'Late') { $border = '1px solid #f59e0b'; $bg_color = 'rgba(245, 158, 11, 0.1)'; $dot = '<i class="fa-solid fa-circle" style="color:#f59e0b; font-size:6px; margin-top:5px; display:block;"></i>';}
                            elseif ($status == 'Holiday') { $border = '1px solid #6b7280'; $text_color = '#9ca3af'; $dot = '<i class="fa-solid fa-bed" style="color:#6b7280; font-size:10px; margin-top:5px; display:block;"></i>'; }
                            $title = $status;
                        } elseif (isset($holiday_map[$current_date])) {
                            $bg_color = 'rgba(255,255,255,0.02)';
                            $text_color = '#6b7280';
                            $border = '1px dashed #6b7280';
                            $title = $holiday_map[$current_date];
                            $dot = '<i class="fa-solid fa-bed" style="color:#6b7280; font-size:10px; margin-top:5px; display:block;"></i>';
                        } elseif ($current_date > date('Y-m-d')) {
                            
                            $text_color = 'rgba(255,255,255,0.2)';
                        }
                        
                        echo "<div style=\"background: $bg_color; border: $border; color: $text_color; padding: 15px 5px; text-align: center; border-radius: 6px; position:relative;\" title=\"$title\">
                                <span style='font-size: 1.1rem; font-weight: bold;'>$d</span>
                                $dot
                              </div>";
                    }
                    ?>
                </div>
            </div>

            
            <div class="glass-panel">
                <h3 style="margin-bottom: 20px; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">Attendance Log</h3>
                
                <?php if(count($paginated_records) > 0): ?>
                <table style="width: 100%; text-align: left; border-collapse: collapse; font-size: 0.95rem;">
                    <thead>
                        <tr style="border-bottom: 2px solid var(--border-color); color: var(--text-muted);">
                            <th style="padding: 12px 10px;">Date <i class="fa-solid fa-sort"></i></th>
                            <th style="padding: 12px 10px;">Day</th>
                            <th style="padding: 12px 10px;">Status</th>
                            <th style="padding: 12px 10px;">Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($paginated_records as $rec): 
                            $date_obj = new DateTime($rec['date']);
                            $status = $rec['status'];
                            $badge_class = '';
                            if ($status == 'Present') $badge_class = 'background: rgba(22, 163, 74, 0.2); color: var(--success);';
                            elseif ($status == 'Absent') $badge_class = 'background: rgba(220, 38, 38, 0.2); color: var(--danger);';
                            elseif ($status == 'Late') $badge_class = 'background: rgba(245, 158, 11, 0.2); color: #f59e0b;';
                            elseif ($status == 'Holiday') $badge_class = 'background: rgba(255, 255, 255, 0.1); color: #9ca3af;';
                        ?>
                        <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                            <td style="padding: 12px 10px;"><?= $date_obj->format('d M, Y') ?></td>
                            <td style="padding: 12px 10px; color: var(--text-muted);"><?= $date_obj->format('l') ?></td>
                            <td style="padding: 12px 10px;">
                                <span style="display:inline-block; padding:3px 10px; border-radius:12px; font-size:0.8rem; font-weight:bold; <?= $badge_class ?>">
                                    <?= esc($status) ?>
                                </span>
                            </td>
                            <td style="padding: 12px 10px; color: #9ca3af;">
                                <?= esc($rec['remarks'] ?: '--') ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                
                <?php if ($total_pages > 1): ?>
                <div style="margin-top: 20px; display: flex; justify-content: flex-end; gap: 5px;">
                    <?php for($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?month=<?= $selected_month ?>&year=<?= $selected_year ?>&p=<?= $i ?>" style="padding: 6px 12px; background: <?= $page == $i ? 'var(--accent-highlight)' : 'rgba(255,255,255,0.05)' ?>; color: <?= $page == $i ? '#fff' : 'var(--text-muted)' ?>; border-radius: 4px; text-decoration: none;">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>
                </div>
                <?php endif; ?>

                <?php else: ?>
                    <p style="text-align: center; color: var(--text-muted); margin: 30px 0;">No attendance records found for <?= date('F Y', strtotime($start_date)) ?>.</p>
                <?php endif; ?>
            </div>

        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
