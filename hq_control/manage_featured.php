<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

check_auth('Admin');

$portal = 'admin';
$page_title = 'Featured Intern Manager | ' . SITE_NAME;

// CSRF token generation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle Form Submission: Add Featured Intern
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_featured') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['toast_msg'] = "Security Error: Invalid CSRF Token.";
        $_SESSION['toast_type'] = "error";
        header("Location: manage_featured.php");
        exit;
    }

    $intern_id = !empty($_POST['intern_id']) ? (int)$_POST['intern_id'] : null;
    $name = trim($_POST['name'] ?? '');
    $college = trim($_POST['college'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $featured_month = trim($_POST['featured_month'] ?? '');

    if (empty($name) || empty($description) || empty($featured_month)) {
        $_SESSION['toast_msg'] = "Please fill in all required fields.";
        $_SESSION['toast_type'] = "error";
        header("Location: manage_featured.php");
        exit;
    }

    $media_url = null;
    $media_type = 'image';

    // File Upload handling
    if (isset($_FILES['media_file']) && $_FILES['media_file']['error'] === UPLOAD_ERR_OK) {
        $tmp_name = $_FILES['media_file']['tmp_name'];
        $original_name = $_FILES['media_file']['name'];
        $ext = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));

        // Validate extension
        $allowed_exts = ['jpg', 'jpeg', 'png', 'mp4', 'webm'];
        if (!in_array($ext, $allowed_exts)) {
            $_SESSION['toast_msg'] = "Forbidden extension! Only JPG, PNG, MP4, and WEBM allowed.";
            $_SESSION['toast_type'] = "error";
            header("Location: manage_featured.php");
            exit;
        }

        // Validate MIME type strictly
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $tmp_name);
        finfo_close($finfo);

        $allowed_mimes = [
            'image/jpeg' => 'image',
            'image/jpg'  => 'image',
            'image/png'  => 'image',
            'video/mp4'  => 'video',
            'video/webm' => 'video'
        ];

        if (!array_key_exists($mime_type, $allowed_mimes)) {
            $_SESSION['toast_msg'] = "Security Alert: Invalid MIME type detected!";
            $_SESSION['toast_type'] = "error";
            header("Location: manage_featured.php");
            exit;
        }

        $media_type = $allowed_mimes[$mime_type];

        // Create secure path & secure renamed file (prevents path traversal / LFI)
        $upload_dir = __DIR__ . '/../uploads/featured/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $secure_filename = bin2hex(random_bytes(16)) . '.' . $ext;
        $destination = $upload_dir . $secure_filename;

        if (move_uploaded_file($tmp_name, $destination)) {
            $media_url = 'uploads/featured/' . $secure_filename;
        } else {
            $_SESSION['toast_msg'] = "Failed to upload file. Check permissions.";
            $_SESSION['toast_type'] = "error";
            header("Location: manage_featured.php");
            exit;
        }
    } else {
        // Fallback: If intern is selected, try to copy their profile photo
        if ($intern_id) {
            $stmt = $pdo->prepare("SELECT profile_photo FROM interns WHERE id = ?");
            $stmt->execute([$intern_id]);
            $profile_photo = $stmt->fetchColumn();
            if ($profile_photo) {
                $media_url = $profile_photo;
                $media_type = 'image';
            }
        }
    }

    // Generate secure unique share token
    $share_token = bin2hex(random_bytes(32));

    try {
        $stmt = $pdo->prepare("INSERT INTO featured_interns (intern_id, name, college, media_url, media_type, description, featured_month, share_token) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$intern_id, $name, $college, $media_url, $media_type, $description, $featured_month, $share_token]);

        $_SESSION['toast_msg'] = "Best Intern successfully featured!";
        $_SESSION['toast_type'] = "success";
    } catch (Exception $e) {
        $_SESSION['toast_msg'] = "Database Error: Could not save record.";
        $_SESSION['toast_type'] = "error";
    }

    header("Location: manage_featured.php");
    exit;
}

// Handle Record Deletion
if (isset($_GET['delete_id'])) {
    $delete_id = (int)$_GET['delete_id'];
    
    // First retrieve media_url to unlink it from local storage
    $stmt = $pdo->prepare("SELECT media_url FROM featured_interns WHERE id = ?");
    $stmt->execute([$delete_id]);
    $media_file = $stmt->fetchColumn();
    
    if ($media_file && strpos($media_file, 'uploads/featured/') === 0) {
        $full_path = __DIR__ . '/../' . $media_file;
        if (file_exists($full_path)) {
            @unlink($full_path);
        }
    }

    $stmt = $pdo->prepare("DELETE FROM featured_interns WHERE id = ?");
    if ($stmt->execute([$delete_id])) {
        $_SESSION['toast_msg'] = "Featured record deleted.";
        $_SESSION['toast_type'] = "success";
    } else {
        $_SESSION['toast_msg'] = "Failed to delete record.";
        $_SESSION['toast_type'] = "error";
    }
    header("Location: manage_featured.php");
    exit;
}

// Fetch all registered active interns for selector
$active_interns = [];
try {
    $active_interns = $pdo->query("SELECT id, name, intern_id, college, profile_photo FROM interns WHERE status = 'Active' ORDER BY name ASC")->fetchAll();
} catch (Exception $e) {}

// Fetch current featured interns list
$featured_list = [];
try {
    $featured_list = $pdo->query("SELECT * FROM featured_interns ORDER BY created_at DESC")->fetchAll();
} catch (Exception $e) {}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="portal-container">
    <?php include_once __DIR__ . '/_sidebar.php'; ?>

    <main class="main-content">
        <header class="topbar">
            <div class="topbar-left">
                <h2><i class="fa-solid fa-star" style="color:var(--accent-highlight);"></i> Featured Intern Directory</h2>
            </div>
        </header>

        <div class="dashboard-content" style="display: grid; grid-template-columns: 1fr 2fr; gap: 30px; align-items: start;">
            
            <!-- Left Side: Add Form -->
            <div class="glass-panel">
                <h3 style="margin-bottom: 20px; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">
                    <i class="fa-solid fa-user-plus" style="color: #00f3ff;"></i> Feature New Intern
                </h3>

                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="add_featured">
                    <input type="hidden" name="csrf_token" value="<?= esc($_SESSION['csrf_token']) ?>">

                    <div style="margin-bottom: 15px;">
                        <label style="display:block; font-size:0.8rem; margin-bottom:5px; color:#94a3b8;">Link to Active Intern (Optional)</label>
                        <select id="intern_select" name="intern_id" class="form-control" style="width:100%; background:#0f172a; border: 1px solid rgba(0,243,255,0.2); color:#fff; padding: 8px 12px; border-radius:6px;" onchange="autoFillIntern()">
                            <option value="">-- Select Intern (Autofill Name & College) --</option>
                            <?php foreach ($active_interns as $ai): ?>
                                <option value="<?= $ai['id'] ?>" data-name="<?= esc($ai['name']) ?>" data-college="<?= esc($ai['college']) ?>">
                                    <?= esc($ai['name']) ?> (<?= esc($ai['intern_id']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div style="margin-bottom: 15px;">
                        <label style="display:block; font-size:0.8rem; margin-bottom:5px; color:#94a3b8;">Full Name *</label>
                        <input type="text" id="featured_name" name="name" class="form-control" required style="width:100%; background:#0f172a; border: 1px solid rgba(0,243,255,0.2); color:#fff; padding: 8px 12px; border-radius:6px;">
                    </div>

                    <div style="margin-bottom: 15px;">
                        <label style="display:block; font-size:0.8rem; margin-bottom:5px; color:#94a3b8;">College/University</label>
                        <input type="text" id="featured_college" name="college" class="form-control" style="width:100%; background:#0f172a; border: 1px solid rgba(0,243,255,0.2); color:#fff; padding: 8px 12px; border-radius:6px;">
                    </div>

                    <div style="margin-bottom: 15px;">
                        <label style="display:block; font-size:0.8rem; margin-bottom:5px; color:#94a3b8;">Featured Month & Year *</label>
                        <select name="featured_month" class="form-control" required style="width:100%; background:#0f172a; border: 1px solid rgba(0,243,255,0.2); color:#fff; padding: 8px 12px; border-radius:6px;">
                            <?php
                            for ($i = 0; $i < 12; $i++) {
                                $m = date("F Y", strtotime("-$i months"));
                                echo "<option value=\"$m\">$m</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div style="margin-bottom: 15px;">
                        <label style="display:block; font-size:0.8rem; margin-bottom:5px; color:#94a3b8;">Showcase File (Photo or Video Clip)</label>
                        <input type="file" name="media_file" accept="image/*,video/*" class="form-control" style="width:100%; background:#0f172a; border: 1px solid rgba(0,243,255,0.2); color:#fff; padding: 8px 12px; border-radius:6px;">
                        <span style="font-size:0.7rem; color:#64748b; margin-top:4px; display:block;">Allowed formats: JPEG, PNG, MP4, WEBM</span>
                    </div>

                    <div style="margin-bottom: 20px;">
                        <label style="display:block; font-size:0.8rem; margin-bottom:5px; color:#94a3b8;">Key Accomplishments / Description *</label>
                        <textarea name="description" rows="4" required placeholder="Highlight task completion rates, exceptional analysis report contributions, CTF ranking, etc." style="width:100%; background:#0f172a; border: 1px solid rgba(0,243,255,0.2); color:#fff; padding: 8px 12px; border-radius:6px; font-size:0.85rem; resize:vertical;"></textarea>
                    </div>

                    <button type="submit" class="btn-primary" style="width:100%; padding:10px 15px; border-radius:6px; font-weight:700; background:linear-gradient(135deg,#00f3ff,#6366f1); border:none; color:#020617; cursor:pointer;">
                        Publish Feature
                    </button>
                </form>
            </div>

            <!-- Right Side: Current list & Share links -->
            <div class="glass-panel">
                <h3 style="margin-bottom: 20px; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">
                    <i class="fa-solid fa-list-check" style="color: #00f3ff;"></i> Featured History
                </h3>

                <?php if (empty($featured_list)): ?>
                    <p style="color:#64748b; text-align:center; padding: 40px;">No interns have been featured yet.</p>
                <?php else: ?>
                    <div style="overflow-x:auto;">
                        <table class="dataTable" style="width:100%;">
                            <thead>
                                <tr>
                                    <th>Media</th>
                                    <th>Intern Details</th>
                                    <th>Month</th>
                                    <th>Shareable Link</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($featured_list as $f):
                                    $public_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/../featured.php?t=' . $f['share_token'];
                                ?>
                                    <tr>
                                        <td>
                                            <?php if ($f['media_type'] === 'video'): ?>
                                                <video src="../<?= esc($f['media_url']) ?>" style="width:60px; height:45px; border-radius:4px; object-fit:cover;" muted></video>
                                            <?php elseif ($f['media_url']): ?>
                                                <img src="../<?= esc($f['media_url']) ?>" style="width:60px; height:45px; border-radius:4px; object-fit:cover;">
                                            <?php else: ?>
                                                <span style="color:#475569; font-size:0.75rem;">None</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div style="font-weight:700; color:#fff;"><?= esc($f['name']) ?></div>
                                            <div style="font-size:0.75rem; color:#64748b;"><?= esc($f['college'] ?: 'No college') ?></div>
                                        </td>
                                        <td style="color:#00f3ff; font-weight:600; font-size:0.85rem;"><?= esc($f['featured_month']) ?></td>
                                        <td>
                                            <div style="display:flex; align-items:center; gap:8px;">
                                                <input type="text" readonly value="<?= esc($public_link) ?>" id="link_<?= $f['id'] ?>" style="background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.08); color:#94a3b8; font-size:0.75rem; padding:4px 8px; border-radius:4px; width:150px; font-family:monospace;">
                                                <button class="btn-primary" onclick="copyLink(<?= $f['id'] ?>)" style="padding:4px 8px; font-size:0.7rem; background:#475569; border:none; border-radius:4px; color:#fff; cursor:pointer;">
                                                    Copy
                                                </button>
                                            </div>
                                        </td>
                                        <td>
                                            <a href="?delete_id=<?= $f['id'] ?>" onclick="return confirm('Are you sure you want to delete this feature?')" style="color:#ef4444; font-size:0.95rem; margin-left:10px;"><i class="fa-solid fa-trash-can"></i></a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </main>
</div>

<script>
function autoFillIntern() {
    const selector = document.getElementById('intern_select');
    const selectedOption = selector.options[selector.selectedIndex];
    
    if (selectedOption.value !== "") {
        document.getElementById('featured_name').value = selectedOption.getAttribute('data-name');
        document.getElementById('featured_college').value = selectedOption.getAttribute('data-college');
    } else {
        document.getElementById('featured_name').value = "";
        document.getElementById('featured_college').value = "";
    }
}

function copyLink(id) {
    const copyText = document.getElementById("link_" + id);
    copyText.select();
    copyText.setSelectionRange(0, 99999);
    navigator.clipboard.writeText(copyText.value);
    
    showToast("Shareable link copied to clipboard!", "success");
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
