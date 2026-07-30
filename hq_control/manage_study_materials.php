<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
check_auth('Admin');

$table = 'study_materials';
$page_title = 'Study Materials Management | HQ';
$success = ''; $error = '';

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS study_materials (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        file_path VARCHAR(255) DEFAULT NULL,
        is_visible TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
} catch (PDOException $e) {}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !validate_csrf($_POST['csrf_token'])) {
        $error = "Security validation failed. Access Denied.";
    } elseif (isset($_POST['action_type']) && $_POST['action_type'] === 'save') {
        $id = (int)($_POST['mod_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        $file_path = null;

        if (empty($title)) {
            $error = "Title is required.";
        } else {
            
            if (!empty($_FILES['mod_file']['name'])) {
                $upload_dir = __DIR__ . '/../uploads/materials/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                $ext = strtolower(pathinfo($_FILES['mod_file']['name'], PATHINFO_EXTENSION));
                $allowed = ['html', 'htm', 'pdf', 'doc', 'docx', 'ppt', 'pptx', 'zip', 'mp4'];
                
                if (in_array($ext, $allowed, true)) {
                    if ($_FILES['mod_file']['size'] < 50 * 1024 * 1024) { 
                        $fname = 'material_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                        if (move_uploaded_file($_FILES['mod_file']['tmp_name'], $upload_dir . $fname)) {
                            $file_path = 'uploads/materials/' . $fname;
                        } else {
                            $error = "Failed to move uploaded file.";
                        }
                    } else {
                        $error = "File size exceeds the 50MB limit.";
                    }
                } else {
                    $error = "Invalid file extension. Allowed: HTML, PDF, Word, PowerPoint, ZIP, MP4.";
                }
            }

            if (!$error) {
                if ($id > 0) {
                    if ($file_path) {
                        
                        $old_path_query = $pdo->prepare("SELECT file_path FROM $table WHERE id = ?");
                        $old_path_query->execute([$id]);
                        $old_file = $old_path_query->fetchColumn();
                        if ($old_file && file_exists(__DIR__ . '/../' . $old_file)) {
                            @unlink(__DIR__ . '/../' . $old_file);
                        }

                        $stmt = $pdo->prepare("UPDATE $table SET title = ?, description = ?, file_path = ? WHERE id = ?");
                        $stmt->execute([$title, $desc, $file_path, $id]);
                    } else {
                        $stmt = $pdo->prepare("UPDATE $table SET title = ?, description = ? WHERE id = ?");
                        $stmt->execute([$title, $desc, $id]);
                    }
                    $success = "Study material updated successfully!";
                } else {
                    $stmt = $pdo->prepare("INSERT INTO $table (title, description, file_path, is_visible) VALUES (?, ?, ?, 1)");
                    $stmt->execute([$title, $desc, $file_path]);
                    $success = "Study material added successfully!";
                }
            }
        }
    } elseif (isset($_POST['action_type']) && $_POST['action_type'] === 'delete') {
        $id = (int)($_POST['delete_id'] ?? 0);
        if ($id > 0) {
            
            $stmt = $pdo->prepare("SELECT file_path FROM $table WHERE id = ?");
            $stmt->execute([$id]);
            $file = $stmt->fetchColumn();
            if ($file && file_exists(__DIR__ . '/../' . $file)) {
                @unlink(__DIR__ . '/../' . $file);
            }

            $stmt = $pdo->prepare("DELETE FROM $table WHERE id = ?");
            $stmt->execute([$id]);
            $success = "Study material deleted successfully!";
        }
    }
}

if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $pdo->prepare("UPDATE $table SET is_visible = 1 - is_visible WHERE id = ?")->execute([$id]);
    header("Location: manage_study_materials.php");
    exit;
}

$materials = [];
try {
    $materials = $pdo->query("SELECT * FROM $table ORDER BY id DESC")->fetchAll();
} catch (Exception $e) {}

require_once __DIR__ . '/../includes/header.php';
?>

<style>
.mat-admin-wrap { padding: 30px; }
.mat-row {
    display: grid;
    grid-template-columns: 1fr 150px 180px;
    gap: 15px;
    align-items: center;
    padding: 15px 20px;
    background: rgba(15,23,42,0.5);
    border-bottom: 1px solid rgba(255,255,255,0.03);
    transition: 0.2s;
    border-radius: 8px;
    margin-bottom: 10px;
}
.mat-row:hover { background: rgba(0,243,255,0.03); }
.mat-info .title { font-size: 0.95rem; font-weight: 700; color: #fff; margin-bottom: 4px; }
.mat-info .desc { font-size: 0.8rem; color: #64748b; margin-bottom: 5px; }
.mat-info .f-link { display: inline-flex; align-items: center; gap: 5px; font-size: 0.72rem; color: #00f3ff; text-decoration: none; }
.mat-info .f-link:hover { text-decoration: underline; }
.mat-actions { display: flex; gap: 8px; justify-content: flex-end; }
.btn-action { padding: 5px 12px; border-radius: 6px; font-size: 0.75rem; font-weight: 700; cursor: pointer; border: 1px solid transparent; text-decoration: none; text-align: center; }
.btn-edit { background: rgba(0,243,255,0.08); color: #00f3ff; border-color: rgba(0,243,255,0.25); }
.btn-edit:hover { background: rgba(0,243,255,0.18); }
.btn-delete { background: rgba(239,68,68,0.08); color: #ef4444; border-color: rgba(239,68,68,0.25); }
.btn-delete:hover { background: rgba(239,68,68,0.18); }
.tog-btn { padding: 5px 11px; border-radius: 6px; text-decoration: none; font-size: 0.72rem; font-weight: 700; display: inline-block; border: 1px solid; text-align: center; }
.tog-on { background: rgba(16,185,129,0.12); color: #10b981; border-color: rgba(16,185,129,0.3); }
.tog-off { background: rgba(100,116,139,0.1); color: #64748b; border-color: rgba(100,116,139,0.25); }

/* Modal */
.modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.85); z-index: 10000; align-items: center; justify-content: center; backdrop-filter: blur(5px); }
.modal-overlay.open { display: flex; }
.modal-box { background: #0f172a; border: 2px solid rgba(0,243,255,0.25); border-radius: 14px; padding: 30px; width: 550px; max-width: 95vw; box-shadow: 0 0 30px rgba(0,243,255,0.15); }
.modal-box h3 { font-family: 'Rajdhani', sans-serif; color: #fff; margin: 0 0 20px 0; font-size: 1.25rem; display: flex; align-items: center; gap: 8px; }
.form-group { margin-bottom: 16px; }
.form-group label { display: block; font-size: 0.75rem; color: #94a3b8; margin-bottom: 6px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
.form-group input[type=text], .form-group textarea { width: 100%; background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; color: #fff; padding: 10px 14px; font-size: 0.85rem; outline: none; box-sizing: border-box; }
.form-group input[type=text]:focus, .form-group textarea:focus { border-color: rgba(0,243,255,0.4); }
.form-group textarea { height: 90px; resize: vertical; }
.file-drop { border: 2px dashed rgba(0,243,255,0.2); border-radius: 8px; padding: 20px; text-align: center; color: #475569; font-size: 0.8rem; cursor: pointer; position: relative; }
.file-drop:hover { border-color: rgba(0,243,255,0.4); color: #94a3b8; }
.file-drop input[type=file] { position: absolute; inset: 0; opacity: 0; cursor: pointer; }
.modal-btns { display: flex; gap: 10px; justify-content: flex-end; margin-top: 25px; }
.btn-save { background: linear-gradient(135deg, #00f3ff, #a855f7); color: #000; border: none; padding: 10px 22px; border-radius: 8px; font-weight: 700; font-size: 0.85rem; cursor: pointer; display: flex; align-items: center; gap: 5px; }
.btn-cancel { background: rgba(255,255,255,0.05); color: #94a3b8; border: 1px solid rgba(255,255,255,0.1); padding: 10px 18px; border-radius: 8px; font-size: 0.85rem; cursor: pointer; }

.alert-s { background: rgba(16,185,129,0.1); border: 1px solid #10b981; color: #10b981; padding: 13px 18px; border-radius: 8px; margin-bottom: 22px; font-size: 0.85rem; font-weight: 600; }
.alert-e { background: rgba(239,68,68,0.1); border: 1px solid #ef4444; color: #ef4444; padding: 13px 18px; border-radius: 8px; margin-bottom: 22px; font-size: 0.85rem; font-weight: 600; }
</style>

<div class="portal-container">
    <?php include_once '_sidebar.php'; ?>
    <main class="main-content">
        <header class="topbar">
            <div class="topbar-left">
                <h2 style="font-family:'Rajdhani',sans-serif; letter-spacing:1.5px;">
                    <i class="fa-solid fa-graduation-cap" style="color:#00f3ff;"></i> Study Materials Control
                </h2>
            </div>
            <div class="topbar-right">
                <button class="btn-primary" onclick="openEdit(0, '', '')" style="display:inline-flex; align-items:center; gap:8px;">
                    <i class="fa-solid fa-plus"></i> Add Study Material
                </button>
            </div>
        </header>

        <div class="dashboard-content mat-admin-wrap">
            <?php if($success): ?><div class="alert-s"><i class="fa-solid fa-check-circle"></i> <?= esc($success) ?></div><?php endif; ?>
            <?php if($error):   ?><div class="alert-e"><i class="fa-solid fa-circle-exclamation"></i> <?= esc($error) ?></div><?php endif; ?>

            <div class="glass-panel" style="margin-bottom: 20px;">
                <div style="font-size:0.85rem; color:#94a3b8; display:flex; align-items:center; gap:8px;">
                    <i class="fa-solid fa-circle-info" style="color:var(--accent-blue);"></i>
                    Uploaded study materials will be displayed in the <strong>Intern Portal</strong> (Study Materials) and the <strong>Officer Portal</strong> (Study Materials) for all active users. HTML files will open securely with a watermark.
                </div>
            </div>

            
            <div class="glass-panel">
                <h3 style="margin-top:0; border-bottom:1px solid var(--border-color); padding-bottom:12px; color:var(--text-primary); font-size:1.05rem; font-family:'Rajdhani',sans-serif; letter-spacing:1px;">
                    <i class="fa-solid fa-folder-open" style="color:#00f3ff; margin-right:5px;"></i> Active Repository (<?= count($materials) ?> Items)
                </h3>

                <?php if(empty($materials)): ?>
                    <div style="text-align:center; padding:50px 20px; color:#475569;">
                        <i class="fa-solid fa-book-open" style="font-size:3rem; margin-bottom:12px; display:block; opacity:0.3;"></i>
                        <span style="font-weight:600;">No study materials added yet.</span><br>
                        <span style="font-size:0.8rem;">Click "+ Add Study Material" to upload your first reference guide.</span>
                    </div>
                <?php else: ?>
                    <div style="display:flex; flex-direction:column; gap:10px;">
                        <?php foreach ($materials as $m): ?>
                            <div class="mat-row">
                                <div class="mat-info">
                                    <div class="title"><?= esc($m['title']) ?></div>
                                    <div class="desc"><?= esc($m['description'] ?: 'No description provided.') ?></div>
                                    <?php if(!empty($m['file_path'])): ?>
                                        <a href="../intern/view_material.php?id=<?= $m['id'] ?>" class="f-link" target="_blank">
                                            <i class="fa-solid fa-file-shield"></i> Open Secure Viewer (<?= strtoupper(pathinfo($m['file_path'], PATHINFO_EXTENSION)) ?>)
                                        </a>
                                    <?php else: ?>
                                        <span style="font-size:0.7rem; color:#475569;"><i class="fa-solid fa-link-slash"></i> No file attached</span>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <a href="?toggle=<?= $m['id'] ?>" class="tog-btn <?= $m['is_visible'] ? 'tog-on' : 'tog-off' ?>">
                                        <i class="fa-solid <?= $m['is_visible'] ? 'fa-eye' : 'fa-eye-slash' ?>"></i>
                                        <?= $m['is_visible'] ? 'VISIBLE' : 'HIDDEN' ?>
                                    </a>
                                </div>
                                <div class="mat-actions">
                                    <button class="btn-action btn-edit" onclick="openEdit(<?= $m['id'] ?>, '<?= addslashes(esc($m['title'])) ?>', '<?= addslashes(esc($m['description'])) ?>')">
                                        <i class="fa-solid fa-pen"></i> EDIT
                                    </button>
                                    <button class="btn-action btn-delete" onclick="confirmDelete(<?= $m['id'] ?>, '<?= addslashes(esc($m['title'])) ?>')">
                                        <i class="fa-solid fa-trash"></i> DELETE
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<div class="modal-overlay" id="editModal">
    <div class="modal-box">
        <h3 id="modalHead"><i class="fa-solid fa-book-open" style="color:var(--accent-blue);"></i> Add Study Material</h3>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= get_csrf_token() ?>">
            <input type="hidden" name="action_type" value="save">
            <input type="hidden" name="mod_id" id="f_id" value="0">
            
            <div class="form-group">
                <label>Material Title *</label>
                <input type="text" name="title" id="f_title" placeholder="e.g. Call Detail Records (CDR) Analysis Guide" required>
            </div>
            
            <div class="form-group">
                <label>Description / Summary</label>
                <textarea name="description" id="f_desc" placeholder="Provide a brief overview of what this material covers..."></textarea>
            </div>
            
            <div class="form-group">
                <label>Attachment File <span style="color:#475569;">(HTML, PDF, WORD, PPT, ZIP, MP4 — Max 50MB)</span></label>
                <div class="file-drop">
                    <i class="fa-solid fa-cloud-arrow-up" style="font-size:1.5rem; margin-bottom:8px; display:block;"></i>
                    <span id="fileLabel">Click or drag file here to upload</span>
                    <input type="file" name="mod_file" id="f_file" accept=".html,.htm,.pdf,.doc,.docx,.ppt,.pptx,.zip,.mp4">
                </div>
            </div>
            
            <div class="modal-btns">
                <button type="button" class="btn-cancel" onclick="closeEdit()">Cancel</button>
                <button type="submit" class="btn-save"><i class="fa-solid fa-floppy-disk"></i> SAVE MATERIAL</button>
            </div>
        </form>
    </div>
</div>

<form method="POST" id="deleteForm" style="display:none;">
    <input type="hidden" name="csrf_token" value="<?= get_csrf_token() ?>">
    <input type="hidden" name="action_type" value="delete">
    <input type="hidden" name="delete_id" id="delete_id" value="0">
</form>

<script>
function openEdit(id, title, desc) {
    document.getElementById('f_id').value = id;
    document.getElementById('f_title').value = title;
    document.getElementById('f_desc').value = desc;
    document.getElementById('fileLabel').innerText = "Click or drag file here to upload";
    document.getElementById('f_file').value = '';
    
    if (id > 0) {
        document.getElementById('modalHead').innerHTML = '<i class="fa-solid fa-pen-to-square" style="color:var(--accent-blue);"></i> Edit Study Material';
    } else {
        document.getElementById('modalHead').innerHTML = '<i class="fa-solid fa-book-open" style="color:var(--accent-blue);"></i> Add Study Material';
    }
    document.getElementById('editModal').classList.add('open');
}

function closeEdit() {
    document.getElementById('editModal').classList.remove('open');
}

function confirmDelete(id, title) {
    if (confirm("Are you sure you want to permanently delete the study material: \"" + title + "\"?\nThis will also remove its associated file from the server.")) {
        document.getElementById('delete_id').value = id;
        document.getElementById('deleteForm').submit();
    }
}

// Show filename on select
document.getElementById('f_file').addEventListener('change', function() {
    if(this.files[0]) {
        document.getElementById('fileLabel').innerText = this.files[0].name;
    }
});

// Close modal when clicking outside form
document.getElementById('editModal').addEventListener('click', function(e) {
    if (e.target === this) closeEdit();
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
