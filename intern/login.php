<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

// Agar intern already logged in hai toh seedha dashboard pe bhejo
if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'Intern') {
    header("Location: dashboard.php");
    exit;
}

$portal = 'intern';
$page_title = 'Intern Login | ' . SITE_NAME;

$errors = [];

$ip_key = 'login_attempts_intern_' . preg_replace('/[^a-zA-Z0-9_]/', '_', $_SERVER['REMOTE_ADDR'] ?? 'unknown');
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
        $stmt = $pdo->prepare("SELECT * FROM interns WHERE mobile = ? OR intern_id = ?");
        $stmt->execute([$mobile, $mobile]);
        $intern = $stmt->fetch();

        if ($intern && password_verify($password, $intern['password_hash'])) {
            if ($intern['status'] !== 'Active') {
                $errors[] = "Your account is not active. Please contact administrator.";
                $_SESSION[$ip_key]['count']++;
            } else {
                
                session_regenerate_id(true);
                $_SESSION[$ip_key] = ['count' => 0, 'last' => time()];
                $_SESSION['user_role'] = 'Intern';
                $_SESSION['user_name'] = $intern['name'];
                $_SESSION['user_mapped_id'] = $intern['intern_id'];
                $_SESSION['user_id'] = $intern['id'];
                $_SESSION['user_photo'] = $intern['profile_photo'] ?: 'assets/images/default-avatar.png';
                log_audit($pdo, $_SESSION['user_mapped_id'], 'Intern', 'Logged In');
                header("Location: dashboard.php");
                exit;
            }
        } else {
            $_SESSION[$ip_key]['count']++;
            $errors[] = "Invalid mobile number or password.";
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
<div class="auth-wrapper" style="min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 2rem;">
    <div class="auth-layout-grid">
        <?php include_once __DIR__ . '/../includes/cyber_news.php'; ?>
        
        <div style="display: flex; justify-content: center;">
            <div class="glass-panel" style="width: 100%; max-width: 400px;">
        <div style="text-align: center; margin-bottom: 20px;">
            <img src="../assets/images/logo.png" alt="Logo" class="brand-logo" style="width: 140px; height: auto; object-fit: contain; margin-bottom: 10px;">
        </div>
        <h2 style="text-align: center; margin-bottom: 5px; color: var(--text-primary);"><?= __('login_title') ?></h2>
        <p style="text-align: center; color: var(--text-muted); margin-bottom: 30px;"><?= __('welcome') ?></p>

        <?php if (!empty($errors)): ?>
            <div style="background: rgba(220, 38, 38, 0.2); border: 1px solid var(--danger); padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <ul style="color: #f87171; list-style-type: none; padding: 0; margin: 0;">
                    <?php foreach ($errors as $error) echo "<li><i class='fa-solid fa-circle-exclamation'></i> " . esc($error) . "</li>"; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <?php csrf_field(); ?>
            <div class="form-group">
                <label class="form-label"><?= __('mobile') ?></label>
                <input type="text" name="mobile" class="form-control" placeholder="Enter here" required value="<?= esc($_POST['mobile'] ?? '') ?>">
            </div>
            
            <div class="form-group">
                <label class="form-label"><?= __('password') ?></label>
                <input type="password" name="password" class="form-control" placeholder="••••••••" required>
            </div>

            <div style="margin-top: 30px;">
                <button type="submit" class="btn-primary" style="width: 100%;">
                    <?= __('login_btn') ?> <i class="fa-solid fa-arrow-right-to-bracket"></i>
                </button>
            </div>
            <div style="margin-top: 20px; text-align: center; color: var(--text-muted); font-size: 0.9rem;">
                <a href="../forgot_password.php" style="color: #60a5fa; display: block; margin-bottom: 10px;">Forgot Password?</a>
                <?= __('dont_have_account') ?> <a href="signup.php"><?= __('register_now') ?></a>
            </div>
            
            <div style="margin-top: 25px; border-top: 1px solid rgba(255,255,255,0.05); padding-top: 15px; text-align: center;">
                <a href="../index.php" style="color: var(--text-muted); text-decoration: none; font-size: 0.9rem; transition: color 0.3s;" onmouseover="this.style.color='#00f3ff'" onmouseout="this.style.color='var(--text-muted)'">
                    <i class="fa-solid fa-arrow-left-long"></i> Back to Home
                </a>
            </div>
        </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
