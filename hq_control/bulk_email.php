<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

check_auth('Admin');

$portal = 'admin';
$page_title = 'Mass Email (Interns) | ' . SITE_NAME;

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !validate_csrf($_POST['csrf_token'])) {
        die("Security Validation Failed (CSRF). Access Denied.");
    }

    $subject = trim($_POST['subject'] ?? '');
    $body = trim($_POST['body'] ?? '');
    $target_batch = trim($_POST['target_batch'] ?? '');

    if (empty($subject) || empty($body)) {
        $message = "Subject and body are required.";
        $message_type = "error";
    } else {
        try {
            $query = "SELECT email, name FROM interns";
            $params = [];
            
            if (!empty($target_batch) && $target_batch !== 'all') {
                $query .= " WHERE batch = ?";
                $params[] = $target_batch;
            }

            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
            $interns = $stmt->fetchAll();

            if (empty($interns)) {
                $message = "No interns found for the selected criteria.";
                $message_type = "warning";
            } else {
                $success_count = 0;
                $fail_count = 0;

                foreach ($interns as $intern) {
                    $personalized_body = "Dear " . $intern['name'] . ",\n\n" . $body;
                    $sent = send_custom_email($intern['email'], $subject, $personalized_body);
                    if ($sent) {
                        $success_count++;
                    } else {
                        $fail_count++;
                    }
                }

                $message = "Emails sent successfully: $success_count. Failed: $fail_count.";
                $message_type = "success";
                
                log_audit($pdo, $_SESSION['user_mapped_id'] ?? 'Admin', 'Admin', "Sent mass email to interns (Success: $success_count)");
            }
        } catch (Exception $e) {
            $message = "An error occurred: " . $e->getMessage();
            $message_type = "error";
        }
    }
}

$batches = [];
try {
    $stmt = $pdo->query("SELECT DISTINCT batch FROM interns WHERE batch IS NOT NULL AND batch != ''");
    $batches = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {}

?>

<?php require_once __DIR__ . '/../includes/header.php'; ?>

<div class="portal-container">
    <?php include_once __DIR__ . '/_sidebar.php'; ?>

    <main class="main-content">
        <header class="topbar">
            <div class="topbar-left">
                <h2>Mass Email (Interns)</h2>
            </div>
        </header>

        <div class="dashboard-content">
            <?php if (!empty($message)): ?>
                <div class="glass-panel" style="background: <?= $message_type == 'success' ? 'rgba(16, 185, 129, 0.1)' : ($message_type == 'error' ? 'rgba(239, 68, 68, 0.1)' : 'rgba(234, 179, 8, 0.1)') ?>; border: 1px solid <?= $message_type == 'success' ? '#10b981' : ($message_type == 'error' ? '#ef4444' : '#eab308') ?>; color: <?= $message_type == 'success' ? '#10b981' : ($message_type == 'error' ? '#f87171' : '#facc15') ?>; padding: 15px; margin-bottom: 20px;">
                    <?= esc($message) ?>
                </div>
            <?php endif; ?>

            <div class="glass-panel" style="max-width: 800px; margin: 0 auto;">
                <h3 style="margin-bottom: 20px; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">
                    <i class="fa-solid fa-paper-plane" style="color: var(--accent-highlight);"></i> Compose Mass Email
                </h3>

                <form method="POST">
                    <?php csrf_field(); ?>
                    
                    <div class="form-group" style="margin-bottom: 20px;">
                        <label class="form-label">Target Audience</label>
                        <select name="target_batch" class="form-control">
                            <option value="all">All Interns</option>
                            <?php foreach ($batches as $batch): ?>
                                <option value="<?= esc($batch) ?>"><?= strripos($batch, 'batch') !== false ? esc($batch) : esc($batch) . ' Batch' ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group" style="margin-bottom: 20px;">
                        <label class="form-label">Email Subject *</label>
                        <input type="text" name="subject" class="form-control" required placeholder="Enter email subject">
                    </div>

                    <div class="form-group" style="margin-bottom: 20px;">
                        <label class="form-label">Email Body *</label>
                        <textarea name="body" class="form-control" rows="10" required placeholder="Enter your message here..."></textarea>
                        <small style="color: var(--text-muted); display: block; margin-top: 5px;">Note: The email will automatically start with "Dear [Intern Name],".</small>
                    </div>

                    <button type="submit" class="btn-primary" style="width: 100%; padding: 12px; font-size: 1rem;">
                        <i class="fa-solid fa-paper-plane"></i> Send Mass Email
                    </button>
                </form>
            </div>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
