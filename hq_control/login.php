<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

// Agar admin already logged in hai toh seedha dashboard pe bhejo
if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'Admin') {
    header("Location: dashboard.php");
    exit;
}

$portal = 'admin';
$page_title = 'Admin Login | ' . SITE_NAME;

$errors = [];

if ($pdo) {
    try {
        
        $pdo->exec("CREATE TABLE IF NOT EXISTS admins (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            name VARCHAR(100) DEFAULT 'Super Admin',
            email VARCHAR(150),
            profile_photo VARCHAR(255) DEFAULT 'assets/images/default-avatar.png',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        
        $check = $pdo->prepare("SELECT id FROM admins WHERE username = 'cybercrime'");
        $check->execute();
        if (!$check->fetch()) {
            
            
            
            $init_pass = bin2hex(random_bytes(10)); 
            error_log("[HQ_ADMIN] One-time admin password generated: " . $init_pass . " (Username: cybercrime)");
            $pwd = password_hash($init_pass, PASSWORD_BCRYPT);
            $pdo->prepare("INSERT INTO admins (username, password_hash, name) VALUES (?, ?, ?)")
                ->execute(['cybercrime', $pwd, 'HQ System Administrator']);
        }

        
        $pdo->exec("CREATE TABLE IF NOT EXISTS directories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            operator_name VARCHAR(255) NOT NULL,
            nodal_name VARCHAR(255),
            email TEXT,
            mobile TEXT,
            category VARCHAR(100),
            address TEXT,
            logo VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");

        
        $pdo->exec("CREATE TABLE IF NOT EXISTS audit_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT,
            user_role VARCHAR(50),
            action TEXT,
            ip_address VARCHAR(45),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");

    } catch (Exception $e) {
        
    }
}

$ip_key = 'login_attempts_admin_' . preg_replace('/[^a-zA-Z0-9_]/', '_', $_SERVER['REMOTE_ADDR'] ?? 'unknown');
if (!isset($_SESSION[$ip_key])) $_SESSION[$ip_key] = ['count' => 0, 'last' => time()];
if (time() - $_SESSION[$ip_key]['last'] > 900) { $_SESSION[$ip_key] = ['count' => 0, 'last' => time()]; } 
if ($_SESSION[$ip_key]['count'] >= 5) {
    http_response_code(403);
    $errors[] = "Too many failed attempts. Please try again after 15 minutes.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($errors)) {
    
    if (!isset($_POST['csrf_token']) || !validate_csrf($_POST['csrf_token'])) {
        die("Security Validation Failed (CSRF). Access Denied.");
    }
    if (!$pdo) {
        $errors[] = "Database Connection Failed. Please ensure your XAMPP MySQL is running to login.";
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        $admin = $stmt->fetch();
        
        if ($admin && password_verify($password, $admin['password_hash'])) {
            
            session_regenerate_id(true);
            $_SESSION[$ip_key] = ['count' => 0, 'last' => time()];
            $_SESSION['user_role'] = 'Admin';
            $_SESSION['user_name'] = $admin['name'];
            $_SESSION['user_id'] = $admin['id'];
            $_SESSION['user_mapped_id'] = 'ADMIN_' . $admin['id'];
            unset($_SESSION['login_attempts']);
            log_audit($pdo, $_SESSION['user_mapped_id'], 'Admin', 'Logged In');
            header("Location: dashboard.php");
            exit;
        } else {
            $_SESSION[$ip_key]['count']++;
            $errors[] = "Access Denied. Invalid Authorization Code.";
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
            <div class="glass-panel" style="width: 100%; max-width: 400px; border-top: 4px solid #eab308;">
        <div style="text-align: center; margin-bottom: 20px;">
            <img src="../assets/images/logo.png" alt="Logo" style="width: 80px; height: 80px; object-fit: contain; margin-bottom: 10px;">
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
                <input type="text" name="username" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label class="form-label"><?= __('password') ?></label>
                <input type="password" name="password" class="form-control" required>
            </div>

            <div style="margin-top: 30px;">
                <button type="submit" class="btn-primary" style="width: 100%; background-color: #eab308; color: #000;">
                    <?= __('login_btn') ?> <i class="fa-solid fa-shield-halved"></i>
                </button>
            </div>
            <div style="margin-top: 20px; text-align: center; font-size: 0.8rem; color: var(--text-muted);">
                <i class="fa-solid fa-triangle-exclamation"></i> Unauthorized access is prohibited. IP is logged.
            </div>
        </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
