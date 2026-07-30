<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$page_title = 'Verify OTP | ' . SITE_NAME;
$error = '';
$success = false;

if (!isset($_SESSION['verify_email'])) {
    header("Location: signup.php");
    exit;
}

$email = $_SESSION['verify_email'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $otp_input = trim($_POST['otp'] ?? '');

    if (empty($otp_input)) {
        $error = "Please enter the OTP.";
    } else {
        $stmt = $pdo->prepare("SELECT id, officer_id, referral_id, otp, otp_expiry FROM officers WHERE email = ?");
        $stmt->execute([$email]);
        $officer = $stmt->fetch();

        if ($officer) {
            if ($officer['otp'] === $otp_input && strtotime($officer['otp_expiry']) > time()) {
                
                $stmt = $pdo->prepare("UPDATE officers SET is_verified = 1, otp = NULL, otp_expiry = NULL WHERE id = ?");
                $stmt->execute([$officer['id']]);

                // Referral Points Allocation
                if (!empty($officer['referral_id'])) {
                    $ref_input = trim($officer['referral_id']);
                    
                    // Check if referrer is another officer
                    $check_ref = $pdo->prepare("SELECT id FROM officers WHERE officer_id = ? LIMIT 1");
                    $check_ref->execute([$ref_input]);
                    $referrer_db_id = $check_ref->fetchColumn();
                    if ($referrer_db_id) {
                        $pdo->prepare("UPDATE officers SET referral_points = COALESCE(referral_points, 0) + 1.0 WHERE id = ?")
                            ->execute([$referrer_db_id]);
                        $pdo->prepare("UPDATE officers SET referral_points = COALESCE(referral_points, 0) + 0.5 WHERE id = ?")
                            ->execute([$officer['id']]);
                    } else {
                        // Check if referrer is an intern
                        $check_ref_intern = $pdo->prepare("SELECT id FROM interns WHERE intern_id = ? LIMIT 1");
                        $check_ref_intern->execute([$ref_input]);
                        $referrer_intern_db_id = $check_ref_intern->fetchColumn();
                        if ($referrer_intern_db_id) {
                            $pdo->prepare("UPDATE interns SET referral_points = COALESCE(referral_points, 0) + 1.0 WHERE id = ?")
                                ->execute([$referrer_intern_db_id]);
                            $pdo->prepare("UPDATE officers SET referral_points = COALESCE(referral_points, 0) + 0.5 WHERE id = ?")
                                ->execute([$officer['id']]);
                        }
                    }
                }
                
                $success = true;
                unset($_SESSION['verify_email']);
                unset($_SESSION['verify_type']);
            } else {
                $error = "Invalid or expired OTP.";
            }
        } else {
            $error = "Account not found.";
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div style="min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 2rem;">
    <div style="width: 100%; max-width: 450px;">
        <div class="glass-panel" style="text-align: center; border-top: 4px solid #ef4444; padding: 40px;">
            
            <?php if ($success): ?>
                <div style="margin-bottom: 25px;">
                    <i class="fa-solid fa-circle-check" style="font-size: 4rem; color: #10b981;"></i>
                </div>
                <h2 style="color: #fff; margin-bottom: 15px;">EMAIL VERIFIED!</h2>
                <p style="color: var(--text-muted); margin-bottom: 25px;">
                    Your forensic account has been verified. However, for security reasons, it is now <strong>Pending Admin Approval</strong>.
                </p>
                <div style="background: rgba(245, 158, 11, 0.1); border: 1px dashed #f59e0b; padding: 15px; border-radius: 8px; margin-bottom: 25px;">
                    <p style="color: #f59e0b; font-size: 0.85rem; margin: 0;">
                        <i class="fa-solid fa-user-shield"></i> Access will be granted once the HQ Admin reviews your badge and credentials.
                    </p>
                </div>
                <a href="login.php" class="btn-primary" style="display: block; text-decoration: none;">RETURN TO LOGIN</a>
            <?php else: ?>
                <div style="margin-bottom: 20px;">
                    <i class="fa-solid fa-envelope-shield" style="font-size: 3.5rem; color: #ef4444;"></i>
                </div>
                <h2 style="color: #fff; margin-bottom: 10px;">OTP VERIFICATION</h2>
                <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 30px;">
                    A security code has been sent to:<br>
                    <strong style="color: var(--accent-blue);"><?= esc($email) ?></strong>
                </p>

                <?php if ($error): ?>
                    <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid #ef4444; color: #ef4444; padding: 12px; border-radius: 6px; margin-bottom: 20px; font-size: 0.9rem;">
                        <i class="fa-solid fa-triangle-exclamation"></i> <?= esc($error) ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="form-group" style="margin-bottom: 25px;">
                        <input type="text" name="otp" class="form-control" placeholder="ENTER 6-DIGIT CODE" maxlength="6" required 
                               style="text-align: center; font-size: 1.5rem; letter-spacing: 10px; font-weight: bold; background: rgba(0,0,0,0.3);">
                    </div>
                    <button type="submit" class="btn-primary" style="width: 100%; padding: 12px;">
                        VERIFY & PROCEED <i class="fa-solid fa-shield-check"></i>
                    </button>
                </form>

                <div style="margin-top: 25px; font-size: 0.85rem;">
                    <p style="color: var(--text-muted); margin-bottom: 15px;">
                        Didn't receive the code? <a href="signup.php" style="color: var(--accent-blue); text-decoration: none;">Check Email & Try Again</a>
                    </p>
                    <div style="border-top: 1px solid rgba(255,255,255,0.05); padding-top: 15px;">
                        <a href="../index.php" style="color: var(--text-muted); text-decoration: none; font-size: 0.85rem; transition: color 0.3s;" onmouseover="this.style.color='#ef4444'" onmouseout="this.style.color='var(--text-muted)'">
                            <i class="fa-solid fa-arrow-left-long"></i> Back to Home
                        </a>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
