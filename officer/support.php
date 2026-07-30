<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

$user_role = (strpos($_SERVER['PHP_SELF'], '/officer/') !== false) ? 'Officer' : 'Intern';
check_auth($user_role);

$portal = strtolower($user_role);
$page_title = 'Technical Support | ' . SITE_NAME;
$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['raise_ticket'])) {
    $subject = trim($_POST['subject']);
    $description = trim($_POST['description']);
    $priority = $_POST['priority'];
    
    $stmt = $pdo->prepare("INSERT INTO help_tickets (user_id, user_role, subject, description, priority) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$user_id, $user_role, $subject, $description, $priority]);
    
    $_SESSION['toast_msg'] = "Ticket raised successfully! HQ will review it.";
    $_SESSION['toast_type'] = "success";
    header("Location: support.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM help_tickets WHERE user_id = ? AND user_role = ? ORDER BY created_at DESC");
$stmt->execute([$user_id, $user_role]);
$my_tickets = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-wrapper">
    <?php include_once '_sidebar.php'; ?>

    <main class="main-content">
        <header class="topbar">
            <h2>HQ Helpdesk & Tech Support</h2>
        </header>

        <div class="dashboard-content">
            <div style="display: grid; grid-template-columns: 1fr 1.5fr; gap: 30px;">
                
                
                <div class="glass-panel" style="border-top: 4px solid var(--accent-highlight);">
                    <h3 class="section-heading"><i class="fa-solid fa-headset"></i> Raise New Ticket</h3>
                    <form method="POST">
                        <input type="hidden" name="raise_ticket" value="1">
                        
                        <div class="form-group">
                            <label class="form-label">Subject / Topic</label>
                            <input type="text" name="subject" class="form-control" required placeholder="e.g. Login issues, Task clarification">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Priority Level</label>
                            <select name="priority" class="form-control" required>
                                <option value="Low">Low (General Inquiry)</option>
                                <option value="Medium">Medium (Operational Buffer)</option>
                                <option value="High">High (Blocking Work)</option>
                                <option value="Critical">Critical (Immediate Security Threat)</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Detailed Description</label>
                            <textarea name="description" class="form-control" rows="5" required placeholder="Describe your issue in detail..."></textarea>
                        </div>

                        <button type="submit" class="btn-primary" style="width: 100%; margin-top: 10px;">
                            <i class="fa-solid fa-paper-plane"></i> DISPATCH TICKET
                        </button>
                    </form>
                </div>

                
                <div class="glass-panel">
                    <h3 class="section-heading"><i class="fa-solid fa-clock-rotate-left"></i> Support History</h3>
                    <div style="display: flex; flex-direction: column; gap: 15px;">
                        <?php if(empty($my_tickets)): ?>
                            <p style="text-align: center; color: var(--text-muted); padding: 40px;">No support tickets raised yet.</p>
                        <?php endif; ?>
                        <?php foreach($my_tickets as $t): ?>
                            <div style="border: 1px solid var(--border-color); padding: 15px; border-radius: 6px; background: rgba(255,255,255,0.02);">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                    <h4 style="margin:0;"><?= esc($t['subject']) ?></h4>
                                    <?php
                                    $s_color = '#64748b'; 
                                    if($t['status'] == 'Open') $s_color = '#3b82f6';
                                    if($t['status'] == 'In Progress') $s_color = '#f59e0b';
                                    if($t['status'] == 'Resolved') $s_color = '#10b981';
                                    ?>
                                    <span class="badge" style="background: <?= $s_color ?>; color: #fff;"><?= esc($t['status']) ?></span>
                                </div>
                                <p style="font-size: 0.85rem; color: var(--text-primary); margin-bottom: 10px;"><?= nl2br(esc($t['description'])) ?></p>
                                
                                <?php if($t['admin_comment']): ?>
                                    <div style="background: rgba(16, 185, 129, 0.05); border-left: 3px solid #10b981; padding: 10px; margin-top: 10px; font-size: 0.85rem;">
                                        <strong><i class="fa-solid fa-user-shield"></i> Admin:</strong> <?= esc($t['admin_comment']) ?>
                                    </div>
                                <?php endif; ?>

                                <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 10px;">
                                    ID: #<?= $t['id'] ?> | Priority: <?= esc($t['priority']) ?> | Sent: <?= date('d M, h:i A', strtotime($t['created_at'])) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

            </div>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
