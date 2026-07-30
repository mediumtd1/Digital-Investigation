<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
check_auth('Officer');

$portal = 'officer';
$page_title = 'Case Diary Workspace | ' . SITE_NAME;
$officer_id = $_SESSION['user_id'];

$fir_id = (int)($_GET['id'] ?? 0);
if (!$fir_id) {
    header("Location: case_diary.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM firs WHERE id = ? AND assigned_officer_id = ?");
$stmt->execute([$fir_id, $officer_id]);
$fir = $stmt->fetch();

if (!$fir) {
    die("Unauthorized Access or FIR Missing.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $new_status = $_POST['status'];
    $stmt = $pdo->prepare("UPDATE firs SET status = ? WHERE id = ?");
    $stmt->execute([$new_status, $fir_id]);
    
    
    $pdo->prepare("INSERT INTO case_diary_entries (fir_id, officer_id, entry_date, notes) VALUES (?, ?, NOW(), ?)")
        ->execute([$fir_id, $officer_id, "**CASE STATUS MUTATED TO**: " . $new_status]);
        
    $_SESSION['toast_msg'] = "Case Status Updated.";
    $_SESSION['toast_type'] = "success";
    header("Location: case_diary_detail.php?id=$fir_id");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_entry'])) {
    $entry_date = $_POST['entry_date'] . ' ' . date('H:i:s');
    $notes = trim($_POST['notes'] ?? '');
    $next_action = trim($_POST['next_action'] ?? '');
    
    
    $full_note = "INVESTIGATION LOG: \n" . $notes . "\n\n>> PLANNED ACTION: " . $next_action;

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("INSERT INTO case_diary_entries (fir_id, officer_id, entry_date, notes, next_action) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$fir_id, $officer_id, $entry_date, $full_note, $next_action]);
        $entry_id = $pdo->lastInsertId();

        
        if (isset($_FILES['evidence']) && !empty($_FILES['evidence']['name'][0])) {
            $upload_dir = __DIR__ . '/../uploads/evidence/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            
            foreach ($_FILES['evidence']['tmp_name'] as $key => $tmp_name) {
                if ($_FILES['evidence']['error'][$key] === UPLOAD_ERR_OK) {
                    $ext = strtolower(pathinfo($_FILES['evidence']['name'][$key], PATHINFO_EXTENSION));
                    $new_name = uniqid('evd_cd_', true) . '.' . $ext;
                    if (move_uploaded_file($tmp_name, $upload_dir . $new_name)) {
                        $ev_stmt = $pdo->prepare("INSERT INTO evidence (case_id, file_name, file_path, file_type, uploaded_by, custody_log) VALUES (?, ?, ?, ?, ?, ?)");
                        $ev_stmt->execute([
                            $fir_id,
                            $_FILES['evidence']['name'][$key],
                            'uploads/evidence/' . $new_name,
                            $ext,
                            $officer_id,
                            json_encode([['date' => date('Y-m-d H:i:s'), 'action' => 'Appended to diary entry #' . $entry_id, 'officer' => $officer_id]])
                        ]);
                        
                        
                        $update_cd = $pdo->prepare("UPDATE case_diary_entries SET notes = CONCAT(notes, '\n📎 ATTACHED: ', ?) WHERE id = ?");
                        $update_cd->execute([$_FILES['evidence']['name'][$key], $entry_id]);
                    }
                }
            }
        }

        $pdo->commit();
        $_SESSION['toast_msg'] = "Diary Event Logged Successfully.";
        $_SESSION['toast_type'] = "success";
        header("Location: case_diary_detail.php?id=$fir_id");
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Logging system failure: " . $e->getMessage();
    }
}

$stmt = $pdo->prepare("
    SELECT * FROM case_diary_entries 
    WHERE fir_id = ? 
    ORDER BY entry_date DESC
");
$stmt->execute([$fir_id]);
$timeline = $stmt->fetchAll();

$crime_meta = json_decode($fir['description'], true) ?? [];
$desc_display = $crime_meta['detailed_desc'] ?? $fir['description'];

?>

<?php require_once __DIR__ . '/../includes/header.php'; ?>

<style>
/* Vertical Timeline Overlay */
.timeline { position: relative; padding-left: 30px; margin-top: 20px; }
.timeline::before {
    content: '';
    position: absolute; left: 10px; top: 0; bottom: 0;
    width: 2px;
    background: rgba(59, 130, 246, 0.3);
}
.timeline-item {
    position: relative; margin-bottom: 25px;
    background: rgba(15, 23, 42, 0.6);
    border: 1px solid var(--border-color);
    padding: 20px; border-radius: 6px;
}
.timeline-item::before {
    content: '';
    position: absolute; left: -26px; top: 20px;
    width: 10px; height: 10px;
    background: #3b82f6; border-radius: 50%;
    box-shadow: 0 0 0 4px rgba(15, 23, 42, 1);
}
</style>

<div class="dashboard-wrapper">
    <?php include_once __DIR__ . '/_sidebar.php'; ?>

    <main class="main-content">
        <header class="topbar">
            <div class="topbar-left">
                <h2><a href="case_diary.php" style="color: var(--text-muted);"><i class="fa-solid fa-arrow-left"></i> Dockets</a> / Diary Terminal</h2>
            </div>
            <div class="topbar-right">
                <a href="print_case_diary.php?id=<?= $fir_id ?>" target="_blank" class="btn-primary" style="background:#eab308; color:black; font-weight:bold;">
                    <i class="fa-solid fa-file-pdf"></i> Print Internal Diary
                </a>
            </div>
        </header>

        <div class="dashboard-content">

            <?php if (isset($error)): ?>
                <div style="background: rgba(220, 38, 38, 0.2); border-left: 4px solid var(--danger); padding: 15px; margin-bottom: 20px;">
                    <i class="fa-solid fa-triangle-exclamation" style="color: var(--danger);"></i> <?= esc($error) ?>
                </div>
            <?php endif; ?>

            
            <div class="glass-panel" style="margin-bottom: 30px; border-top: 4px solid #f59e0b;">
                <div style="display: flex; justify-content: space-between; align-items: start;">
                    <div>
                        <h2 style="margin: 0 0 5px 0; color: #f8fafc; font-family: monospace; font-size: 1.8rem;"><?= esc($fir['fir_number']) ?></h2>
                        <div style="color: var(--text-muted); font-size: 0.95rem; margin-bottom: 15px;">
                            <strong>Complainant:</strong> <?= esc($fir['complainant_name']) ?> | <strong>Crime Vector:</strong> <?= esc($fir['crime_type']) ?>
                        </div>
                        <div style="background: rgba(0,0,0,0.2); padding: 10px; border-radius: 4px; border-left: 3px solid #64748b; font-size: 0.9rem; color: #cbd5e1; max-width: 900px;">
                            <?= nl2br(esc($desc_display)) ?>
                        </div>
                    </div>
                    
                    <div style="min-width: 250px;">
                        <form method="POST" id="statusForm" style="background: rgba(255,255,255,0.03); padding: 15px; border-radius: 4px; border: 1px solid var(--border-color);">
                            <input type="hidden" name="update_status" value="1">
                            <label style="color: var(--text-muted); font-size: 0.85rem; font-weight:bold; text-transform:uppercase; margin-bottom: 5px; display:block;">File Status Check</label>
                            <select name="status" class="form-control" style="margin-bottom: 10px;">
                                <option value="Registered" <?= $fir['status'] == 'Registered' ? 'selected' : '' ?>>Registered</option>
                                <option value="Under Investigation" <?= $fir['status'] == 'Under Investigation' ? 'selected' : '' ?>>Under Investigation</option>
                                <option value="Chargesheet Filed" <?= $fir['status'] == 'Chargesheet Filed' ? 'selected' : '' ?>>Chargesheet Filed</option>
                                <option value="Closed" <?= $fir['status'] == 'Closed' ? 'selected' : '' ?>>Closed</option>
                            </select>
                            <button type="button" class="btn-primary" onclick="confirmStatus()" style="width: 100%; background: #3b82f6;">Apply Transformation</button>
                        </form>
                    </div>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px; align-items: start;">
                
                
                <div>
                    <h3 style="border-bottom: 2px solid rgba(59, 130, 246, 0.5); padding-bottom: 10px;"><i class="fa-solid fa-clock-rotate-left"></i> Chronological Tracking</h3>
                    
                    <div class="timeline">
                        <?php foreach($timeline as $tm): ?>
                        <div class="timeline-item">
                            <div style="display: flex; justify-content: space-between; border-bottom: 1px dotted rgba(255,255,255,0.1); padding-bottom: 10px; margin-bottom: 10px;">
                                <span style="font-weight: bold; color: #93c5fd;"><i class="fa-regular fa-calendar-check"></i> <?= date('l, d M Y', strtotime($tm['entry_date'])) ?></span>
                                <span style="font-family: monospace; color: var(--text-muted);"><i class="fa-regular fa-clock"></i> <?= date('H:i:s', strtotime($tm['entry_date'])) ?></span>
                            </div>
                            <div style="color: #e2e8f0; line-height: 1.6; font-size: 0.95rem;">
                                <?= nl2br(esc($tm['notes'])) ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        
                        <?php if(empty($timeline)): ?>
                            <div style="color: var(--text-muted); font-style: italic; margin-top: 20px;">No diary segments logged internally.</div>
                        <?php endif; ?>
                    </div>
                </div>

                
                <div class="glass-panel" style="position: sticky; top: 90px; border-top: 4px solid #10b981;">
                    <h3 style="margin-bottom: 20px; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;"><i class="fa-solid fa-pen-fancy"></i> Record Action</h3>
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="add_entry" value="1">
                        
                        <div class="form-group">
                            <label class="form-label">Timestamp Registration</label>
                            <input type="date" name="entry_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Internal Analysis / Notes *</label>
                            <textarea name="notes" class="form-control" rows="6" placeholder="Log site discoveries, interrogate notes, IP trace results..." required minlength="20"></textarea>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Next Tactical Procedure</label>
                            <input type="text" name="next_action" class="form-control" placeholder="Issuing warrant, reviewing proxy logs...">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Chain Reference Evidentiary Uploads</label>
                            <input type="file" name="evidence[]" class="form-control" multiple accept=".pdf,.doc,.docx,.jpg,.png,.txt,.csv" style="background: rgba(0,0,0,0.3);">
                            <small style="color:var(--text-muted); font-size: 0.75rem;">(Attaches permanently to this exact diary block timestamp)</small>
                        </div>

                        <button type="submit" class="btn-primary" style="width: 100%; background: #10b981; margin-top: 10px;">
                            <i class="fa-solid fa-server"></i> Append Blockchain Memory
                        </button>
                    </form>
                </div>

            </div>
        </div>
    </main>
</div>

<script>
function confirmStatus() {
    if (confirm("Execute official case docket status shift? This action logs universally.")) {
        document.getElementById('statusForm').submit();
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
