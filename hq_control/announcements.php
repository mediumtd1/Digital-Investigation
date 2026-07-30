<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

check_auth('Admin');

$portal = 'admin';
$page_title = 'System Announcements | ' . SITE_NAME;

$success_msg = '';
$error_msg = '';

if (isset($_GET['deleted'])) {
    $success_msg = "Announcement deleted successfully.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'publish') {
    
    if (!isset($_POST['csrf_token']) || !validate_csrf($_POST['csrf_token'])) {
        die("Security Validation Failed (CSRF). Access Denied.");
    }
    
    $title = trim($_POST['title']);
    $message = trim($_POST['message']);
    $target_role = $_POST['target_role'];
    $priority = $_POST['priority'];
    
    $attachment = null;
    
    
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
        $allowed_exts = ['pdf', 'doc', 'docx', 'jpg', 'png', 'zip', 'txt'];
        $file_name = $_FILES['attachment']['name'];
        $file_tmp = $_FILES['attachment']['tmp_name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        if (in_array($file_ext, $allowed_exts)) {
            $new_name = uniqid('ann_') . '.' . $file_ext;
            $upload_path = __DIR__ . '/../uploads/announcements/' . $new_name;
            
            
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $file_tmp);
            finfo_close($finfo);
            $allowed_mimes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'image/jpeg', 'image/png', 'application/zip', 'text/plain'];
            
            if (in_array($mime, $allowed_mimes) && move_uploaded_file($file_tmp, $upload_path)) {
                $attachment = 'uploads/announcements/' . $new_name;
            } else {
                $error_msg = "Failed to upload file or invalid file content.";
            }
        } else {
            $error_msg = "Invalid file type. Allowed: PDF, DOC, JPG, PNG, ZIP, TXT.";
        }
    }
    
    if (empty($error_msg) && !empty($title) && !empty($message)) {
        $stmt = $pdo->prepare("INSERT INTO announcements (title, message, target_role, priority, created_by, attachment) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$title, $message, $target_role, $priority, 'Admin', $attachment]);
        $announcement_id = $pdo->lastInsertId();
        $success_msg = "Announcement published successfully!";

        
        if (in_array($target_role, ['All', 'Interns_Only'])) {
            try {
                $interns = $pdo->query("SELECT id, name, email FROM interns WHERE status = 'Active'")->fetchAll();
                $subject = "New HQ Announcement: " . $title;
                
                $abs_attachment = !empty($attachment) ? __DIR__ . '/../' . $attachment : '';
                foreach ($interns as $i) {
                    
                    try {
                        $ins_notif = $pdo->prepare("INSERT INTO notifications (intern_id, message, link) VALUES (?, ?, ?)");
                        $ins_notif->execute([$i['id'], "New announcement: " . $title, "announcements.php?id=" . $announcement_id]);
                    } catch (Exception $ne) {}

                    if (!empty($i['email'])) {
                        $body = "Dear " . $i['name'] . ",\r\n\r\n"
                              . "HQ Command has published a new announcement targeted at interns.\r\n\r\n"
                              . "----------------------------------------\r\n"
                              . "Title: " . $title . "\r\n"
                              . "Priority Level: " . $priority . "\r\n"
                              . "----------------------------------------\r\n\r\n"
                              . "Message:\r\n" . $message . "\r\n\r\n"
                              . (!empty($attachment) ? "An attachment has been included with this announcement.\r\n\r\n" : "")
                              . "Please log in to your Intern Portal to read full announcements and access materials.\r\n\r\n"
                              . "Regards,\r\nDigital Investigation HQ";
                        send_custom_email($i['email'], $subject, $body, $abs_attachment ?: null);
                    }
                }
            } catch (Exception $e) {}
        }
    } elseif (empty($error_msg)) {
        $error_msg = "Please fill in title and message.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_ann_id'])) {
    if (!validate_csrf($_POST['csrf_token'] ?? '')) {
        die("CSRF check failed.");
    }
    $del_id = (int)$_POST['delete_ann_id'];
    $stmt = $pdo->prepare("SELECT attachment FROM announcements WHERE id = ?");
    $stmt->execute([$del_id]);
    $ann = $stmt->fetch();
    if ($ann) {
        if (!empty($ann['attachment']) && file_exists(__DIR__ . '/../' . $ann['attachment'])) {
            unlink(__DIR__ . '/../' . $ann['attachment']);
        }
        $pdo->prepare("DELETE FROM announcements WHERE id = ?")->execute([$del_id]);
        header("Location: announcements.php?deleted=1");
        exit;
    }
}

$announcements = $pdo->query("SELECT * FROM announcements ORDER BY created_at DESC")->fetchAll();

?>

<?php require_once __DIR__ . '/../includes/header.php'; ?>

<div class="dashboard-wrapper">
    <?php include_once __DIR__ . '/_sidebar.php'; ?>

    <main class="main-content">
        <header class="topbar">
            <div class="topbar-left">
                <h2>Manage Announcements & Assignments</h2>
            </div>
            <div class="topbar-right">
                <div class="user-profile">
                    <div style="width: 40px; height: 40px; background: #eab308; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: black; font-weight: bold; font-size: 1.2rem;">A</div>
                    <div class="user-info">
                        <span class="user-name">System Administrator</span>
                        <span class="user-role">Super Admin</span>
                    </div>
                </div>
            </div>
        </header>

        <div class="dashboard-content">
            <?php if (!empty($success_msg)): ?>
            <div style="background: rgba(16, 185, 129, 0.2); border: 1px solid var(--success); padding: 15px; border-radius: 8px; margin-bottom: 20px; color: #6ee7b7;">
                <i class="fa-solid fa-circle-check"></i> <?= esc($success_msg) ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($error_msg)): ?>
            <div style="background: rgba(220, 38, 38, 0.2); border: 1px solid var(--danger); padding: 15px; border-radius: 8px; margin-bottom: 20px; color: #fca5a5;">
                <i class="fa-solid fa-circle-exclamation"></i> <?= esc($error_msg) ?>
            </div>
            <?php endif; ?>

            <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 20px;">
                
                
                <div class="glass-panel">
                    <h3 style="margin-bottom: 20px; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">Publish New</h3>
                    
                    <form method="POST" enctype="multipart/form-data">
                        <?php csrf_field(); ?>
                        <input type="hidden" name="action" value="publish">
                        
                        <div class="form-group" style="margin-bottom: 15px;">
                            <label class="form-label">Title</label>
                            <input type="text" name="title" class="form-control" required placeholder="e.g. New Training Guidelines">
                        </div>
                        
                        <div class="form-group" style="margin-bottom: 15px;">
                            <label class="form-label">Message Details</label>
                            <textarea name="message" class="form-control" rows="5" required placeholder="Type the announcement here..."></textarea>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                            <div class="form-group">
                                <label class="form-label">Audience</label>
                                <select name="target_role" class="form-control" required>
                                    <option value="All">All Entities</option>
                                    <option value="Officers_Only">Officers Only</option>
                                    <option value="Interns_Only">Interns Only</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Priority Level</label>
                                <select name="priority" class="form-control" required>
                                    <option value="General">General</option>
                                    <option value="Info" style="color: #60a5fa;">Information</option>
                                    <option value="Urgent" style="color: #ef4444;">Urgent Update</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group" style="margin-bottom: 25px;">
                            <label class="form-label"><i class="fa-solid fa-paperclip"></i> Upload Assignment/Material (Optional)</label>
                            <input type="file" name="attachment" class="form-control" style="padding: 10px; background: rgba(0,0,0,0.2);">
                            <small style="color: var(--text-muted); display: block; margin-top: 5px;">Supported: PDF, DOC, JPG, PNG, ZIP, TXT (Max 5MB)</small>
                        </div>

                        <button type="submit" class="btn-primary" style="width: 100%;"><i class="fa-solid fa-paper-plane"></i> Publish Now</button>
                    </form>
                </div>

                
                <div class="glass-panel">
                    <h3 style="margin-bottom: 20px; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">Recent Publications</h3>
                    
                    <div style="max-height: 600px; overflow-y: auto; padding-right: 10px;">
                        <?php if(count($announcements) > 0): ?>
                            <?php foreach($announcements as $ann): 
                                $bg_border = '1px solid rgba(255,255,255,0.05)';
                                $priority_color = '#94a3b8'; 
                                if ($ann['priority'] == 'Info') { $bg_border = '1px solid rgba(96, 165, 250, 0.4)'; $priority_color = '#60a5fa'; }
                                if ($ann['priority'] == 'Urgent') { $bg_border = '1px solid rgba(239, 68, 68, 0.4)'; $priority_color = '#ef4444'; }
                            ?>
                            <div style="background: rgba(0,0,0,0.2); border: <?= $bg_border ?>; padding: 15px; border-radius: 8px; margin-bottom: 15px; position: relative;">
                                
                                <form method="POST" style="position: absolute; top: 15px; right: 15px;" onsubmit="return confirm('Delete this announcement?');">
                                    <?php csrf_field(); ?>
                                    <input type="hidden" name="delete_ann_id" value="<?= $ann['id'] ?>">
                                    <button type="submit" style="background:none; border:none; cursor:pointer; color:var(--text-muted); transition:color 0.3s;" onmouseover="this.style.color='var(--danger)'" onmouseout="this.style.color='var(--text-muted)'">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </form>

                                <div style="display: flex; gap: 10px; margin-bottom: 10px; align-items: center;">
                                    <span class="badge" style="background: <?= $priority_color ?>; color: #fff;"><i class="fa-solid fa-circle-info"></i> <?= esc($ann['priority']) ?></span>
                                    <span class="badge" style="background: #eab308; color: #000;"><i class="fa-solid fa-users"></i> <?= esc(str_replace('_Only', '', $ann['target_role'])) ?></span>
                                    <span style="font-size: 0.8rem; color: var(--text-muted);"><i class="fa-regular fa-clock"></i> <?= date('d M, Y h:i A', strtotime($ann['created_at'])) ?></span>
                                </div>
                                <h4 style="margin: 0 0 10px 0; font-size: 1.1rem; color: #f8fafc;"><?= esc($ann['title']) ?></h4>
                                <p style="margin: 0 0 15px 0; font-size: 0.95rem; color: #cbd5e1; line-height: 1.5; white-space: pre-wrap;"><?= esc($ann['message']) ?></p>
                                
                                <?php if(!empty($ann['attachment'])): 
                                    $ext = pathinfo($ann['attachment'], PATHINFO_EXTENSION);
                                    $icon = 'fa-file';
                                    if(in_array($ext, ['pdf'])) $icon = 'fa-file-pdf';
                                    if(in_array($ext, ['doc','docx'])) $icon = 'fa-file-word';
                                    if(in_array($ext, ['jpg','png'])) $icon = 'fa-file-image';
                                    if(in_array($ext, ['zip'])) $icon = 'fa-file-zipper';
                                ?>
                                <div style="border-top: 1px solid rgba(255,255,255,0.05); padding-top: 10px;">
                                    <a href="../<?= esc($ann['attachment']) ?>" download class="btn-primary" style="padding: 5px 12px; font-size: 0.85rem; background: #475569;"><i class="fa-solid <?= $icon ?>"></i> Download Attachment</a>
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p style="color: var(--text-muted); text-align: center; padding: 30px;">No announcements published yet.</p>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
