<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

check_auth('Intern');

$portal = 'intern';
$page_title = 'Elite Leaderboard | ' . SITE_NAME;

$current_intern_id = $_SESSION['user_id'];

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS intern_points (
        id INT AUTO_INCREMENT PRIMARY KEY,
        intern_id INT NOT NULL UNIQUE,
        module_points INT DEFAULT 0,
        task_points INT DEFAULT 0,
        attendance_points INT DEFAULT 0,
        total_points INT GENERATED ALWAYS AS (module_points + task_points + attendance_points) STORED,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
} catch (Exception $e) {
}

try {
    $cols = $pdo->query("SHOW COLUMNS FROM audit_logs LIKE 'user_mapped_id'")->fetchAll();
    if (empty($cols)) {
        $has_user_id = $pdo->query("SHOW COLUMNS FROM audit_logs LIKE 'user_id'")->fetchAll();
        if (!empty($has_user_id)) {
            $pdo->exec("ALTER TABLE audit_logs CHANGE COLUMN user_id user_mapped_id VARCHAR(50)");
        } else {
            $pdo->exec("ALTER TABLE audit_logs ADD COLUMN user_mapped_id VARCHAR(50)");
        }
    }
} catch (Exception $e) {
}

try {
    $cols = $pdo->query("SHOW COLUMNS FROM task_assignments LIKE 'score'")->fetchAll();
    if (empty($cols)) {
        $pdo->exec("ALTER TABLE task_assignments ADD COLUMN score INT DEFAULT 0");
    }
} catch (Exception $e) {
}
try {
    $cols = $pdo->query("SHOW COLUMNS FROM task_assignments LIKE 'review_status'")->fetchAll();
    if (empty($cols)) {
        $pdo->exec("ALTER TABLE task_assignments ADD COLUMN review_status VARCHAR(50) DEFAULT 'Pending'");
    }
} catch (Exception $e) {
}
try {
    $cols = $pdo->query("SHOW COLUMNS FROM task_assignments LIKE 'review_comment'")->fetchAll();
    if (empty($cols)) {
        $pdo->exec("ALTER TABLE task_assignments ADD COLUMN review_comment TEXT");
    }
} catch (Exception $e) {
}
try {
    $cols = $pdo->query("SHOW COLUMNS FROM task_assignments LIKE 'submitted_at'")->fetchAll();
    if (empty($cols)) {
        $pdo->exec("ALTER TABLE task_assignments ADD COLUMN submitted_at DATETIME");
    }
} catch (Exception $e) {
}
try {
    $cols = $pdo->query("SHOW COLUMNS FROM task_assignments LIKE 'submission_file'")->fetchAll();
    if (empty($cols)) {
        $pdo->exec("ALTER TABLE task_assignments ADD COLUMN submission_file VARCHAR(255)");
    }
} catch (Exception $e) {
}
try {
    $cols = $pdo->query("SHOW COLUMNS FROM task_assignments LIKE 'submission_text'")->fetchAll();
    if (empty($cols)) {
        $pdo->exec("ALTER TABLE task_assignments ADD COLUMN submission_text TEXT");
    }
} catch (Exception $e) {
}
try {
    $cols = $pdo->query("SHOW COLUMNS FROM task_assignments LIKE 'feedback'")->fetchAll();
    if (empty($cols)) {
        $pdo->exec("ALTER TABLE task_assignments ADD COLUMN feedback TEXT");
    }
} catch (Exception $e) {
}

$selected_month = $_GET['month'] ?? date('Y-m');

$leaderboard = [];
try {
    
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS ctf_submissions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            intern_id INT NOT NULL,
            flag_submitted VARCHAR(255) DEFAULT NULL,
            is_correct TINYINT(1) DEFAULT 0,
            partial_steps VARCHAR(255) DEFAULT NULL,
            partial_notes TEXT DEFAULT NULL,
            points_awarded INT DEFAULT 0,
            submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (intern_id) REFERENCES interns(id) ON DELETE CASCADE
        )");
        try {
            $pdo->exec("ALTER TABLE ctf_submissions ADD COLUMN partial_steps VARCHAR(255) DEFAULT NULL");
        } catch (PDOException $e) {
        }
        try {
            $pdo->exec("ALTER TABLE ctf_submissions ADD COLUMN partial_notes TEXT DEFAULT NULL");
        } catch (PDOException $e) {
        }
        try {
            $pdo->exec("ALTER TABLE ctf_submissions ADD COLUMN points_awarded INT DEFAULT 0");
        } catch (PDOException $e) {
        }
        try {
            $pdo->exec("ALTER TABLE ctf_submissions ADD COLUMN submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
        } catch (PDOException $e) {
        }
    } catch (PDOException $e) {
    }

    
    try {
        $pdo->exec("UPDATE task_assignments SET score = 15 WHERE status='Completed' AND review_status='Approved' AND score = 0");
    } catch (Exception $e) {
    }
    
    try {
        $pdo->exec("UPDATE task_assignments SET submitted_at = NOW() WHERE status='Completed' AND submitted_at IS NULL");
    } catch (Exception $e) {
    }

    
    $excluded_names = ['Prateek Mishra', 'Atul Shrivastava', 'Unnati Saxena', 'Palak Rai'];
    $exclude_placeholders = implode(',', array_fill(0, count($excluded_names), '?'));

    
    
    
    $stmt_batch = $pdo->prepare("SELECT batch FROM interns WHERE id = ?");
    $stmt_batch->execute([$current_intern_id]);
    $my_batch = $stmt_batch->fetchColumn();
    
    if (!empty($my_batch)) {
        $selected_batch = $my_batch;
    } else {
        $selected_batch = date('F Y', strtotime($selected_month . '-01')) . ' Batch';
    }

    $stmt = $pdo->prepare("
        SELECT i.id, i.name, i.college, i.profile_photo, i.intern_id as mapped_id,
               COALESCE(i.referral_points, 0) as referral_points,
               (SELECT COUNT(*) * 10 FROM module_tracking WHERE intern_id = i.id AND DATE_FORMAT(opened_at, '%Y-%m') = ?) as module_points,
               (SELECT COALESCE(SUM(score), 0) FROM task_assignments WHERE intern_id = i.id AND status='Completed' AND review_status='Approved' AND DATE_FORMAT(submitted_at, '%Y-%m') = ?) as task_points,
               (SELECT COUNT(*) * 5 FROM intern_attendance WHERE intern_id = i.id AND status='Present' AND DATE_FORMAT(date, '%Y-%m') = ?) as attendance_points,
               (SELECT COALESCE(SUM(points_awarded), 0) FROM ctf_submissions WHERE intern_id = i.id AND DATE_FORMAT(submitted_at, '%Y-%m') = ?) as ctf_points,
               (SELECT COALESCE(SUM(points_earned), 0) FROM intern_challenges WHERE intern_id = i.id AND DATE_FORMAT(submitted_at, '%Y-%m') = ?) as challenge_points
        FROM interns i
        WHERE i.batch = ? AND i.name NOT IN ($exclude_placeholders)
    ");
    $params = array_merge(
        [$selected_month, $selected_month, $selected_month, $selected_month, $selected_month, $selected_batch],
        $excluded_names
    );
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($results as &$row) {
        $row['module_points']   = (int)   ($row['module_points']     ?? 0);
        $row['task_points']     = (int)   ($row['task_points']       ?? 0);
        $row['attendance_points']=(int)   ($row['attendance_points'] ?? 0);
        $row['ctf_points']      = (int)   ($row['ctf_points']        ?? 0);
        $row['challenge_points']= (int)   ($row['challenge_points']  ?? 0);
        $row['referral_points'] = (float) ($row['referral_points']   ?? 0);
        $row['total_points'] = $row['module_points'] + $row['task_points'] + $row['attendance_points'] + $row['ctf_points'] + $row['challenge_points'] + $row['referral_points'];
    }
    unset($row);

    
    usort($results, function ($a, $b) {
        if ($b['total_points'] == $a['total_points']) {
            return strcmp($a['name'], $b['name']);
        }
        return $b['total_points'] - $a['total_points'];
    });

    $leaderboard = array_slice($results, 0, 50);
} catch (Exception $e) {
    error_log("Leaderboard DB error: " . $e->getMessage() . " | Trace: " . $e->getTraceAsString());
    $leaderboard = [];
}

$my_rank = 0;
foreach ($leaderboard as $idx => $row) {
    if ($row['id'] == $current_intern_id) {
        $my_rank = $idx + 1;
        break;
    }
}
?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>

<style>
    /* Leaderboard Specific Styles */
    .lb-page {
        padding: 30px;
    }

    .lb-hero {
        text-align: center;
        margin-bottom: 40px;
        position: relative;
    }

    .lb-hero h1 {
        font-family: 'Rajdhani', sans-serif;
        font-size: 3rem;
        color: #fff;
        letter-spacing: 4px;
        text-transform: uppercase;
        margin: 0;
        text-shadow: 0 0 30px rgba(0, 243, 255, 0.5);
    }

    .lb-hero p {
        color: #64748b;
        margin-top: 8px;
    }

    /* My rank banner */
    .my-rank-banner {
        background: linear-gradient(135deg, rgba(0, 243, 255, 0.08), rgba(168, 85, 247, 0.08));
        border: 1px solid rgba(0, 243, 255, 0.3);
        border-radius: 12px;
        padding: 20px 30px;
        display: flex;
        align-items: center;
        gap: 20px;
        margin-bottom: 35px;
        position: relative;
        overflow: hidden;
    }

    .my-rank-banner::before {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(90deg, transparent, rgba(0, 243, 255, 0.03), transparent);
        animation: shimmer 3s infinite;
    }

    @keyframes shimmer {
        0% {
            transform: translateX(-100%)
        }

        100% {
            transform: translateX(100%)
        }
    }

    /* Top 3 Podium */
    .podium-row {
        display: flex;
        justify-content: center;
        align-items: flex-end;
        gap: 20px;
        margin-bottom: 50px;
        min-height: 280px;
    }

    .podium-card {
        text-align: center;
        display: flex;
        flex-direction: column;
        align-items: center;
        position: relative;
    }

    .podium-card.rank-1 {
        order: 2;
    }

    .podium-card.rank-2 {
        order: 3;
    }

    .podium-card.rank-3 {
        order: 1;
    }

    .podium-avatar {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        object-fit: cover;
        margin-bottom: 12px;
        border: 3px solid transparent;
        position: relative;
    }

    .rank-1 .podium-avatar {
        width: 100px;
        height: 100px;
        border-color: #fbbf24;
        box-shadow: 0 0 30px rgba(251, 191, 36, 0.6), 0 0 60px rgba(251, 191, 36, 0.3);
    }

    .rank-2 .podium-avatar {
        border-color: #94a3b8;
        box-shadow: 0 0 20px rgba(148, 163, 184, 0.5), 0 0 40px rgba(148, 163, 184, 0.2);
    }

    .rank-3 .podium-avatar {
        border-color: #cd7f32;
        box-shadow: 0 0 20px rgba(205, 127, 50, 0.5), 0 0 40px rgba(205, 127, 50, 0.2);
    }

    .podium-crown {
        font-size: 1.8rem;
        margin-bottom: 5px;
        animation: float 3s ease-in-out infinite;
    }

    .rank-1 .podium-crown {
        font-size: 2.2rem;
    }

    @keyframes float {

        0%,
        100% {
            transform: translateY(0)
        }

        50% {
            transform: translateY(-8px)
        }
    }

    .podium-name {
        font-family: 'Rajdhani', sans-serif;
        font-size: 1rem;
        color: #fff;
        font-weight: 700;
        max-width: 130px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .podium-points {
        font-family: 'Share Tech Mono', monospace;
        font-size: 0.85rem;
        margin-top: 4px;
    }

    .rank-1 .podium-points {
        color: #fbbf24;
    }

    .rank-2 .podium-points {
        color: #94a3b8;
    }

    .rank-3 .podium-points {
        color: #cd7f32;
    }

    .podium-block {
        width: 120px;
        border-radius: 8px 8px 0 0;
        display: flex;
        align-items: center;
        justify-content: center;
        font-family: 'Rajdhani', sans-serif;
        font-size: 2rem;
        font-weight: 900;
        color: rgba(255, 255, 255, 0.4);
        margin-top: 15px;
    }

    .rank-1 .podium-block {
        height: 120px;
        background: linear-gradient(180deg, rgba(251, 191, 36, 0.3), rgba(251, 191, 36, 0.05));
        border: 1px solid rgba(251, 191, 36, 0.4);
        width: 140px;
    }

    .rank-2 .podium-block {
        height: 90px;
        background: linear-gradient(180deg, rgba(148, 163, 184, 0.2), rgba(148, 163, 184, 0.03));
        border: 1px solid rgba(148, 163, 184, 0.3);
    }

    .rank-3 .podium-block {
        height: 70px;
        background: linear-gradient(180deg, rgba(205, 127, 50, 0.2), rgba(205, 127, 50, 0.03));
        border: 1px solid rgba(205, 127, 50, 0.3);
    }

    /* Main Table */
    .lb-table-wrap {
        overflow-x: auto;
    }

    .lb-table {
        width: 100%;
        border-collapse: collapse;
    }

    .lb-table th {
        padding: 14px 20px;
        text-align: left;
        font-size: 0.7rem;
        font-weight: 800;
        letter-spacing: 2px;
        text-transform: uppercase;
        color: #475569;
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    }

    .lb-table td {
        padding: 14px 20px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.03);
        transition: 0.2s;
    }

    .lb-table tr:hover td {
        background: rgba(0, 243, 255, 0.03);
    }

    .lb-table tr.is-me td {
        background: rgba(0, 243, 255, 0.06);
    }

    .lb-table tr.is-me td:first-child {
        border-left: 3px solid #00f3ff;
    }

    .rank-badge {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 900;
        font-size: 0.9rem;
        font-family: 'Rajdhani', sans-serif;
    }

    .rank-badge.top1 {
        background: rgba(251, 191, 36, 0.15);
        color: #fbbf24;
        border: 1px solid rgba(251, 191, 36, 0.4);
    }

    .rank-badge.top2 {
        background: rgba(148, 163, 184, 0.15);
        color: #94a3b8;
        border: 1px solid rgba(148, 163, 184, 0.4);
    }

    .rank-badge.top3 {
        background: rgba(205, 127, 50, 0.15);
        color: #cd7f32;
        border: 1px solid rgba(205, 127, 50, 0.4);
    }

    .rank-badge.normal {
        background: rgba(255, 255, 255, 0.05);
        color: #64748b;
        border: 1px solid rgba(255, 255, 255, 0.08);
    }

    .pts-pill {
        display: inline-block;
        padding: 3px 10px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-family: 'Share Tech Mono', monospace;
        font-weight: bold;
    }

    .pts-pill.mod {
        background: rgba(0, 243, 255, 0.1);
        color: #00f3ff;
    }

    .pts-pill.task {
        background: rgba(168, 85, 247, 0.1);
        color: #a855f7;
    }

    .pts-pill.att {
        background: rgba(16, 185, 129, 0.1);
        color: #10b981;
    }

    .total-pts {
        font-family: 'Rajdhani', sans-serif;
        font-size: 1.3rem;
        font-weight: 900;
        color: #fff;
    }

    .top1-row .total-pts {
        color: #fbbf24;
        text-shadow: 0 0 10px rgba(251, 191, 36, 0.5);
    }

    .top2-row .total-pts {
        color: #94a3b8;
    }

    .top3-row .total-pts {
        color: #cd7f32;
    }

    .intern-mini-photo {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        object-fit: cover;
        border: 1px solid rgba(255, 255, 255, 0.1);
        vertical-align: middle;
        margin-right: 10px;
    }
</style>

<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Share+Tech+Mono&display=swap">

<div class="dashboard-wrapper">
    <?php include_once '_sidebar.php'; ?>

    <main class="main-content">
        <header class="topbar">
            <div class="topbar-left">
                <h2 style="font-family:'Rajdhani',sans-serif; letter-spacing:2px;">
                    <i class="fa-solid fa-trophy" style="color:#fbbf24;"></i> ELITE LEADERBOARD
                </h2>
            </div>
            <div class="topbar-right">
                <div class="user-profile">
                    <?php
                    
                    if (empty($_SESSION['user_photo'])) {
                        try {
                            $stmt = $pdo->prepare("SELECT profile_photo FROM interns WHERE id = ?");
                            $stmt->execute([$_SESSION['user_id']]);
                            $_SESSION['user_photo'] = $stmt->fetchColumn() ?: '';
                        } catch (Exception $e) {
                        }
                    }

                    $u_photo = $_SESSION['user_photo'];
                    $u_name = $_SESSION['user_name'];

                    
                    if (!empty($u_photo) && file_exists(__DIR__ . '/../' . $u_photo)) {
                        $photo_url = '../' . $u_photo;
                    } else {
                        
                        $photo_url = "https://ui-avatars.com/api/?name=" . urlencode($u_name) . "&background=00f3ff&color=000&bold=true";
                    }
                    ?>
                    <img src="<?= $photo_url ?>" alt="Profile" class="profile-pic"
                        style="width:45px; height:45px; object-fit: cover; border-radius: 50%; border: 2px solid var(--accent-blue);">
                    <div class="user-info">
                        <span class="user-name"><?php echo esc($_SESSION['user_name']) ?></span>
                        <span class="user-role">Intern • <?php echo esc($_SESSION['user_mapped_id'] ?? '') ?></span>
                    </div>
                </div>
            </div>
        </header>

        <div class="dashboard-content lb-page">

            <div class="lb-hero">
                <h1>⚡ Elite Leaderboard</h1>
                <p>Rankings are updated in real-time based on modules, tasks &amp; attendance</p>
                <form method="GET" style="margin-top: 20px; display: inline-block;">
                    <label
                        style="color: #fff; margin-right: 10px; font-weight: bold; font-family: 'Rajdhani', sans-serif; letter-spacing: 1px;">MONTH:
                    </label>
                    <input type="month" name="month" value="<?= esc($selected_month) ?>"
                        style="padding: 8px 15px; border-radius: 8px; font-weight: bold; background: rgba(0,243,255,0.1); color: #00f3ff; border: 1px solid rgba(0,243,255,0.4); outline: none; cursor: pointer;"
                        onchange="this.form.submit()">
                </form>
            </div>

            
            <?php
            $my_pts = 0;
            foreach ($leaderboard as $row) {
                if ($row['id'] == $current_intern_id) {
                    $my_pts = $row['total_points'];
                    break;
                }
            }
            ?>
            <div class="my-rank-banner">
                <div
                    style="background: rgba(0,243,255,0.1); width:55px; height:55px; border-radius:50%; display:flex; align-items:center; justify-content:center; border:2px solid rgba(0,243,255,0.4); flex-shrink:0;">
                    <i class="fa-solid fa-crosshairs" style="color:#00f3ff; font-size:1.4rem;"></i>
                </div>
                <div>
                    <div style="color:#94a3b8; font-size:0.75rem; letter-spacing:2px; text-transform:uppercase;">YOUR
                        CURRENT RANK</div>
                    <div style="font-family:'Rajdhani',sans-serif; font-size:1.5rem; color:#fff; font-weight:900;">
                        #<?= $my_rank ?: 'N/A' ?>
                        <span style="font-family:'Share Tech Mono',monospace; font-size:1rem; color:#00f3ff;">
                            — <?= number_format($my_pts) ?> pts
                        </span>
                    </div>
                </div>
                <div style="margin-left:auto; display:flex; gap:20px;">
                    <div style="text-align:center;">
                        <div
                            style="font-family:'Share Tech Mono',monospace; color:#00f3ff; font-size:1.1rem; font-weight:bold;">
                            <?= $leaderboard[0]['total_points'] ?? 0 ?></div>
                        <div style="font-size:0.7rem; color:#475569; text-transform:uppercase;">Top Score</div>
                    </div>
                    <div style="text-align:center;">
                        <div
                            style="font-family:'Share Tech Mono',monospace; color:#a855f7; font-size:1.1rem; font-weight:bold;">
                            <?= count($leaderboard) ?></div>
                        <div style="font-size:0.7rem; color:#475569; text-transform:uppercase;">Total Cadets</div>
                    </div>
                </div>
            </div>

            
            <?php if (count($leaderboard) >= 1): ?>
                <div class="podium-row">
                    <?php
                    $top3 = array_slice($leaderboard, 0, 3);
                    $crowns = ['🥇', '🥈', '🥉'];
                    $orders = [1 => 'rank-1', 2 => 'rank-2', 3 => 'rank-3'];
                    foreach ([2, 1, 3] as $display_pos):
                        if (!isset($top3[$display_pos - 1]))
                            continue;
                        $p = $top3[$display_pos - 1];
                        $photo = !empty($p['profile_photo']) ? '../' . $p['profile_photo'] : "https://ui-avatars.com/api/?name=" . urlencode($p['name']) . "&background=0D8ABC&color=fff&size=100";
                        ?>
                        <div class="podium-card <?= $orders[$display_pos] ?>">
                            <div class="podium-crown"><?= $crowns[$display_pos - 1] ?></div>
                            <img src="<?= esc($photo) ?>" class="podium-avatar" alt="<?= esc($p['name']) ?>">
                            <div class="podium-name"><?= esc($p['name']) ?></div>
                            <div class="podium-points"><?= number_format($p['total_points']) ?> pts</div>
                            <div class="podium-block"><?= $display_pos ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            
            <div class="glass-panel" style="padding:0; overflow:hidden;">
                <div
                    style="padding:20px 25px; border-bottom:1px solid rgba(255,255,255,0.05); display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:15px;">
                    <div style="display:flex; align-items:center; gap:20px; flex-wrap:wrap;">
                        <h3 style="margin:0; font-family:'Rajdhani',sans-serif; letter-spacing:1px;">
                            <i class="fa-solid fa-list-ol" style="color:#00f3ff;"></i> FULL RANKINGS
                        </h3>
                        <div style="position: relative;">
                            <i class="fa-solid fa-magnifying-glass"
                                style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #64748b; font-size: 0.85rem;"></i>
                            <input type="text" id="leaderboardSearch" placeholder="Search cadet name..."
                                onkeyup="filterLeaderboard()"
                                style="padding: 8px 12px 8px 32px; width: 220px; border-radius: 8px; font-size: 0.85rem; background: rgba(255,255,255,0.05); color: #fff; border: 1px solid rgba(0,243,255,0.3); outline: none; transition: 0.3s; box-shadow: 0 0 10px rgba(0,0,0,0.2);" />
                        </div>
                    </div>
                    <div style="display:flex; gap:15px; font-size:0.75rem; color:#64748b; flex-wrap: wrap;">
                        <span><span class="pts-pill mod">10 pts</span> / Module</span>
                        <span><span class="pts-pill task">15 pts</span> / Task</span>
                        <span><span class="pts-pill att">5 pts</span> / Attendance</span>
                        <span><span class="pts-pill"
                                style="background:rgba(168,85,247,0.1); color:#a855f7; padding: 3px 10px; border-radius: 20px; font-weight:bold; font-family:'Share Tech Mono',monospace;">2
                                pts</span> / Daily Quiz</span>
                        <span><span class="pts-pill ctf-badge"
                                style="background:rgba(245,158,11,0.1); color:#f59e0b; padding: 3px 10px; border-radius: 20px; font-weight:bold; font-family:'Share Tech Mono',monospace;">Up
                                to 100 pts</span> / CTF</span>
                        <span><span class="pts-pill"
                                style="background:rgba(16,185,129,0.1); color:#10b981; padding: 3px 10px; border-radius: 20px; font-weight:bold; font-family:'Share Tech Mono',monospace;">1 pt</span> / Referral</span>
                    </div>
                </div>
                <div class="lb-table-wrap">
                    <table class="lb-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Cadet</th>
                                <th>Institute</th>
                                <th>Modules</th>
                                <th>Tasks</th>
                                <th>Attendance</th>
                                <th>Daily Quiz</th>
                                <th>CTF Points</th>
                                <th>Referrals</th>
                                <th>Total Score</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($leaderboard)): ?>
                                <tr>
                                    <td colspan="9" style="text-align:center; padding:50px; color:#475569;">No ranking data
                                        available yet. Complete modules to earn points!</td>
                                </tr>
                            <?php else:
                                foreach ($leaderboard as $idx => $row):
                                    $rank = $idx + 1;
                                    $is_me = ($row['id'] == $current_intern_id);
                                    $badge_class = $rank == 1 ? 'top1' : ($rank == 2 ? 'top2' : ($rank == 3 ? 'top3' : 'normal'));
                                    $row_class = $rank == 1 ? 'top1-row' : ($rank == 2 ? 'top2-row' : ($rank == 3 ? 'top3-row' : ''));
                                    if ($is_me)
                                        $row_class .= ' is-me';
                                    $photo = !empty($row['profile_photo']) ? '../' . $row['profile_photo'] : "https://ui-avatars.com/api/?name=" . urlencode($row['name']) . "&background=0D8ABC&color=fff&size=72";
                                    ?>
                                    <tr class="<?= $row_class ?>">
                                        <td>
                                            <div class="rank-badge <?= $badge_class ?>">
                                                <?php if ($rank <= 3): ?>
                                                    <?= ['🥇', '🥈', '🥉'][$rank - 1] ?>
                                                <?php else: ?>
                                                    <?= $rank ?>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <img src="<?= esc($photo) ?>" class="intern-mini-photo"
                                                alt="<?= esc($row['name']) ?>">
                                            <span style="font-weight:600; color:#fff;"><?= esc($row['name']) ?></span>
                                            <?php if ($is_me): ?>
                                                <span
                                                    style="background:rgba(0,243,255,0.1); color:#00f3ff; font-size:0.65rem; padding:2px 6px; border-radius:3px; margin-left:6px; font-weight:bold;">YOU</span>
                                            <?php endif; ?>
                                            <div
                                                style="font-family:'Share Tech Mono',monospace; font-size:0.7rem; color:#475569; margin-top:2px;">
                                                <?= esc($row['mapped_id']) ?></div>
                                        </td>
                                        <td
                                            style="color:#94a3b8; font-size:0.85rem; max-width:180px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                                            <?= esc($row['college']) ?></td>
                                        <td><span class="pts-pill mod"><?= number_format($row['module_points']) ?></span></td>
                                        <td><span class="pts-pill task"><?= number_format($row['task_points']) ?></span></td>
                                        <td><span class="pts-pill att"><?= number_format($row['attendance_points']) ?></span></td>
                                        <td><span class="pts-pill"
                                                style="background:rgba(168,85,247,0.1); color:#a855f7;"><?= number_format($row['challenge_points'] ?? 0) ?></span>
                                        </td>
                                        <td><span class="pts-pill"
                                                style="background:rgba(245,158,11,0.1); color:#f59e0b;"><?= number_format($row['ctf_points'] ?? 0) ?></span>
                                        </td>
                                        <td><span class="pts-pill"
                                                style="background:rgba(16,185,129,0.1); color:#10b981;"><?= number_format($row['referral_points'] ?? 0, 1) ?></span>
                                        </td>
                                        <td>
                                            <div class="total-pts"><?= number_format($row['total_points']) ?></div>
                                        </td>
                                    </tr>
                                <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </main>
</div>

<script>
    function filterLeaderboard() {
        const input = document.getElementById("leaderboardSearch");
        const filter = input.value.toLowerCase().trim();
        const rows = document.querySelectorAll(".lb-table tbody tr");

        rows.forEach(row => {
            // Skip empty table cell row if there is one
            if (row.cells.length < 2) return;

            // Name column is the second column (td[1])
            const nameCell = row.cells[1];
            if (nameCell) {
                const nameText = nameCell.textContent || nameCell.innerText;
                if (nameText.toLowerCase().indexOf(filter) > -1) {
                    row.style.display = "";
                } else {
                    row.style.display = "none";
                }
            }
        });
    }
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>