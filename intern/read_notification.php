<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
check_auth('Intern');

$intern_id = $_SESSION['user_id'];
$notif_id = (int)($_GET['id'] ?? 0);

if ($notif_id > 0) {
    try {
        // Fetch notification to get link
        $stmt = $pdo->prepare("SELECT * FROM notifications WHERE id = ? AND intern_id = ?");
        $stmt->execute([$notif_id, $intern_id]);
        $notif = $stmt->fetch();

        if ($notif) {
            // Mark as read
            $upd = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ?");
            $upd->execute([$notif_id]);

            // Redirect
            $redirect_link = !empty($notif['link']) ? $notif['link'] : 'dashboard.php';
            header("Location: " . $redirect_link);
            exit;
        }
    } catch (Exception $e) {}
}

header("Location: dashboard.php");
exit;
