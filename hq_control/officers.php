<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

check_auth('Admin');

$portal = 'admin';
$page_title = 'All Officers | ' . SITE_NAME;

?>

<?php
require_once __DIR__ . '/../includes/header.php';

$officers = [];
$db_error = false;
try {
    // Ensure referral_points and other columns exist (self-healing database)
    try { $pdo->exec("ALTER TABLE officers ADD COLUMN referral_points DECIMAL(4,1) DEFAULT 0"); } catch (PDOException $e2) {}
    try { $pdo->exec("ALTER TABLE officers ADD COLUMN referral_id VARCHAR(50) DEFAULT NULL"); } catch (PDOException $e2) {}
    try { $pdo->exec("ALTER TABLE officers ADD COLUMN is_verified TINYINT(1) DEFAULT 0"); } catch (PDOException $e2) {}
    try { $pdo->exec("ALTER TABLE officers ADD COLUMN otp VARCHAR(10) DEFAULT NULL"); } catch (PDOException $e2) {}
    try { $pdo->exec("ALTER TABLE officers ADD COLUMN otp_expiry DATETIME DEFAULT NULL"); } catch (PDOException $e2) {}
    $stmt = $pdo->query("
        SELECT o.*,
               COALESCE(o.referral_points, 0) AS ref_pts,
               (SELECT COUNT(*) FROM officers o2 WHERE o2.referral_id = o.officer_id) AS ref_officer_count,
               (SELECT COUNT(*) FROM interns i  WHERE i.referral_id  = o.officer_id) AS ref_intern_count
        FROM officers o ORDER BY o.name ASC
    ");
    $officers = $stmt->fetchAll();
} catch (Exception $e) {
    $db_error = true;
    $db_error_msg = $e->getMessage();
}
?>

<style>
.database-alert {
    background: rgba(239, 68, 68, 0.1);
    border: 1px solid #ef4444;
    color: #f87171;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 15px;
}

/* Fix for cramped table (chipka chipka) */
.dataTable {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    margin-top: 10px;
}
.dataTable th {
    padding: 16px 20px;
    color: #94a3b8;
    font-size: 0.85rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1px;
    border-bottom: 2px solid rgba(0, 243, 255, 0.2);
    background: rgba(0, 0, 0, 0.4);
    text-align: left;
}
.dataTable td {
    padding: 20px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    vertical-align: middle;
    line-height: 1.5;
}
.dataTable tbody tr {
    transition: background-color 0.2s ease;
}
.dataTable tbody tr:hover {
    background-color: rgba(0, 243, 255, 0.03);
}
</style>

<div class="portal-container">
    <?php include_once __DIR__ . '/_sidebar.php'; ?>

    <main class="main-content">
        <header class="topbar">
            <div class="topbar-left">
                <h2><?= __('officer_force') ?></h2>
            </div>
            <div class="topbar-right">
                <span class="user-status"><i class="fa-solid fa-circle-check" style="color: var(--success);"></i> System Admin</span>
            </div>
        </header>

        <div class="dashboard-content">
            <?php if ($db_error): ?>
                <div class="database-alert">
                    <i class="fa-solid fa-triangle-exclamation" style="font-size: 2rem;"></i>
                    <div>
                        <h3 style="color: #ef4444; margin-bottom: 5px;">Database Recovery Required</h3>
                        <p style="font-size: 0.9rem; margin: 0;">The 'officers' table is inaccessible in your local XAMPP engine. <?= isset($db_error_msg) ? esc($db_error_msg) : 'Please re-import your SQL file.' ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <div class="glass-panel">
                <h3 style="margin-bottom: 25px; color: var(--text-primary); border-bottom: 1px solid var(--border-color); padding-bottom: 15px;">
                    <i class="fa-solid fa-user-shield" style="color: var(--accent-highlight);"></i> All Personnel (<?= count($officers) ?>)
                </h3>
                
                <?php if ($db_error): ?>
                    <p style="text-align: center; color: var(--text-muted); padding: 40px;"><i class="fa-solid fa-plug-circle-xmark"></i> Table unreadable. Data recovery mode active.</p>
                <?php elseif (empty($officers)): ?>
                    <p style="text-align: center; color: var(--text-muted); padding: 40px;">No active officers found in record.</p>
                <?php else: ?>
                    <table class="dataTable">
                        <thead>
                            <tr>
                                <th>Photo</th>
                                <th><?= __('full_name') ?></th>
                                <th><?= __('rank') ?></th>
                                <th><?= __('official_email') ?></th>
                                <th><?= __('mobile') ?></th>
                                <th>Referrals</th>
                                <th>Registration Date</th>
                                <th><?= __('actions') ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($officers as $off): ?>
                            <tr>
                                <td>
                                    <?php
                                    $name_enc = urlencode($off['name'] ?? 'Officer');
                                    $avatar_url = "https://ui-avatars.com/api/?name={$name_enc}&background=059669&color=fff&size=150";
                                    $photo_url = !empty($off['profile_photo']) ? '../' . ltrim($off['profile_photo'], '/') : $avatar_url;
                                    if (empty($off['profile_photo']) && !empty($off['photo'])) { $photo_url = '../' . ltrim($off['photo'], '/'); }
                                    ?>
                                    <img src="<?= esc($photo_url) ?>" alt="Avatar" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 2px solid #00f3ff;">
                                </td>
                                <td style="font-weight: bold; color: var(--text-primary);"><?= esc($off['name']) ?></td>
                                <td style="color: var(--accent-blue); font-weight: 500;"><?= esc($off['rank']) ?></td>
                                <td style="font-size: 0.9rem;"><?= esc($off['email']) ?></td>
                                <td style="font-family: monospace; color: #94a3b8;"><?= esc($off['mobile']) ?></td>
                                <td>
                                    <?php
                                        $ref_total = ($off['ref_officer_count'] ?? 0) + ($off['ref_intern_count'] ?? 0);
                                        $ref_pts   = (float)($off['ref_pts'] ?? 0);
                                    ?>
                                    <?php if ($ref_total > 0): ?>
                                    <div style="display:flex; flex-direction:column; gap:4px;">
                                        <span style="font-weight:700; color:#6366f1; font-size:0.9rem;"><?= $ref_total ?> referred</span>
                                        <?php if ($off['ref_officer_count'] > 0): ?>
                                        <span style="font-size:0.72rem; color:#94a3b8;"><i class="fa-solid fa-shield-halved" style="color:#6366f1;"></i> <?= $off['ref_officer_count'] ?> officer<?= $off['ref_officer_count'] > 1 ? 's' : '' ?></span>
                                        <?php endif; ?>
                                        <?php if ($off['ref_intern_count'] > 0): ?>
                                        <span style="font-size:0.72rem; color:#94a3b8;"><i class="fa-solid fa-user-graduate" style="color:#a855f7;"></i> <?= $off['ref_intern_count'] ?> intern<?= $off['ref_intern_count'] > 1 ? 's' : '' ?></span>
                                        <?php endif; ?>
                                        <?php if ($ref_pts > 0): ?>
                                        <span style="font-size:0.72rem; color:#10b981; font-weight:600;"><?= number_format($ref_pts, 1) ?> pts</span>
                                        <?php endif; ?>
                                    </div>
                                    <?php else: ?>
                                    <span style="color:#475569; font-size:0.8rem;">—</span>
                                    <?php endif; ?>
                                    <?php if (!empty($off['referral_id'])): ?>
                                    <div style="margin-top:4px; font-size:0.68rem; color:#00f3ff;"><i class="fa-solid fa-arrow-right-to-bracket"></i> via <?= esc($off['referral_id']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span style="color: #10b981; font-weight: bold; font-size: 0.85rem;"><?= date('d M Y', strtotime($off['created_at'])) ?></span><br>
                                    <span style="color: #64748b; font-size: 0.75rem;"><?= date('h:i A', strtotime($off['created_at'])) ?></span>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 8px;">
                                        <a href="officer_detail.php?id=<?= $off['id'] ?>" class="btn-primary" style="padding: 6px 12px; font-size: 0.75rem; background: #6366f1; border: none; border-radius: 4px; color: white; text-decoration: none; display: inline-block;">
                                            <i class="fa-solid fa-user-pen"></i> Manage
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
