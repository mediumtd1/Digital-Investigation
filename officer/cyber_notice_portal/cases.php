<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
require_once 'config/db.php';

$page_title = "Case Management";

// Fetch Cases
$stmt = $pdo->prepare("SELECT * FROM cases WHERE investigating_officer_id = ? ORDER BY created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$cases = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cases | CyberNotice</title>
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
                        <th>FIR Number</th>
                        <th>Complainant</th>
                        <th>Sections</th>
                        <th>Status</th>
                        <th>Created</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($cases)): ?>
                        <tr><td colspan="5" style="text-align: center; color: var(--text-dim); padding: 2rem;">No cases assigned.</td></tr>
                    <?php else: ?>
                        <?php foreach ($cases as $c): ?>
                        <tr>
                            <td><strong><?php echo $c['fir_number']; ?></strong></td>
                            <td><?php echo $c['complainant_name']; ?></td>
                            <td><?php echo $c['sections']; ?></td>
                            <td>
                                <span style="background: <?php echo $c['status'] == 'Open' ? 'rgba(34, 197, 94, 0.2)' : 'rgba(239, 68, 68, 0.2)'; ?>; padding: 4px 8px; border-radius: 4px; font-size: 0.8rem;">
                                    <?php echo $c['status']; ?>
                                </span>
                            </td>
                            <td><?php echo date('d/m/Y', strtotime($c['created_at'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>
