<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

check_auth('Admin');

$portal = 'admin';
$page_title = 'Officer Detail | ' . SITE_NAME;

$officer_id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM officers WHERE id = ?");
$stmt->execute([$officer_id]);
$officer = $stmt->fetch();

if (!$officer) {
    $_SESSION['toast_msg'] = "Officer not found.";
    $_SESSION['toast_type'] = "error";
    header("Location: officers.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    if (!validate_csrf($_POST['csrf_token'] ?? '')) {
        $_SESSION['toast_msg'] = "Security validation failed. Please try again.";
        $_SESSION['toast_type'] = "error";
        header("Location: officer_detail.php?id=" . intval($officer_id));
        exit;
    }
    $allowed_statuses = ['Active', 'Suspended', 'Pending'];
    $allowed_ranks    = ['Constable', 'Head Constable', 'Sub-Inspector', 'Inspector', 'DSP'];

    $new_status = in_array($_POST['status'] ?? '', $allowed_statuses) ? $_POST['status'] : $officer['status'];
    $new_rank   = in_array($_POST['rank']   ?? '', $allowed_ranks)    ? $_POST['rank']   : $officer['rank'];

    $upd = $pdo->prepare("UPDATE officers SET status = ?, rank = ? WHERE id = ?");
    $upd->execute([$new_status, $new_rank, $officer_id]);

    $_SESSION['toast_msg'] = "Officer profile updated successfully.";
    $_SESSION['toast_type'] = "success";
    header("Location: officer_detail.php?id=" . intval($officer_id));
    exit;
}
?>

<?php require_once __DIR__ . '/../includes/header.php'; ?>

<div class="portal-container">
    <?php include_once __DIR__ . '/_sidebar.php'; ?>

    <main class="main-content">
        <header class="topbar">
            <div class="topbar-left">
                <h2>Manage Officer Profile</h2>
            </div>
            <div class="topbar-right">
                <a href="officers.php" class="btn-primary" style="background-color: #475569; text-decoration: none;">&larr; Back</a>
            </div>
        </header>

        <div class="dashboard-content">
            <div class="glass-panel" style="max-width: 800px; margin: 0 auto;">
                <h3 style="margin-bottom: 25px; color: var(--text-primary); border-bottom: 1px solid var(--border-color); padding-bottom: 15px;">
                    <i class="fa-solid fa-user-shield" style="color: var(--accent-highlight);"></i> <?= esc($officer['name']) ?> (<?= esc($officer['officer_id']) ?>)
                </h3>
                
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= get_csrf_token() ?>">
                    <input type="hidden" name="action" value="update">
                    
                    <div style="text-align: center; margin-bottom: 30px;">
                        <?php
                        
                        $name_enc = urlencode($officer['name'] ?? 'Officer');
                        $avatar_url = "https://ui-avatars.com/api/?name={$name_enc}&background=059669&color=fff&size=150";
                        $photo_url = !empty($officer['profile_photo']) ? '../' . ltrim($officer['profile_photo'], '/') : $avatar_url;
                        
                        if (empty($officer['profile_photo']) && !empty($officer['photo'])) {
                            $photo_url = '../' . ltrim($officer['photo'], '/');
                        }
                        ?>
                        <img src="<?= esc($photo_url) ?>" alt="Officer Photo" style="width: 150px; height: 150px; border-radius: 50%; object-fit: cover; border: 3px solid #00f3ff; padding: 3px; background: #0f172a;">
                        <p style="color: #94a3b8; font-size: 0.85rem; margin-top: 10px;">ID: <?= esc($officer['officer_id']) ?></p>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                        <div class="form-group">
                            <label class="form-label">Registration Date</label>
                            <input type="text" class="form-control" value="<?= date('d M Y - h:i A', strtotime($officer['created_at'])) ?>" disabled style="color: #10b981; font-weight: bold; background: rgba(16,185,129,0.05); border-color: rgba(16,185,129,0.2);">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Full Name</label>
                            <input type="text" class="form-control" value="<?= esc($officer['name']) ?>" disabled>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Badge Number</label>
                            <input type="text" class="form-control" value="<?= esc($officer['badge_number'] ?? 'N/A') ?>" disabled>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Official Email</label>
                            <input type="text" class="form-control" value="<?= esc($officer['email']) ?>" disabled>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Mobile Number</label>
                            <input type="text" class="form-control" value="<?= esc($officer['mobile']) ?>" disabled>
                        </div>
                        <div class="form-group">
                            <label class="form-label">State</label>
                            <input type="text" class="form-control" value="<?= esc($officer['state'] ?? 'N/A') ?>" disabled>
                        </div>
                        <div class="form-group">
                            <label class="form-label">District / City</label>
                            <input type="text" class="form-control" value="<?= esc($officer['district'] ?? 'N/A') ?>" disabled>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Designation / Unit</label>
                            <input type="text" class="form-control" value="<?= esc($officer['designation'] ?? 'N/A') ?>" disabled>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Joining Date</label>
                            <input type="text" class="form-control" value="<?= esc($officer['joining_date']) ?>" disabled>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px;">
                        <div class="form-group">
                            <label class="form-label">Rank</label>
                            <select name="rank" class="form-control" required>
                                <option value="Constable" <?= $officer['rank'] == 'Constable' ? 'selected' : '' ?>>Constable</option>
                                <option value="Head Constable" <?= $officer['rank'] == 'Head Constable' ? 'selected' : '' ?>>Head Constable</option>
                                <option value="Sub-Inspector" <?= $officer['rank'] == 'Sub-Inspector' ? 'selected' : '' ?>>Sub-Inspector</option>
                                <option value="Inspector" <?= $officer['rank'] == 'Inspector' ? 'selected' : '' ?>>Inspector</option>
                                <option value="DSP" <?= $officer['rank'] == 'DSP' ? 'selected' : '' ?>>DSP</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Account Status</label>
                            <select name="status" class="form-control" required>
                                <option value="Active" <?= $officer['status'] == 'Active' ? 'selected' : '' ?>>Active</option>
                                <option value="Suspended" <?= $officer['status'] == 'Suspended' ? 'selected' : '' ?>>Suspended</option>
                                <option value="Pending" <?= $officer['status'] == 'Pending' ? 'selected' : '' ?>>Pending Review</option>
                            </select>
                        </div>
                    </div>

                    <div style="border-top: 1px solid var(--border-color); padding-top: 20px; text-align: right;">
                        <button type="submit" class="btn-primary" style="background-color: #6366f1;">
                            <i class="fa-solid fa-floppy-disk"></i> Save Profile Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
