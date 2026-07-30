<?php
require_once 'config/config.php';
require_once 'config/db.php';

if (!isset($_SESSION['reset_mobile']) || $_SESSION['otp_context'] !== 'forgot_password') {
    header("Location: forgot_password.php");
    exit;
}

$page_title = 'Update Password | ' . SITE_NAME;
$errors = [];
$step = isset($_SESSION['otp_verified']) ? 2 : 1;
$mobile = $_SESSION['reset_mobile'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf($_POST['csrf_token'] ?? '')) {
        $errors[] = "Security validation failed. Please try again.";
    } else {
        
    if (isset($_POST['action']) && $_POST['action'] === 'verify_otp') {
        $otp_input = trim($_POST['otp'] ?? '');
        $stmt = $pdo->prepare("SELECT id, otp_hash FROM otp_verifications WHERE mobile = ? AND used = 0 AND expires_at > NOW() ORDER BY id DESC LIMIT 1");
        $stmt->execute([$mobile]);
        $db_otp = $stmt->fetch();

        if ($db_otp && password_verify($otp_input, $db_otp['otp_hash'])) {
            $pdo->prepare("UPDATE otp_verifications SET used = 1 WHERE id = ?")->execute([$db_otp['id']]);
            $_SESSION['otp_verified'] = true;
            $step = 2;
        } else {
            $errors[] = "Invalid or expired OTP. Please check your toast message.";
        }
    }

    
    elseif (isset($_POST['action']) && $_POST['action'] === 'reset_password') {
        $pass = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if (strlen($pass) < 6) {
            $errors[] = "Password must be at least 6 characters.";
        } elseif ($pass !== $confirm) {
            $errors[] = "Passwords do not match.";
        } else {
            $hashed = password_hash($pass, PASSWORD_BCRYPT);
            $role = $_SESSION['reset_role'];
            $uid = $_SESSION['reset_user_id'];

            try {
                $stmt = $pdo->prepare("UPDATE interns SET password_hash = ? WHERE id = ?");
                $stmt->execute([$hashed, $uid]);

                unset($_SESSION['reset_mobile'], $_SESSION['reset_role'], $_SESSION['reset_user_id'], $_SESSION['otp_verified'], $_SESSION['otp_context']);
                $_SESSION['toast_msg'] = "Password reset successfully! You can now login.";
                $_SESSION['toast_type'] = "success";

                $redirect = 'intern/login.php';
                header("Location: " . $redirect);
                exit;
            } catch (Exception $e) {
                $errors[] = "Database error. Please try again.";
            }
        }
    }
}
}

require_once 'includes/header.php';
?>

<div class="auth-wrapper"
    style="min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 2rem;">
    <div class="glass-panel" style="width: 100%; max-width: 450px; border-top: 4px solid var(--success);">
        <div style="text-align: center; margin-bottom: 25px;">
            <img src="assets/images/logo.png" alt="Logo"
                style="width: 80px; height: 80px; object-fit: contain; margin-bottom: 15px;">
            <h2 style="margin-top: 15px; color: var(--text-primary);">
                <?= ($step == 1) ? 'Verify Recovery Code' : 'Set New Password' ?></h2>
            <p style="color: var(--text-muted);">Resetting password for: <strong><?= esc($mobile) ?></strong></p>
        </div>

        <?php if (!empty($errors)): ?>
            <div
                style="background: rgba(220, 38, 38, 0.2); border: 1px solid var(--danger); padding: 15px; border-radius: 4px; margin-bottom: 20px;">
                <ul style="color: #f87171; list-style-type: none; padding: 0; margin: 0; font-size: 0.9rem;">
                    <?php foreach ($errors as $error)
                        echo "<li><i class='fa-solid fa-circle-exclamation'></i> " . esc($error) . "</li>"; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ($step == 1): ?>
            
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= get_csrf_token() ?>">
                <input type="hidden" name="action" value="verify_otp">
                <div class="form-group">
                    <label class="form-label" style="text-align: center; display: block;">Enter 6-Digit OTP</label>
                    <input type="text" name="otp" class="form-control" placeholder="000000" maxlength="6" required
                        style="text-align: center; font-size: 1.5rem; letter-spacing: 10px; font-family: monospace;">
                </div>
                <div style="margin-top: 30px;">
                    <button type="submit" class="btn-primary" style="width: 100%;">Verify OTP <i
                            class="fa-solid fa-circle-check"></i></button>
                </div>
            </form>
        <?php else: ?>
            
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= get_csrf_token() ?>">
                <input type="hidden" name="action" value="reset_password">
                <div class="form-group">
                    <label class="form-label">New Password</label>
                    <input type="password" name="new_password" class="form-control" placeholder="••••••••" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Confirm New Password</label>
                    <input type="password" name="confirm_password" class="form-control" placeholder="••••••••" required>
                </div>
                <div style="margin-top: 30px;">
                    <button type="submit" class="btn-primary" style="width: 100%; background-color: var(--success);">Update
                        My Password <i class="fa-solid fa-floppy-disk"></i></button>
                </div>
            </form>
        <?php endif; ?>

        <div style="margin-top: 20px; text-align: center;">
            <a href="forgot_password.php"
                style="color: var(--text-muted); text-decoration: none; font-size: 0.85rem;">Cancel and Restart</a>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>