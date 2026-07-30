<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
check_auth('Officer');

$portal = 'officer';
$page_title = 'FIR Details | ' . SITE_NAME;
$officer_id = $_SESSION['user_id'];

$fir_id = (int)($_GET['id'] ?? 0);
if (!$fir_id) {
    header("Location: fir.php?tab=all");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_fir'])) {
    $new_status = $_POST['status'];
    $investigation_note = trim($_POST['investigation_note'] ?? '');
    
    try {
        $pdo->beginTransaction();

        
        $stmt = $pdo->prepare("UPDATE firs SET status = ? WHERE id = ?");
        $stmt->execute([$new_status, $fir_id]);
        
        
        if (!empty($investigation_note) || $new_status) {
            $diary_entry = "Status updated to: $new_status \n" . $investigation_note;
            $cd_stmt = $pdo->prepare("INSERT INTO case_diary_entries (fir_id, officer_id, entry_date, notes) VALUES (?, ?, NOW(), ?)");
            $cd_stmt->execute([$fir_id, $officer_id, $diary_entry]);
        }
        
        $pdo->commit();
        
        $_SESSION['toast_msg'] = "Case investigation updated successfully.";
        $_SESSION['toast_type'] = "success";
        header("Location: fir_detail.php?id=" . $fir_id);
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Failed to update: " . $e->getMessage();
    }
}

$stmt = $pdo->prepare("SELECT * FROM firs WHERE id = ?");
$stmt->execute([$fir_id]);
$fir = $stmt->fetch();

if (!$fir) {
    header("Location: fir.php?tab=all");
    exit;
}

$comp_meta = json_decode($fir['complainant_address'], true) ?? [];
$crime_meta = json_decode($fir['description'], true) ?? [];
$accused_meta = json_decode($fir['accused_details'], true) ?? [];

$desc_display = $crime_meta['detailed_desc'] ?? $fir['description'];
$address_display = $comp_meta['address'] ?? $fir['complainant_address'];

$stmt = $pdo->prepare("
    SELECT cd.*, o.name as officer_name, o.rank 
    FROM case_diary_entries cd 
    JOIN officers o ON cd.officer_id = o.id 
    WHERE cd.fir_id = ? 
    ORDER BY cd.entry_date DESC
");
$stmt->execute([$fir_id]);
$diary_entries = $stmt->fetchAll();

?>

<?php require_once __DIR__ . '/../includes/header.php'; ?>

<div class="dashboard-wrapper">
    <?php include_once __DIR__ . '/_sidebar.php'; ?>

    <main class="main-content">
        <header class="topbar">
            <div class="topbar-left">
                <h2><a href="fir.php?tab=all" style="color: var(--text-muted);"><i class="fa-solid fa-arrow-left"></i> FIRs</a> / Case Dossier</h2>
            </div>
            <div class="topbar-right">
                <a href="print_fir.php?id=<?= $fir_id ?>" target="_blank" class="btn-primary" style="background: #3b82f6;">
                    <i class="fa-solid fa-print"></i> Print FIR Copy
                </a>
            </div>
        </header>

        <div class="dashboard-content">

            <?php if (isset($error)): ?>
                <div style="background: rgba(220, 38, 38, 0.2); border-left: 4px solid var(--danger); padding: 15px; margin-bottom: 20px;">
                    <i class="fa-solid fa-triangle-exclamation" style="color: var(--danger);"></i> <?= esc($error) ?>
                </div>
            <?php endif; ?>

            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px; align-items: start;">
                
                
                <div>
                    
                    <div class="glass-panel" style="border-top: 4px solid #ef4444; margin-bottom: 20px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); padding-bottom: 10px; margin-bottom: 15px;">
                            <h2 style="margin:0; font-family: monospace; color: #f8fafc; font-size: 1.5rem;"><?= esc($fir['fir_number']) ?></h2>
                            <span class="badge <?= $fir['status'] == 'Closed' ? 'badge-success' : 'badge-warning' ?>" style="font-size: 1rem; padding: 6px 15px;"><?= esc($fir['status']) ?></span>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px;">
                            <div>
                                <strong style="color: var(--text-muted); font-size: 0.85rem; text-transform: uppercase;">Crime Category</strong>
                                <div style="color: #cbd5e1; font-size: 1.1rem;"><?= esc($fir['crime_type']) ?></div>
                            </div>
                            <div>
                                <strong style="color: var(--text-muted); font-size: 0.85rem; text-transform: uppercase;">Incident Timestamp</strong>
                                <div style="color: #cbd5e1; font-size: 1.1rem;"><?= date('d F Y, H:i', strtotime($fir['incident_date'])) ?></div>
                            </div>
                        </div>
                        
                        <strong style="color: var(--text-muted); font-size: 0.85rem; text-transform: uppercase;">Narrative Details</strong>
                        <div style="background: rgba(0,0,0,0.3); padding: 15px; border-radius: 4px; margin-top: 5px; color: #e2e8f0; line-height: 1.6;">
                            <?= nl2br(esc($desc_display)) ?>
                        </div>
                    </div>

                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                        <div class="glass-panel">
                            <h4 style="color: #60a5fa; border-bottom: 1px solid var(--border-color); padding-bottom: 10px; margin-bottom: 15px;"><i class="fa-solid fa-user"></i> Complainant</h4>
                            <table style="width: 100%; border-collapse: collapse; font-size: 0.9rem;">
                                <tr><th style="text-align:left; color: var(--text-muted); padding: 5px 0; width: 40%;">Name:</th><td style="font-weight: bold;"><?= esc($fir['complainant_name']) ?></td></tr>
                                <tr><th style="text-align:left; color: var(--text-muted); padding: 5px 0;">Mobile:</th><td><?= esc($fir['complainant_mobile']) ?></td></tr>
                                <tr><th style="text-align:left; color: var(--text-muted); padding: 5px 0;">Email:</th><td><?= esc($fir['complainant_email'] ?: 'N/A') ?></td></tr>
                                <tr><th style="text-align:left; color: var(--text-muted); padding: 5px 0;">Age/Gender:</th><td><?= esc($comp_meta['age'] ?? 'N/A') ?> / <?= esc($comp_meta['gender'] ?? 'N/A') ?></td></tr>
                                <tr><th style="text-align:left; color: var(--text-muted); padding: 5px 0; vertical-align: top;">Address:</th><td style="color: #cbd5e1;"><?= esc($address_display) ?>, <?= esc($comp_meta['district'] ?? '') ?></td></tr>
                            </table>
                        </div>
                        
                        <div class="glass-panel">
                            <h4 style="color: #f87171; border-bottom: 1px solid var(--border-color); padding-bottom: 10px; margin-bottom: 15px;"><i class="fa-solid fa-user-ninja"></i> Accused Data</h4>
                            <table style="width: 100%; border-collapse: collapse; font-size: 0.9rem;">
                                <tr><th style="text-align:left; color: var(--text-muted); padding: 5px 0; width: 40%;">Name/Alias:</th><td style="font-weight: bold;"><?= esc($accused_meta['name'] ?? 'Unknown') ?></td></tr>
                                <tr><th style="text-align:left; color: var(--text-muted); padding: 5px 0;">Mobile:</th><td><?= esc($accused_meta['mobile'] ?? 'Unknown') ?></td></tr>
                                <tr><th style="text-align:left; color: var(--text-muted); padding: 5px 0;">IP Address:</th><td style="font-family: monospace; color:#3b82f6;"><?= esc($accused_meta['ip'] ?? 'Unknown') ?></td></tr>
                                <tr><th style="text-align:left; color: var(--text-muted); padding: 5px 0;">Social URL:</th><td style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width: 150px;"><?= esc($accused_meta['social'] ?? 'Unknown') ?></td></tr>
                            </table>
                        </div>
                    </div>
                    
                    
                    <div class="glass-panel">
                        <h4 style="margin-bottom: 15px; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;"><i class="fa-solid fa-paperclip"></i> Evidentiary Artifacts</h4>
                        <?php
                        $ev_stmt = $pdo->prepare("SELECT * FROM evidence WHERE case_id = ?");
                        $ev_stmt->execute([$fir_id]);
                        $evidence_files = $ev_stmt->fetchAll();
                        
                        if(count($evidence_files) > 0): ?>
                            <div style="display: flex; flex-wrap: wrap; gap: 10px;">
                            <?php foreach($evidence_files as $ev): ?>
                                <a href="../<?= esc($ev['file_path']) ?>" download target="_blank" style="display: inline-flex; align-items:center; gap: 8px; background: rgba(59, 130, 246, 0.1); border: 1px solid #3b82f6; color: #93c5fd; padding: 8px 15px; border-radius: 4px; text-decoration: none; font-size: 0.85rem;">
                                    <i class="fa-solid fa-download"></i> <?= esc($ev['file_name']) ?>
                                </a>
                            <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p style="color: var(--text-muted); font-size: 0.9rem; font-style: italic;">No evidence files attached to this dossier.</p>
                        <?php endif; ?>
                    </div>
                </div>

                
                <div>
                    
                    <div class="glass-panel" style="margin-bottom: 20px; background: rgba(15, 23, 42, 0.8);">
                        <h3 style="margin-bottom: 15px;"><i class="fa-solid fa-bolt"></i> Investigation Action</h3>
                        <form method="POST">
                            <input type="hidden" name="update_fir" value="1">
                            <div class="form-group">
                                <label class="form-label">Current Status</label>
                                <select name="status" class="form-control" style="background: rgba(0,0,0,0.5);">
                                    <option value="Registered" <?= $fir['status'] == 'Registered' ? 'selected' : '' ?>>Registered</option>
                                    <option value="Under Investigation" <?= $fir['status'] == 'Under Investigation' ? 'selected' : '' ?>>Under Investigation</option>
                                    <option value="Chargesheet Filed" <?= $fir['status'] == 'Chargesheet Filed' ? 'selected' : '' ?>>Chargesheet Filed</option>
                                    <option value="Closed" <?= $fir['status'] == 'Closed' ? 'selected' : '' ?>>Closed</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Investigation Report / Internal Notes</label>
                                <textarea name="investigation_note" class="form-control" rows="4" placeholder="Log finding/action directly into case diary..." style="background: rgba(0,0,0,0.5);"></textarea>
                            </div>
                            <button type="submit" class="btn-primary" style="width: 100%; background: #10b981;">
                                <i class="fa-solid fa-plus"></i> Append to Diary
                            </button>
                        </form>
                    </div>

                    
                    <div class="glass-panel" style="max-height: 500px; overflow-y: auto;">
                        <h4 style="margin-bottom: 15px; position: sticky; top: -20px; background: inherit; padding-bottom: 10px; border-bottom: 1px solid var(--border-color);"><i class="fa-solid fa-book-journal-whills"></i> Case Diary Trace</h4>
                        
                        <div style="display: flex; flex-direction: column; gap: 15px;">
                            <?php foreach($diary_entries as $diary): ?>
                                <div style="background: rgba(255,255,255,0.02); padding: 12px; border-left: 3px solid #64748b; border-radius: 4px;">
                                    <div style="font-size: 0.8rem; color: var(--text-muted); display: flex; justify-content: space-between; margin-bottom: 8px;">
                                        <span><i class="fa-solid fa-clock"></i> <?= date('d M, Y h:i A', strtotime($diary['entry_date'])) ?></span>
                                        <span style="color: #93c5fd; font-weight: bold;"><?= esc($diary['officer_name']) ?></span>
                                    </div>
                                    <div style="font-size: 0.9rem; color: #e2e8f0; line-height: 1.4;">
                                        <?= nl2br(esc($diary['notes'])) ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                </div>

            </div>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
