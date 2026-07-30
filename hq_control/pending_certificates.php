<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

check_auth('Admin');

$portal = 'admin';
$page_title = 'Security Endorsements Console | ' . SITE_NAME;

function auto_generate_certificates($pdo) {
    
    $stmt = $pdo->query("
        SELECT i.*, 
               (SELECT COUNT(*) FROM intern_attendance WHERE intern_id = i.id AND status = 'Present') as attendance_present_count,
               (SELECT COUNT(*) FROM module_tracking WHERE intern_id = i.id) as modules_completed,
               (SELECT COUNT(*) FROM task_assignments WHERE intern_id = i.id AND status = 'Completed' AND review_status = 'Approved') as tasks_approved
        FROM interns i
        WHERE i.status = 'Active' 
          AND i.batch IS NOT NULL 
          AND i.batch != ''
          AND i.id NOT IN (SELECT DISTINCT intern_id FROM certificates)
    ");
    $eligible_interns = $stmt->fetchAll();
    
    $generated_count = 0;
    foreach ($eligible_interns as $intern) {
        $batch = trim($intern['batch']);
        
        $is_complete = false;
        if (preg_match('/([a-zA-Z]+)\s+(\d{4})/i', $batch, $matches)) {
            $month_name = $matches[1];
            $year = (int)$matches[2];
            
            $batch_month_time = strtotime("$month_name $year");
            if ($batch_month_time) {
                $current_year = (int)date('Y');
                $current_month = (int)date('n');
                
                $batch_year = (int)date('Y', $batch_month_time);
                $batch_month = (int)date('n', $batch_month_time);
                
                if ($current_year > $batch_year) {
                    $is_complete = true;
                } elseif ($current_year === $batch_year) {
                    if ($current_month > $batch_month) {
                        $is_complete = true;
                    } elseif ($current_month === $batch_month) {
                        
                        if ((int)date('j') >= 25) {
                            $is_complete = true;
                        }
                    }
                }
            }
        }
        
        if ($is_complete) {
            $has_activity = ($intern['attendance_present_count'] > 0 || $intern['modules_completed'] > 0 || $intern['tasks_approved'] > 0);
            if ($has_activity) {
                try {
                    $hash = bin2hex(random_bytes(32));
                    $qr_token = bin2hex(random_bytes(32));
                    $title = "Internship Completion Certificate";
                    $description = "Successful completion of the Cyber Academy Training Programme for batch " . $batch;
                    
                    $ins = $pdo->prepare("
                        INSERT INTO certificates 
                        (intern_id, certificate_hash, qr_token, approval_status, title, description, is_automatic) 
                        VALUES (?, ?, ?, 'pending', ?, ?, 1)
                    ");
                    $ins->execute([$intern['id'], $hash, $qr_token, $title, $description]);
                    $generated_count++;
                } catch (Exception $e) {
                    
                }
            }
        }
    }
    return $generated_count;
}

auto_generate_certificates($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $action = $_POST['action'];
    
    try {
        if ($action === 'approve_batch') {
            $batch_to_approve = trim($_POST['batch'] ?? '');
            if (empty($batch_to_approve)) {
                echo json_encode(['success' => false, 'message' => 'Batch name is required']);
                exit;
            }
            
            $custom_date = !empty($_POST['custom_date']) ? $_POST['custom_date'] : date('Y-m-d');
            $admin_uid = $_SESSION['user_id'] ?? 0;
            
            $stmt = $pdo->prepare("UPDATE certificates c 
                JOIN interns i ON c.intern_id = i.id
                SET c.approval_status = 'approved', 
                    c.issue_date_custom = ?, 
                    c.approved_by = ?, 
                    c.approved_at = NOW(), 
                    c.is_enabled = 1,
                    c.rejection_reason = NULL 
                WHERE c.approval_status = 'pending' AND i.batch = ?");
            $stmt->execute([$custom_date, $admin_uid, $batch_to_approve]);
            
            echo json_encode(['success' => true, 'message' => 'All pending certificates in ' . $batch_to_approve . ' approved successfully!']);
            exit;
        }

        $cert_id = isset($_POST['cert_id']) ? (int)$_POST['cert_id'] : 0;
        if ($cert_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid certificate ID']);
            exit;
        }

        if ($action === 'approve') {
            $custom_date = !empty($_POST['custom_date']) ? $_POST['custom_date'] : date('Y-m-d');
            $admin_uid = $_SESSION['user_id'] ?? 0;
            
            $stmt = $pdo->prepare("UPDATE certificates SET 
                approval_status = 'approved', 
                issue_date_custom = ?, 
                approved_by = ?, 
                approved_at = NOW(), 
                is_enabled = 1,
                rejection_reason = NULL 
                WHERE id = ?");
            $stmt->execute([$custom_date, $admin_uid, $cert_id]);
            
            echo json_encode(['success' => true, 'message' => 'Certificate approved successfully!']);
            exit;
            
        } elseif ($action === 'reject') {
            $reason = trim($_POST['reason'] ?? '');
            if (empty($reason)) {
                echo json_encode(['success' => false, 'message' => 'Rejection reason is required']);
                exit;
            }
            
            $stmt = $pdo->prepare("UPDATE certificates SET 
                approval_status = 'rejected', 
                rejection_reason = ?, 
                is_enabled = 0 
                WHERE id = ?");
            $stmt->execute([$reason, $cert_id]);
            
            echo json_encode(['success' => true, 'message' => 'Request successfully rejected.']);
            exit;
            
        } elseif ($action === 'toggle') {
            
            $stmt = $pdo->prepare("SELECT is_enabled FROM certificates WHERE id = ?");
            $stmt->execute([$cert_id]);
            $current = $stmt->fetchColumn();
            
            if ($current === false) {
                echo json_encode(['success' => false, 'message' => 'Certificate record not found']);
                exit;
            }
            
            $new_status = $current ? 0 : 1;
            $upd = $pdo->prepare("UPDATE certificates SET is_enabled = ? WHERE id = ?");
            $upd->execute([$new_status, $cert_id]);
            
            echo json_encode([
                'success' => true, 
                'new_status' => $new_status, 
                'message' => 'Certificate status updated!'
            ]);
            exit;
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        exit;
    }
}

$pending_stmt = $pdo->query("
    SELECT c.*, i.name as intern_name, i.intern_id as intern_display_id, i.batch as intern_batch,
    (SELECT COUNT(*) FROM module_tracking WHERE intern_id = i.id) as modules_completed,
    (SELECT COUNT(*) FROM internship_modules WHERE is_visible = 1) as total_modules
    FROM certificates c
    JOIN interns i ON c.intern_id = i.id
    WHERE c.approval_status = 'pending'
    ORDER BY c.issued_at ASC
");
$pending_records = $pending_stmt->fetchAll();

$pending_by_batch = [];
foreach ($pending_records as $cert) {
    $b = trim($cert['intern_batch'] ?? 'Uncategorized');
    if (empty($b)) { $b = 'Uncategorized'; }
    $pending_by_batch[$b][] = $cert;
}
ksort($pending_by_batch);

$approved_stmt = $pdo->query("
    SELECT c.*, i.name as intern_name, i.intern_id as intern_display_id
    FROM certificates c
    JOIN interns i ON c.intern_id = i.id
    WHERE c.approval_status = 'approved'
    ORDER BY c.approved_at DESC
");
$approved_records = $approved_stmt->fetchAll();

$rejected_stmt = $pdo->query("
    SELECT c.*, i.name as intern_name, i.intern_id as intern_display_id
    FROM certificates c
    JOIN interns i ON c.intern_id = i.id
    WHERE c.approval_status = 'rejected'
    ORDER BY c.issued_at DESC
");
$rejected_records = $rejected_stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<style>
/* Administrative Dashboard Styles (Futuristic Cyber) */
.admin-page {
    padding: 30px;
    font-family: 'Rajdhani', sans-serif;
}
.cyber-section-heading {
    font-family: 'Rajdhani', sans-serif;
    color: #fff;
    text-shadow: 0 0 10px rgba(0, 243, 255, 0.4);
    letter-spacing: 2px;
    font-size: 1.8rem;
    margin-bottom: 25px;
    text-transform: uppercase;
}
.console-grid {
    display: flex;
    flex-direction: column;
    gap: 30px;
}
.console-tabs {
    display: flex;
    gap: 10px;
    border-bottom: 1px solid rgba(0, 243, 255, 0.15);
    padding-bottom: 15px;
}
.console-tab {
    background: rgba(15, 23, 42, 0.5);
    border: 1px solid rgba(255, 255, 255, 0.08);
    color: #94a3b8;
    padding: 10px 25px;
    border-radius: 8px;
    font-family: 'Share Tech Mono', monospace;
    font-size: 0.85rem;
    cursor: pointer;
    transition: all 0.3s;
    text-transform: uppercase;
    letter-spacing: 1px;
}
.console-tab:hover {
    border-color: rgba(0, 243, 255, 0.3);
    color: #fff;
}
.console-tab.active {
    background: rgba(0, 243, 255, 0.08);
    border-color: #00f3ff;
    color: #00f3ff;
    box-shadow: 0 0 15px rgba(0, 243, 255, 0.1);
}
.console-content-pane {
    display: none;
    animation: fadeIn 0.4s ease-out forwards;
}
.console-content-pane.active {
    display: block;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.cyber-table-panel {
    background: rgba(15, 23, 42, 0.4);
    border: 1px solid rgba(0, 243, 255, 0.1);
    border-radius: 16px;
    padding: 20px;
    backdrop-filter: blur(10px);
}
.cyber-table {
    width: 100%;
    border-collapse: collapse;
    font-family: 'Rajdhani', sans-serif;
}
.cyber-table th {
    text-align: left;
    color: #00f3ff;
    border-bottom: 1px solid rgba(0, 243, 255, 0.2);
    padding: 15px 10px;
    font-size: 0.95rem;
    letter-spacing: 1px;
    text-transform: uppercase;
    font-family: 'Share Tech Mono', monospace;
}
.cyber-table td {
    padding: 15px 10px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.04);
    color: #fff;
    font-size: 0.95rem;
}
.cyber-table tr {
    transition: all 0.3s;
}
.cyber-table tr:hover {
    background: rgba(255, 255, 255, 0.02);
}

/* Modals */
.cyber-modal {
    position: fixed;
    inset: 0;
    z-index: 1000;
    display: none;
    align-items: center;
    justify-content: center;
    background: rgba(2, 6, 23, 0.85);
    backdrop-filter: blur(10px);
    animation: modalFadeIn 0.3s forwards;
}
.cyber-modal-container {
    background: rgba(15, 23, 42, 0.95);
    border: 1px solid #00f3ff;
    border-radius: 20px;
    width: 450px;
    max-width: 90%;
    padding: 30px;
    box-shadow: 0 0 40px rgba(0, 243, 255, 0.25);
}
@keyframes modalFadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.form-group {
    margin-bottom: 20px;
}
.form-label {
    display: block;
    color: #00f3ff;
    font-family: 'Share Tech Mono', monospace;
    font-size: 0.85rem;
    margin-bottom: 8px;
    text-transform: uppercase;
}
.form-control {
    width: 100%;
    background: rgba(2, 6, 23, 0.6);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 8px;
    padding: 10px 14px;
    color: #fff;
    font-family: 'Rajdhani', sans-serif;
    font-size: 1rem;
    transition: all 0.3s;
}
.form-control:focus {
    border-color: #00f3ff;
    outline: none;
    box-shadow: 0 0 10px rgba(0, 243, 255, 0.2);
}
.btn-cyber-action {
    padding: 6px 14px;
    border-radius: 6px;
    font-family: 'Share Tech Mono', monospace;
    font-size: 0.8rem;
    cursor: pointer;
    font-weight: bold;
    border: 1px solid;
    transition: all 0.3s;
}
.btn-approve {
    background: rgba(16, 185, 129, 0.1);
    color: #10b981;
    border-color: rgba(16, 185, 129, 0.4);
}
.btn-approve:hover {
    background: #10b981;
    color: #020617;
    box-shadow: 0 0 15px rgba(16, 185, 129, 0.4);
}
.btn-reject {
    background: rgba(239, 68, 68, 0.1);
    color: #ef4444;
    border-color: rgba(239, 68, 68, 0.4);
}
.btn-reject:hover {
    background: #ef4444;
    color: #020617;
    box-shadow: 0 0 15px rgba(239, 68, 68, 0.4);
}
.btn-toggle-on {
    background: rgba(16, 185, 129, 0.1);
    color: #10b981;
    border-color: rgba(16, 185, 129, 0.4);
}
.btn-toggle-off {
    background: rgba(100, 116, 139, 0.1);
    color: #94a3b8;
    border-color: rgba(100, 116, 139, 0.4);
}

.search-container {
    display: flex;
    gap: 15px;
    margin-bottom: 20px;
}
</style>

<div class="dashboard-wrapper">
    <?php include_once __DIR__ . '/_sidebar.php'; ?>

    <main class="main-content">
        <header class="topbar">
            <h2>Certificate Approvals</h2>
            <div class="user-info">
                <span class="user-name">Endorsement Desk</span>
            </div>
        </header>

        <div class="dashboard-content admin-page">
            <h3 class="cyber-section-heading">Credential Operations Center</h3>

            <div class="console-grid">
                
                <div class="console-tabs">
                    <button class="console-tab active" onclick="switchTab('pending', this)">Pending Endorsements (<?= count($pending_records) ?>)</button>
                    <button class="console-tab" onclick="switchTab('approved', this)">Approved Archive (<?= count($approved_records) ?>)</button>
                    <button class="console-tab" onclick="switchTab('rejected', this)">Rejected Registry (<?= count($rejected_records) ?>)</button>
                </div>

                <div class="search-container">
                    <input type="text" id="consoleSearch" class="form-control" placeholder="Search by Intern Name, ID or Hash..." style="max-width: 350px;" onkeyup="filterRecords()">
                </div>

                
                <div id="pane-pending" class="console-content-pane active">
                    <?php if (empty($pending_by_batch)): ?>
                        <div class="cyber-table-panel" style="text-align: center; color: #64748b; padding: 40px; border-style: dashed;">
                            <i class="fa-solid fa-folder-open" style="font-size: 2rem; color: rgba(0, 243, 255, 0.3); margin-bottom: 15px; display: block;"></i>
                            No pending certificate approval requests. All systems clear.
                        </div>
                    <?php else: ?>
                        <?php foreach($pending_by_batch as $batch_name => $batch_certs): ?>
                            <div class="cyber-table-panel" style="margin-bottom: 30px;">
                                <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid rgba(0,243,255,0.2); padding-bottom: 15px; margin-bottom: 15px;">
                                    <h4 style="color: #00f3ff; font-family: 'Share Tech Mono', monospace; font-size: 1.2rem; margin: 0; display: flex; align-items: center; gap: 10px;">
                                        <i class="fa-solid fa-users-viewfinder"></i> <?= esc($batch_name) ?> 
                                        <span style="font-size: 0.8rem; background: rgba(0, 243, 255, 0.1); color: #00f3ff; padding: 2px 8px; border-radius: 12px; font-weight: normal; border: 1px solid rgba(0, 243, 255, 0.2);">
                                            <?= count($batch_certs) ?> Pending
                                        </span>
                                    </h4>
                                    <button class="btn-cyber-action btn-approve" onclick="openBatchApproveModal('<?= esc($batch_name, 'js') ?>')">
                                        <i class="fa-solid fa-circle-check"></i> Approve All Interns
                                    </button>
                                </div>
                                <table class="cyber-table">
                                    <thead>
                                        <tr>
                                            <th>Intern Details</th>
                                            <th>Training Progress</th>
                                            <th>Submission Time</th>
                                            <th style="text-align: right;">Clearance Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="table-pending-batch">
                                        <?php foreach($batch_certs as $cert): ?>
                                        <tr id="row-<?= $cert['id'] ?>" class="record-row">
                                            <td>
                                                <strong style="color: #fff; font-size:1.05rem;"><?= esc($cert['intern_name']) ?></strong><br>
                                                <small style="color: #00f3ff; font-family:'Share Tech Mono', monospace;"><?= esc($cert['intern_display_id']) ?></small>
                                            </td>
                                            <td>
                                                <span style="font-family:'Share Tech Mono', monospace;"><?= $cert['modules_completed'] ?> / <?= $cert['total_modules'] ?> Modules</span>
                                                <div style="width: 120px; height: 4px; background: rgba(255,255,255,0.05); border-radius: 5px; overflow: hidden; margin-top:5px;">
                                                    <div style="width: <?= ($cert['total_modules'] > 0) ? ($cert['modules_completed'] / $cert['total_modules']) * 100 : 0 ?>%; height: 100%; background: #00f3ff;"></div>
                                                </div>
                                            </td>
                                            <td>
                                                <span style="color: #64748b; font-size: 0.85rem;"><?= date('d M Y H:i', strtotime($cert['issued_at'])) ?></span>
                                            </td>
                                            <td style="text-align: right;">
                                                <div style="display:inline-flex; gap:8px;">
                                                    <button class="btn-cyber-action btn-approve" onclick="openApproveModal(<?= $cert['id'] ?>, '<?= esc($cert['intern_name']) ?>')">
                                                        <i class="fa-solid fa-check"></i> Grant
                                                    </button>
                                                    <button class="btn-cyber-action btn-reject" onclick="openRejectModal(<?= $cert['id'] ?>, '<?= esc($cert['intern_name']) ?>')">
                                                        <i class="fa-solid fa-xmark"></i> Deny
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                
                <div id="pane-approved" class="console-content-pane">
                    <div class="cyber-table-panel">
                        <table class="cyber-table">
                            <thead>
                                <tr>
                                    <th>Intern Details</th>
                                    <th>Certificate ID</th>
                                    <th>Approved On</th>
                                    <th>Status</th>
                                    <th style="text-align: right;">Control</th>
                                </tr>
                            </thead>
                            <tbody id="table-approved">
                                <?php foreach($approved_records as $cert): ?>
                                <tr id="row-<?= $cert['id'] ?>" class="record-row">
                                    <td>
                                        <strong style="color: #fff; font-size:1.05rem;"><?= esc($cert['intern_name']) ?></strong><br>
                                        <small style="color: #94a3b8; font-family:'Share Tech Mono', monospace;"><?= esc($cert['intern_display_id']) ?></small>
                                    </td>
                                    <td>
                                        <span style="color: #00f3ff; font-family: 'Share Tech Mono', monospace; font-size: 0.85rem;"><?= substr($cert['certificate_hash'], 0, 16) ?>...</span>
                                    </td>
                                    <td>
                                        <span style="color: #64748b; font-size: 0.85rem;"><?= date('d M Y H:i', strtotime($cert['approved_at'])) ?></span>
                                    </td>
                                    <td id="status-col-<?= $cert['id'] ?>">
                                        <?php if ($cert['is_enabled']): ?>
                                            <span style="color:#10b981; font-size: 0.8rem; font-family:'Share Tech Mono', monospace;"><i class="fa-solid fa-circle-check"></i> ENABLED</span>
                                        <?php else: ?>
                                            <span style="color:#ef4444; font-size: 0.8rem; font-family:'Share Tech Mono', monospace;"><i class="fa-solid fa-circle-xmark"></i> DISABLED</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align: right;">
                                        <div style="display:inline-flex; gap:8px;">
                                            <a href="../intern/view_certificate.php?id=<?= $cert['id'] ?>&preview=1" class="btn-cyber-action btn-cyber-secondary" style="padding: 4px 10px;" target="_blank">
                                                <i class="fa-solid fa-eye"></i> View
                                            </a>
                                            <button id="toggle-btn-<?= $cert['id'] ?>" class="btn-cyber-action <?= $cert['is_enabled'] ? 'btn-toggle-on' : 'btn-toggle-off' ?>" onclick="toggleCertificate(<?= $cert['id'] ?>)">
                                                <i class="fa-solid <?= $cert['is_enabled'] ? 'fa-toggle-on' : 'fa-toggle-off' ?>"></i> Status
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($approved_records)): ?>
                                    <tr class="empty-row"><td colspan="5" style="text-align: center; color: #64748b; padding: 30px;">No approved certificates found.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                
                <div id="pane-rejected" class="console-content-pane">
                    <div class="cyber-table-panel">
                        <table class="cyber-table">
                            <thead>
                                <tr>
                                    <th>Intern Details</th>
                                    <th>Rejection Reason</th>
                                    <th>Denied On</th>
                                    <th style="text-align: right;">Administrative Action</th>
                                </tr>
                            </thead>
                            <tbody id="table-rejected">
                                <?php foreach($rejected_records as $cert): ?>
                                <tr id="row-<?= $cert['id'] ?>" class="record-row">
                                    <td>
                                        <strong style="color: #fff; font-size:1.05rem;"><?= esc($cert['intern_name']) ?></strong><br>
                                        <small style="color: #94a3b8; font-family:'Share Tech Mono', monospace;"><?= esc($cert['intern_display_id']) ?></small>
                                    </td>
                                    <td style="color: #ef4444; max-width: 250px;">
                                        <strong><?= esc($cert['rejection_reason']) ?></strong>
                                    </td>
                                    <td>
                                        <span style="color: #64748b; font-size: 0.85rem;"><?= date('d M Y H:i', strtotime($cert['issued_at'])) ?></span>
                                    </td>
                                    <td style="text-align: right;">
                                        <button class="btn-cyber-action btn-approve" onclick="openApproveModal(<?= $cert['id'] ?>, '<?= esc($cert['intern_name']) ?>')">
                                            <i class="fa-solid fa-rotate-left"></i> Re-Evaluate / Approve
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($rejected_records)): ?>
                                    <tr class="empty-row"><td colspan="4" style="text-align: center; color: #64748b; padding: 30px;">No rejected request files.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </main>
</div>

<div id="approveModal" class="cyber-modal">
    <div class="cyber-modal-container">
        <h4 style="color:#00f3ff; margin:0 0 20px 0; font-size:1.3rem; text-transform:uppercase; letter-spacing:1px;"><i class="fa-solid fa-award"></i> Grant Endorsement</h4>
        <p style="color:#94a3b8; font-size:0.9rem; margin-bottom:20px;">Intern: <strong id="approveInternName" style="color:#fff;"></strong></p>
        
        <form id="approveForm" onsubmit="submitApprove(event)">
            <input type="hidden" id="approveCertId" name="cert_id">
            <div class="form-group">
                <label class="form-label">Custom Completion Date</label>
                <input type="date" id="customDate" class="form-control" value="<?= date('Y-m-d') ?>" required>
            </div>
            
            <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:25px;">
                <button type="button" class="btn-cyber-action btn-cyber-secondary" style="border:none;" onclick="closeModal('approveModal')">Abort</button>
                <button type="submit" class="btn-cyber-action btn-approve" style="border-radius:8px; padding: 8px 20px;">Confirm & Issue</button>
            </div>
        </form>
    </div>
</div>

<div id="rejectModal" class="cyber-modal">
    <div class="cyber-modal-container" style="border-color: #ef4444; box-shadow: 0 0 40px rgba(239, 68, 68, 0.25);">
        <h4 style="color:#ef4444; margin:0 0 20px 0; font-size:1.3rem; text-transform:uppercase; letter-spacing:1px;"><i class="fa-solid fa-triangle-exclamation"></i> Deny Endorsement</h4>
        <p style="color:#94a3b8; font-size:0.9rem; margin-bottom:20px;">Intern: <strong id="rejectInternName" style="color:#fff;"></strong></p>
        
        <form id="rejectForm" onsubmit="submitReject(event)">
            <input type="hidden" id="rejectCertId" name="cert_id">
            <div class="form-group">
                <label class="form-label">Reason for Denial</label>
                <textarea id="rejectionReason" class="form-control" rows="3" placeholder="Explain what curriculum units require revision..." required></textarea>
            </div>
            
            <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:25px;">
                <button type="button" class="btn-cyber-action btn-cyber-secondary" style="border:none;" onclick="closeModal('rejectModal')">Cancel</button>
                <button type="submit" class="btn-cyber-action btn-reject" style="border-radius:8px; padding: 8px 20px;">Confirm Deny</button>
            </div>
        </form>
    </div>

<div id="batchApproveModal" class="cyber-modal">
    <div class="cyber-modal-container">
        <h4 style="color:#00f3ff; margin:0 0 20px 0; font-size:1.3rem; text-transform:uppercase; letter-spacing:1px;"><i class="fa-solid fa-users-gear"></i> Bulk Grant Endorsements</h4>
        <p style="color:#94a3b8; font-size:0.9rem; margin-bottom:20px;">Batch: <strong id="batchApproveName" style="color:#fff;"></strong></p>
        
        <form id="batchApproveForm" onsubmit="submitBatchApprove(event)">
            <input type="hidden" id="batchApproveNameInput" name="batch">
            <div class="form-group">
                <label class="form-label">Custom Completion Date</label>
                <input type="date" id="batchCustomDate" class="form-control" value="<?= date('Y-m-d') ?>" required>
            </div>
            
            <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:25px;">
                <button type="button" class="btn-cyber-action btn-cyber-secondary" style="border:none;" onclick="closeModal('batchApproveModal')">Abort</button>
                <button type="submit" class="btn-cyber-action btn-approve" style="border-radius:8px; padding: 8px 20px;">Confirm & Issue All</button>
            </div>
        </form>
    </div>
</div>

<script>
// 100% Native Vanilla ES6 Javascript (No jQuery dependencies for DOM binding/AJAX)
function switchTab(tabId, btn) {
    document.querySelectorAll('.console-tab').forEach(el => el.classList.remove('active'));
    document.querySelectorAll('.console-content-pane').forEach(el => el.classList.remove('active'));
    
    btn.classList.add('active');
    document.getElementById('pane-' + tabId).classList.add('active');
    
    filterRecords();
}

function filterRecords() {
    const q = document.getElementById('consoleSearch').value.toLowerCase();
    const activePane = document.querySelector('.console-content-pane.active');
    if (!activePane) return;
    
    activePane.querySelectorAll('.record-row').forEach(row => {
        const text = row.innerText.toLowerCase();
        if (text.includes(q)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

// Modal Handlers (Pure Vanilla JS)
function openApproveModal(id, name) {
    document.getElementById('approveCertId').value = id;
    document.getElementById('approveInternName').innerText = name;
    document.getElementById('approveModal').style.display = 'flex';
}

function openRejectModal(id, name) {
    document.getElementById('rejectCertId').value = id;
    document.getElementById('rejectInternName').innerText = name;
    document.getElementById('rejectModal').style.display = 'flex';
}

function closeModal(id) {
    document.getElementById(id).style.display = 'none';
}

function openBatchApproveModal(batchName) {
    document.getElementById('batchApproveNameInput').value = batchName;
    document.getElementById('batchApproveName').innerText = batchName;
    document.getElementById('batchApproveModal').style.display = 'flex';
}

// Native Fetch AJAX Submissions
function submitApprove(e) {
    e.preventDefault();
    const id = document.getElementById('approveCertId').value;
    const customDate = document.getElementById('customDate').value;
    
    const params = new URLSearchParams();
    params.append('action', 'approve');
    params.append('cert_id', id);
    params.append('custom_date', customDate);
    
    fetch('pending_certificates.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: params.toString()
    })
    .then(response => response.json())
    .then(res => {
        if (res.success) {
            closeModal('approveModal');
            const row = document.getElementById('row-' + id);
            if (row) {
                row.style.transition = 'opacity 0.3s';
                row.style.opacity = '0';
                setTimeout(() => {
                    row.remove();
                    location.reload();
                }, 300);
            } else {
                location.reload();
            }
        } else {
            alert(res.message);
        }
    })
    .catch(err => {
        console.error(err);
        alert('Network connection error during approval protocol.');
    });
}

function submitBatchApprove(e) {
    e.preventDefault();
    const batch = document.getElementById('batchApproveNameInput').value;
    const customDate = document.getElementById('batchCustomDate').value;
    
    const params = new URLSearchParams();
    params.append('action', 'approve_batch');
    params.append('batch', batch);
    params.append('custom_date', customDate);
    
    fetch('pending_certificates.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: params.toString()
    })
    .then(response => response.json())
    .then(res => {
        if (res.success) {
            closeModal('batchApproveModal');
            alert(res.message);
            location.reload();
        } else {
            alert(res.message);
        }
    })
    .catch(err => {
        console.error(err);
        alert('Network connection error during bulk approval protocol.');
    });
}

function submitReject(e) {
    e.preventDefault();
    const id = document.getElementById('rejectCertId').value;
    const reason = document.getElementById('rejectionReason').value;
    
    const params = new URLSearchParams();
    params.append('action', 'reject');
    params.append('cert_id', id);
    params.append('reason', reason);
    
    fetch('pending_certificates.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: params.toString()
    })
    .then(response => response.json())
    .then(res => {
        if (res.success) {
            closeModal('rejectModal');
            const row = document.getElementById('row-' + id);
            if (row) {
                row.style.transition = 'opacity 0.3s';
                row.style.opacity = '0';
                setTimeout(() => {
                    row.remove();
                    location.reload();
                }, 300);
            } else {
                location.reload();
            }
        } else {
            alert(res.message);
        }
    })
    .catch(err => {
        console.error(err);
        alert('Network connection error during rejection protocol.');
    });
}

function toggleCertificate(id) {
    const params = new URLSearchParams();
    params.append('action', 'toggle');
    params.append('cert_id', id);
    
    fetch('pending_certificates.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: params.toString()
    })
    .then(response => response.json())
    .then(res => {
        if (res.success) {
            const col = document.getElementById('status-col-' + id);
            const btn = document.getElementById('toggle-btn-' + id);
            if (res.new_status == 1) {
                col.innerHTML = '<span style="color:#10b981; font-size: 0.8rem; font-family:\'Share Tech Mono\', monospace;"><i class="fa-solid fa-circle-check"></i> ENABLED</span>';
                btn.className = 'btn-cyber-action btn-toggle-on';
                btn.innerHTML = '<i class="fa-solid fa-toggle-on"></i> Status';
            } else {
                col.innerHTML = '<span style="color:#ef4444; font-size: 0.8rem; font-family:\'Share Tech Mono\', monospace;"><i class="fa-solid fa-circle-xmark"></i> DISABLED</span>';
                btn.className = 'btn-cyber-action btn-toggle-off';
                btn.innerHTML = '<i class="fa-solid fa-toggle-off"></i> Status';
            }
        } else {
            alert(res.message);
        }
    })
    .catch(err => {
        console.error(err);
        alert('Network connection error during status toggle protocol.');
    });
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
