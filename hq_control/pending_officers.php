<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

check_auth('Admin');

$portal = 'admin';
$page_title = 'Pending Officer Approvals | Admin Portal';

if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    if ($_GET['action'] === 'approve') {
        $pdo->prepare("UPDATE officers SET status = 'Active', is_verified = 1, otp = NULL, otp_expiry = NULL WHERE id = ?")->execute([$id]);
        header("Location: pending_officers.php?msg=" . urlencode("Officer Approved & Activated"));
    } elseif ($_GET['action'] === 'force_activate') {
        $pdo->prepare("UPDATE officers SET status = 'Active', is_verified = 1, otp = NULL, otp_expiry = NULL WHERE id = ?")->execute([$id]);
        header("Location: pending_officers.php?msg=" . urlencode("Officer Force-Activated by Admin"));
    } elseif ($_GET['action'] === 'reject') {
        $pdo->prepare("UPDATE officers SET status = 'Rejected' WHERE id = ?")->execute([$id]);
        header("Location: pending_officers.php?msg=" . urlencode("Officer Rejected"));
    }
    exit;
}

$stmt = $pdo->query("SELECT *, 'unverified' as list_type FROM officers WHERE is_verified = 0 ORDER BY created_at DESC");
$unverified_officers = $stmt->fetchAll();

$stmt = $pdo->query("SELECT *, 'pending' as list_type FROM officers WHERE is_verified = 1 AND status = 'Pending' ORDER BY created_at DESC");
$pending_officers = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<style>
.dataTable {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0 8px;
    margin-top: -8px;
}
.dataTable th {
    padding: 12px 20px;
    text-align: left;
    color: #64748b;
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 1px;
}
.dataTable td {
    padding: 16px 20px;
    background: rgba(255, 255, 255, 0.03);
    border-top: 1px solid rgba(255, 255, 255, 0.05);
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
}
.dataTable tr td:first-child {
    border-left: 1px solid rgba(255, 255, 255, 0.05);
    border-radius: 8px 0 0 8px;
}
.dataTable tr td:last-child {
    border-right: 1px solid rgba(255, 255, 255, 0.05);
    border-radius: 0 8px 8px 0;
}
.dataTable tr:hover td {
    background: rgba(255, 255, 255, 0.06);
    border-color: rgba(0, 243, 255, 0.2);
}
</style>

<div class="portal-container">
    <?php include '_sidebar.php'; ?>

    <main class="main-content">
        <header class="topbar">
            <div class="topbar-left">
                <h2><?= __('pending_approvals') ?></h2>
            </div>
            <div class="topbar-right">
                <span class="user-status"><i class="fa-solid fa-circle-check" style="color: var(--success);"></i> System Admin</span>
            </div>
        </header>

        <div class="dashboard-content">

            <?php if (isset($_GET['msg'])): ?>
                <div style="background:rgba(16,185,129,.1);border:1px solid #10b981;color:#10b981;padding:13px 18px;border-radius:8px;margin-bottom:20px;font-weight:600;">
                    <i class="fa-solid fa-circle-check"></i> <?= esc($_GET['msg']) ?>
                </div>
            <?php endif; ?>

            
            <?php if (!empty($unverified_officers)): ?>
            <div class="glass-panel" style="margin-bottom:25px;border-left:4px solid #ef4444;">
                <h3 style="margin-bottom:20px;color:#ef4444;border-bottom:1px solid rgba(239,68,68,.2);padding-bottom:12px;">
                    <i class="fa-solid fa-envelope-circle-check"></i> Email Not Verified (<?= count($unverified_officers) ?>) — Stuck at OTP
                </h3>
                <table class="dataTable">
                    <thead><tr>
                        <th>Name</th><th>Email</th><th>Mobile</th><th>Registered</th><th>Action</th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($unverified_officers as $off): ?>
                    <tr>
                        <td style="font-weight:bold;color:#fff;"><?= esc($off['name']) ?></td>
                        <td style="color:#94a3b8;"><?= esc($off['email']) ?></td>
                        <td style="font-family:monospace;color:#64748b;"><?= esc($off['mobile']) ?></td>
                        <td style="color:#64748b;font-size:.85rem;"><?= date('d M Y', strtotime($off['created_at'])) ?></td>
                        <td>
                            <a href="?action=force_activate&id=<?= $off['id'] ?>" 
                               onclick="return confirm('Force activate <?= addslashes(esc($off['name'])) ?> without OTP?')"
                               style="background:rgba(245,158,11,.15);color:#f59e0b;border:1px solid rgba(245,158,11,.4);padding:6px 14px;border-radius:6px;text-decoration:none;font-size:.75rem;font-weight:700;display:inline-flex;align-items:center;gap:5px;">
                                <i class="fa-solid fa-bolt"></i> FORCE ACTIVATE
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

            
            <div class="glass-panel">
                <h3 style="margin-bottom:25px;color:var(--text-primary);border-bottom:1px solid var(--border-color);padding-bottom:15px;">
                    <i class="fa-solid fa-user-clock" style="color:var(--accent-highlight);"></i>
                    Pending Admin Approval (<?= count($pending_officers) ?>)
                </h3>
                <?php if (empty($pending_officers)): ?>
                    <p style="text-align:center;color:var(--text-muted);padding:30px;">No officers awaiting approval.</p>
                <?php else: ?>
                    <table class="dataTable">
                        <thead><tr>
                            <th>Name</th><th>Rank</th><th>Email</th><th>Mobile</th><th>Joined</th><th>Actions</th>
                        </tr></thead>
                        <tbody>
                            <?php foreach ($pending_officers as $off): ?>
                            <tr>
                                <td style="font-weight:bold;color:var(--text-primary);"><?= esc($off['name']) ?></td>
                                <td style="color:var(--accent-blue);font-weight:500;"><?= esc($off['rank']) ?></td>
                                <td style="font-size:.9rem;"><?= esc($off['email']) ?></td>
                                <td style="font-family:monospace;color:#94a3b8;"><?= esc($off['mobile']) ?></td>
                                <td><?= date('d M Y', strtotime($off['joining_date'])) ?></td>
                                <td>
                                    <div style="display:flex;gap:8px;">
                                        <a href="?action=approve&id=<?= $off['id'] ?>" style="background:#10b981;color:#fff;padding:6px 15px;font-size:.75rem;border-radius:4px;text-decoration:none;display:flex;align-items:center;gap:5px;font-weight:bold;">
                                            <i class="fa-solid fa-check"></i> Approve
                                        </a>
                                        <a href="?action=reject&id=<?= $off['id'] ?>" onclick="return confirm('Reject this officer?')" style="background:#ef4444;color:#fff;padding:6px 15px;font-size:.75rem;border-radius:4px;text-decoration:none;display:flex;align-items:center;gap:5px;font-weight:bold;">
                                            <i class="fa-solid fa-xmark"></i> Reject
                                        </a>
                                    </div>
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
