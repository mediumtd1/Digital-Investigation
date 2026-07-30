<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
check_auth('Admin');

$page_title = 'CTF Challenge Management — HQ Control';
$portal = 'admin';

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
    try { $pdo->exec("ALTER TABLE ctf_submissions ADD COLUMN partial_steps VARCHAR(255) DEFAULT NULL"); } catch(PDOException $e) {}
    try { $pdo->exec("ALTER TABLE ctf_submissions ADD COLUMN partial_notes TEXT DEFAULT NULL");  } catch(PDOException $e) {}
    try { $pdo->exec("ALTER TABLE ctf_submissions ADD COLUMN points_awarded INT DEFAULT 0");      } catch(PDOException $e) {}
} catch(PDOException $e) {}

$msg = '';
$msg_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'override_points') {
        $sub_id = (int)($_POST['sub_id'] ?? 0);
        $pts    = max(0, min(200, (int)($_POST['points'] ?? 0)));
        if ($sub_id > 0) {
            $pdo->prepare("UPDATE ctf_submissions SET points_awarded = ? WHERE id = ?")->execute([$pts, $sub_id]);
            $msg = "Points updated successfully.";
            $msg_type = 'success';
        }
    }
    if ($_POST['action'] === 'delete_submission') {
        $sub_id = (int)($_POST['sub_id'] ?? 0);
        if ($sub_id > 0) {
            $pdo->prepare("DELETE FROM ctf_submissions WHERE id = ?")->execute([$sub_id]);
            $msg = "Submission deleted.";
            $msg_type = 'warn';
        }
    }
}

$stmt = $pdo->query("
    SELECT cs.*, i.name as intern_name, i.intern_id as intern_code, i.batch, i.college
    FROM ctf_submissions cs
    JOIN interns i ON cs.intern_id = i.id
    ORDER BY cs.is_correct DESC, cs.points_awarded DESC, cs.submitted_at ASC
");
$submissions = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

$total   = count($submissions);
$solved  = count(array_filter($submissions, fn($s) => $s['is_correct']));
$partial = $total - $solved;
$total_pts_awarded = array_sum(array_column($submissions, 'points_awarded'));

include __DIR__ . '/../includes/header.php';
?>
<?php include __DIR__ . '/_sidebar.php'; ?>

<div class="main-content" style="padding: 30px; padding-top: 80px;">

    
    <div style="display:flex; align-items:center; gap:15px; margin-bottom:30px;">
        <div style="width:50px;height:50px;background:linear-gradient(135deg,#f59e0b,#b45309);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.4rem;box-shadow:0 0 20px rgba(245,158,11,0.4);">
            <i class="fa-solid fa-flag"></i>
        </div>
        <div>
            <h1 style="font-family:'Rajdhani',sans-serif;font-size:1.8rem;font-weight:800;background:linear-gradient(90deg,#f59e0b,#fbbf24);-webkit-background-clip:text;-webkit-text-fill-color:transparent;margin:0;letter-spacing:1px;">CTF CHALLENGE MANAGEMENT</h1>
            <p style="color:#64748b;font-size:0.85rem;margin:4px 0 0;">Monitor intern submissions, override points, and track flag captures</p>
        </div>
    </div>

    <?php if ($msg): ?>
    <div style="background:<?= $msg_type==='success'?'rgba(16,185,129,0.1)':'rgba(245,158,11,0.1)' ?>;border:1px solid <?= $msg_type==='success'?'#10b981':'#f59e0b' ?>;border-radius:8px;padding:12px 18px;color:<?= $msg_type==='success'?'#10b981':'#f59e0b' ?>;margin-bottom:20px;display:flex;align-items:center;gap:10px;">
        <i class="fa-solid <?= $msg_type==='success'?'fa-circle-check':'fa-triangle-exclamation' ?>"></i> <?= esc($msg) ?>
    </div>
    <?php endif; ?>

    
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:16px;margin-bottom:30px;">
        <?php
        $stats = [
            ['Total Submissions', $total,            'fa-inbox',       '#00f3ff', 'rgba(0,243,255,0.1)'],
            ['Flag Captured',     $solved,            'fa-flag',        '#10b981', 'rgba(16,185,129,0.1)'],
            ['Partial Progress',  $partial,           'fa-circle-half-stroke','#f59e0b','rgba(245,158,11,0.1)'],
            ['Total Pts Awarded', $total_pts_awarded, 'fa-star',        '#a78bfa', 'rgba(167,139,250,0.1)'],
        ];
        foreach ($stats as [$label, $val, $icon, $color, $bg]):
        ?>
        <div style="background:<?= $bg ?>;border:1px solid <?= $color ?>33;border-radius:12px;padding:18px;text-align:center;">
            <i class="fa-solid <?= $icon ?>" style="font-size:1.5rem;color:<?= $color ?>;margin-bottom:8px;display:block;"></i>
            <div style="font-size:1.8rem;font-weight:800;color:#fff;font-family:'Rajdhani',sans-serif;"><?= $val ?></div>
            <div style="font-size:0.75rem;color:#94a3b8;text-transform:uppercase;letter-spacing:1px;"><?= $label ?></div>
        </div>
        <?php endforeach; ?>
    </div>

    
    <div style="background:linear-gradient(135deg,rgba(245,158,11,0.08),rgba(180,83,9,0.08));border:1px solid rgba(245,158,11,0.3);border-radius:12px;padding:20px 24px;margin-bottom:28px;display:flex;align-items:center;gap:20px;">
        <div style="font-size:2.5rem;">🏴</div>
        <div>
            <div style="font-family:'Rajdhani',sans-serif;font-weight:800;font-size:1.1rem;color:#fbbf24;letter-spacing:1px;">CORRECT FLAG</div>
            <code style="font-family:'Share Tech Mono',monospace;color:#00f3ff;font-size:1rem;background:rgba(0,0,0,0.4);padding:4px 12px;border-radius:6px;border:1px solid rgba(0,243,255,0.3);">flag{intern_detective_master}</code>
            <div style="color:#64748b;font-size:0.8rem;margin-top:6px;">Full capture = <strong style="color:#10b981;">100 pts</strong> &nbsp;|&nbsp; Partial milestone = <strong style="color:#f59e0b;">15 pts/step (max 5 steps)</strong> &nbsp;|&nbsp; You can override any submission's points below.</div>
        </div>
    </div>

    
    <div class="glass-panel" style="overflow:hidden;">
        <div style="padding:18px 22px;border-bottom:1px solid rgba(255,255,255,0.06);display:flex;align-items:center;justify-content:space-between;">
            <div style="font-family:'Rajdhani',sans-serif;font-weight:700;color:#fff;font-size:1.05rem;letter-spacing:1px;"><i class="fa-solid fa-list-check" style="color:#f59e0b;margin-right:8px;"></i>ALL INTERN SUBMISSIONS</div>
            <?php if ($total === 0): ?>
            <span style="font-size:0.8rem;color:#64748b;">No submissions yet</span>
            <?php else: ?>
            <span style="font-size:0.8rem;color:#94a3b8;"><?= $total ?> submission<?= $total != 1 ? 's' : '' ?></span>
            <?php endif; ?>
        </div>

        <?php if (empty($submissions)): ?>
        <div style="padding:60px;text-align:center;color:#475569;">
            <i class="fa-solid fa-inbox" style="font-size:3rem;margin-bottom:16px;display:block;opacity:0.4;"></i>
            <p style="font-family:'Rajdhani',sans-serif;font-size:1.1rem;">No CTF submissions received yet.</p>
            <p style="font-size:0.85rem;">Interns can submit flags from their Module 7 (CTF Challenge) page.</p>
        </div>
        <?php else: ?>
        <div style="overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;font-size:0.85rem;">
                <thead>
                    <tr style="background:rgba(0,0,0,0.3);">
                        <th style="padding:12px 16px;text-align:left;color:#64748b;font-size:0.7rem;letter-spacing:1px;text-transform:uppercase;border-bottom:1px solid rgba(255,255,255,0.05);">#</th>
                        <th style="padding:12px 16px;text-align:left;color:#64748b;font-size:0.7rem;letter-spacing:1px;text-transform:uppercase;border-bottom:1px solid rgba(255,255,255,0.05);">Intern</th>
                        <th style="padding:12px 16px;text-align:left;color:#64748b;font-size:0.7rem;letter-spacing:1px;text-transform:uppercase;border-bottom:1px solid rgba(255,255,255,0.05);">Batch</th>
                        <th style="padding:12px 16px;text-align:left;color:#64748b;font-size:0.7rem;letter-spacing:1px;text-transform:uppercase;border-bottom:1px solid rgba(255,255,255,0.05);">Status</th>
                        <th style="padding:12px 16px;text-align:left;color:#64748b;font-size:0.7rem;letter-spacing:1px;text-transform:uppercase;border-bottom:1px solid rgba(255,255,255,0.05);">Flag / Milestones</th>
                        <th style="padding:12px 16px;text-align:left;color:#64748b;font-size:0.7rem;letter-spacing:1px;text-transform:uppercase;border-bottom:1px solid rgba(255,255,255,0.05);">Pts Awarded</th>
                        <th style="padding:12px 16px;text-align:left;color:#64748b;font-size:0.7rem;letter-spacing:1px;text-transform:uppercase;border-bottom:1px solid rgba(255,255,255,0.05);">Submitted At</th>
                        <th style="padding:12px 16px;text-align:center;color:#64748b;font-size:0.7rem;letter-spacing:1px;text-transform:uppercase;border-bottom:1px solid rgba(255,255,255,0.05);">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($submissions as $idx => $sub):
                    $is_correct = (bool)$sub['is_correct'];
                    $steps = $sub['partial_steps'] ? explode(',', $sub['partial_steps']) : [];
                    $border_color = $is_correct ? '#10b981' : '#f59e0b';
                    $row_bg = ($idx % 2 === 0) ? 'rgba(255,255,255,0.01)' : 'transparent';
                ?>
                <tr style="background:<?= $row_bg ?>;border-left:3px solid <?= $border_color ?>;">
                    <td style="padding:14px 16px;color:#64748b;"><?= $idx + 1 ?></td>
                    <td style="padding:14px 16px;">
                        <div style="font-weight:600;color:#e2e8f0;"><?= esc($sub['intern_name']) ?></div>
                        <div style="font-family:monospace;font-size:0.75rem;color:#64748b;"><?= esc($sub['intern_code']) ?></div>
                        <div style="font-size:0.72rem;color:#475569;"><?= esc($sub['college']) ?></div>
                    </td>
                    <td style="padding:14px 16px;">
                        <span style="background:rgba(0,243,255,0.1);color:#00f3ff;padding:3px 8px;border-radius:4px;font-size:0.75rem;font-weight:600;"><?= esc($sub['batch']) ?></span>
                    </td>
                    <td style="padding:14px 16px;">
                        <?php if ($is_correct): ?>
                        <span style="background:rgba(16,185,129,0.15);color:#10b981;border:1px solid rgba(16,185,129,0.4);padding:4px 10px;border-radius:6px;font-size:0.78rem;font-weight:700;display:inline-flex;align-items:center;gap:5px;">
                            <i class="fa-solid fa-flag"></i> FLAG CAPTURED
                        </span>
                        <?php else: ?>
                        <span style="background:rgba(245,158,11,0.15);color:#f59e0b;border:1px solid rgba(245,158,11,0.4);padding:4px 10px;border-radius:6px;font-size:0.78rem;font-weight:700;display:inline-flex;align-items:center;gap:5px;">
                            <i class="fa-solid fa-circle-half-stroke"></i> PARTIAL (<?= count($steps) ?>/5 steps)
                        </span>
                        <?php endif; ?>
                    </td>
                    <td style="padding:14px 16px;max-width:220px;">
                        <?php if ($is_correct && $sub['flag_submitted']): ?>
                        <code style="font-family:'Share Tech Mono',monospace;color:#10b981;font-size:0.78rem;background:rgba(16,185,129,0.08);padding:3px 8px;border-radius:4px;"><?= esc($sub['flag_submitted']) ?></code>
                        <?php elseif (!empty($steps)): ?>
                        <div style="display:flex;flex-wrap:wrap;gap:4px;">
                            <?php foreach ($steps as $step): ?>
                            <span style="background:rgba(245,158,11,0.1);color:#fbbf24;border:1px solid rgba(245,158,11,0.2);padding:2px 7px;border-radius:4px;font-size:0.72rem;"><?= esc(trim($step)) ?></span>
                            <?php endforeach; ?>
                        </div>
                        <?php if ($sub['partial_notes']): ?>
                        <div style="font-size:0.75rem;color:#64748b;margin-top:5px;font-style:italic;">"<?= esc(mb_substr($sub['partial_notes'], 0, 80)) ?><?= strlen($sub['partial_notes']) > 80 ? '…' : '' ?>"</div>
                        <?php endif; ?>
                        <?php else: ?>
                        <span style="color:#475569;font-style:italic;">—</span>
                        <?php endif; ?>
                    </td>
                    <td style="padding:14px 16px;">
                        
                        <form method="POST" style="display:flex;align-items:center;gap:6px;" onsubmit="return confirm('Update points for this submission?')">
                            <input type="hidden" name="action" value="override_points">
                            <input type="hidden" name="sub_id" value="<?= (int)$sub['id'] ?>">
                            <input type="number" name="points" value="<?= (int)$sub['points_awarded'] ?>" min="0" max="200"
                                   style="width:65px;background:rgba(0,0,0,0.4);border:1px solid rgba(0,243,255,0.2);border-radius:6px;color:#fff;padding:5px 8px;font-family:'Share Tech Mono',monospace;font-size:0.85rem;text-align:center;">
                            <button type="submit" style="background:linear-gradient(135deg,#00f3ff,#0284c7);border:none;border-radius:6px;color:#020617;padding:6px 10px;cursor:pointer;font-weight:700;font-size:0.75rem;white-space:nowrap;">
                                <i class="fa-solid fa-pen-to-square"></i> Save
                            </button>
                        </form>
                    </td>
                    <td style="padding:14px 16px;color:#64748b;font-size:0.78rem;white-space:nowrap;">
                        <?= date('d M Y', strtotime($sub['submitted_at'])) ?><br>
                        <span style="color:#475569;"><?= date('H:i', strtotime($sub['submitted_at'])) ?></span>
                    </td>
                    <td style="padding:14px 16px;text-align:center;">
                        <form method="POST" onsubmit="return confirm('Delete this submission? The intern will be able to re-submit.')">
                            <input type="hidden" name="action" value="delete_submission">
                            <input type="hidden" name="sub_id" value="<?= (int)$sub['id'] ?>">
                            <button type="submit" title="Delete Submission" style="background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.3);color:#f87171;border-radius:6px;padding:6px 10px;cursor:pointer;font-size:0.78rem;transition:0.2s;" onmouseenter="this.style.background='rgba(239,68,68,0.25)'" onmouseleave="this.style.background='rgba(239,68,68,0.1)'">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    
    <?php
    $submitted_ids = array_column($submissions, 'intern_id');
    $not_submitted_sql = "SELECT id, name, intern_id as intern_code, batch, college FROM interns WHERE 1=1";
    $not_submitted_params = [];
    if (!empty($submitted_ids)) {
        $placeholders = implode(',', array_fill(0, count($submitted_ids), '?'));
        $not_submitted_sql .= " AND id NOT IN ($placeholders)";
        $not_submitted_params = $submitted_ids;
    }
    $not_submitted_sql .= " ORDER BY name ASC";
    $stmt2 = $pdo->prepare($not_submitted_sql);
    $stmt2->execute($not_submitted_params);
    $not_submitted = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    ?>
    <?php if (!empty($not_submitted)): ?>
    <div class="glass-panel" style="margin-top:24px;overflow:hidden;">
        <div style="padding:16px 22px;border-bottom:1px solid rgba(255,255,255,0.06);display:flex;align-items:center;gap:10px;">
            <i class="fa-solid fa-user-clock" style="color:#64748b;"></i>
            <span style="font-family:'Rajdhani',sans-serif;font-weight:700;color:#94a3b8;font-size:1rem;letter-spacing:1px;">PENDING — INTERNS WHO HAVEN'T SUBMITTED YET (<?= count($not_submitted) ?>)</span>
        </div>
        <div style="padding:16px 22px;display:flex;flex-wrap:wrap;gap:10px;">
            <?php foreach ($not_submitted as $ns): ?>
            <div style="background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.06);border-radius:8px;padding:10px 14px;display:flex;align-items:center;gap:10px;">
                <div style="width:32px;height:32px;background:rgba(100,116,139,0.2);border-radius:50%;display:flex;align-items:center;justify-content:center;color:#64748b;font-size:0.85rem;">
                    <i class="fa-solid fa-user"></i>
                </div>
                <div>
                    <div style="font-weight:600;color:#cbd5e1;font-size:0.85rem;"><?= esc($ns['name']) ?></div>
                    <div style="font-size:0.72rem;color:#475569;"><?= esc($ns['intern_code']) ?> &bull; <?= esc($ns['batch']) ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

</div>
</body>
</html>
