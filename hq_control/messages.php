<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

check_auth('Admin');

$portal = 'admin';
$page_title = 'Contact Messages | ' . SITE_NAME;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_msg_id'])) {
    if (!validate_csrf($_POST['csrf_token'] ?? '')) {
        die("CSRF check failed.");
    }
    $id = (int)$_POST['delete_msg_id'];
    $stmt = $pdo->prepare("DELETE FROM contact_messages WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: messages.php");
    exit;
}

$stmt = $pdo->query("SELECT * FROM contact_messages ORDER BY created_at DESC");
$messages = $stmt->fetchAll();
?>

<?php require_once __DIR__ . '/../includes/header.php'; ?>

<div class="portal-container">
    <?php include_once __DIR__ . '/_sidebar.php'; ?>

    <main class="main-content">
        <header class="topbar">
            <div class="topbar-left">
                <h2>User Inquiries & Messages</h2>
            </div>
            <div class="topbar-right">
                <span class="user-status"><i class="fa-solid fa-envelope" style="color: var(--accent-blue);"></i> Inbox (<?= count($messages) ?>)</span>
            </div>
        </header>

        <div class="dashboard-content">
            <div class="glass-panel">
                <h3 style="margin-bottom: 25px; border-bottom: 1px solid var(--border-color); padding-bottom: 15px;">
                    <i class="fa-solid fa-inbox"></i> Received Messages
                </h3>

                <?php if (empty($messages)): ?>
                    <p style="text-align: center; color: var(--text-muted); padding: 40px;">No messages received through the contact form yet.</p>
                <?php else: ?>
                    <div style="display: flex; flex-direction: column; gap: 20px;">
                        <?php foreach ($messages as $msg): ?>
                            <div class="glass-panel" style="background: rgba(255,255,255,0.02); border-left: 3px solid var(--accent-blue);">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 15px;">
                                    <div>
                                        <h4 style="color: var(--accent-blue); margin-bottom: 5px;"><?= esc($msg['full_name']) ?></h4>
                                        <span style="font-size: 0.85rem; color: var(--text-muted);">
                                            <i class="fa-solid fa-envelope"></i> <?= esc($msg['email']) ?> | 
                                            <i class="fa-solid fa-phone"></i> <?= esc($msg['phone'] ?: 'No Phone') ?>
                                        </span>
                                    </div>
                                    <div style="text-align: right;">
                                        <span style="display: block; font-size: 0.8rem; color: var(--text-muted); margin-bottom: 5px;"><?= date('d M Y, H:i', strtotime($msg['created_at'])) ?></span>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this message?');">
                                            <?php csrf_field(); ?>
                                            <input type="hidden" name="delete_msg_id" value="<?= $msg['id'] ?>">
                                            <button type="submit" style="background:none; border:none; cursor:pointer; color:var(--danger); font-size:0.8rem; text-decoration:none;">
                                                <i class="fa-solid fa-trash-can"></i> Delete
                                            </button>
                                        </form>
                                    </div>
                                </div>
                                <div style="padding: 15px; background: rgba(0,0,0,0.2); border-radius: 4px; color: var(--text-primary); line-height: 1.6;">
                                    <?= nl2br(esc($msg['message'])) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
