<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
require_once 'config/db.php';

$page_title = "Officer Dashboard";

// Fetch Stats
$total_notices = $pdo->query("SELECT COUNT(*) FROM notices WHERE user_id = {$_SESSION['user_id']}")->fetchColumn();
$active_cases = $pdo->query("SELECT COUNT(*) FROM cases WHERE investigating_officer_id = {$_SESSION['user_id']} AND status = 'Open'")->fetchColumn();
$recent_notices = $pdo->prepare("SELECT n.*, c.fir_number FROM notices n JOIN cases c ON n.case_id = c.id WHERE n.user_id = ? ORDER BY n.created_at DESC LIMIT 5");
$recent_notices->execute([$_SESSION['user_id']]);
$notices = $recent_notices->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | CyberNotice</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <main class="main-content">
        <?php include 'includes/header.php'; ?>
        
        <div class="stats-grid">
            <div class="stat-card">
                <p style="color: var(--text-dim); font-size: 0.9rem;">Total Notices Drafted</p>
                <h2 style="font-size: 2rem; margin-top: 0.5rem;"><?php echo $total_notices; ?></h2>
            </div>
            <div class="stat-card">
                <p style="color: var(--text-dim); font-size: 0.9rem;">My Active Cases</p>
                <h2 style="font-size: 2rem; margin-top: 0.5rem;"><?php echo $active_cases; ?></h2>
            </div>
            <div class="stat-card" style="border-color: var(--accent-color);">
                <p style="color: var(--text-dim); font-size: 0.9rem;">Pending Actions</p>
                <h2 style="font-size: 2rem; margin-top: 0.5rem;">0</h2>
            </div>
        </div>

        <div class="header" style="margin-top: 3rem;">
            <h2 style="font-size: 1.2rem;">Recently Drafted Notices</h2>
            <a href="create_notice.php" class="btn" style="font-size: 0.8rem; padding: 0.5rem 1rem;">+ Draft New Notice</a>
        </div>

        <div class="stat-card" style="padding: 0; overflow: hidden;">
            <?php if (empty($notices)): ?>
                <div style="padding: 2rem; text-align: center; color: var(--text-dim);">
                    No notices drafted yet.
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Notice ID</th>
                            <th>Case / FIR</th>
                            <th>Recipient</th>
                            <th>Type</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($notices as $notice): ?>
                        <tr>
                            <td>#<?php echo $notice['id']; ?></td>
                            <td><?php echo $notice['fir_number']; ?></td>
                            <td><?php echo $notice['recipient_name']; ?></td>
                            <td><span style="background: rgba(37, 99, 235, 0.2); padding: 4px 8px; border-radius: 4px; font-size: 0.8rem;"><?php echo $notice['notice_type']; ?></span></td>
                            <td><?php echo date('d M Y', strtotime($notice['created_at'])); ?></td>
                            <td>
                                <a href="view_notice.php?id=<?php echo $notice['id']; ?>" style="color: var(--police-blue); text-decoration: none; font-size: 0.9rem;">View / Print</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
