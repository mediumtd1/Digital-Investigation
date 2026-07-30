<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

check_auth('Admin');

$portal = 'admin';
$page_title = 'Task Management | ' . SITE_NAME;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_task'])) {
    
    if (!isset($_POST['csrf_token']) || !validate_csrf($_POST['csrf_token'])) {
        die("Security Validation Failed (CSRF). Access Denied.");
    }
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $priority = $_POST['priority'] ?? 'Medium';
    $assign_to = $_POST['assign_to'] ?? 'All';
    $due_date = $_POST['due_date'] ?? '';
    
    
    $attachment_path = '';
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
        $tmp_name = $_FILES['attachment']['tmp_name'];
        $ext = strtolower(pathinfo($_FILES['attachment']['name'], PATHINFO_EXTENSION));
        
        if (in_array($ext, ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'zip'])) {
            
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $tmp_name);
            finfo_close($finfo);
            $allowed_mimes = [
                'application/pdf', 
                'application/msword', 
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'image/jpeg', 
                'image/png', 
                'application/zip',
                'application/x-zip-compressed'
            ];
            
            if ($_FILES['attachment']['size'] > 25 * 1024 * 1024) {
                $form_error = "File size exceeds the 25MB limit.";
            } elseif (!in_array($mime, $allowed_mimes)) {
                $form_error = "Security Violation: Invalid file content.";
            } else {
                $upload_dir = __DIR__ . '/../uploads/tasks/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
                $new_name = uniqid('task_', true) . '.' . $ext;
                if (move_uploaded_file($tmp_name, $upload_dir . $new_name)) {
                    $attachment_path = 'uploads/tasks/' . $new_name;
                }
            }
        }
    }

    if (!empty($title) && !empty($description) && !empty($due_date) && empty($form_error)) {
        
        $stmt = $pdo->prepare("INSERT INTO tasks (title, description, priority, assigned_by, due_date, attachment) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$title, $description, $priority, $_SESSION['user_name'], $due_date, $attachment_path]);
        $task_id = $pdo->lastInsertId();

        
        $interns_to_assign = [];
        if ($assign_to === 'All') {
            $interns_to_assign = $pdo->query("SELECT id, name, email FROM interns WHERE status = 'Active'")->fetchAll();
        } elseif (strpos($assign_to, 'Batch:') === 0) {
            $batch_name = substr($assign_to, 6);
            $stmt_batch = $pdo->prepare("SELECT id, name, email FROM interns WHERE status = 'Active' AND batch = ?");
            $stmt_batch->execute([$batch_name]);
            $interns_to_assign = $stmt_batch->fetchAll();
        } else {
            $stmt_intern = $pdo->prepare("SELECT id, name, email FROM interns WHERE id = ?");
            $stmt_intern->execute([(int)$assign_to]);
            $interns_to_assign = $stmt_intern->fetchAll();
        }

        $assign_stmt = $pdo->prepare("INSERT INTO task_assignments (task_id, intern_id) VALUES (?, ?)");
        foreach ($interns_to_assign as $i) {
            $assign_stmt->execute([$task_id, $i['id']]);
            
            
            if (!empty($i['email'])) {
                $subject = "New Task Assigned: " . $title;
                $body = "Dear " . $i['name'] . ",\r\n\r\n"
                      . "A new task has been assigned to you by HQ Admin (" . ($_SESSION['user_name'] ?? 'HQ Admin') . ").\r\n\r\n"
                      . "----------------------------------------\r\n"
                      . "Task: " . $title . "\r\n"
                      . "Priority: " . $priority . "\r\n"
                      . "Due Date: " . date('d M, Y', strtotime($due_date)) . "\r\n"
                      . "----------------------------------------\r\n\r\n"
                      . "Description:\r\n" . $description . "\r\n\r\n"
                      . "Please log in to your Intern Portal to view details and submit your work before the deadline.\r\n\r\n"
                      . "Regards,\r\nDigital Investigation HQ";
                send_custom_email($i['email'], $subject, $body);
            }
        }
        
        log_audit($pdo, $_SESSION['user_id'], 'Admin', 'Created task: ' . $title);
        $_SESSION['toast_msg'] = "Task assigned successfully.";
        $_SESSION['toast_type'] = "success";
        header("Location: tasks.php");
        exit;
    } else {
        $form_error = $form_error ?? "Please fill in all required fields.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_task_id'])) {
    if (!isset($_POST['csrf_token']) || !validate_csrf($_POST['csrf_token'])) {
        die("Security Validation Failed (CSRF). Access Denied.");
    }
    $del_id = (int)$_POST['delete_task_id'];
    
    
    $stmt = $pdo->prepare("SELECT attachment FROM tasks WHERE id = ?");
    $stmt->execute([$del_id]);
    $task = $stmt->fetch();
    if ($task && !empty($task['attachment']) && file_exists(__DIR__ . '/../' . $task['attachment'])) {
        @unlink(__DIR__ . '/../' . $task['attachment']);
    }
    
    $pdo->prepare("DELETE FROM tasks WHERE id = ?")->execute([$del_id]);
    $pdo->prepare("DELETE FROM task_assignments WHERE task_id = ?")->execute([$del_id]);
    
    log_audit($pdo, $_SESSION['user_id'], 'Admin', 'Deleted task ID: ' . $del_id);
    $_SESSION['toast_msg'] = "Task deleted successfully.";
    $_SESSION['toast_type'] = "success";
    header("Location: tasks.php");
    exit;
}

$interns = $pdo->query("SELECT id, name, intern_id FROM interns WHERE status = 'Active'")->fetchAll();
$batches = $pdo->query("SELECT DISTINCT batch FROM interns WHERE batch IS NOT NULL AND batch != '' ORDER BY batch ASC")->fetchAll(PDO::FETCH_COLUMN);

$page = isset($_GET['p']) ? max(1, (int)$_GET['p']) : 1;
$limit = 15;
$offset = ($page - 1) * $limit;

$search = trim($_GET['search'] ?? '');
$filter_priority = $_GET['filter_priority'] ?? '';

$where_clauses = ["1=1"];
$params = [];

if ($search !== '') {
    $where_clauses[] = "(t.title LIKE ?)";
    $params[] = "%$search%";
}
if ($filter_priority !== '') {
    $where_clauses[] = "t.priority = ?";
    $params[] = $filter_priority;
}

$where_sql = implode(' AND ', $where_clauses);

$count_query = "SELECT COUNT(DISTINCT t.id) FROM tasks t WHERE $where_sql";
$stmt = $pdo->prepare($count_query);
$stmt->execute($params);
$total_rows = $stmt->fetchColumn();
$total_pages = ceil($total_rows / $limit);

$query = "
    SELECT t.id, t.title, t.priority, t.due_date, t.created_at,
           COUNT(ta.id) as total_assigned,
           SUM(CASE WHEN ta.status = 'Completed' THEN 1 ELSE 0 END) as total_completed
    FROM tasks t
    LEFT JOIN task_assignments ta ON t.id = ta.task_id
    WHERE $where_sql
    GROUP BY t.id
    ORDER BY t.created_at DESC
    LIMIT $limit OFFSET $offset
";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<?php require_once __DIR__ . '/../includes/header.php'; ?>

<div class="dashboard-wrapper">
    <?php include_once __DIR__ . '/_sidebar.php'; ?>

    <main class="main-content">
        <header class="topbar">
            <div class="topbar-left">
                <h2>Task Orchestration</h2>
            </div>
        </header>

        <div class="dashboard-content">
            
            <?php if (!empty($form_error)): ?>
                <div style="background: rgba(220, 38, 38, 0.2); border-left: 4px solid var(--danger); padding: 15px; margin-bottom: 20px;">
                    <i class="fa-solid fa-triangle-exclamation" style="color: var(--danger);"></i> <?= esc($form_error) ?>
                </div>
            <?php endif; ?>

            <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 30px; align-items: start;">
                
                
                <div class="glass-panel" style="border-top: 4px solid #eab308;">
                    <h3 style="margin-bottom: 20px; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">Assign New Task</h3>
                    <form method="POST" enctype="multipart/form-data">
                        <?php csrf_field(); ?>
                        <input type="hidden" name="create_task" value="1">
                        
                        <div class="form-group">
                            <label class="form-label">Task Title *</label>
                            <input type="text" name="title" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Description *</label>
                            <textarea name="description" class="form-control" rows="4" required></textarea>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                            <div class="form-group">
                                <label class="form-label">Priority</label>
                                <select name="priority" class="form-control">
                                    <option value="High" style="color:#ef4444; font-weight:bold;">High</option>
                                    <option value="Medium" style="color:#f59e0b; font-weight:bold;" selected>Medium</option>
                                    <option value="Low" style="color:#10b981; font-weight:bold;">Low</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Due Date *</label>
                                <input type="date" name="due_date" class="form-control" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Assign To</label>
                            <select name="assign_to" class="form-control" required>
                                <option value="All">== All Active Interns ==</option>
                                <?php if (!empty($batches)): ?>
                                    <optgroup label="Assign to Specific Batch">
                                        <?php foreach ($batches as $b): ?>
                                            <option value="Batch:<?= esc($b) ?>">Batch: <?= esc($b) ?></option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                <?php endif; ?>
                                <optgroup label="Assign to Individual Intern">
                                    <?php foreach($interns as $i): ?>
                                        <option value="<?= $i['id'] ?>"><?= esc($i['name']) ?> (<?= esc($i['intern_id']) ?>)</option>
                                    <?php endforeach; ?>
                                </optgroup>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Attachment (Optional, max 25MB)</label>
                            <input type="file" name="attachment" class="form-control" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.zip">
                        </div>

                        <button type="submit" class="btn-primary" style="width: 100%; background: #eab308; color: black; font-weight:bold;">
                            <i class="fa-solid fa-paper-plane"></i> Dispatch Task
                        </button>
                    </form>
                </div>

                
                <div class="glass-panel">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 20px; align-items: center; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">
                        <h3 style="margin: 0;">Task Directory</h3>
                        
                        <form method="GET" style="display: flex; gap: 10px;">
                            <select name="filter_priority" class="form-control" style="width: auto; padding: 6px;" onchange="this.form.submit()">
                                <option value="">All Priorities</option>
                                <option value="High" <?= $filter_priority == 'High' ? 'selected' : '' ?>>High</option>
                                <option value="Medium" <?= $filter_priority == 'Medium' ? 'selected' : '' ?>>Medium</option>
                                <option value="Low" <?= $filter_priority == 'Low' ? 'selected' : '' ?>>Low</option>
                            </select>
                            <input type="text" name="search" class="form-control" placeholder="Search title..." value="<?= esc($search) ?>" style="width: 150px; padding: 6px;">
                            <button type="submit" class="btn-primary" style="padding: 6px 12px;"><i class="fa-solid fa-magnifying-glass"></i></button>
                        </form>
                    </div>

                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Task Title</th>
                                <th>Priority</th>
                                <th>Due Date</th>
                                <th>Completion</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($tasks as $t): 
                                $today_str = date('Y-m-d');
                                $is_overdue = ($t['due_date'] < $today_str);
                                $row_style = $is_overdue ? 'background: rgba(220, 38, 38, 0.05);' : '';
                                
                                $p_color = '#10b981';
                                if ($t['priority'] == 'Medium') $p_color = '#f59e0b';
                                if ($t['priority'] == 'High') $p_color = '#ef4444';
                                
                                $comp_pct = $t['total_assigned'] > 0 ? round(($t['total_completed'] / $t['total_assigned']) * 100) : 0;
                            ?>
                            <tr style="<?= $row_style ?>">
                                <td style="font-weight: 500;">
                                    <?= esc($t['title']) ?>
                                    <?php if($is_overdue) echo ' <i class="fa-solid fa-triangle-exclamation" style="color:var(--danger);" title="Overdue"></i>'; ?>
                                </td>
                                <td>
                                    <span style="border-left: 3px solid <?= $p_color ?>; padding-left: 8px; font-size: 0.85rem; color: <?= $p_color ?>; font-weight:bold;"><?= esc($t['priority']) ?></span>
                                </td>
                                <td style="<?= $is_overdue ? 'color:var(--danger);' : 'color:var(--text-muted);' ?>"><?= date('d M, Y', strtotime($t['due_date'])) ?></td>
                                <td>
                                    <div style="font-size: 0.8rem; margin-bottom: 3px; color: var(--text-muted);"><?= $t['total_completed'] ?> / <?= $t['total_assigned'] ?> (<?= $comp_pct ?>%)</div>
                                    <div style="width: 100%; height: 6px; background: rgba(255,255,255,0.1); border-radius: 3px; overflow: hidden;">
                                        <div style="width: <?= $comp_pct ?>%; height: 100%; background: #60a5fa; transition: width 0.3s;"></div>
                                    </div>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 8px;">
                                        <a href="task_detail.php?id=<?= $t['id'] ?>" class="btn-primary" style="padding: 5px 12px; font-size: 0.8rem; text-decoration: none; background: #3b82f6;">
                                            Manage <i class="fa-solid fa-arrow-right"></i>
                                        </a>
                                        <form method="POST" action="tasks.php" style="display:inline;" onsubmit="return confirm('Permanently delete this task and all submissions? This cannot be undone.')">
                                            <?php csrf_field(); ?>
                                            <input type="hidden" name="delete_task_id" value="<?= $t['id'] ?>">
                                            <button type="submit" class="btn-primary" style="padding: 5px 12px; font-size: 0.8rem; background: #ef4444; border-color: #ef4444; border: none; cursor: pointer;">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if(empty($tasks)): ?>
                                <tr><td colspan="5" style="text-align: center; color: var(--text-muted);">No tasks found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>

                    
                    <?php if ($total_pages > 1): ?>
                    <div style="margin-top: 20px; display: flex; justify-content: flex-end; gap: 5px;">
                        <?php for($i = 1; $i <= $total_pages; $i++): ?>
                            <a href="?search=<?= urlencode($search) ?>&filter_priority=<?= urlencode($filter_priority) ?>&p=<?= $i ?>" style="padding: 6px 12px; background: <?= $page == $i ? 'var(--accent-highlight)' : 'rgba(255,255,255,0.05)' ?>; color: <?= $page == $i ? '#fff' : 'var(--text-muted)' ?>; border-radius: 4px; text-decoration: none;">
                                <?= $i ?>
                            </a>
                        <?php endfor; ?>
                    </div>
                    <?php endif; ?>
                    
                </div>
            </div>

        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
