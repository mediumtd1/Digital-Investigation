<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
require_once 'config/db.php';

$page_title = "My Drafted Notices";

// Fetch All Notices for User
$stmt = $pdo->prepare("SELECT n.*, c.fir_number FROM notices n JOIN cases c ON n.case_id = c.id WHERE n.user_id = ? ORDER BY n.created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$notices = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Notices | CyberNotice</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <main class="main-content">
        <?php include 'includes/header.php'; ?>

        <div class="stat-card" style="padding: 0; overflow: hidden; margin-top: 1rem;">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>FIR Number</th>
                        <th>Recipient</th>
                        <th>Notice Type</th>
                        <th>Drafted On</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($notices)): ?>
                        <tr><td colspan="6" style="text-align: center; color: var(--text-dim); padding: 2rem;">No notices found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($notices as $n): ?>
                        <tr>
                            <td>#<?php echo $n['id']; ?></td>
                            <td><strong><?php echo $n['fir_number']; ?></strong></td>
                            <td><?php echo $n['recipient_name']; ?></td>
                            <td><?php echo $n['notice_type']; ?></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($n['created_at'])); ?></td>
                            <td>
                                <a href="view_notice.php?id=<?php echo $n['id']; ?>" class="btn" style="padding: 4px 12px; font-size: 0.8rem;">View / Print</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>
