<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

$portal = 'intern';
$page_title = 'Verify OTP | ' . SITE_NAME;

if (!isset($_SESSION['otp_mobile']) || !isset($_SESSION['otp_context'])) {
    header("Location: login.php");
    exit;
}

$mobile = $_SESSION['otp_mobile'];
$context = $_SESSION['otp_context'];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    if (!isset($_POST['csrf_token']) || !validate_csrf($_POST['csrf_token'])) {
        die("Security Validation Failed (CSRF). Access Denied.");
    }

    
    if (!isset($_SESSION['otp_attempts'])) $_SESSION['otp_attempts'] = 0;
    $_SESSION['otp_attempts']++;

    if ($_SESSION['otp_attempts'] > 5) {
        $errors[] = "Too many failed attempts. For security reasons, please start over.";
        unset($_SESSION['otp_mobile'], $_SESSION['otp_context'], $_SESSION['otp_attempts']);
        header("Location: signup.php");
        exit;
    }

    $otp_input = trim($_POST['otp'] ?? '');
    
    
    $stmt = $pdo->prepare("SELECT id, otp_hash FROM otp_verifications WHERE mobile = ? AND used = 0 AND expires_at > NOW() ORDER BY id DESC LIMIT 1");
    $stmt->execute([$mobile]);
    $db_otp = $stmt->fetch();

    if ($db_otp && password_verify($otp_input, $db_otp['otp_hash'])) {
        
        $pdo->prepare("UPDATE otp_verifications SET used = 1 WHERE id = ?")->execute([$db_otp['id']]);

        if ($context === 'signup') {
            $data = $_SESSION['pending_signup'];
            
            $state_code = strtoupper(substr($data['state'] ?? 'XX', 0, 3));
            $random_str = strtoupper(substr(uniqid('', true), -6));
            $intern_id  = 'MPCYB' . date('Y') . $state_code . '-' . $random_str;

            $stmt = $pdo->prepare(
                "INSERT INTO interns (intern_id, name, father_name, dob, gender, email, mobile, state, city, college, course, specialization, password_hash, profile_photo, batch, father_occupation, edu_state, university, semester, high_school_percentage, higher_secondary_percentage, country, pincode, postal_address, resume_path, referral_id, status)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Active')"
            );
            $stmt->execute([
                $intern_id,
                $data['name'], $data['father_name'], $data['dob'], $data['gender'],
                $data['email'], $data['mobile'],
                $data['state'] ?? '', $data['city'] ?? '',
                $data['college'], $data['course'],
                $data['specialization'] ?? '',
                $data['password_hash'], $data['photo_path'],
                $data['batch'] ?? '',
                $data['father_occupation'] ?? '',
                $data['edu_state'] ?? '',
                $data['university'] ?? '',
                $data['semester'] ?? 0,
                $data['high_school_percentage'] ?? '',
                $data['higher_secondary_percentage'] ?? '',
                $data['country'] ?? '',
                $data['pincode'] ?? '',
                $data['postal_address'] ?? '',
                $data['resume_path'] ?? null,
                $data['referral_id'] ?? null
            ]);

            log_audit($pdo, $intern_id, 'Intern', 'Account created');

            
            if (!empty($data['referral_id'])) {
                try {
                    $ref_input = trim($data['referral_id']);

                    
                    if (strtoupper($ref_input) === strtoupper($intern_id)) {
                        error_log("Self-referral attempt blocked for: $intern_id");
                    } else {
                        
                        $check_ref = $pdo->prepare("SELECT id FROM interns WHERE intern_id = ? LIMIT 1");
                        $check_ref->execute([$ref_input]);
                        $referrer_db_id = $check_ref->fetchColumn();

                        if ($referrer_db_id) {
                            
                            $pdo->prepare("UPDATE interns SET referral_points = COALESCE(referral_points, 0) + 1 WHERE id = ?")
                                ->execute([$referrer_db_id]);

                            
                            $new_id_stmt = $pdo->prepare("SELECT id FROM interns WHERE intern_id = ? LIMIT 1");
                            $new_id_stmt->execute([$intern_id]);
                            $new_intern_db_id = $new_id_stmt->fetchColumn();

                            if ($new_intern_db_id) {
                                
                                $pdo->prepare("UPDATE interns SET referral_points = COALESCE(referral_points, 0) + 0.5 WHERE id = ?")
                                    ->execute([$new_intern_db_id]);
                            }
                        }
                        
                    }
                } catch (Exception $ref_e) {
                    error_log("Referral points error: " . $ref_e->getMessage());
                }
            }

            
            unset($_SESSION['pending_signup'], $_SESSION['otp_mobile'], $_SESSION['otp_context']);

            $_SESSION['toast_msg'] = "Registration successful! Your Intern ID is $intern_id. Please login.";
            $_SESSION['toast_type'] = "success";
            header("Location: login.php");
            exit;
        } elseif ($context === 'login') {
            
            $intern_id_db = $_SESSION['pending_login_id'];
            $stmt = $pdo->prepare("SELECT * FROM interns WHERE id = ?");
            $stmt->execute([$intern_id_db]);
            $intern = $stmt->fetch();

            $_SESSION['user_id'] = $intern['id'];
            $_SESSION['user_mapped_id'] = $intern['intern_id'];
            $_SESSION['user_name'] = $intern['name'];
            $_SESSION['user_photo'] = $intern['profile_photo'];
            $_SESSION['user_role'] = 'Intern';

            log_audit($pdo, $intern['intern_id'], 'Intern', 'Logged in');

            unset($_SESSION['pending_login_id'], $_SESSION['otp_mobile'], $_SESSION['otp_context']);
            
            header("Location: dashboard.php");
            exit;
        }
    } else {
        $errors[] = "Invalid or expired OTP. Please check and try again.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['resend_otp']) && $context === 'signup') {
    if (isset($_SESSION['pending_signup']['email'])) {
        $new_otp    = generate_otp(6);
        $hashed_otp = password_hash($new_otp, PASSWORD_BCRYPT);
        $expires    = date('Y-m-d H:i:s', strtotime('+5 minutes'));

        $pdo->prepare("UPDATE otp_verifications SET used = 1 WHERE mobile = ? AND used = 0")->execute([$mobile]);
        $pdo->prepare("INSERT INTO otp_verifications (mobile, otp_hash, expires_at) VALUES (?, ?, ?)")->execute([$mobile, $hashed_otp, $expires]);

        $sent = MOCK_OTP ? true : send_otp_email($_SESSION['pending_signup']['email'], $new_otp);
        if (MOCK_OTP) { $_SESSION['toast_msg'] = "DEV — Resent OTP: $new_otp"; $_SESSION['toast_type'] = 'info'; }

        $_SESSION['toast_msg'] = $sent ? "OTP resent successfully to your email." : "Failed to resend OTP. Try again.";
        $_SESSION['toast_type'] = $sent ? "success" : "error";
        header("Location: otp_verify.php");
        exit;
    }
}
?>

<?php require_once __DIR__ . '/../includes/header.php'; ?>

<div class="auth-wrapper" style="min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 2rem;">
    <div class="glass-panel" style="width: 100%; max-width: 400px;">
        <div style="text-align: center; margin-bottom: 20px;">
            <i class="fa-solid fa-mobile-screen-button" style="font-size: 3rem; color: var(--accent-highlight);"></i>
        </div>
        <h2 style="text-align: center; margin-bottom: 5px; color: var(--text-primary);">Verify OTP</h2>
        <p style="text-align: center; color: var(--text-muted); margin-bottom: 30px;">
            <?php if ($context === 'signup' && isset($_SESSION['pending_signup']['email'])): ?>
                An OTP has been sent to your email: <strong><?= esc($_SESSION['pending_signup']['email']) ?></strong><br>
                <small style="color: var(--text-muted); font-size: 0.8rem;">Check your inbox (and spam folder) for the OTP.</small>
            <?php else: ?>
                An OTP has been sent to <strong>+91-<?= esc(substr($mobile, 0, 2) . '...' . substr($mobile, -3)) ?></strong>
            <?php endif; ?>
        </p>

        <?php if (!empty($errors)): ?>
            <div style="background: rgba(220, 38, 38, 0.2); border: 1px solid var(--danger); padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <ul style="color: #f87171; list-style-type: none; padding: 0; margin: 0;">
                    <?php foreach ($errors as $error) echo "<li><i class='fa-solid fa-circle-exclamation'></i> " . esc($error) . "</li>"; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST">
            <?php csrf_field(); ?>
            <div class="form-group">
                <input type="text" name="otp" class="form-control" placeholder="6-digit OTP" style="text-align: center; font-size: 1.5rem; letter-spacing: 5px;" maxlength="6" autofocus required>
            </div>

            <div style="margin-top: 30px;">
                <button type="submit" class="btn-primary" style="width: 100%;">
                    Verify <i class="fa-solid fa-check"></i>
                </button>
            </div>
            
            <div style="margin-top: 20px; text-align: center; color: var(--text-muted); font-size: 0.9rem;">
                <span id="timer">01:00</span>
                <form method="POST" id="resend-form" style="display:none; margin-top: 8px;">
                    <?php csrf_field(); ?>
                    <input type="hidden" name="resend_otp" value="1">
                    <button type="submit" style="background: none; border: none; color: var(--accent-blue); cursor: pointer; font-size: 0.9rem; text-decoration: underline;">
                        Resend OTP to Email
                    </button>
                </form>
            </div>
        </form>
    </div>
</div>

<script>
    let timeLeft = 60;
    const timerElem = document.getElementById('timer');
    const resendForm = document.getElementById('resend-form');

    const countdown = setInterval(() => {
        timeLeft--;
        let min = Math.floor(timeLeft / 60);
        let sec = timeLeft % 60;
        timerElem.innerText = `0${min}:${sec < 10 ? '0' : ''}${sec}`;

        if (timeLeft <= 0) {
            clearInterval(countdown);
            timerElem.style.display = 'none';
            resendForm.style.display = 'block';
        }
    }, 1000);
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
