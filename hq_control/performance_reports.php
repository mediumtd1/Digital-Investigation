<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
check_auth('Admin');

$portal = 'admin';
$page_title = 'Intern Performance Intelligence | HQ';

$filter_batch  = trim($_GET['batch'] ?? '');
$filter_search = trim($_GET['q'] ?? '');
$per_page      = 25;
$current_page  = max(1, (int)($_GET['page'] ?? 1));

// Build WHERE
$where = ["i.status = 'Active'"];
$params = [];
if ($filter_batch !== '') { $where[] = "i.batch = ?"; $params[] = $filter_batch; }
if ($filter_search !== '') { $where[] = "(i.name LIKE ? OR i.intern_id LIKE ?)"; $params[] = "%$filter_search%"; $params[] = "%$filter_search%"; }
$where_sql = 'WHERE ' . implode(' AND ', $where);

// Total count (isolated — must not fail)
try {
    $cnt = $pdo->prepare("SELECT COUNT(*) FROM interns i $where_sql");
    $cnt->execute($params);
    $total_intern_count = (int)$cnt->fetchColumn();
} catch(Exception $e) { $total_intern_count = 0; }

// Compute pages & offset NOW before any other query can corrupt them
$total_pages = max(1, (int)ceil($total_intern_count / $per_page));
if ($current_page > $total_pages) $current_page = $total_pages;
$offset = ($current_page - 1) * $per_page;


$stmt = $pdo->prepare("
    SELECT 
        i.id, i.name, i.intern_id, i.college, i.batch,
        (SELECT COUNT(*) FROM intern_attendance WHERE intern_id = i.id AND status = 'Present') as present_count,
        (SELECT COUNT(*) FROM intern_attendance WHERE intern_id = i.id) as total_attendance_logs,
        (SELECT COUNT(*) FROM module_tracking WHERE intern_id = i.id) as completed_modules
    FROM interns i
    $where_sql
    ORDER BY completed_modules DESC, name ASC
    LIMIT $per_page OFFSET $offset
");
$stmt->execute($params);
$interns = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get IDs of current page only — avoids loading all data
$page_intern_ids = array_column($interns, 'id');
$id_placeholders = $page_intern_ids ? implode(',', array_fill(0, count($page_intern_ids), '?')) : '0';

$total_modules = $pdo->query("SELECT COUNT(*) FROM internship_modules WHERE is_visible = 1")->fetchColumn();

$completed_modules_map = [];
if ($page_intern_ids) {
    $modules_stmt = $pdo->prepare("
        SELECT mt.intern_id, im.week, im.day, im.title, mt.opened_at as completed_at
        FROM module_tracking mt
        JOIN internship_modules im ON mt.module_id = im.id
        WHERE mt.intern_id IN ($id_placeholders)
        ORDER BY im.week ASC, im.day ASC
    ");
    $modules_stmt->execute($page_intern_ids);
    while ($row = $modules_stmt->fetch(PDO::FETCH_ASSOC)) {
        $completed_modules_map[$row['intern_id']][] = $row;
    }
}

$tasks_map = [];
if ($page_intern_ids) {
    $tasks_stmt = $pdo->prepare("
        SELECT ta.intern_id, t.title as task_title, ta.status, ta.score, ta.review_status, ta.review_comment, t.due_date
        FROM task_assignments ta
        JOIN tasks t ON ta.task_id = t.id
        WHERE ta.intern_id IN ($id_placeholders)
        ORDER BY t.created_at DESC
    ");
    $tasks_stmt->execute($page_intern_ids);
    while ($row = $tasks_stmt->fetch(PDO::FETCH_ASSOC)) {
        $tasks_map[$row['intern_id']][] = $row;
    }
}

$ctf_map = [];
if ($page_intern_ids) {
    $ctf_stmt = $pdo->prepare("
        SELECT cs.intern_id, cs.flag_submitted, cs.is_correct, cs.points_awarded, cs.partial_steps, cs.submitted_at, cs.partial_notes
        FROM ctf_submissions cs
        WHERE cs.intern_id IN ($id_placeholders)
    ");
    $ctf_stmt->execute($page_intern_ids);
    while ($row = $ctf_stmt->fetch(PDO::FETCH_ASSOC)) {
        $ctf_map[$row['intern_id']] = $row;
    }
}

$challenge_map = [];
try {
    if ($page_intern_ids) {
        $ch_stmt = $pdo->prepare("
            SELECT intern_id,
                   COUNT(*) as total_attempted,
                   SUM(is_correct) as total_correct,
                   COALESCE(SUM(points_earned), 0) as total_pts
            FROM intern_challenges
            WHERE intern_id IN ($id_placeholders)
            GROUP BY intern_id
        ");
        $ch_stmt->execute($page_intern_ids);
        while ($row = $ch_stmt->fetch(PDO::FETCH_ASSOC)) {
            $challenge_map[$row['intern_id']] = $row;
        }
    }
} catch(Exception $e) {}

// Batches for filter dropdown
$batches = $pdo->query("SELECT DISTINCT batch FROM interns WHERE batch IS NOT NULL AND batch != '' ORDER BY batch ASC")->fetchAll(PDO::FETCH_COLUMN);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-wrapper">
    <?php include_once '_sidebar.php'; ?>
    <main class="main-content">
        <header class="topbar">
            <h2><i class="fa-solid fa-chart-line"></i> Performance Intelligence</h2>
        </header>

        <div class="dashboard-content" style="padding: 30px;">
            
            <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 30px;">
                <div style="background: rgba(15, 23, 42, 0.6); border: 1px solid rgba(0, 243, 255, 0.2); border-radius: 12px; padding: 20px; text-align: center;">
                    <i class="fa-solid fa-users" style="font-size: 2rem; color: #00f3ff; margin-bottom: 10px;"></i>
                    <h3 style="margin: 0; font-size: 1.8rem; font-family: 'Rajdhani', sans-serif; color: #fff;"><?= $total_intern_count ?></h3>
                    <p style="margin: 5px 0 0; font-size: 0.8rem; color: #64748b; text-transform: uppercase;">Active Interns</p>
                </div>
                <div style="background: rgba(15, 23, 42, 0.6); border: 1px solid rgba(168, 85, 247, 0.2); border-radius: 12px; padding: 20px; text-align: center;">
                    <i class="fa-solid fa-book-open" style="font-size: 2rem; color: #a855f7; margin-bottom: 10px;"></i>
                    <h3 style="margin: 0; font-size: 1.8rem; font-family: 'Rajdhani', sans-serif; color: #fff;"><?= $total_modules ?></h3>
                    <p style="margin: 5px 0 0; font-size: 0.8rem; color: #64748b; text-transform: uppercase;">Total Modules</p>
                </div>
                <div style="background: rgba(15, 23, 42, 0.6); border: 1px solid rgba(16, 185, 129, 0.2); border-radius: 12px; padding: 20px; text-align: center;">
                    <i class="fa-solid fa-calendar-check" style="font-size: 2rem; color: #10b981; margin-bottom: 10px;"></i>
                    <h3 style="margin: 0; font-size: 1.8rem; font-family: 'Rajdhani', sans-serif; color: #fff;">
                        <?php
                        $avg_att = 0;
                        if (count($interns) > 0) {
                            $sum_att = 0;
                            foreach ($interns as $i) {
                                $sum_att += ($i['total_attendance_logs'] > 0) ? ($i['present_count'] / $i['total_attendance_logs']) * 100 : 0;
                            }
                            $avg_att = round($sum_att / count($interns));
                        }
                        echo $avg_att . '%';
                        ?>
                    </h3>
                    <p style="margin: 5px 0 0; font-size: 0.8rem; color: #64748b; text-transform: uppercase;">Average Attendance</p>
                </div>
                <div style="background: rgba(15, 23, 42, 0.6); border: 1px solid rgba(245, 158, 11, 0.2); border-radius: 12px; padding: 20px; text-align: center;">
                    <i class="fa-solid fa-flag" style="font-size: 2rem; color: #f59e0b; margin-bottom: 10px;"></i>
                    <h3 style="margin: 0; font-size: 1.8rem; font-family: 'Rajdhani', sans-serif; color: #fff;">
                        <?php
                        $solved_ctf = 0;
                        foreach ($ctf_map as $c) {
                            if (!empty($c['is_correct'])) $solved_ctf++;
                        }
                        echo $solved_ctf;
                        ?>
                    </h3>
                    <p style="margin: 5px 0 0; font-size: 0.8rem; color: #64748b; text-transform: uppercase;">CTF Solved Flags</p>
                </div>
                <div style="background: rgba(15, 23, 42, 0.6); border: 1px solid rgba(168, 85, 247, 0.2); border-radius: 12px; padding: 20px; text-align: center;">
                    <i class="fa-solid fa-gamepad" style="font-size: 2rem; color: #a855f7; margin-bottom: 10px;"></i>
                    <h3 style="margin: 0; font-size: 1.8rem; font-family: 'Rajdhani', sans-serif; color: #fff;">
                        <?php
                        $total_ch_correct = 0;
                        foreach ($challenge_map as $ch) {
                            $total_ch_correct += (int)$ch['total_correct'];
                        }
                        echo $total_ch_correct;
                        ?>
                    </h3>
                    <p style="margin: 5px 0 0; font-size: 0.8rem; color: #64748b; text-transform: uppercase;">Daily Quiz Solved</p>
                </div>
            </div>

            
            <div class="glass-panel" style="margin-bottom:20px;">
                <form method="GET" style="display:flex; flex-wrap:wrap; gap:10px; align-items:center;">
                    <input type="text" name="q" placeholder="Search intern name / ID..." value="<?= esc($filter_search) ?>"
                           style="flex:1; min-width:200px; background:rgba(0,0,0,0.3); color:#fff; border:1px solid #334155; border-radius:6px; padding:8px 12px; font-size:0.85rem;">
                    <select name="batch" style="background:#0f172a; color:#fff; border:1px solid #334155; border-radius:6px; padding:8px 12px; font-size:0.85rem;">
                        <option value="">All Batches</option>
                        <?php foreach ($batches as $b): ?>
                        <option value="<?= esc($b) ?>" <?= $filter_batch === $b ? 'selected' : '' ?>><?= esc($b) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn-primary" style="padding:8px 20px; font-size:0.85rem;"><i class="fa-solid fa-filter"></i> Filter</button>
                    <?php if ($filter_search || $filter_batch): ?>
                    <a href="performance_reports.php" style="color:#64748b; font-size:0.82rem; text-decoration:none;"><i class="fa-solid fa-rotate-left"></i> Reset</a>
                    <?php endif; ?>
                    <span style="margin-left:auto; font-size:0.78rem; color:#64748b;">
                        <?= $total_intern_count ?> interns &middot; Page <strong style="color:#94a3b8;"><?= $current_page ?></strong> of <strong style="color:#94a3b8;"><?= $total_pages ?></strong>
                    </span>
                </form>
            </div>

            <div class="glass-panel">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Intern Identity</th>
                            <th>College / Institution</th>
                            <th>Attendance</th>
                            <th>Curriculum Progress</th>
                            <th>Integrity / Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($interns as $i): 
                            $attendance_pct = ($i['total_attendance_logs'] > 0) ? round(($i['present_count'] / $i['total_attendance_logs']) * 100) : 0;
                            $progress_pct = ($total_modules > 0) ? round(($i['completed_modules'] / $total_modules) * 100) : 0;
                        ?>
                        <tr style="cursor: pointer;" onclick="toggleDetails(<?= $i['id'] ?>)">
                            <td>
                                <div style="font-weight:bold; color:#fff;"><?= esc($i['name']) ?></div>
                                <div style="font-size:0.75rem; color:#00f3ff; font-family:monospace;"><?= esc($i['intern_id']) ?></div>
                            </td>
                            <td style="color:#94a3b8; font-size:0.85rem;">
                                <?= esc($i['college']) ?>
                                <?php if (!empty($i['batch'])): ?>
                                    <div style="font-size: 0.72rem; color: #a855f7; margin-top: 3px; font-weight: 600;"><i class="fa-solid fa-layer-group"></i> <?= esc($i['batch']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="display:flex; align-items:center; gap:10px;">
                                    <div style="flex-grow:1; height:6px; background:rgba(255,255,255,0.05); border-radius:10px; width:80px;">
                                        <div style="width:<?= $attendance_pct ?>%; height:100%; background:<?= $attendance_pct > 75 ? '#10b981' : '#f59e0b' ?>; border-radius:10px;"></div>
                                    </div>
                                    <span style="font-size:0.75rem; font-family: monospace;"><?= $attendance_pct ?>%</span>
                                </div>
                            </td>
                            <td>
                                <div style="display:flex; align-items:center; gap:10px;">
                                    <div style="flex-grow:1; height:6px; background:rgba(255,255,255,0.05); border-radius:10px; width:80px;">
                                        <div style="width:<?= $progress_pct ?>%; height:100%; background:#a855f7; border-radius:10px;"></div>
                                    </div>
                                    <span style="font-size:0.75rem; font-family: monospace;"><?= $i['completed_modules'] ?>/<?= $total_modules ?></span>
                                </div>
                            </td>
                            <td>
                                <div style="display: flex; align-items: center; justify-content: space-between; width: 100%;">
                                    <span class="status-badge" style="background:rgba(16,185,129,0.1); color:#10b981; font-weight: 800; font-size: 0.7rem;">OPTIMAL</span>
                                    <button class="btn-primary btn-sm" style="background: rgba(0, 243, 255, 0.1); border: 1px solid rgba(0, 243, 255, 0.3); color: #00f3ff; margin-left: 10px; font-weight: 800;">
                                        <i class="fa-solid fa-magnifying-glass-chart"></i> INSPECT
                                    </button>
                                </div>
                            </td>
                        </tr>
                        
                        
                        <tr id="details-<?= $i['id'] ?>" class="details-row" style="display: none; background: rgba(15, 23, 42, 0.8);">
                            <td colspan="5" style="padding: 24px; border: 1px solid rgba(0, 243, 255, 0.15); border-radius: 8px;">
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
                                    
                                    
                                    <div>
                                        
                                        <div style="margin-bottom: 25px;">
                                            <h4 style="color:#00f3ff; font-family:'Rajdhani',sans-serif; font-size: 1rem; border-bottom: 1px solid rgba(255,255,255,0.08); padding-bottom: 8px; margin-top: 0; margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
                                                <i class="fa-solid fa-book-open"></i> COMPLETED CURRICULUM UNITS (<?= isset($completed_modules_map[$i['id']]) ? count($completed_modules_map[$i['id']]) : 0 ?>)
                                            </h4>
                                            <div style="max-height:160px; overflow-y:auto; background:rgba(0,0,0,0.3); padding:10px; border-radius:8px; border: 1px solid rgba(255,255,255,0.03);">
                                                <?php if (isset($completed_modules_map[$i['id']]) && !empty($completed_modules_map[$i['id']])): ?>
                                                    <div style="display:flex; flex-direction:column; gap:6px;">
                                                        <?php foreach ($completed_modules_map[$i['id']] as $cm): ?>
                                                            <div style="display:flex; justify-content:space-between; font-size:0.78rem; padding: 6px 10px; background:rgba(255,255,255,0.02); border-radius:4px; border-left: 2px solid #00f3ff;">
                                                                <span style="color:#e2e8f0;">W<?= $cm['week'] ?>D<?= $cm['day'] ?>: <?= esc($cm['title']) ?></span>
                                                                <span style="color:#64748b; font-family:monospace;"><?= date('d M H:i', strtotime($cm['completed_at'])) ?></span>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php else: ?>
                                                    <div style="color:#64748b; font-size:0.8rem; text-align:center; padding:20px;">No modules completed yet.</div>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        
                                        <div>
                                            <h4 style="color:#f59e0b; font-family:'Rajdhani',sans-serif; font-size: 1rem; border-bottom: 1px solid rgba(255,255,255,0.08); padding-bottom: 8px; margin-top: 0; margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
                                                <i class="fa-solid fa-flag"></i> CAPTURE THE FLAG (CTF) PROFILE
                                            </h4>
                                            <div style="background:rgba(0,0,0,0.3); padding:15px; border-radius:8px; border: 1px solid rgba(255,255,255,0.03); font-size: 0.82rem;">
                                                <?php if (isset($ctf_map[$i['id']])): 
                                                    $c = $ctf_map[$i['id']];
                                                    $steps = $c['partial_steps'] ? explode(',', $c['partial_steps']) : [];
                                                ?>
                                                    <div style="display:flex; justify-content:space-between; margin-bottom:8px; align-items: center;">
                                                        <span style="color:#94a3b8;">CTF Solved:</span>
                                                        <span style="font-weight:bold; color:<?= $c['is_correct'] ? '#10b981' : '#f59e0b' ?>;">
                                                            <?= $c['is_correct'] ? '🏁 FLAG CAPTURED' : '⏳ PARTIAL PROGRESS' ?>
                                                        </span>
                                                    </div>
                                                    <div style="display:flex; justify-content:space-between; margin-bottom:8px;">
                                                        <span style="color:#94a3b8;">Points Awarded:</span>
                                                        <span style="color:#fff; font-weight:bold; font-family:monospace;"><?= (int)$c['points_awarded'] ?> pts</span>
                                                    </div>
                                                    <?php if (!empty($steps)): ?>
                                                        <div style="margin-top: 8px;">
                                                            <div style="color:#94a3b8; margin-bottom:5px;">Steps Mastered:</div>
                                                            <div style="display:flex; flex-wrap:wrap; gap:4px;">
                                                                <?php foreach ($steps as $st): ?>
                                                                    <span style="background:rgba(245,158,11,0.1); color:#fbbf24; border:1px solid rgba(245,158,11,0.2); padding:2px 6px; border-radius:4px; font-size:0.7rem;"><?= esc(trim($st)) ?></span>
                                                                <?php endforeach; ?>
                                                            </div>
                                                        </div>
                                                    <?php endif; ?>
                                                    <?php if (!empty($c['partial_notes'])): ?>
                                                        <div style="margin-top: 8px; color: #64748b; font-style: italic; font-size: 0.76rem; max-height: 50px; overflow-y: auto;">
                                                            Notes: "<?= esc($c['partial_notes']) ?>"
                                                        </div>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <div style="color:#64748b; text-align:center; padding:10px;">No CTF activities or submissions recorded.</div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>

                                    
                                    <div>
                                        
                                        <div style="margin-bottom: 25px;">
                                            <h4 style="color:#a855f7; font-family:'Rajdhani',sans-serif; font-size: 1rem; border-bottom: 1px solid rgba(255,255,255,0.08); padding-bottom: 8px; margin-top: 0; margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
                                                <i class="fa-solid fa-list-check"></i> ASSIGNED TASKS & REVIEWS (<?= isset($tasks_map[$i['id']]) ? count($tasks_map[$i['id']]) : 0 ?>)
                                            </h4>
                                            <div style="max-height:160px; overflow-y:auto; background:rgba(0,0,0,0.3); padding:10px; border-radius:8px; border: 1px solid rgba(255,255,255,0.03);">
                                                <?php if (isset($tasks_map[$i['id']]) && !empty($tasks_map[$i['id']])): ?>
                                                    <div style="display:flex; flex-direction:column; gap:6px;">
                                                        <?php foreach ($tasks_map[$i['id']] as $t): 
                                                            $ts_color = ($t['status'] === 'Completed') ? '#10b981' : (($t['status'] === 'In Progress') ? '#3b82f6' : '#64748b');
                                                        ?>
                                                            <div style="display:flex; flex-direction:column; gap:4px; font-size:0.78rem; padding:8px 10px; background:rgba(255,255,255,0.02); border-radius:4px; border-left: 2px solid #a855f7;">
                                                                <div style="display:flex; justify-content:space-between; align-items:center;">
                                                                    <span style="font-weight:600; color:#e2e8f0;"><?= esc($t['task_title']) ?></span>
                                                                    <span style="color:<?= $ts_color ?>; font-weight:bold; font-size:0.72rem;"><?= esc($t['status']) ?></span>
                                                                </div>
                                                                <?php if ($t['status'] === 'Completed'): ?>
                                                                    <div style="display:flex; justify-content:space-between; font-size:0.72rem; color:#94a3b8; margin-top: 3px;">
                                                                        <span>Score: <strong style="color:#a855f7; font-family:monospace;"><?= (int)$t['score'] ?> pts</strong></span>
                                                                        <span>Review: <strong style="color:<?= $t['review_status'] === 'Approved' ? '#10b981' : '#ef4444' ?>;"><?= esc($t['review_status']) ?></strong></span>
                                                                    </div>
                                                                    <?php if (!empty($t['review_comment'])): ?>
                                                                        <div style="font-size:0.7rem; color:#64748b; font-style:italic; margin-top:2px;">HQ: "<?= esc($t['review_comment']) ?>"</div>
                                                                    <?php endif; ?>
                                                                <?php endif; ?>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php else: ?>
                                                    <div style="color:#64748b; font-size:0.8rem; text-align:center; padding:20px;">No task assignments found.</div>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        
                                        <div style="margin-bottom: 25px;">
                                            <h4 style="color:#10b981; font-family:'Rajdhani',sans-serif; font-size: 1rem; border-bottom: 1px solid rgba(255,255,255,0.08); padding-bottom: 8px; margin-top: 0; margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
                                                <i class="fa-solid fa-calendar-check"></i> ATTENDANCE AND DISCIPLINE REPORT
                                            </h4>
                                            <div style="background:rgba(0,0,0,0.3); padding:15px; border-radius:8px; border: 1px solid rgba(255,255,255,0.03); font-size: 0.82rem;">
                                                <div style="display:flex; justify-content:space-between; margin-bottom:10px;">
                                                    <span style="color:#94a3b8;">Present Sessions:</span>
                                                    <span style="color:#fff; font-weight:bold;"><?= $i['present_count'] ?> / <?= $i['total_attendance_logs'] ?> days</span>
                                                </div>
                                                <div style="display:flex; justify-content:space-between; margin-bottom:10px;">
                                                    <span style="color:#94a3b8;">Average Duty Attendance:</span>
                                                    <span style="color:#10b981; font-weight:bold; font-family:monospace; font-size: 0.9rem;"><?= $attendance_pct ?>%</span>
                                                </div>
                                                <div style="display:flex; justify-content:space-between; align-items:center;">
                                                    <span style="color:#94a3b8;">Attendance Integrity:</span>
                                                    <span style="background:rgba(16,185,129,0.15); color:#10b981; border: 1px solid rgba(16,185,129,0.4); padding:3px 10px; border-radius:4px; font-size:0.75rem; font-weight:bold;">
                                                        <?= $attendance_pct >= 90 ? 'EXCELLENT' : ($attendance_pct >= 75 ? 'SATISFACTORY' : 'REQUIRES REVIEW') ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>

                                        
                                        <div>
                                            <h4 style="color:#a855f7; font-family:'Rajdhani',sans-serif; font-size: 1rem; border-bottom: 1px solid rgba(255,255,255,0.08); padding-bottom: 8px; margin-top: 0; margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
                                                <i class="fa-solid fa-gamepad"></i> DAILY CHALLENGE PERFORMANCE
                                            </h4>
                                            <div style="background:rgba(0,0,0,0.3); padding:15px; border-radius:8px; border: 1px solid rgba(255,255,255,0.03); font-size: 0.82rem;">
                                            <?php
                                            $ch = isset($challenge_map[$i['id']]) ? $challenge_map[$i['id']] : null;
                                            $ch_attempted = $ch ? (int)$ch['total_attempted'] : 0;
                                            $ch_correct   = $ch ? (int)$ch['total_correct']   : 0;
                                            $ch_pts       = $ch ? (int)$ch['total_pts']       : 0;
                                            $ch_acc       = $ch_attempted > 0 ? round(($ch_correct / $ch_attempted) * 100) : 0;
                                            ?>
                                                <div style="display:flex; justify-content:space-between; margin-bottom:8px;">
                                                    <span style="color:#94a3b8;">Quizzes Attempted:</span>
                                                    <span style="color:#fff; font-weight:bold;"><?= $ch_attempted ?></span>
                                                </div>
                                                <div style="display:flex; justify-content:space-between; margin-bottom:8px;">
                                                    <span style="color:#94a3b8;">Correct Answers:</span>
                                                    <span style="color:#10b981; font-weight:bold;"><?= $ch_correct ?></span>
                                                </div>
                                                <div style="display:flex; justify-content:space-between; margin-bottom:8px;">
                                                    <span style="color:#94a3b8;">Points Earned:</span>
                                                    <span style="color:#a855f7; font-weight:bold; font-family:monospace;"><?= $ch_pts ?> pts</span>
                                                </div>
                                                <div style="display:flex; justify-content:space-between; align-items:center;">
                                                    <span style="color:#94a3b8;">Accuracy:</span>
                                                    <span style="background:rgba(168,85,247,0.15); color:#a855f7; border:1px solid rgba(168,85,247,0.4); padding:3px 10px; border-radius:4px; font-size:0.75rem; font-weight:bold;"><?= $ch_acc ?>%</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($interns)): ?>
                            <tr>
                                <td colspan="5" style="text-align: center; color: var(--text-muted); padding: 40px;">No active interns enrolled.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($total_pages > 1): ?>
            <?php $pg_base = array_filter(['q' => $filter_search, 'batch' => $filter_batch]); ?>
            <div style="display:flex; justify-content:center; align-items:center; gap:6px; margin-top:24px; flex-wrap:wrap;">
                <?php if ($current_page > 1): ?>
                <a href="?<?= http_build_query(array_merge($pg_base, ['page' => $current_page - 1])) ?>"
                   style="padding:7px 14px; border-radius:6px; background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.1); color:#94a3b8; text-decoration:none; font-size:0.82rem;">
                    <i class="fa-solid fa-chevron-left"></i> Prev
                </a>
                <?php endif; ?>
                <?php for ($pg = max(1,$current_page-2); $pg <= min($total_pages,$current_page+2); $pg++): ?>
                <a href="?<?= http_build_query(array_merge($pg_base, ['page' => $pg])) ?>"
                   style="padding:7px 12px; border-radius:6px; border:1px solid; text-decoration:none; font-size:0.82rem; font-weight:600;
                          <?= $pg===$current_page ? 'background:rgba(0,243,255,0.15);border-color:#00f3ff;color:#00f3ff;' : 'background:rgba(255,255,255,0.04);border-color:rgba(255,255,255,0.08);color:#64748b;' ?>">
                    <?= $pg ?>
                </a>
                <?php endfor; ?>
                <?php if ($current_page < $total_pages): ?>
                <a href="?<?= http_build_query(array_merge($pg_base, ['page' => $current_page + 1])) ?>"
                   style="padding:7px 14px; border-radius:6px; background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.1); color:#94a3b8; text-decoration:none; font-size:0.82rem;">
                    Next <i class="fa-solid fa-chevron-right"></i>
                </a>
                <?php endif; ?>
                <span style="font-size:0.75rem; color:#334155; margin-left:8px;">
                    Showing <?= ($offset+1) ?>–<?= min($offset+$per_page, $total_intern_count) ?> of <?= $total_intern_count ?>
                </span>
            </div>
            <?php endif; ?>

        </div>
    </main>
</div>

<script>
function toggleDetails(id) {
    const row = document.getElementById('details-' + id);
    if (!row) return;
    
    // Toggle displaying style
    if (row.style.display === 'none') {
        row.style.display = 'table-row';
        row.style.animation = 'fadeIn 0.3s ease-out';
    } else {
        row.style.display = 'none';
    }
}
</script>

<style>
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(4px); }
    to { opacity: 1; transform: translateY(0); }
}
.details-row {
    animation: fadeIn 0.3s ease-out;
}
.details-row td {
    border-bottom: 2px solid rgba(0, 243, 255, 0.2) !important;
}
.admin-table tbody tr:not(.details-row):hover {
    background: rgba(0, 243, 255, 0.05) !important;
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
