<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

check_auth('Admin');

$portal = 'admin';
$page_title = 'Certificates Management | ' . SITE_NAME;

$cert_cols = [
    "title VARCHAR(255) DEFAULT NULL AFTER intern_id",
    "description TEXT AFTER title",
    "intern_name_custom VARCHAR(255) DEFAULT NULL AFTER description",
    "issue_date_custom DATE DEFAULT NULL AFTER intern_name_custom",
    "is_automatic TINYINT(1) DEFAULT 1 AFTER issue_date_custom",
    "is_enabled TINYINT(1) DEFAULT 1 AFTER is_automatic"
];
foreach ($cert_cols as $cdef) {
    try {
        $pdo->exec("ALTER TABLE certificates ADD COLUMN $cdef");
    } catch (Exception $e) {
        
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['issue_cert'])) {
    $intern_id = (int)$_POST['intern_id'];
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $custom_name = trim($_POST['intern_name_custom'] ?? '');
    $custom_date = $_POST['issue_date_custom'] ?? date('Y-m-d');
    $is_automatic = isset($_POST['is_automatic']) ? 1 : 0;

    try {
        $hash = bin2hex(random_bytes(32));
        $qr_token = bin2hex(random_bytes(32));
        $stmt = $pdo->prepare("INSERT INTO certificates (intern_id, certificate_hash, qr_token, approval_status, title, description, intern_name_custom, issue_date_custom, is_automatic, approved_at) VALUES (?, ?, ?, 'approved', ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$intern_id, $hash, $qr_token, $title, $description, $custom_name ?: null, $custom_date, $is_automatic]);
        $_SESSION['toast_msg'] = "Certificate record created!";
        $_SESSION['toast_type'] = "success";
    } catch(Exception $e) {
        $_SESSION['toast_msg'] = "Error: " . $e->getMessage();
        $_SESSION['toast_type'] = "error";
    }
    header("Location: certificates.php");
    exit;
}

if (isset($_GET['toggle_id'])) {
    $tid = (int)$_GET['toggle_id'];
    $pdo->prepare("UPDATE certificates SET is_enabled = 1 - is_enabled WHERE id = ?")->execute([$tid]);
    header("Location: certificates.php");
    exit;
}

if (isset($_GET['delete_id'])) {
    $did = (int)$_GET['delete_id'];
    $pdo->prepare("DELETE FROM certificates WHERE id = ?")->execute([$did]);
    header("Location: certificates.php");
    exit;
}

$interns = $pdo->query("SELECT id, name, intern_id FROM interns ORDER BY name ASC")->fetchAll();

$stmt = $pdo->query("
    SELECT c.*, i.name as intern_name, i.intern_id as intern_display_id 
    FROM certificates c 
    JOIN interns i ON c.intern_id = i.id 
    ORDER BY c.issued_at DESC
");
$issued_certificates = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-wrapper">
    <?php include_once __DIR__ . '/_sidebar.php'; ?>

    <main class="main-content">
        <header class="topbar">
            <h2>Certificates Management</h2>
            <div class="user-info">
                <span class="user-name">System Administrator</span>
            </div>
        </header>

        <div class="dashboard-content" style="display: grid; grid-template-columns: 1fr 2fr; gap: 20px;">
            
            
            <div class="glass-panel">
                <h3 class="section-heading">Issue New Certificate</h3>
                <form method="POST">
                    <input type="hidden" name="issue_cert" value="1">
                    
                    <div class="form-group">
                        <label class="form-label">Select Intern</label>
                        <select name="intern_id" class="form-control" required>
                            <option value="">-- Choose Intern --</option>
                            <?php foreach($interns as $i): ?>
                                <option value="<?= $i['id'] ?>"><?= esc($i['name']) ?> (<?= esc($i['intern_id']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Certificate Title</label>
                        <input type="text" name="title" class="form-control" placeholder="e.g. Internship Completion" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Description / Remarks</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Performance remarks..."></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Custom Display Name (Optional)</label>
                        <input type="text" name="intern_name_custom" class="form-control" placeholder="Leave blank to use profile name">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Completion Date</label>
                        <input type="date" name="issue_date_custom" class="form-control" value="<?= date('Y-m-d') ?>">
                    </div>

                    <div class="form-group">
                        <label style="display: flex; align-items: center; gap: 10px; color: #94a3b8; font-size: 0.85rem; cursor: pointer;">
                            <input type="checkbox" name="is_automatic" value="1" checked> Enable Auto-Generation
                        </label>
                    </div>

                    <button type="submit" class="btn-primary" style="width: 100%; margin-top: 20px;">
                        <i class="fa-solid fa-award"></i> Issue Now
                    </button>
                </form>
            </div>

            
            <div class="glass-panel">
                <h3 class="section-heading">Recently Issued Certificates</h3>
                <div style="overflow-x: auto;">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Intern</th>
                                <th>Title</th>
                                <th>Issued On</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($issued_certificates as $cert): ?>
                            <tr>
                                <td>
                                    <strong><?= esc($cert['intern_name']) ?></strong><br>
                                    <small><?= esc($cert['intern_display_id']) ?></small>
                                </td>
                                <td><?= esc($cert['title']) ?></td>
                                <td><?= date('d M, Y', strtotime($cert['issued_at'])) ?></td>
                                <td>
                                    <?php if ($cert['is_enabled']): ?>
                                        <span style="color:#10b981; font-size:0.75rem;"><i class="fa-solid fa-circle-check"></i> ENABLED</span>
                                    <?php else: ?>
                                        <span style="color:#ef4444; font-size:0.75rem;"><i class="fa-solid fa-circle-xmark"></i> DISABLED</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="display:flex; gap:5px;">
                                        <a href="../intern/view_certificate.php?id=<?= $cert['id'] ?>&preview=1" target="_blank" class="btn-primary" style="padding: 5px 10px; font-size: 0.7rem; background: var(--accent-highlight);">
                                            <i class="fa-solid fa-eye"></i> View
                                        </a>
                                        <a href="?toggle_id=<?= $cert['id'] ?>" class="btn-primary" style="padding: 5px 10px; font-size: 0.7rem; background: <?= $cert['is_enabled'] ? '#ef4444' : '#10b981' ?>;">
                                            <i class="fa-solid <?= $cert['is_enabled'] ? 'fa-ban' : 'fa-check' ?>"></i> <?= $cert['is_enabled'] ? 'Disable' : 'Enable' ?>
                                        </a>
                                        <a href="?delete_id=<?= $cert['id'] ?>" onclick="return confirm('Delete this certificate permanently?')" class="btn-primary" style="padding: 5px 10px; font-size: 0.7rem; background: #334155;">
                                            <i class="fa-solid fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($issued_certificates)): ?>
                                <tr><td colspan="5" style="text-align: center; color: var(--text-muted); padding: 20px;">No certificates issued yet.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
