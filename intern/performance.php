<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

check_auth('Intern');

$portal = 'intern';
$page_title = 'Performance | ' . SITE_NAME;
$intern_id  = $_SESSION['user_id'];

$modules_completed = 0; $modules_total = 30;
$tasks_completed   = 0; $tasks_total    = 0;
$attendance_present= 0; $attendance_total = 0;
$avg_score         = 0;

try {
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM module_tracking WHERE intern_id = ?");
    $stmt->execute([$intern_id]); $modules_completed = (int)$stmt->fetchColumn();

    $row = $pdo->query("SELECT COUNT(*) FROM internship_modules WHERE is_visible = 1")->fetchColumn();
    $modules_total = max(30, (int)$row);

    
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM task_assignments WHERE intern_id = ?");
    $stmt->execute([$intern_id]); $tasks_total = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM task_assignments WHERE intern_id = ? AND status = 'Completed'");
    $stmt->execute([$intern_id]); $tasks_completed = (int)$stmt->fetchColumn();

    
    $current_month = date('m');
    $current_year = date('Y');
    $days_in_month = cal_days_in_month(CAL_GREGORIAN, $current_month, $current_year);
    $start_date = "$current_year-$current_month-01";
    $end_date = "$current_year-$current_month-$days_in_month";

    $stmt = $pdo->prepare("SELECT date, status FROM intern_attendance WHERE intern_id = ? AND date BETWEEN ? AND ?");
    $stmt->execute([$intern_id, $start_date, $end_date]);
    $attendance_records = $stmt->fetchAll();

    $attendance_map = [];
    $stats = ['Present' => 0, 'Absent' => 0, 'Late' => 0, 'Holiday' => 0];
    foreach($attendance_records as $rec) {
        $attendance_map[$rec['date']] = $rec;
        if (isset($stats[$rec['status']])) {
            $stats[$rec['status']]++;
        }
    }

    $holidays = [];
    try {
        $stmt = $pdo->prepare("SELECT date FROM holidays WHERE date BETWEEN ? AND ?");
        $stmt->execute([$start_date, $end_date]);
        $holidays = $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch(Exception $e) { $holidays = []; }
    $holiday_map = array_fill_keys($holidays, true);

    $today_str = date('Y-m-d');
    for ($d = 1; $d <= $days_in_month; $d++) {
        $current_date = $current_year . '-' . $current_month . '-' . str_pad($d, 2, '0', STR_PAD_LEFT);
        if ($current_date > $today_str) {
            continue; 
        }
        if (!isset($attendance_map[$current_date])) {
            if (isset($holiday_map[$current_date])) {
                $stats['Holiday']++;
            } else {
                $stats['Absent']++;
            }
        }
    }

    $attendance_present = $stats['Present'];
    $attendance_total = $stats['Present'] + $stats['Absent'] + $stats['Late'];

    
    $stmt = $pdo->prepare("SELECT AVG(score) FROM performance_scores WHERE intern_id = ?");
    $stmt->execute([$intern_id]); $avg_score = round((float)$stmt->fetchColumn(), 1);

    
    $stmt = $pdo->prepare("SELECT COALESCE(referral_points, 0) FROM interns WHERE id = ?");
    $stmt->execute([$intern_id]);
    $referral_pts_raw = (float)$stmt->fetchColumn();

    
    $stmt2 = $pdo->prepare("SELECT intern_id FROM interns WHERE id = ?");
    $stmt2->execute([$intern_id]);
    $my_intern_id_str = $stmt2->fetchColumn();
    $stmt3 = $pdo->prepare("SELECT COUNT(*) FROM interns WHERE referral_id = ?");
    $stmt3->execute([$my_intern_id_str]);
    $referral_count = (int)$stmt3->fetchColumn();
} catch(Exception $e) {}

$referral_pts_raw = $referral_pts_raw ?? 0;
$referral_count   = $referral_count   ?? 0;

$weekly = [];
try {
    $stmt = $pdo->prepare("
        SELECT m.week, COUNT(t.id) as completed
        FROM internship_modules m
        LEFT JOIN module_tracking t ON m.id = t.module_id AND t.intern_id = ?
        WHERE m.is_visible = 1
        GROUP BY m.week
        ORDER BY m.week ASC
    ");
    $stmt->execute([$intern_id]);
    $weekly = $stmt->fetchAll();
} catch(Exception $e) {}

$task_rows = [];
try {
    $stmt = $pdo->prepare("
        SELECT t.title, t.due_date, ta.status, ta.submitted_at
        FROM task_assignments ta
        JOIN tasks t ON ta.task_id = t.id
        WHERE ta.intern_id = ?
        ORDER BY t.due_date DESC
        LIMIT 20
    ");
    $stmt->execute([$intern_id]);
    $task_rows = $stmt->fetchAll();
} catch(Exception $e) {}

$streak = 0;
try {
    $stmt = $pdo->prepare("SELECT date, status FROM intern_attendance WHERE intern_id = ? ORDER BY date DESC LIMIT 30");
    $stmt->execute([$intern_id]);
    $att_history = $stmt->fetchAll();
    foreach ($att_history as $a) {
        if ($a['status'] === 'Present') $streak++; else break;
    }
} catch(Exception $e) {}

$challenge_total_attempted = 0;
$challenge_total_correct   = 0;
$challenge_pts_earned      = 0;
$challenge_history         = [];
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM intern_challenges WHERE intern_id = ?");
    $stmt->execute([$intern_id]);
    $challenge_total_attempted = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM intern_challenges WHERE intern_id = ? AND is_correct = 1");
    $stmt->execute([$intern_id]);
    $challenge_total_correct = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COALESCE(SUM(points_earned), 0) FROM intern_challenges WHERE intern_id = ?");
    $stmt->execute([$intern_id]);
    $challenge_pts_earned = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare("
        SELECT ic.submitted_at, ic.is_correct, ic.points_earned, dc.question as title
        FROM intern_challenges ic
        JOIN daily_challenges dc ON ic.challenge_id = dc.id
        WHERE ic.intern_id = ?
        ORDER BY ic.submitted_at DESC
        LIMIT 10
    ");
    $stmt->execute([$intern_id]);
    $challenge_history = $stmt->fetchAll();
} catch(Exception $e) {}

$pts_module    = $modules_completed * 10;
$pts_task      = $tasks_completed * 15;
$pts_att       = $attendance_present * 5;
$pts_challenge = $challenge_pts_earned;
$pts_referral  = $referral_pts_raw;
$total_pts     = $pts_module + $pts_task + $pts_att + $pts_challenge + $pts_referral;

$mod_pct       = $modules_total   > 0 ? round(($modules_completed / $modules_total) * 100) : 0;
$task_pct      = $tasks_total     > 0 ? round(($tasks_completed   / $tasks_total)   * 100) : 0;
$att_pct       = $attendance_total> 0 ? round(($attendance_present/$attendance_total)*100) : 0;
$challenge_acc = $challenge_total_attempted > 0 ? round(($challenge_total_correct / $challenge_total_attempted) * 100) : 0;
?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Share+Tech+Mono&display=swap">

<style>
.perf-page { padding: 30px; }

/* Stat Cards */
.perf-stats-row { display:grid; grid-template-columns:repeat(auto-fit, minmax(160px, 1fr)); gap:20px; margin-bottom:30px; }
.perf-stat-card {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius:14px;
    padding:22px;
    position:relative;
    overflow:hidden;
    transition:0.3s;
}
.perf-stat-card:hover { transform:translateY(-4px); }
.perf-stat-card::before {
    content:'';
    position:absolute; bottom:0; left:0; right:0; height:3px;
}
.perf-stat-card.c-cyan::before  { background:linear-gradient(90deg,transparent,#00f3ff,transparent); }
.perf-stat-card.c-purple::before{ background:linear-gradient(90deg,transparent,#a855f7,transparent); }
.perf-stat-card.c-green::before { background:linear-gradient(90deg,transparent,#10b981,transparent); }
.perf-stat-card.c-amber::before { background:linear-gradient(90deg,transparent,#f59e0b,transparent); }
.perf-stat-val {
    font-family:'Rajdhani',sans-serif;
    font-size:2.5rem;
    font-weight:900;
    line-height:1;
    margin-bottom:5px;
}
.c-cyan  .perf-stat-val { color:#00f3ff; text-shadow:0 0 15px rgba(0,243,255,0.5); }
.c-purple.perf-stat-val, .c-purple .perf-stat-val { color:#a855f7; text-shadow:0 0 15px rgba(168,85,247,0.5); }
.c-green .perf-stat-val { color:#10b981; text-shadow:0 0 15px rgba(16,185,129,0.5); }
.c-amber .perf-stat-val { color:#f59e0b; text-shadow:0 0 15px rgba(245,158,11,0.5); }
.perf-stat-label { font-size:0.75rem; color:#64748b; text-transform:uppercase; letter-spacing:1.5px; }
.perf-stat-icon {
    position:absolute; top:20px; right:20px;
    font-size:2rem; opacity:0.08;
}

/* Progress Bar */
.progress-section { margin-bottom:30px; }
.progress-item { margin-bottom:20px; }
.progress-meta { display:flex; justify-content:space-between; margin-bottom:8px; }
.progress-label { font-size:0.85rem; font-weight:600; color:#fff; }
.progress-val { font-family:'Share Tech Mono',monospace; font-size:0.8rem; }
.progress-track {
    height:10px; background:rgba(255,255,255,0.05);
    border-radius:10px; overflow:hidden;
    position:relative;
}
.progress-fill {
    height:100%;
    border-radius:10px;
    transition:width 1.5s cubic-bezier(0.25,0.46,0.45,0.94);
    position:relative;
}
.progress-fill::after {
    content:'';
    position:absolute; top:0; left:0; right:0; bottom:0;
    background:linear-gradient(90deg,transparent,rgba(255,255,255,0.2),transparent);
    animation:shimmer2 2s infinite;
}
@keyframes shimmer2 { 0%{transform:translateX(-100%)} 100%{transform:translateX(300%)} }
.fill-cyan   { background:linear-gradient(90deg,#0891b2,#00f3ff); box-shadow:0 0 10px rgba(0,243,255,0.4); }
.fill-purple { background:linear-gradient(90deg,#7c3aed,#a855f7); box-shadow:0 0 10px rgba(168,85,247,0.4); }
.fill-green  { background:linear-gradient(90deg,#059669,#10b981); box-shadow:0 0 10px rgba(16,185,129,0.4); }

/* Task table */
.perf-task-table { width:100%; border-collapse:collapse; }
.perf-task-table th { padding:12px 16px; font-size:0.7rem; font-weight:800; letter-spacing:2px; text-transform:uppercase; color:#475569; border-bottom:1px solid rgba(255,255,255,0.05); text-align:left; }
.perf-task-table td { padding:13px 16px; border-bottom:1px solid rgba(255,255,255,0.03); font-size:0.85rem; }
.perf-task-table tr:hover td { background:rgba(255,255,255,0.01); }

/* Weekly grid */
.week-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(160px,1fr)); gap:15px; }
.perf-week-card {
    background:rgba(255,255,255,0.03);
    border:1px solid rgba(255,255,255,0.06);
    border-radius:10px;
    padding:16px;
    text-align:center;
    transition:0.3s;
}
.perf-week-card:hover { border-color:rgba(0,243,255,0.3); background:rgba(0,243,255,0.03); }
.week-circle {
    width:60px; height:60px;
    border-radius:50%;
    margin:0 auto 12px;
    display:flex; align-items:center; justify-content:center;
    font-family:'Rajdhani',sans-serif;
    font-size:1.2rem;
    font-weight:900;
    position:relative;
}
.week-number { font-size:0.75rem; color:#64748b; text-transform:uppercase; letter-spacing:1px; }

/* Streak */
.streak-badge {
    display:inline-flex; align-items:center; gap:10px;
    background:linear-gradient(135deg,rgba(245,158,11,0.1),rgba(239,68,68,0.05));
    border:1px solid rgba(245,158,11,0.3);
    border-radius:50px;
    padding:10px 20px;
    font-family:'Rajdhani',sans-serif;
    font-size:1.1rem;
    font-weight:700;
    color:#f59e0b;
}
</style>

<div class="dashboard-wrapper">
    <?php include_once '_sidebar.php'; ?>

    <main class="main-content">
        <header class="topbar">
            <div class="topbar-left">
                <h2 style="font-family:'Rajdhani',sans-serif; letter-spacing:2px;">
                    <i class="fa-solid fa-chart-line" style="color:#00f3ff;"></i> PERFORMANCE MATRIX
                </h2>
            </div>
            <div class="topbar-right">
                <div class="user-profile">
                    <?php
                    $photo = $_SESSION['user_photo'] ?? '';
                    $name_enc = urlencode($_SESSION['user_name'] ?? 'U');
                    $photo_src = !empty($photo)
                        ? (strpos($photo,'http')===0 ? $photo : '../' . ltrim($photo,'/'))
                        : "https://ui-avatars.com/api/?name={$name_enc}&background=0D8ABC&color=fff&size=80";
                    ?>
                    <img src="<?= esc($photo_src) ?>" class="profile-pic" alt="Profile">
                    <div class="user-info">
                        <span class="user-name"><?= esc($_SESSION['user_name']) ?></span>
                        <span class="user-role">Intern</span>
                    </div>
                </div>
            </div>
        </header>

        <div class="dashboard-content perf-page">

            
            <div class="perf-stats-row">
                <div class="perf-stat-card c-cyan">
                    <i class="fa-solid fa-book-open perf-stat-icon"></i>
                    <div class="perf-stat-val"><?= $modules_completed ?></div>
                    <div class="perf-stat-label">Modules Completed</div>
                    <div style="font-family:'Share Tech Mono',monospace; color:#64748b; font-size:0.75rem; margin-top:5px;"><?= $modules_completed ?> / <?= $modules_total ?></div>
                </div>
                <div class="perf-stat-card c-purple">
                    <i class="fa-solid fa-list-check perf-stat-icon"></i>
                    <div class="perf-stat-val"><?= $tasks_completed ?></div>
                    <div class="perf-stat-label">Tasks Completed</div>
                    <div style="font-family:'Share Tech Mono',monospace; color:#64748b; font-size:0.75rem; margin-top:5px;"><?= $tasks_completed ?> / <?= $tasks_total ?></div>
                </div>
                <div class="perf-stat-card c-green">
                    <i class="fa-solid fa-calendar-check perf-stat-icon"></i>
                    <div class="perf-stat-val"><?= $attendance_present ?></div>
                    <div class="perf-stat-label">Days Present</div>
                    <div style="font-family:'Share Tech Mono',monospace; color:#64748b; font-size:0.75rem; margin-top:5px;"><?= $att_pct ?>% attendance</div>
                </div>
                <div class="perf-stat-card" style="border-color:rgba(168,85,247,0.2);">
                    <i class="fa-solid fa-gamepad perf-stat-icon" style="color:#a855f7;"></i>
                    <div class="perf-stat-val" style="color:#a855f7; text-shadow:0 0 15px rgba(168,85,247,0.5);"><?= $challenge_total_correct ?></div>
                    <div class="perf-stat-label">Challenges Solved</div>
                    <div style="font-family:'Share Tech Mono',monospace; color:#64748b; font-size:0.75rem; margin-top:5px;"><?= $challenge_acc ?>% accuracy</div>
                    <div style="position:absolute; bottom:0; left:0; right:0; height:3px; background:linear-gradient(90deg,transparent,#a855f7,transparent);"></div>
                </div>
                <div class="perf-stat-card c-amber">
                    <i class="fa-solid fa-star perf-stat-icon"></i>
                    <div class="perf-stat-val"><?= number_format($total_pts) ?></div>
                    <div class="perf-stat-label">Total Points</div>
                    <div style="font-family:'Share Tech Mono',monospace; color:#64748b; font-size:0.75rem; margin-top:5px;">Rank score</div>
                </div>
                <div class="perf-stat-card" style="border-color:rgba(16,185,129,0.2);">
                    <i class="fa-solid fa-user-plus perf-stat-icon" style="color:#10b981;"></i>
                    <div class="perf-stat-val" style="color:#10b981; text-shadow:0 0 15px rgba(16,185,129,0.5);"><?= $referral_count ?></div>
                    <div class="perf-stat-label">Referrals Made</div>
                    <div style="font-family:'Share Tech Mono',monospace; color:#64748b; font-size:0.75rem; margin-top:5px;"><?= number_format($pts_referral, 1) ?> pts earned</div>
                    <div style="position:absolute; bottom:0; left:0; right:0; height:3px; background:linear-gradient(90deg,transparent,#10b981,transparent);"></div>
                </div>
            </div>

            
            <div style="display:grid; grid-template-columns:2fr 1fr; gap:25px; margin-bottom:30px;">
                <div class="glass-panel progress-section">
                    <h3 style="margin-bottom:25px; font-family:'Rajdhani',sans-serif; letter-spacing:1px; display:flex; align-items:center; gap:10px;">
                        <i class="fa-solid fa-signal" style="color:#00f3ff;"></i> PROGRESS OVERVIEW
                    </h3>

                    <div class="progress-item">
                        <div class="progress-meta">
                            <span class="progress-label"><i class="fa-solid fa-book-open" style="color:#00f3ff; margin-right:6px;"></i> Daily Modules</span>
                            <span class="progress-val" style="color:#00f3ff;"><?= $modules_completed ?> / <?= $modules_total ?> — <?= $mod_pct ?>%</span>
                        </div>
                        <div class="progress-track"><div class="progress-fill fill-cyan" id="prog-mod" style="width:0%"></div></div>
                    </div>

                    <div class="progress-item">
                        <div class="progress-meta">
                            <span class="progress-label"><i class="fa-solid fa-list-check" style="color:#a855f7; margin-right:6px;"></i> Assigned Tasks</span>
                            <span class="progress-val" style="color:#a855f7;"><?= $tasks_completed ?> / <?= $tasks_total ?> — <?= $task_pct ?>%</span>
                        </div>
                        <div class="progress-track"><div class="progress-fill fill-purple" id="prog-task" style="width:0%"></div></div>
                    </div>

                    <div class="progress-item">
                        <div class="progress-meta">
                            <span class="progress-label"><i class="fa-solid fa-calendar-check" style="color:#10b981; margin-right:6px;"></i> Attendance Rate</span>
                            <span class="progress-val" style="color:#10b981;"><?= $attendance_present ?> / <?= $attendance_total ?> — <?= $att_pct ?>%</span>
                        </div>
                        <div class="progress-track"><div class="progress-fill fill-green" id="prog-att" style="width:0%"></div></div>
                    </div>

                    <div class="progress-item">
                        <div class="progress-meta">
                            <span class="progress-label"><i class="fa-solid fa-gamepad" style="color:#a855f7; margin-right:6px;"></i> Daily Challenge Accuracy</span>
                            <span class="progress-val" style="color:#a855f7;"><?= $challenge_total_correct ?> / <?= $challenge_total_attempted ?> — <?= $challenge_acc ?>%</span>
                        </div>
                        <div class="progress-track"><div class="progress-fill" id="prog-challenge" style="width:0%; background:linear-gradient(90deg,#7c3aed,#a855f7); box-shadow:0 0 10px rgba(168,85,247,0.4);"></div></div>
                    </div>
                </div>

                
                <div class="glass-panel" style="display:flex; flex-direction:column; gap:18px;">
                    <h3 style="margin:0; font-family:'Rajdhani',sans-serif; letter-spacing:1px; display:flex; align-items:center; gap:10px;">
                        <i class="fa-solid fa-coins" style="color:#f59e0b;"></i> POINTS BREAKDOWN
                    </h3>
                    <div style="display:flex; justify-content:space-between; align-items:center; padding:12px; background:rgba(0,243,255,0.05); border-radius:8px; border:1px solid rgba(0,243,255,0.1);">
                        <span style="color:#94a3b8; font-size:0.85rem;"><i class="fa-solid fa-book-open" style="color:#00f3ff; width:16px;"></i> Modules</span>
                        <span style="font-family:'Share Tech Mono',monospace; color:#00f3ff; font-weight:bold;"><?= $pts_module ?> pts</span>
                    </div>
                    <div style="display:flex; justify-content:space-between; align-items:center; padding:12px; background:rgba(168,85,247,0.05); border-radius:8px; border:1px solid rgba(168,85,247,0.1);">
                        <span style="color:#94a3b8; font-size:0.85rem;"><i class="fa-solid fa-list-check" style="color:#a855f7; width:16px;"></i> Tasks</span>
                        <span style="font-family:'Share Tech Mono',monospace; color:#a855f7; font-weight:bold;"><?= $pts_task ?> pts</span>
                    </div>
                    <div style="display:flex; justify-content:space-between; align-items:center; padding:12px; background:rgba(16,185,129,0.05); border-radius:8px; border:1px solid rgba(16,185,129,0.1);">
                        <span style="color:#94a3b8; font-size:0.85rem;"><i class="fa-solid fa-calendar-check" style="color:#10b981; width:16px;"></i> Attendance</span>
                        <span style="font-family:'Share Tech Mono',monospace; color:#10b981; font-weight:bold;"><?= $pts_att ?> pts</span>
                    </div>
                    <div style="display:flex; justify-content:space-between; align-items:center; padding:12px; background:rgba(168,85,247,0.05); border-radius:8px; border:1px solid rgba(168,85,247,0.1);">
                        <span style="color:#94a3b8; font-size:0.85rem;"><i class="fa-solid fa-gamepad" style="color:#a855f7; width:16px;"></i> Daily Challenges</span>
                        <span style="font-family:'Share Tech Mono',monospace; color:#a855f7; font-weight:bold;"><?= $pts_challenge ?> pts</span>
                    </div>
                    <div style="display:flex; justify-content:space-between; align-items:center; padding:12px; background:rgba(16,185,129,0.05); border-radius:8px; border:1px solid rgba(16,185,129,0.1);">
                        <span style="color:#94a3b8; font-size:0.85rem;"><i class="fa-solid fa-user-plus" style="color:#10b981; width:16px;"></i> Referrals <span style="font-size:0.75rem; color:#475569;">(<?= $referral_count ?> made)</span></span>
                        <span style="font-family:'Share Tech Mono',monospace; color:#10b981; font-weight:bold;"><?= number_format($pts_referral, 1) ?> pts</span>
                    </div>
                    <div style="border-top:1px solid rgba(255,255,255,0.05); padding-top:15px; text-align:center;">
                        <div style="font-size:0.7rem; color:#64748b; letter-spacing:2px; text-transform:uppercase;">TOTAL SCORE</div>
                        <div style="font-family:'Rajdhani',sans-serif; font-size:2.5rem; font-weight:900; color:#f59e0b; text-shadow:0 0 20px rgba(245,158,11,0.4);"><?= number_format($total_pts) ?></div>
                    </div>
                    <?php if ($streak > 0): ?>
                    <div class="streak-badge" style="justify-content:center;">
                        <i class="fa-solid fa-fire"></i> <?= $streak ?>-Day Streak!
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            
            <?php if (!empty($weekly)): ?>
            <div class="glass-panel" style="margin-bottom:25px;">
                <h3 style="margin-bottom:20px; font-family:'Rajdhani',sans-serif; letter-spacing:1px; display:flex; align-items:center; gap:10px;">
                    <i class="fa-solid fa-calendar-week" style="color:#a855f7;"></i> WEEK-BY-WEEK BREAKDOWN
                </h3>
                <div class="week-grid">
                    <?php foreach($weekly as $w):
                        $wn = (int)$w['week'];
                        $comp = (int)$w['completed'];
                        $pct = 7 > 0 ? min(100, round(($comp/7)*100)) : 0;
                        $color = $pct == 100 ? '#10b981' : ($pct > 0 ? '#00f3ff' : '#475569');
                        $border = "2px solid " . ($pct == 100 ? 'rgba(16,185,129,0.5)' : ($pct > 0 ? 'rgba(0,243,255,0.4)' : 'rgba(255,255,255,0.05)'));
                    ?>
                    <div class="perf-week-card">
                        <div class="week-circle" style="border:<?= $border ?>; background:<?= $pct==100 ? 'rgba(16,185,129,0.1)' : ($pct>0 ? 'rgba(0,243,255,0.08)' : 'rgba(255,255,255,0.02)') ?>; color:<?= $color ?>;">
                            <?php if ($pct == 100): ?>
                                <i class="fa-solid fa-check"></i>
                            <?php else: ?>
                                <?= $comp ?>/7
                            <?php endif; ?>
                        </div>
                        <div style="font-weight:700; color:#fff; font-family:'Rajdhani',sans-serif;">Week <?= $wn ?></div>
                        <div class="week-number"><?= $pct ?>% done</div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            
            <?php if (!empty($task_rows)): ?>
            <div class="glass-panel" style="padding:0; overflow:hidden;">
                <div style="padding:20px 25px; border-bottom:1px solid rgba(255,255,255,0.05);">
                    <h3 style="margin:0; font-family:'Rajdhani',sans-serif; letter-spacing:1px;">
                        <i class="fa-solid fa-list-check" style="color:#a855f7;"></i> TASK HISTORY
                    </h3>
                </div>
                <table class="perf-task-table">
                    <thead><tr><th>Task</th><th>Status</th><th>Submitted</th><th>Due Date</th></tr></thead>
                    <tbody>
                    <?php foreach($task_rows as $t):
                        $sc = $t['status'] == 'Completed' ? '#10b981' : ($t['status'] == 'In Progress' ? '#f59e0b' : '#ef4444');
                    ?>
                    <tr>
                        <td style="color:#fff; font-weight:500;"><?= esc($t['title']) ?></td>
                        <td><span style="background:rgba(0,0,0,0.3); color:<?= $sc ?>; padding:3px 10px; border-radius:20px; font-size:0.75rem; font-weight:bold; border:1px solid <?= $sc ?>33;"><?= esc($t['status']) ?></span></td>
                        <td style="color:#64748b; font-size:0.85rem;"><?= $t['submitted_at'] ? date('d M Y', strtotime($t['submitted_at'])) : '—' ?></td>
                        <td style="color:#64748b; font-size:0.85rem;"><?= $t['due_date'] ? date('d M Y', strtotime($t['due_date'])) : '—' ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

            
            <?php if (!empty($challenge_history)): ?>
            <div class="glass-panel" style="padding:0; overflow:hidden; margin-top: 0;">
                <div style="padding:20px 25px; border-bottom:1px solid rgba(255,255,255,0.05); display:flex; align-items:center; gap:12px;">
                    <h3 style="margin:0; font-family:'Rajdhani',sans-serif; letter-spacing:1px;">
                        <i class="fa-solid fa-gamepad" style="color:#a855f7;"></i> DAILY CHALLENGE HISTORY
                    </h3>
                    <span style="background:rgba(168,85,247,0.15); color:#a855f7; font-size:0.72rem; padding:3px 10px; border-radius:20px; font-weight:bold; border:1px solid rgba(168,85,247,0.3);"><?= $challenge_pts_earned ?> pts earned</span>
                </div>
                <table class="perf-task-table">
                    <thead><tr><th>Challenge</th><th>Result</th><th>Points</th><th>Date</th></tr></thead>
                    <tbody>
                    <?php foreach($challenge_history as $ch): ?>
                    <tr>
                        <td style="color:#fff; font-weight:500;"><?= esc($ch['title']) ?></td>
                        <td>
                            <?php if ($ch['is_correct']): ?>
                                <span style="background:rgba(16,185,129,0.1); color:#10b981; padding:3px 10px; border-radius:20px; font-size:0.75rem; font-weight:bold; border:1px solid rgba(16,185,129,0.3);"><i class="fa-solid fa-check"></i> Correct</span>
                            <?php else: ?>
                                <span style="background:rgba(239,68,68,0.1); color:#ef4444; padding:3px 10px; border-radius:20px; font-size:0.75rem; font-weight:bold; border:1px solid rgba(239,68,68,0.3);"><i class="fa-solid fa-xmark"></i> Incorrect</span>
                            <?php endif; ?>
                        </td>
                        <td style="font-family:'Share Tech Mono',monospace; color:#a855f7; font-weight:bold;"><?= (int)$ch['points_earned'] ?> pts</td>
                        <td style="color:#64748b; font-size:0.85rem;"><?= $ch['submitted_at'] ? date('d M Y, h:i A', strtotime($ch['submitted_at'])) : '—' ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

        </div>
    </main>
</div>

<script>
// Animate progress bars on load
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(() => {
        document.getElementById('prog-mod').style.width       = '<?= $mod_pct       ?>%';
        document.getElementById('prog-task').style.width      = '<?= $task_pct      ?>%';
        document.getElementById('prog-att').style.width       = '<?= $att_pct       ?>%';
        document.getElementById('prog-challenge').style.width = '<?= $challenge_acc  ?>%';
    }, 300);
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
