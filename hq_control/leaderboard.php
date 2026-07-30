<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
check_auth('Admin');

$portal = 'admin';
$page_title = 'Leaderboard Intelligence & Analytics | HQ';

// Get all unique batches for filtering
$batches_stmt = $pdo->query("SELECT DISTINCT batch FROM interns WHERE batch IS NOT NULL AND batch != '' ORDER BY batch DESC");
$all_batches = $batches_stmt->fetchAll(PDO::FETCH_COLUMN);

// Ensure we have current month and batch
$selected_month = $_GET['month'] ?? date('Y-m');

// Automatically derive batch name if not provided
$default_batch = date('F Y', strtotime($selected_month . '-01')) . ' Batch';
$selected_batch = $_GET['batch'] ?? $default_batch;

// If we have no batches in database yet, make sure selected batch is in list
if (empty($all_batches)) {
    $all_batches = [$default_batch];
}

$leaderboard = [];
$stats = [
    'total_points' => 0,
    'avg_points' => 0,
    'top_score' => 0,
    'top_performer' => 'N/A',
    'active_count' => 0
];

try {
    $excluded_names = ['Prateek Mishra', 'Atul Shrivastava', 'Unnati Saxena', 'Palak Rai'];
    $exclude_placeholders = implode(',', array_fill(0, count($excluded_names), '?'));

    $stmt = $pdo->prepare("
        SELECT i.id, i.name, i.college, i.profile_photo, i.intern_id as mapped_id, i.batch,
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

    // Sort by total points descending
    usort($results, function ($a, $b) {
        if ($b['total_points'] == $a['total_points']) {
            return strcmp($a['name'], $b['name']);
        }
        return $b['total_points'] - $a['total_points'];
    });

    $leaderboard = $results;

    // Calculate statistics
    $total_interns = count($leaderboard);
    if ($total_interns > 0) {
        $sum_points = 0;
        foreach ($leaderboard as $row) {
            $sum_points += $row['total_points'];
            if ($row['total_points'] > 0) {
                $stats['active_count']++;
            }
        }
        $stats['total_points'] = $sum_points;
        $stats['avg_points'] = round($sum_points / $total_interns, 1);
        $stats['top_score'] = $leaderboard[0]['total_points'];
        $stats['top_performer'] = $leaderboard[0]['name'];
    }

} catch (Exception $e) {
    error_log("Admin Leaderboard DB error: " . $e->getMessage());
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-wrapper">
    <?php include_once '_sidebar.php'; ?>

    <main class="main-content">
        <header class="topbar">
            <div class="topbar-left">
                <h2 style="font-family:'Rajdhani',sans-serif; letter-spacing:2px;">
                    <i class="fa-solid fa-trophy" style="color:#fbbf24;"></i> LEADERBOARD INTELLIGENCE
                </h2>
            </div>
        </header>

        <div class="dashboard-content" style="padding: 30px;">
            
            <!-- Filters Card -->
            <div class="glass-panel" style="margin-bottom: 30px; padding: 20px;">
                <form method="GET" class="d-flex gap-3 align-items-end flex-wrap">
                    <div>
                        <label class="form-label" style="font-weight: bold; color: #cbd5e1; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 1px;">Selected Batch</label>
                        <select name="batch" class="form-control" style="background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); color: #fff; padding: 8px 12px; border-radius: 8px; cursor: pointer; outline: none; min-width: 220px;" onchange="this.form.submit()">
                            <?php foreach ($all_batches as $b): ?>
                                <option value="<?= esc($b) ?>" <?= $selected_batch === $b ? 'selected' : '' ?>><?= esc($b) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="form-label" style="font-weight: bold; color: #cbd5e1; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 1px;">Selected Month</label>
                        <input type="month" name="month" value="<?= esc($selected_month) ?>" class="form-control" style="background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); color: #fff; padding: 7px 12px; border-radius: 8px; cursor: pointer; outline: none;" onchange="this.form.submit()">
                    </div>
                    <div style="margin-left: auto;">
                        <input type="text" id="adminSearchInput" placeholder="Search cadet name..." onkeyup="filterAdminLeaderboard()" style="background: rgba(0,243,255,0.05); border: 1px solid rgba(0,243,255,0.2); color: #fff; padding: 8px 15px; border-radius: 8px; font-size: 0.9rem; outline: none; width: 250px; transition: all 0.3s ease;">
                    </div>
                </form>
            </div>

            <!-- Stats Widgets -->
            <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">
                <div style="background: rgba(15, 23, 42, 0.6); border: 1px solid rgba(0, 243, 255, 0.2); border-radius: 12px; padding: 20px; text-align: center;">
                    <i class="fa-solid fa-users" style="font-size: 2rem; color: #00f3ff; margin-bottom: 10px;"></i>
                    <h3 style="margin: 0; font-size: 1.8rem; font-family: 'Rajdhani', sans-serif; color: #fff;"><?= count($leaderboard) ?></h3>
                    <p style="margin: 5px 0 0; font-size: 0.8rem; color: #64748b; text-transform: uppercase;">Total Cadets</p>
                </div>
                <div style="background: rgba(15, 23, 42, 0.6); border: 1px solid rgba(168, 85, 247, 0.2); border-radius: 12px; padding: 20px; text-align: center;">
                    <i class="fa-solid fa-star" style="font-size: 2rem; color: #a855f7; margin-bottom: 10px;"></i>
                    <h3 style="margin: 0; font-size: 1.8rem; font-family: 'Rajdhani', sans-serif; color: #fff;"><?= number_format($stats['total_points']) ?></h3>
                    <p style="margin: 5px 0 0; font-size: 0.8rem; color: #64748b; text-transform: uppercase;">Cumulative Points</p>
                </div>
                <div style="background: rgba(15, 23, 42, 0.6); border: 1px solid rgba(16, 185, 129, 0.2); border-radius: 12px; padding: 20px; text-align: center;">
                    <i class="fa-solid fa-calculator" style="font-size: 2rem; color: #10b981; margin-bottom: 10px;"></i>
                    <h3 style="margin: 0; font-size: 1.8rem; font-family: 'Rajdhani', sans-serif; color: #fff;"><?= $stats['avg_points'] ?></h3>
                    <p style="margin: 5px 0 0; font-size: 0.8rem; color: #64748b; text-transform: uppercase;">Average Score</p>
                </div>
                <div style="background: rgba(15, 23, 42, 0.6); border: 1px solid rgba(245, 158, 11, 0.2); border-radius: 12px; padding: 20px; text-align: center;">
                    <i class="fa-solid fa-medal" style="font-size: 2rem; color: #f59e0b; margin-bottom: 10px;"></i>
                    <h3 style="margin: 0; font-size: 1.2rem; font-family: 'Rajdhani', sans-serif; color: #fff; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;" title="<?= esc($stats['top_performer']) ?>">
                        <?= esc($stats['top_performer']) ?> (<?= $stats['top_score'] ?>)
                    </h3>
                    <p style="margin: 5px 0 0; font-size: 0.8rem; color: #64748b; text-transform: uppercase;">Top Cadet</p>
                </div>
            </div>

            <!-- Detailed Analysis Table -->
            <div class="glass-panel">
                <div style="padding: 20px 25px; border-bottom: 1px solid rgba(255,255,255,0.05); display: flex; align-items: center; justify-content: space-between;">
                    <h3 style="margin:0; font-family:'Rajdhani',sans-serif; color:#fff; font-size:1.2rem;"><i class="fa-solid fa-ranking-star" style="color:#00f3ff; margin-right: 8px;"></i> BATCH RANKINGS ANALYSIS</h3>
                    <span style="font-size:0.8rem; background: rgba(0,243,255,0.1); color: #00f3ff; padding: 4px 10px; border-radius: 20px; border: 1px solid rgba(0,243,255,0.2); font-weight: bold;"><?= esc($selected_batch) ?> • <?= date('F Y', strtotime($selected_month . '-01')) ?></span>
                </div>
                <table class="admin-table" id="leaderboardAdminTable">
                    <thead>
                        <tr>
                            <th style="width: 70px; text-align: center;">Rank</th>
                            <th>Cadet Detail</th>
                            <th style="text-align: center; color: #38bdf8;">Modules (10pt)</th>
                            <th style="text-align: center; color: #22d3ee;">Tasks</th>
                            <th style="text-align: center; color: #4ade80;">Attendance (5pt)</th>
                            <th style="text-align: center; color: #fbbf24;">CTF Pts</th>
                            <th style="text-align: center; color: #a78bfa;">Quizzes</th>
                            <th style="text-align: center; color: #ec4899;">Referrals</th>
                            <th style="text-align: center; font-weight: bold; color: #fff;">Total Score</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($leaderboard)): ?>
                            <tr>
                                <td colspan="9" style="text-align: center; padding: 40px; color: #64748b;">No cadets or activities registered for this batch/month yet.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($leaderboard as $idx => $row): 
                                $rank = $idx + 1;
                                $photo = !empty($row['profile_photo']) ? '../' . $row['profile_photo'] : "https://ui-avatars.com/api/?name=" . urlencode($row['name']) . "&background=0D8ABC&color=fff&size=50";
                                ?>
                                <tr class="leaderboard-row">
                                    <td style="text-align: center;">
                                        <?php if ($rank == 1): ?>
                                            <span style="font-size: 1.4rem;" title="1st Place">🥇</span>
                                        <?php elseif ($rank == 2): ?>
                                            <span style="font-size: 1.4rem;" title="2nd Place">🥈</span>
                                        <?php elseif ($rank == 3): ?>
                                            <span style="font-size: 1.4rem;" title="3rd Place">🥉</span>
                                        <?php else: ?>
                                            <span style="font-family: monospace; color:#94a3b8; font-weight: bold;">#<?= $rank ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 12px;">
                                            <img src="<?= esc($photo) ?>" alt="Cadet Pic" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 1px solid rgba(255,255,255,0.1);">
                                            <div>
                                                <a href="intern_detail.php?id=<?= $row['id'] ?>" class="cadet-name-link" style="color: #fff; font-weight: bold; text-decoration: none; font-size: 0.95rem; transition: color 0.2s;"><?= esc($row['name']) ?></a>
                                                <div style="font-size: 0.75rem; color: #64748b;"><?= esc($row['mapped_id']) ?> • <?= esc($row['college']) ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td style="text-align: center; font-family: monospace; font-size: 0.95rem;"><?= $row['module_points'] ?></td>
                                    <td style="text-align: center; font-family: monospace; font-size: 0.95rem;"><?= $row['task_points'] ?></td>
                                    <td style="text-align: center; font-family: monospace; font-size: 0.95rem;"><?= $row['attendance_points'] ?></td>
                                    <td style="text-align: center; font-family: monospace; font-size: 0.95rem;"><?= $row['ctf_points'] ?></td>
                                    <td style="text-align: center; font-family: monospace; font-size: 0.95rem;"><?= $row['challenge_points'] ?></td>
                                    <td style="text-align: center; font-family: monospace; font-size: 0.95rem;"><?= $row['referral_points'] ?></td>
                                    <td style="text-align: center; font-family: monospace; font-weight: bold; font-size: 1.05rem; color: #00f3ff;"><?= $row['total_points'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </main>
</div>

<style>
    .cadet-name-link:hover {
        color: #00f3ff !important;
    }
    .leaderboard-row:hover {
        background: rgba(255, 255, 255, 0.02) !important;
    }
    #adminSearchInput:focus {
        border-color: rgba(0, 243, 255, 0.8) !important;
        box-shadow: 0 0 8px rgba(0, 243, 255, 0.2);
    }
</style>

<script>
function filterAdminLeaderboard() {
    const input = document.getElementById("adminSearchInput");
    const filter = input.value.toUpperCase();
    const table = document.getElementById("leaderboardAdminTable");
    const rows = table.getElementsByClassName("leaderboard-row");

    for (let i = 0; i < rows.length; i++) {
        const nameLink = rows[i].getElementsByClassName("cadet-name-link")[0];
        if (nameLink) {
            const textValue = nameLink.textContent || nameLink.innerText;
            if (textValue.toUpperCase().indexOf(filter) > -1) {
                rows[i].style.display = "";
            } else {
                rows[i].style.display = "none";
            }
        }
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
