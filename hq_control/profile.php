<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

check_auth('Admin');

$portal = 'admin';
$page_title = 'My Security Profile | ' . SITE_NAME;
$admin_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT * FROM admins WHERE id = ?");
$stmt->execute([$admin_id]);
$admin = $stmt->fetch();

if (!$admin) {
    die("Critical Security Error: Admin profile not found.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    if (!isset($_POST['csrf_token']) || !validate_csrf($_POST['csrf_token'])) {
        die("Security Validation Failed (CSRF). Access Denied.");
    }
    
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $username = trim($_POST['username']);
    $new_password = $_POST['new_password'];
    
    try {
        
        $stmt = $pdo->prepare("UPDATE admins SET name = ?, email = ?, username = ? WHERE id = ?");
        $stmt->execute([$name, $email, $username, $admin_id]);
        
        
        if (!empty($new_password)) {
            $hash = password_hash($new_password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("UPDATE admins SET password_hash = ? WHERE id = ?");
            $stmt->execute([$hash, $admin_id]);
        }
        
        
        if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['profile_photo']['name'], PATHINFO_EXTENSION));
            
            if (in_array($ext, ['jpg', 'jpeg', 'png'])) {
                
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime = finfo_file($finfo, $_FILES['profile_photo']['tmp_name']);
                finfo_close($finfo);
                
                if (in_array($mime, ['image/jpeg', 'image/png'])) {
                    $new_name = 'admin_' . $admin_id . '_' . time() . '.' . $ext;
                    $upload_path = __DIR__ . '/../uploads/profiles/' . $new_name;
                    
                    if (!is_dir(__DIR__ . '/../uploads/profiles/')) {
                        mkdir(__DIR__ . '/../uploads/profiles/', 0777, true);
                    }
                    
                    if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $upload_path)) {
                        $db_path = 'uploads/profiles/' . $new_name;
                        $pdo->prepare("UPDATE admins SET profile_photo = ? WHERE id = ?")->execute([$db_path, $admin_id]);
                    }
                }
            }
        }
        
        $_SESSION['user_name'] = $name;
        $_SESSION['toast_msg'] = "Profile security details updated successfully.";
        $_SESSION['toast_type'] = "success";
        header("Location: profile.php");
        exit;
        
    } catch (Exception $e) {
        $_SESSION['toast_msg'] = "Update failed: " . $e->getMessage();
        $_SESSION['toast_type'] = "error";
    }
}
?>

<?php require_once __DIR__ . '/../includes/header.php'; ?>

<div class="portal-container">
    <?php include_once __DIR__ . '/_sidebar.php'; ?>

    <main class="main-content">
        <header class="topbar">
            <div class="topbar-left">
                <h2>Manage Administrator Profile</h2>
            </div>
        </header>

        <div class="dashboard-content">
            <div class="glass-panel" style="max-width: 800px; margin: 0 auto; border-top: 4px solid #00f3ff;">
                <form method="POST" enctype="multipart/form-data" class="profile-form">
                    <?php csrf_field(); ?>
                    <div style="display: grid; grid-template-columns: 200px 1fr; gap: 40px; align-items: start;">
                        
                        <div style="text-align: center;">
                            <img src="../<?= esc($admin['profile_photo'] ?: 'assets/images/default-avatar.png') ?>" 
                                 style="width: 180px; height: 180px; border-radius: 12px; object-fit: cover; border: 3px solid #1e293b; margin-bottom: 15px;">
                            <label class="btn-primary" style="display: block; font-size: 0.8rem; background: #334155; cursor: pointer;">
                                <i class="fa-solid fa-camera"></i> Change Photo
                                <input type="file" name="profile_photo" style="display: none;">
                            </label>
                            <p style="font-size: 0.7rem; color: var(--text-muted); margin-top: 10px;">JPG, PNG or WebP accepted.</p>
                        </div>

                        
                        <div>
                            <h3 style="margin-bottom: 20px; color: #e2e8f0; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">Identity & Security</h3>
                            
                            <div class="form-group">
                                <label class="form-label">Full Display Name</label>
                                <input type="text" name="name" class="form-control" value="<?= esc($admin['name']) ?>" required>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Official Email</label>
                                <input type="email" name="email" class="form-control" value="<?= esc($admin['email']) ?>" placeholder="admin@digitalinvestigation.in">
                            </div>

                            <div class="form-group">
                                <label class="form-label">Secret Username</label>
                                <input type="text" name="username" class="form-control" value="<?= esc($admin['username']) ?>" required style="font-family: monospace; color: #00f3ff;">
                                <small style="color: var(--text-muted);">This is used for logging into the portal.</small>
                            </div>

                            <h3 style="margin-bottom: 20px; margin-top: 30px; color: #e2e8f0; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">Update Password</h3>
                            
                            <div class="form-group">
                                <label class="form-label">New Secret Password</label>
                                <input type="password" name="new_password" class="form-control" placeholder="Leave blank to keep current password">
                            </div>

                            <div style="margin-top: 40px; display: flex; gap: 15px;">
                                <button type="submit" class="btn-primary" style="flex: 1;">
                                    <i class="fa-solid fa-shield-check"></i> Save Security Profile
                                </button>
                                <a href="dashboard.php" class="btn-primary" style="background: transparent; border: 1px solid #334155; color: #94a3b8; text-decoration: none; text-align: center;">
                                    Cancel
                                </a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
