<?php
require_once 'config/config.php';
require_once 'config/db.php';

$page_title = 'Reset Password Request | ' . SITE_NAME;
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['identifier'] ?? '');
    if (!validate_csrf($_POST['csrf_token'] ?? '')) {
        $errors[] = "Security validation failed. Please try again.";
    } elseif (empty($identifier)) {
        $errors[] = "Please enter your registered email address.";
    } else {
        
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM otp_verifications WHERE mobile = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 MINUTE)");
            $stmt->execute([$identifier]);
            if ($stmt->fetchColumn() > 2) {
                $errors[] = "Too many requests. Please wait a minute before trying again.";
            }
        } catch(Exception $e) {}
        
        if (empty($errors)) {
            
        try {
            $user = null;
            $stmt = $pdo->prepare("SELECT id, mobile, name, email FROM interns WHERE email = ?");
            $stmt->execute([$identifier]);
            $user = $stmt->fetch();

            if ($user) {
                
                $otp = generate_otp(6);
                $hashed_otp = password_hash($otp, PASSWORD_BCRYPT);
                $expires = date('Y-m-d H:i:s', strtotime('+10 minutes'));

                $stmt = $pdo->prepare("INSERT INTO otp_verifications (mobile, otp_hash, expires_at) VALUES (?, ?, ?)");
                $stmt->execute([$user['mobile'], $hashed_otp, $expires]);

                
                $_SESSION['reset_mobile'] = $user['mobile'];
                $_SESSION['reset_role'] = 'Intern';
                $_SESSION['reset_user_id'] = $user['id'];
                $_SESSION['otp_context'] = 'forgot_password';
                
                if (isset($user['email'])) {
                    if (send_otp_email($user['email'], $otp, "Your Password Reset OTP")) {
                        $_SESSION['toast_msg'] = "An OTP has been sent to your registered email address.";
                        $_SESSION['toast_type'] = "success";
                        header("Location: reset_password.php");
                        exit;
                    } else {
                        $errors[] = "Failed to send OTP to your email. Please try again later.";
                    }
                } else {
                    $_SESSION['toast_msg'] = "A recovery code has been generated. Please check your registered device.";
                    $_SESSION['toast_type'] = "success";
                    header("Location: reset_password.php");
                    exit;
                }
                exit;
            } else {
                $errors[] = "No active account found for the provided details.";
            }
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'engine') !== false || strpos($e->getMessage(), 'exist') !== false) {
                $errors[] = "Database Table Corrupted: Please run 'setup_tables.php' to rebuild your system tables.";
            } else {
                $errors[] = "System Error: Unable to process request at this time.";
            }
        }
    }
}
}

require_once 'includes/header.php';
?>

<div class="auth-wrapper" style="min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 2rem;">
    <div class="glass-panel" style="width: 100%; max-width: 450px; border-top: 4px solid var(--accent-blue);">
        <div style="text-align: center; margin-bottom: 25px;">
            <img src="assets/images/logo.png" alt="Logo" style="width: 80px; height: 80px; object-fit: contain; margin-bottom: 15px;">
            <h2 style="margin-top: 15px; color: var(--text-primary);">Account Recovery</h2>
            <p style="color: var(--text-muted);">Secure Password Reset System</p>
        </div>

        <?php if (!empty($errors)): ?>
            <div style="background: rgba(220, 38, 38, 0.2); border: 1px solid var(--danger); padding: 15px; border-radius: 4px; margin-bottom: 20px;">
                <ul style="color: #f87171; list-style-type: none; padding: 0; margin: 0; font-size: 0.9rem;">
                    <?php foreach ($errors as $error) echo "<li><i class='fa-solid fa-circle-exclamation'></i> " . esc($error) . "</li>"; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= get_csrf_token() ?>">
            <div class="form-group">
            
            </div>

            <div class="form-group">
               <label class="form-label">Registered Email Address</label>

<input type="email"
name="identifier"
class="form-control"
placeholder="Enter your registered email"
required style="border-radius: 4px;">
            </div>

            <div style="margin-top: 30px;">
                <button type="submit" class="btn-primary" style="width: 100%; border-radius: 4px;">
                    Initiate Recovery <i class="fa-solid fa-paper-plane"></i>
                </button>
            </div>
            
            <div style="margin-top: 20px; text-align: center;">
                <a href="index.php" style="color: var(--text-muted); text-decoration: none; font-size: 0.9rem;"><i class="fa-solid fa-arrow-left"></i> Back to Home</a>
            </div>
        </form>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>