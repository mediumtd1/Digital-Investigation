<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

check_auth('Intern');

$portal = 'intern';
$page_title = 'My Tasks | ' . SITE_NAME;
$intern_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assignment_id'])) {
    
    if (!isset($_POST['csrf_token']) || !validate_csrf($_POST['csrf_token'])) {
        $_SESSION['toast_msg'] = "Security validation failed. Please try again.";
        $_SESSION['toast_type'] = "error";
        header("Location: tasks.php");
        exit;
    }
    $assignment_id = (int) $_POST['assignment_id'];
    $new_status = $_POST['status'];
    $submission_text = trim($_POST['submission_text'] ?? '');

    
    $stmt = $pdo->prepare("SELECT * FROM task_assignments WHERE id = ? AND intern_id = ?");
    $stmt->execute([$assignment_id, $intern_id]);
    $assignment = $stmt->fetch();

    if ($assignment) {
        $submission_file = $assignment['submission_file']; 

        
        if (isset($_FILES['submission_file']) && $_FILES['submission_file']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['submission_file']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'pdf', 'zip', 'doc', 'docx', 'xls', 'xlsx'])) {
                if ($_FILES['submission_file']['size'] <= 25 * 1024 * 1024) {
                    $upload_dir = __DIR__ . '/../uploads/tasks/';
                    if (!is_dir($upload_dir))
                        mkdir($upload_dir, 0755, true);

                    $new_filename = 'submission_' . $assignment_id . '_' . time() . '.' . $ext;
                    if (move_uploaded_file($_FILES['submission_file']['tmp_name'], $upload_dir . $new_filename)) {
                        $submission_file = 'uploads/tasks/' . $new_filename;
                    }
                } else {
                    $_SESSION['toast_msg'] = "File size exceeds the 25MB limit.";
                    $_SESSION['toast_type'] = "error";
                    header("Location: tasks.php");
                    exit;
                }
            }
        }

        if ($new_status === 'Completed') {
            $update_stmt = $pdo->prepare("UPDATE task_assignments SET status = ?, submission_text = ?, submission_file = ?, submitted_at = COALESCE(submitted_at, NOW()) WHERE id = ?");
        } else {
            $update_stmt = $pdo->prepare("UPDATE task_assignments SET status = ?, submission_text = ?, submission_file = ?, submitted_at = NULL WHERE id = ?");
        }
        $update_stmt->execute([$new_status, $submission_text, $submission_file, $assignment_id]);

        $_SESSION['toast_msg'] = "Task proof submitted successfully!";
        $_SESSION['toast_type'] = "success";
    } else {
        $_SESSION['toast_msg'] = "Invalid task assignment.";
        $_SESSION['toast_type'] = "error";
    }

    header("Location: tasks.php");
    exit;
}

$stmt = $pdo->prepare("
    SELECT t.title, t.description, t.assigned_by, t.created_at, t.due_date, t.priority, t.attachment, 
           ta.id as assignment_id, ta.status, ta.submission_text, ta.submission_file, ta.review_status, ta.review_comment
    FROM task_assignments ta 
    JOIN tasks t ON ta.task_id = t.id 
    WHERE ta.intern_id = ?
    ORDER BY t.due_date ASC
");
$stmt->execute([$intern_id]);
$all_tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

$kanban = [
    'Pending' => [],
    'In Progress' => [],
    'Completed' => []
];

$completed_history = [];

$today_str = date('Y-m-d');

foreach ($all_tasks as $task) {
    if ($task['status'] === 'Completed') {
        $completed_history[] = $task;
    }
    $kanban[$task['status']][] = $task;
}

?>

<?php require_once __DIR__ . '/../includes/header.php'; ?>

<div class="dashboard-wrapper">
    <?php include_once __DIR__ . '/_sidebar.php'; ?>

    <main class="main-content">
        <header class="topbar">
            <div class="topbar-left">
                <h2>My Workspace Tasks</h2>
            </div>
        </header>

        <div class="dashboard-content">

            
            <div
                style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; align-items: start; margin-bottom: 40px;">

                
                <div class="glass-panel"
                    style="background: rgba(17, 24, 39, 0.5); padding: 15px; border-top: 4px solid #f87171;">
                    <h3
                        style="margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center;">
                        <span>📋 Pending</span>
                        <span
                            style="background: rgba(255,255,255,0.1); padding: 2px 8px; border-radius: 12px; font-size: 0.8rem;"><?= count($kanban['Pending']) ?></span>
                    </h3>

                    <div style="display: flex; flex-direction: column; gap: 15px;">
                        <?php foreach ($kanban['Pending'] as $t): ?>
                            <?= renderTaskCard($t, $today_str) ?>
                        <?php endforeach; ?>
                        <?php if (empty($kanban['Pending']))
                            echo '<div style="color:var(--text-muted); text-align:center; padding: 20px 0; font-size: 0.9rem;">No pending tasks</div>'; ?>
                    </div>
                </div>

                
                <div class="glass-panel"
                    style="background: rgba(17, 24, 39, 0.5); padding: 15px; border-top: 4px solid #60a5fa;">
                    <h3
                        style="margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center;">
                        <span>🔄 In Progress</span>
                        <span
                            style="background: rgba(255,255,255,0.1); padding: 2px 8px; border-radius: 12px; font-size: 0.8rem;"><?= count($kanban['In Progress']) ?></span>
                    </h3>

                    <div style="display: flex; flex-direction: column; gap: 15px;">
                        <?php foreach ($kanban['In Progress'] as $t): ?>
                            <?= renderTaskCard($t, $today_str) ?>
                        <?php endforeach; ?>
                        <?php if (empty($kanban['In Progress']))
                            echo '<div style="color:var(--text-muted); text-align:center; padding: 20px 0; font-size: 0.9rem;">No tasks in progress</div>'; ?>
                    </div>
                </div>

                
                <div class="glass-panel"
                    style="background: rgba(17, 24, 39, 0.5); padding: 15px; border-top: 4px solid #34d399;">
                    <h3
                        style="margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center;">
                        <span>✅ Completed</span>
                        <span
                            style="background: rgba(255,255,255,0.1); padding: 2px 8px; border-radius: 12px; font-size: 0.8rem;"><?= count($kanban['Completed']) ?></span>
                    </h3>

                    <div style="display: flex; flex-direction: column; gap: 15px;">
                        <?php foreach ($kanban['Completed'] as $t): ?>
                            <?= renderTaskCard($t, $today_str) ?>
                        <?php endforeach; ?>
                        <?php if (empty($kanban['Completed']))
                            echo '<div style="color:var(--text-muted); text-align:center; padding: 20px 0; font-size: 0.9rem;">No completed tasks</div>'; ?>
                    </div>
                </div>
            </div>

            
            <div class="glass-panel">
                <h3 style="margin-bottom: 20px; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">
                    Completed Task History</h3>

                <?php if (count($completed_history) > 0): ?>
                    <table style="width: 100%; text-align: left; border-collapse: collapse; font-size: 0.95rem;">
                        <thead>
                            <tr style="border-bottom: 2px solid var(--border-color); color: var(--text-muted);">
                                <th style="padding: 12px 10px;">Task Title</th>
                                <th style="padding: 12px 10px;">Due Date</th>
                                <th style="padding: 12px 10px;">Admin Review</th>
                                <th style="padding: 12px 10px;">Admin Feedback</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($completed_history as $hist): ?>
                                <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                                    <td style="padding: 12px 10px;"><strong><?= esc($hist['title']) ?></strong></td>
                                    <td style="padding: 12px 10px; color: var(--text-muted);">
                                        <?= date('d M, Y', strtotime($hist['due_date'])) ?></td>
                                    <td style="padding: 12px 10px;">
                                        <?php
                                        $r_status = $hist['review_status'];
                                        if ($r_status == 'Pending')
                                            echo '<span style="color:#f59e0b;"><i class="fa-solid fa-clock"></i> Pending Review</span>';
                                        elseif ($r_status == 'Approved')
                                            echo '<span style="color:var(--success);"><i class="fa-solid fa-check-circle"></i> Approved</span>';
                                        elseif ($r_status == 'Rejected')
                                            echo '<span style="color:var(--danger);"><i class="fa-solid fa-xmark-circle"></i> Rejected</span>';
                                        ?>
                                    </td>
                                    <td style="padding: 12px 10px; color: #9ca3af; font-style: italic;">
                                        <?= esc($hist['review_comment'] ?: 'No comments provided') ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="text-align: center; color: var(--text-muted); margin: 30px 0;">No completed tasks in history.
                    </p>
                <?php endif; ?>
            </div>

        </div>
    </main>
</div>

<?php

function renderTaskCard($t, $today_str)
{
    ob_start();

    $p_color = '#10b981'; 
    if ($t['priority'] == 'Medium')
        $p_color = '#f59e0b';
    if ($t['priority'] == 'High')
        $p_color = '#ef4444';

    $due_class = "color: var(--text-muted);";
    if ($t['due_date'] < $today_str && $t['status'] != 'Completed') {
        $due_class = "color: #ef4444; font-weight: bold;";
    } elseif ($t['due_date'] == $today_str && $t['status'] != 'Completed') {
        $due_class = "color: #f59e0b; font-weight: bold;";
    }

    ?>
    <div
        style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.05); border-radius: 6px; padding: 15px; position: relative; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
        <span
            style="position: absolute; top: 15px; right: 15px; background: <?= $p_color ?>; color: #fff; font-size: 0.65rem; padding: 2px 6px; border-radius: 3px; font-weight: bold; text-transform: uppercase;">
            <?= esc($t['priority']) ?>
        </span>

        <h4 style="margin: 0 0 8px 0; font-size: 1.1rem; color: var(--text-primary); padding-right: 40px;">
            <?= esc($t['title']) ?></h4>
        <p style="font-size: 0.85rem; color: #9ca3af; margin-bottom: 12px; line-height: 1.4;">
            <?= nl2br(esc($t['description'])) ?></p>

        <div style="font-size: 0.8rem; margin-bottom: 12px; display: flex; flex-direction: column; gap: 5px;">
            <div style="color: var(--text-muted);"><i class="fa-solid fa-user-tie"></i> By: <?= esc($t['assigned_by']) ?>
            </div>
            <div style="color: var(--text-muted);"><i class="fa-solid fa-calendar-plus"></i> Set:
                <?= date('d M, Y', strtotime($t['created_at'])) ?></div>
            <div style="<?= $due_class ?>"><i class="fa-regular fa-clock"></i> Due:
                <?= date('d M, Y', strtotime($t['due_date'])) ?></div>
        </div>

        <?php if (!empty($t['attachment'])): ?>
            <a href="../<?= esc($t['attachment']) ?>" download target="_blank"
                style="display: inline-block; font-size: 0.8rem; background: rgba(59, 130, 246, 0.1); color: #60a5fa; padding: 4px 10px; border-radius: 4px; margin-bottom: 15px;">
                <i class="fa-solid fa-paperclip"></i> Download Resource
            </a>
        <?php endif; ?>

        
        <hr style="border: 0; border-top: 1px solid rgba(255,255,255,0.05); margin: 10px 0 15px 0;">
        <form method="POST" enctype="multipart/form-data" style="display: flex; flex-direction: column; gap: 10px;">
            <input type="hidden" name="csrf_token" value="<?= get_csrf_token() ?>">
            <input type="hidden" name="assignment_id" value="<?= $t['assignment_id'] ?>">

            <label style="font-size: 0.75rem; color: var(--text-muted); margin-bottom: -5px;">Execution Status</label>
            <select name="status" class="form-control" style="font-size: 0.85rem; padding: 8px;">
                <option value="Pending" <?= $t['status'] == 'Pending' ? 'selected' : '' ?>>📋 Pending</option>
                <option value="In Progress" <?= $t['status'] == 'In Progress' ? 'selected' : '' ?>>🔄 In Progress</option>
                <option value="Completed" <?= $t['status'] == 'Completed' ? 'selected' : '' ?>>✅ Completed</option>
            </select>

            <label style="font-size: 0.75rem; color: var(--text-muted); margin-bottom: -5px;">Submission Notes</label>
            <textarea name="submission_text" class="form-control" rows="2" placeholder="Explain what you did..."
                style="font-size: 0.85rem; resize: vertical;"><?= esc($t['submission_text'] ?? '') ?></textarea>

            <label style="font-size: 0.75rem; color: var(--text-muted); margin-bottom: -5px;">Proof
                (Screenshot/PDF/Doc/Excel - Max 25MB)</label>
            <input type="file" name="submission_file" class="form-control"
                accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.zip" style="font-size: 0.8rem; padding: 6px;">

            <?php if (!empty($t['submission_file'])): ?>
                <div style="font-size: 0.75rem;">
                    <i class="fa-solid fa-cloud-arrow-down"></i> <a href="../<?= esc($t['submission_file']) ?>"
                        target="_blank">Current Proof</a>
                </div>
            <?php endif; ?>

            <button type="submit" class="btn-primary" style="padding: 10px; font-size: 0.85rem; border-radius: 4px;">
                <i class="fa-solid fa-cloud-arrow-up"></i> Submit Proof
            </button>
        </form>

    </div>
    <?php
    return ob_get_clean();
}
?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>