<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

// Agar officer already logged in hai toh seedha dashboard pe bhejo
if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'Officer') {
    header("Location: dashboard.php");
    exit;
}

$portal = 'officer';
$page_title = 'Officer Login | ' . SITE_NAME;

$errors = [];

$ip_key = 'login_attempts_officer_' . preg_replace('/[^a-zA-Z0-9_]/', '_', $_SERVER['REMOTE_ADDR'] ?? 'unknown');
if (!isset($_SESSION[$ip_key])) $_SESSION[$ip_key] = ['count' => 0, 'last' => time()];
if (time() - $_SESSION[$ip_key]['last'] > 900) { $_SESSION[$ip_key] = ['count' => 0, 'last' => time()]; } 
if ($_SESSION[$ip_key]['count'] >= 5) {
    http_response_code(403);
    $errors[] = "Too many failed attempts. Please try again after 15 minutes.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($errors)) {
    
    if (!isset($_POST['csrf_token']) || !validate_csrf($_POST['csrf_token'])) {
        http_response_code(403);
        die("Security Validation Failed (CSRF). Access Denied.");
    }

    $mobile = trim($_POST['mobile'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($mobile) || empty($password)) {
        $errors[] = "Please enter both mobile and password.";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM officers WHERE mobile = ? OR officer_id = ?");
            $stmt->execute([$mobile, $mobile]);
            $officer = $stmt->fetch();

            if ($officer && password_verify($password, $officer['password_hash'])) {
                
                if (!$officer['is_verified']) {
                    $new_otp    = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                    $otp_expiry = date('Y-m-d H:i:s', time() + 600); 
                    $pdo->prepare("UPDATE officers SET otp = ?, otp_expiry = ? WHERE id = ?")
                        ->execute([$new_otp, $otp_expiry, $officer['id']]);
                    send_otp_email($officer['email'], $new_otp, 'Officer Portal – Email Verification OTP');
                    $_SESSION['verify_email'] = $officer['email'];
                    $_SESSION['verify_type']  = 'officer';
                    header("Location: otp_verify.php");
                    exit;
                }

                if ($officer['status'] === 'Pending') {
                    $errors[] = "Your account is awaiting Admin approval. You will be notified once activated.";
                } elseif ($officer['status'] !== 'Active') {
                    $_SESSION[$ip_key]['count']++;
                    $errors[] = "Your account is not currently active. Please contact HQ Administrator.";
                } else {
                    
                    session_regenerate_id(true);
                    $_SESSION[$ip_key] = ['count' => 0, 'last' => time()];
                    $_SESSION['user_role'] = 'Officer';
                    $_SESSION['user_name'] = $officer['name'];
                    $_SESSION['user_mapped_id'] = $officer['officer_id'];
                    $_SESSION['user_id'] = $officer['id'];
                    log_audit($pdo, $_SESSION['user_mapped_id'], 'Officer', 'Logged In');
                    header("Location: dashboard.php");
                    exit;
                }
            } else {
                
                $_SESSION[$ip_key]['count']++;
                $errors[] = "Invalid credentials. Please check your ID and password.";
            }
        } catch (\Exception $e) {
            error_log("Officer login DB error: " . $e->getMessage());
            $errors[] = "A system error occurred. Please try again later.";
        }
    }
}
?>

<?php require_once __DIR__ . '/../includes/header.php'; ?>

<style>
    .auth-layout-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 40px;
        align-items: center;
        max-width: 1200px;
        margin: 0 auto;
        width: 100%;
    }

    @media (max-width: 900px) {
        .auth-layout-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
<div class="auth-wrapper"
    style="min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 2rem;">
    <div class="auth-layout-grid">
        <?php include_once __DIR__ . '/../includes/cyber_news.php'; ?>

        <div style="display: flex; justify-content: center;">
            <div class="glass-panel"
                style="width: 100%; max-width: 400px; border: 2px solid #00f3ff; border-top: 4px solid #ef4444; position: relative; overflow: hidden;">
                <div
                    style="position: absolute; top: 0; left: 0; width: 20px; height: 20px; border-left: 3px solid #00f3ff; border-top: 3px solid #00f3ff;">
                </div>
                <div
                    style="position: absolute; bottom: 0; right: 0; width: 20px; height: 20px; border-right: 3px solid #00f3ff; border-bottom: 3px solid #00f3ff;">
                </div>
                <div style="text-align: center; margin-bottom: 20px;">
                    <img src="../assets/images/logo.png" alt="Logo"
                        style="width: 80px; height: 80px; object-fit: contain; margin-bottom: 10px;">
                </div>
                <h2 style="text-align: center; margin-bottom: 5px; color: var(--text-primary);"><?= __('login_title') ?>
                </h2>
                <p style="text-align: center; color: var(--text-muted); margin-bottom: 30px;"><?= __('welcome') ?></p>

                <?php if (!empty($errors)): ?>
                    <div
                        style="background: rgba(220, 38, 38, 0.2); border: 1px solid var(--danger); padding: 15px; border-radius: 4px; margin-bottom: 20px;">
                        <ul style="color: #f87171; list-style-type: none; padding: 0; margin: 0; font-size: 0.9rem;">
                            <?php foreach ($errors as $error)
                                echo "<li><i class='fa-solid fa-triangle-exclamation'></i> " . esc($error) . "</li>"; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="POST" action="login.php">
                    <?php csrf_field(); ?>
                    <div class="form-group">
                        <label class="form-label"
                            style="text-transform: uppercase; font-size: 0.8rem; letter-spacing: 1px;"><?= __('mobile') ?></label>
                        <input type="text" name="mobile" class="form-control" placeholder="Enter ID or Mobile" required
                            style="border-radius: 4px;">
                    </div>

                    <div class="form-group">
                        <label class="form-label"
                            style="text-transform: uppercase; font-size: 0.8rem; letter-spacing: 1px;"><?= __('password') ?></label>
                        <input type="password" name="password" class="form-control" placeholder="••••••••" required
                            style="border-radius: 4px;">
                    </div>

                    <div style="margin-top: 30px;">
                        <button type="submit" class="btn-primary"
                            style="width: 100%; background-color: #ef4444; border-radius: 4px;">
                            <?= __('login_btn') ?> <i class="fa-solid fa-fingerprint"></i>
                        </button>
                    </div>

                    <div
                        style="margin-top: 20px; padding-top: 15px; border-top: 1px solid var(--border-color); text-align: center; color: var(--text-muted); font-size: 0.85rem;">
                        <?= __('dont_have_account') ?> <a href="signup.php"
                            style="color: #ef4444; font-weight: 600;"><?= __('register_now') ?></a>
                        <br><small style="color: var(--text-muted);">(Requires Admin Approval)</small>
                        <br><br>
                        <a href="../forgot_password.php" style="color: #60a5fa; display: block; margin-bottom: 15px;">Reset Password Request</a>
                        
                        <div style="margin-top: 15px; border-top: 1px solid rgba(255,255,255,0.05); padding-top: 15px;">
                            <a href="../index.php" style="color: var(--text-muted); text-decoration: none; font-size: 0.85rem; transition: color 0.3s;" onmouseover="this.style.color='#ef4444'" onmouseout="this.style.color='var(--text-muted)'">
                                <i class="fa-solid fa-arrow-left-long"></i> Back to Home
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>