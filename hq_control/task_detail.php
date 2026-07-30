<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

check_auth('Admin');

$portal = 'admin';
$page_title = 'Task Review | ' . SITE_NAME;

$task_id = (int)$_GET['id'] ?? 0;
if (!$task_id) {
    header("Location: tasks.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM tasks WHERE id = ?");
$stmt->execute([$task_id]);
$task = $stmt->fetch();

if (!$task) {
    header("Location: tasks.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assignment_id'])) {
    $a_id = (int)$_POST['assignment_id'];
    $r_status = $_POST['review_status'];
    $r_comment = trim($_POST['review_comment'] ?? '');
    $r_score = (int)($_POST['score'] ?? 15);

    $stmt = $pdo->prepare("UPDATE task_assignments SET review_status = ?, review_comment = ?, score = ?, reviewed_by = ? WHERE id = ?");
    $stmt->execute([$r_status, $r_comment, $r_score, $_SESSION['user_name'], $a_id]);
    
    
    try {
        $assign_stmt = $pdo->prepare("SELECT ta.intern_id, t.title FROM task_assignments ta JOIN tasks t ON ta.task_id = t.id WHERE ta.id = ?");
        $assign_stmt->execute([$a_id]);
        $assign_detail = $assign_stmt->fetch();
        if ($assign_detail) {
            $notif_msg = "Your submission for task '" . $assign_detail['title'] . "' has been reviewed and " . strtolower($r_status) . " (Score: " . $r_score . ").";
            $notif_link = "tasks.php";
            
            $notif_stmt = $pdo->prepare("INSERT INTO notifications (intern_id, message, link) VALUES (?, ?, ?)");
            $notif_stmt->execute([$assign_detail['intern_id'], $notif_msg, $notif_link]);
        }
    } catch (Exception $e) {}

    $_SESSION['toast_msg'] = "Review submitted successfully.";
    $_SESSION['toast_type'] = "success";
    header("Location: task_detail.php?id=" . $task_id);
    exit;
}

$stmt = $pdo->prepare("
    SELECT ta.id, ta.intern_id, ta.task_id, ta.status, ta.submission_text,
           ta.submission_file, ta.review_status, ta.review_comment, ta.score,
           i.name as intern_name, i.intern_id as intern_uuid
    FROM task_assignments ta 
    JOIN interns i ON ta.intern_id = i.id 
    WHERE ta.task_id = ?
    ORDER BY ta.status DESC, ta.id DESC
");
$stmt->execute([$task_id]);
$assignments = $stmt->fetchAll();

?>

<?php require_once __DIR__ . '/../includes/header.php'; ?>

<div class="dashboard-wrapper">
    <?php include_once __DIR__ . '/_sidebar.php'; ?>

    <main class="main-content">
        <header class="topbar">
            <div class="topbar-left">
                <h2><a href="tasks.php" style="color: var(--text-muted);"><i class="fa-solid fa-arrow-left"></i> Tasks</a> / Details</h2>
            </div>
        </header>

        <div class="dashboard-content">

            
            <div class="glass-panel" style="margin-bottom: 30px; border-top: 4px solid #60a5fa;">
                <div style="display: flex; justify-content: space-between; align-items: start;">
                    <div>
                        <h2 style="margin: 0 0 10px 0;"><?= esc($task['title']) ?></h2>
                        <p style="color: var(--text-muted); margin-bottom: 15px; font-size: 0.95rem; max-width: 800px;">
                            <?= nl2br(esc($task['description'])) ?>
                        </p>
                        <div style="display: flex; gap: 20px; font-size: 0.9rem;">
                            <div><i class="fa-solid fa-calendar-plus" style="color: #64748b;"></i> Created: <?= date('d M, Y', strtotime($task['created_at'])) ?></div>
                            <div style="color: <?= $task['due_date'] < date('Y-m-d') ? '#ef4444' : 'inherit' ?>"><i class="fa-regular fa-clock"></i> Due: <?= date('d M, Y', strtotime($task['due_date'])) ?></div>
                            <div><i class="fa-solid fa-flag" style="color: <?= $task['priority'] == 'High' ? '#ef4444' : ($task['priority'] == 'Medium' ? '#f59e0b' : '#10b981') ?>;"></i> Priority: <?= esc($task['priority']) ?></div>
                        </div>
                    </div>
                    <?php if(!empty($task['attachment'])): ?>
                    <div>
                        <a href="../<?= esc($task['attachment']) ?>" download target="_blank" class="btn-primary" style="background-color: #64748b;">
                            <i class="fa-solid fa-download"></i> Task Resource
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            
            <div class="glass-panel">
                <h3 style="margin-bottom: 20px; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">Intern Submissions & Review Matrix</h3>
                
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Intern</th>
                            <th>Status / Notes</th>
                            <th>Submitted File</th>
                            <th>Review Status</th>
                            <th style="width: 320px;">Admin Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($assignments as $a): 
                            $status_color = '#64748b';
                            if ($a['status'] == 'In Progress') $status_color = '#3b82f6';
                            if ($a['status'] == 'Completed') $status_color = '#10b981';
                        ?>
                        <tr>
                            <td>
                                <strong><?= esc($a['intern_name']) ?></strong><br>
                                <span style="font-family: monospace; color: #93c5fd; font-size: 0.85rem;"><?= esc($a['intern_uuid']) ?></span>
                            </td>
                            <td>
                                <div style="margin-bottom: 6px;">
                                    <span style="display: inline-block; padding: 2px 8px; border-radius: 12px; font-size: 0.8rem; font-weight: bold; background: rgba(255,255,255,0.1); color: <?= $status_color ?>;">
                                        <?= esc($a['status']) ?>
                                    </span>
                                </div>
                                <?php if (!empty($a['submission_text'])): ?>
                                    <div style="background: rgba(0,0,0,0.2); padding: 8px; border-radius: 4px; font-size: 0.85rem; color: #cbd5e1; border-left: 3px solid <?= $status_color ?>; max-width: 250px; word-break: break-word;">
                                        <?= nl2br(esc($a['submission_text'])) ?>
                                    </div>
                                <?php else: ?>
                                    <span style="color: var(--text-muted); font-size: 0.85rem; font-style: italic;">No notes.</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($a['submission_file'])): 
                                    $file_path = $a['submission_file'];
                                    $file_ext  = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
                                    $file_name = basename($file_path);
                                    $file_url  = '../' . $file_path;
                                    
                                    $icon = 'fa-file';
                                    $icon_color = '#94a3b8';
                                    if (in_array($file_ext, ['jpg','jpeg','png','gif','webp'])) { $icon = 'fa-file-image'; $icon_color = '#60a5fa'; }
                                    elseif ($file_ext === 'pdf')                                { $icon = 'fa-file-pdf';   $icon_color = '#ef4444'; }
                                    elseif (in_array($file_ext, ['doc','docx']))               { $icon = 'fa-file-word';  $icon_color = '#3b82f6'; }
                                    elseif (in_array($file_ext, ['xls','xlsx']))               { $icon = 'fa-file-excel'; $icon_color = '#10b981'; }
                                    elseif ($file_ext === 'zip')                               { $icon = 'fa-file-zipper'; $icon_color = '#f59e0b'; }
                                ?>
                                    <div style="display: flex; flex-direction: column; gap: 8px; align-items: flex-start;">
                                        <?php if (in_array($file_ext, ['jpg','jpeg','png','gif','webp'])): ?>
                                            <a href="<?= esc($file_url) ?>" target="_blank">
                                                <img src="<?= esc($file_url) ?>" alt="Submission" style="max-width: 120px; max-height: 80px; border-radius: 6px; border: 1px solid rgba(255,255,255,0.1); object-fit: cover;">
                                            </a>
                                        <?php endif; ?>
                                        <a href="<?= esc($file_url) ?>" target="_blank" download
                                           style="display: inline-flex; align-items: center; gap: 6px; font-size: 0.82rem; background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.12); color: <?= $icon_color ?>; padding: 5px 10px; border-radius: 6px; text-decoration: none; transition: background 0.2s;"
                                           onmouseover="this.style.background='rgba(255,255,255,0.12)'" onmouseout="this.style.background='rgba(255,255,255,0.06)'">
                                            <i class="fa-solid <?= $icon ?>"></i>
                                            <span style="max-width: 130px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?= esc($file_name) ?>">
                                                <?= esc($file_name) ?>
                                            </span>
                                            <i class="fa-solid fa-download" style="font-size: 0.75rem; opacity: 0.6;"></i>
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <span style="color: var(--text-muted); font-size: 0.82rem; font-style: italic;">No file attached</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                $rs = $a['review_status'];
                                if ($rs == 'Pending') echo '<span style="color: #f59e0b;"><i class="fa-solid fa-clock"></i> Pending</span>';
                                elseif ($rs == 'Approved') echo '<span style="color: #10b981;"><i class="fa-solid fa-check"></i> Approved</span>';
                                elseif ($rs == 'Rejected') echo '<span style="color: #ef4444;"><i class="fa-solid fa-xmark"></i> Rejected</span>';
                                if (!empty($a['review_comment'])) {
                                    echo '<div style="font-size: 0.8rem; color: #9ca3af; margin-top: 5px; font-style:italic;">"' . esc($a['review_comment']) . '"</div>';
                                }
                                ?>
                            </td>
                            <td>
                                <?php if ($a['status'] === 'Completed'): ?>
                                <form method="POST" style="display: flex; flex-direction: column; gap: 8px;">
                                    <input type="hidden" name="assignment_id" value="<?= $a['id'] ?>">
                                    <textarea name="review_comment" rows="2" class="form-control" style="font-size: 0.85rem; padding: 5px; min-height: 40px;" placeholder="Leave feedback..."><?= esc($a['review_comment'] ?? '') ?></textarea>
                                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                                        <label class="form-label" style="font-size: 0.8rem; margin: 0; white-space: nowrap;">Points (Default 15):</label>
                                        <input type="number" name="score" value="<?= $a['score'] ? esc($a['score']) : 15 ?>" min="0" max="100" class="form-control" style="font-size: 0.85rem; padding: 4px 8px; height: 32px; background: rgba(255,255,255,0.05); color: #fff; border: 1px solid var(--border-color); border-radius: 4px;">
                                    </div>
                                    <div style="display: flex; gap: 10px;">
                                        <button type="submit" name="review_status" value="Approved" class="btn-primary" style="padding: 5px 10px; font-size: 0.8rem; background: #10b981; flex: 1;">
                                            <i class="fa-solid fa-check"></i> Approve
                                        </button>
                                        <button type="submit" name="review_status" value="Rejected" class="btn-primary" style="padding: 5px 10px; font-size: 0.8rem; background: #ef4444; flex: 1;">
                                            <i class="fa-solid fa-xmark"></i> Reject
                                        </button>
                                    </div>
                                </form>
                                <?php else: ?>
                                    <span style="color: var(--text-muted); font-size: 0.85rem;">Not submitted yet.</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if(empty($assignments)): ?>
                            <tr><td colspan="4" style="text-align: center; color: var(--text-muted);">No interns assigned to this task.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
