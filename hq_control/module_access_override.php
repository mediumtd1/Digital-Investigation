<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
check_auth('Admin');

$portal = 'admin';
$page_title = 'Module Access Override | ' . SITE_NAME;

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS module_access_overrides (
        id INT AUTO_INCREMENT PRIMARY KEY,
        intern_id INT NOT NULL,
        module_id INT NOT NULL,
        granted_by VARCHAR(100),
        granted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        expires_at DATETIME DEFAULT NULL,
        reason TEXT,
        UNIQUE KEY unique_override (intern_id, module_id)
    )");
} catch(Exception $e) {}

$msg = ''; $msg_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action    = $_POST['action']    ?? '';
    $intern_id = (int)($_POST['intern_id'] ?? 0);
    $module_id = (int)($_POST['module_id'] ?? 0);
    $reason    = trim($_POST['reason'] ?? 'HQ Manual Override');

    if ($action === 'grant' && $intern_id && $module_id) {
        try {
            $stmt = $pdo->prepare("INSERT INTO module_access_overrides (intern_id, module_id, granted_by, reason)
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE granted_by=VALUES(granted_by), reason=VALUES(reason), granted_at=NOW()");
            $stmt->execute([$intern_id, $module_id, $_SESSION['user_name'] ?? 'HQ Admin', $reason]);
            log_audit($pdo, $_SESSION['user_mapped_id'] ?? 'HQ', 'Admin', "Granted module override: Intern #{$intern_id}, Module #{$module_id}");
            $msg = 'Access granted successfully.'; $msg_type = 'success';
        } catch(Exception $e) { $msg = 'Error: ' . $e->getMessage(); $msg_type = 'error'; }
    } elseif ($action === 'bulk_grant') {
        $batch = trim($_POST['batch'] ?? '');
        $bulk_module_id = $_POST['module_id'] ?? '';
        
        if (!empty($batch) && !empty($bulk_module_id)) {
            try {
                $intern_stmt = $pdo->prepare("SELECT id FROM interns WHERE batch = ?");
                $intern_stmt->execute([$batch]);
                $batch_interns = $intern_stmt->fetchAll(PDO::FETCH_COLUMN);
                
                if (empty($batch_interns)) {
                    throw new Exception("No interns found in batch: " . esc($batch));
                }
                
                $modules_to_grant = [];
                if ($bulk_module_id === 'all') {
                    $modules_to_grant = $pdo->query("SELECT id FROM internship_modules")->fetchAll(PDO::FETCH_COLUMN);
                } else {
                    $modules_to_grant = [(int)$bulk_module_id];
                }
                
                if (empty($modules_to_grant)) {
                    throw new Exception("No modules found to override.");
                }
                
                $pdo->beginTransaction();
                $stmt = $pdo->prepare("INSERT INTO module_access_overrides (intern_id, module_id, granted_by, reason)
                    VALUES (?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE granted_by=VALUES(granted_by), reason=VALUES(reason), granted_at=NOW()");
                
                foreach ($batch_interns as $b_intern_id) {
                    foreach ($modules_to_grant as $b_mod_id) {
                        $stmt->execute([$b_intern_id, $b_mod_id, $_SESSION['user_name'] ?? 'HQ Admin', $reason]);
                    }
                }
                $pdo->commit();
                
                $module_count_str = ($bulk_module_id === 'all') ? "all modules" : "module #{$bulk_module_id}";
                log_audit($pdo, $_SESSION['user_mapped_id'] ?? 'HQ', 'Admin', "Granted bulk override for batch '{$batch}': {$module_count_str} for " . count($batch_interns) . " interns");
                $msg = 'Bulk access granted successfully for batch: ' . esc($batch); $msg_type = 'success';
            } catch(Exception $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $msg = 'Error: ' . $e->getMessage(); $msg_type = 'error';
            }
        } else {
            $msg = 'Error: Batch and Module are required.'; $msg_type = 'error';
        }
    } elseif ($action === 'revoke') {
        try {
            $stmt = $pdo->prepare("DELETE FROM module_access_overrides WHERE intern_id=? AND module_id=?");
            $stmt->execute([$intern_id, $module_id]);
            log_audit($pdo, $_SESSION['user_mapped_id'] ?? 'HQ', 'Admin', "Revoked module override: Intern #{$intern_id}, Module #{$module_id}");
            $msg = 'Access revoked.'; $msg_type = 'warn';
        } catch(Exception $e) { $msg = 'Error: ' . $e->getMessage(); $msg_type = 'error'; }
    } elseif ($action === 'revoke_all') {
        try {
            $stmt = $pdo->prepare("DELETE FROM module_access_overrides");
            $stmt->execute();
            log_audit($pdo, $_SESSION['user_mapped_id'] ?? 'HQ', 'Admin', "Revoked ALL module overrides");
            $msg = 'All overrides revoked successfully.'; $msg_type = 'warn';
        } catch(Exception $e) { $msg = 'Error: ' . $e->getMessage(); $msg_type = 'error'; }
    }
}

$interns = $pdo->query("SELECT id, name, intern_id as uid, college FROM interns ORDER BY name")->fetchAll();
$modules = $pdo->query("SELECT id, week, day, title, is_visible FROM internship_modules ORDER BY week, day")->fetchAll();
$batches = $pdo->query("SELECT DISTINCT batch FROM interns WHERE batch IS NOT NULL AND batch != '' ORDER BY batch")->fetchAll(PDO::FETCH_COLUMN);

$overrides = [];
try {
    $rows = $pdo->query("
        SELECT o.*, i.name as intern_name, i.intern_id as intern_uid, m.title as module_title, m.week, m.day
        FROM module_access_overrides o
        JOIN interns i ON o.intern_id = i.id
        JOIN internship_modules m ON o.module_id = m.id
        ORDER BY o.granted_at DESC
    ")->fetchAll();
    foreach ($rows as $r) {
        $overrides[] = $r;
    }
} catch(Exception $e) {}
?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Share+Tech+Mono&display=swap">

<style>
.ov-page { padding: 28px; }
.ov-hero { margin-bottom:30px; }
.ov-hero h2 { font-family:'Rajdhani',sans-serif; font-size:2rem; color:#fff; letter-spacing:2px; margin:0; }

.ov-form { display:grid; grid-template-columns:1fr 1fr 1fr 1fr auto; gap:14px; align-items:end; margin-bottom:30px; flex-wrap:wrap; }
.ov-form .form-group { margin:0; }
.ov-form select, .ov-form input[type=text] {
    width:100%; padding:11px 14px; background:rgba(255,255,255,0.04);
    border:1px solid rgba(255,255,255,0.08); border-radius:10px;
    color:#fff; font-size:0.85rem; box-sizing:border-box;
}
.ov-form select:focus, .ov-form input:focus { outline:none; border-color:rgba(0,243,255,0.4); }
.ov-form option { background:#0f172a; color:#fff; }

.btn-grant { background:linear-gradient(135deg,#059669,#10b981); border:none; color:#000; padding:12px 22px; border-radius:10px; cursor:pointer; font-family:'Rajdhani',sans-serif; font-weight:800; font-size:0.9rem; white-space:nowrap; }
.btn-grant:hover { transform:scale(1.04); }

.ovr-table { width:100%; border-collapse:collapse; }
.ovr-table th { padding:12px 16px; font-size:0.7rem; font-weight:800; letter-spacing:2px; text-transform:uppercase; color:#475569; border-bottom:1px solid rgba(255,255,255,0.05); text-align:left; }
.ovr-table td { padding:12px 16px; border-bottom:1px solid rgba(255,255,255,0.03); font-size:0.85rem; }
.ovr-table tr:hover td { background:rgba(255,255,255,0.01); }

.btn-revoke { background:rgba(239,68,68,0.1); border:1px solid rgba(239,68,68,0.3); color:#ef4444; padding:5px 12px; border-radius:6px; cursor:pointer; font-size:0.78rem; font-weight:700; font-family:'Rajdhani',sans-serif; }
.btn-revoke:hover { background:rgba(239,68,68,0.2); }

.empty-state { text-align:center; padding:60px; color:#475569; }
</style>

<div class="portal-container">
    <?php include '_sidebar.php'; ?>
    <main class="main-content">
        <header class="topbar">
            <div class="topbar-left">
                <h2 style="font-family:'Rajdhani',sans-serif; letter-spacing:2px;">
                    <i class="fa-solid fa-key" style="color:#f59e0b;"></i> MODULE ACCESS OVERRIDE
                </h2>
            </div>
            <div class="topbar-right">
                <span style="background:rgba(245,158,11,0.1); color:#f59e0b; padding:6px 15px; border-radius:50px; font-size:0.75rem; font-weight:bold; border:1px solid rgba(245,158,11,0.2);">
                    <i class="fa-solid fa-shield-halved"></i> HQ COMMAND ONLY
                </span>
            </div>
        </header>

        <div class="dashboard-content ov-page">
            <div class="ov-hero">
                <h2><span style="color:#f59e0b;">//</span> Manual Module Access Control</h2>
                <p style="color:#64748b; margin-top:6px;">Grant specific interns access to modules that are expired, locked, or date-restricted. This overrides the sliding-window schedule.</p>
            </div>

            <?php if ($msg): ?>
            <script>document.addEventListener('DOMContentLoaded', () => showToast('<?= esc($msg) ?>', '<?= $msg_type ?>'));</script>
            <?php endif; ?>

            
            <div class="glass-panel" style="margin-bottom:28px;">
                <h3 style="font-family:'Rajdhani',sans-serif; margin-bottom:20px; display:flex; align-items:center; gap:10px;">
                    <i class="fa-solid fa-plus-circle" style="color:#10b981;"></i> GRANT NEW ACCESS
                </h3>
                <form method="POST">
                    <input type="hidden" name="action" value="grant">
                    <div class="ov-form">
                        <div class="form-group">
                            <label class="form-label">Intern</label>
                            <select name="intern_id" required>
                                <option value="">-- Select Intern --</option>
                                <?php foreach($interns as $i): ?>
                                <option value="<?= $i['id'] ?>"><?= esc($i['name']) ?> (<?= esc($i['uid']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Module</label>
                            <select name="module_id" required>
                                <option value="">-- Select Module --</option>
                                <?php foreach($modules as $m): ?>
                                <option value="<?= $m['id'] ?>">Wk<?= $m['week'] ?>/D<?= $m['day'] ?> — <?= esc(substr($m['title'],0,40)) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Reason</label>
                            <input type="text" name="reason" placeholder="e.g. Medical leave, Technical issue..." value="HQ Manual Override">
                        </div>
                        <div></div>
                        <button type="submit" class="btn-grant">
                            <i class="fa-solid fa-unlock-keyhole"></i> GRANT ACCESS
                        </button>
                    </div>
                </form>
            </div>

            <div class="glass-panel" style="margin-bottom:28px;">
                <h3 style="font-family:'Rajdhani',sans-serif; margin-bottom:20px; display:flex; align-items:center; gap:10px;">
                    <i class="fa-solid fa-users" style="color:#00f3ff;"></i> BULK GRANT ACCESS BY BATCH
                </h3>
                <form method="POST" onsubmit="return confirm('Are you sure you want to bulk override access for all interns in this batch?');">
                    <input type="hidden" name="action" value="bulk_grant">
                    <div class="ov-form">
                        <div class="form-group">
                            <label class="form-label">Select Batch</label>
                            <select name="batch" required>
                                <option value="">-- Select Batch --</option>
                                <?php foreach($batches as $b): ?>
                                <option value="<?= esc($b) ?>"><?= esc($b) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Module</label>
                            <select name="module_id" required>
                                <option value="">-- Select Module --</option>
                                <option value="all" style="color:#00f3ff; font-weight:bold;">★ ALL MODULES ★</option>
                                <?php foreach($modules as $m): ?>
                                <option value="<?= $m['id'] ?>">Wk<?= $m['week'] ?>/D<?= $m['day'] ?> — <?= esc(substr($m['title'],0,40)) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Reason</label>
                            <input type="text" name="reason" placeholder="e.g. June Batch Access Override..." value="Bulk Batch Override">
                        </div>
                        <div></div>
                        <button type="submit" class="btn-grant" style="background:linear-gradient(135deg,#0284c7,#0ea5e9);">
                            <i class="fa-solid fa-unlock-keyhole"></i> BULK GRANT
                        </button>
                    </div>
                </form>
            </div>

            
            <div class="glass-panel" style="padding:0; overflow:hidden;">
                <div style="padding:20px 25px; border-bottom:1px solid rgba(255,255,255,0.05); display:flex; justify-content:space-between; align-items:center;">
                    <h3 style="margin:0; font-family:'Rajdhani',sans-serif; letter-spacing:1px;">
                        <i class="fa-solid fa-list-check" style="color:#00f3ff;"></i> ACTIVE OVERRIDES
                        <span style="font-family:'Share Tech Mono',monospace; font-size:0.9rem; color:#64748b; margin-left:10px;"><?= count($overrides) ?> records</span>
                    </h3>
                    <?php if (!empty($overrides)): ?>
                    <form method="POST" onsubmit="return confirm('WARNING: Are you sure you want to revoke ALL active overrides? This will reset all interns to their default schedule.');">
                        <input type="hidden" name="action" value="revoke_all">
                        <button type="submit" class="btn-revoke" style="padding: 8px 16px; font-size: 0.85rem;">
                            <i class="fa-solid fa-ban"></i> REVOKE ALL OVERRIDES
                        </button>
                    </form>
                    <?php endif; ?>
                </div>
                <?php if (empty($overrides)): ?>
                <div class="empty-state">
                    <i class="fa-solid fa-shield-halved" style="font-size:2.5rem; color:#334155; display:block; margin-bottom:12px;"></i>
                    No active overrides. All modules follow the standard schedule.
                </div>
                <?php else: ?>
                <table class="ovr-table">
                    <thead>
                        <tr>
                            <th>Intern</th>
                            <th>Module</th>
                            <th>Wk/Day</th>
                            <th>Reason</th>
                            <th>Granted By</th>
                            <th>Granted At</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach($overrides as $o): ?>
                    <tr>
                        <td>
                            <div style="font-weight:600; color:#fff;"><?= esc($o['intern_name']) ?></div>
                            <div style="font-family:'Share Tech Mono',monospace; font-size:0.7rem; color:#475569;"><?= esc($o['intern_uid']) ?></div>
                        </td>
                        <td style="max-width:200px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"><?= esc($o['module_title']) ?></td>
                        <td><span style="background:rgba(0,243,255,0.1); color:#00f3ff; padding:2px 8px; border-radius:4px; font-family:'Share Tech Mono',monospace; font-size:0.75rem;">W<?= $o['week'] ?>/D<?= $o['day'] ?></span></td>
                        <td style="color:#94a3b8; font-size:0.82rem;"><?= esc($o['reason']) ?></td>
                        <td style="color:#64748b; font-size:0.82rem;"><?= esc($o['granted_by']) ?></td>
                        <td style="color:#64748b; font-size:0.82rem;"><?= date('d M Y, H:i', strtotime($o['granted_at'])) ?></td>
                        <td>
                            <form method="POST" onsubmit="return confirm('Revoke this access override?');">
                                <input type="hidden" name="action"    value="revoke">
                                <input type="hidden" name="intern_id" value="<?= $o['intern_id'] ?>">
                                <input type="hidden" name="module_id" value="<?= $o['module_id'] ?>">
                                <button type="submit" class="btn-revoke"><i class="fa-solid fa-ban"></i> Revoke</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>

        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
