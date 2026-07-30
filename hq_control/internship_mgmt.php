<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

check_auth('Admin');

$page_title = 'Manage Internship Programmes | Admin';
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    if (!isset($_POST['csrf_token']) || !validate_csrf($_POST['csrf_token'])) {
        die("Security Validation Failed (CSRF). Access Denied.");
    }
    
    if ($_POST['action'] === 'add') {
        try {
            
            $image_path = 'assets/images/cyber-banner.jpg';
            if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
                $target_dir = "../uploads/academy/";
                if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
                
                $ext = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION));
                $file_name = time() . "_" . bin2hex(random_bytes(8)) . "." . $ext;
                $target_file = $target_dir . $file_name;

                if (in_array($ext, ['jpg', 'jpeg', 'png'])) {
                    
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mime = finfo_file($finfo, $_FILES["image"]["tmp_name"]);
                    finfo_close($finfo);
                    
                    if (in_array($mime, ['image/jpeg', 'image/png'])) {
                        if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                            $image_path = "uploads/academy/" . $file_name;
                        }
                    }
                }
            }

            
            $title = base64_decode($_POST['f1'] ?? '');
            $desc = base64_decode($_POST['f2'] ?? '');
            $duration = base64_decode($_POST['f3'] ?? '');
            $price = base64_decode($_POST['f4'] ?? '');
            
            if (!empty($title)) {
                $stmt = $pdo->prepare("INSERT INTO internship_programmes (title, description, price, duration, image_url) VALUES (?, ?, ?, ?, ?)");
                if ($stmt->execute([$title, $desc, $price, $duration, $image_path])) {
                    $success = "New Forensic Programme launched successfully!";
                } else {
                    $error = "Failed to launch programme.";
                }
            }
        } catch (PDOException $e) { $error = "Error: " . $e->getMessage(); }
    }
}

if (isset($_GET['rm_id'])) {
    $stmt = $pdo->prepare("DELETE FROM internship_programmes WHERE id = ?");
    $stmt->execute([$_GET['rm_id']]);
    $success = "Programme removed from Academy.";
}

$programmes = $pdo->query("SELECT * FROM internship_programmes ORDER BY created_at DESC")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<style>
.academy-mgmt-container {
    padding: 30px;
    max-width: 1200px;
}
.mgmt-card {
    background: rgba(15, 23, 42, 0.6);
    backdrop-filter: blur(15px);
    border: 1px solid rgba(0, 243, 255, 0.2);
    border-radius: 12px;
    padding: 30px;
    margin-bottom: 30px;
    box-shadow: 0 20px 50px rgba(0,0,0,0.3);
}
.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 25px;
}
.cyber-input {
    background: rgba(0,0,0,0.6) !important;
    border: 1px solid rgba(0, 243, 255, 0.1) !important;
    color: #fff !important;
    padding: 15px !important;
    border-radius: 8px !important;
    width: 100%;
    font-family: 'Rajdhani', sans-serif;
}
.cyber-input:focus {
    border-color: var(--accent-blue) !important;
    background: rgba(0,0,0,0.8) !important;
    box-shadow: 0 0 15px rgba(0,243,255,0.1) !important;
}
.btn-launch {
    grid-column: span 2;
    background: linear-gradient(90deg, var(--accent-blue), #00a2ff);
    color: #000;
    font-weight: 800;
    padding: 18px;
    border-radius: 8px;
    cursor: pointer;
    border: none;
    text-transform: uppercase;
    transition: 0.3s;
    margin-top: 15px;
    font-size: 1rem;
}
.btn-launch:hover { background: #fff; box-shadow: 0 0 30px rgba(0,243,255,0.5); transform: translateY(-2px); }
</style>

<div class="dashboard-wrapper">
    <?php include_once '_sidebar.php'; ?>
    <main class="main-content">
        <header class="topbar">
            <h2 style="font-family: 'Rajdhani', sans-serif;"><i class="fa-solid fa-graduation-cap"></i> ACADEMY MANAGEMENT</h2>
        </header>
        
        <div class="dashboard-content academy-mgmt-container">

            <?php if($success): ?>
                <div style="background: rgba(16, 185, 129, 0.1); border: 1px solid #10b981; color: #10b981; padding: 20px; border-radius: 8px; margin-bottom: 25px; font-weight: bold;">
                    <i class="fa-solid fa-check-circle"></i> <?= $success ?>
                </div>
            <?php endif; ?>

            
            <div class="mgmt-card">
                <h3 style="color: var(--accent-blue); margin-bottom: 25px; font-size: 1.2rem; border-bottom: 1px solid rgba(0,243,255,0.1); padding-bottom: 15px; font-family: 'Rajdhani', sans-serif;">LAUNCH NEW FORENSIC PROGRAMME</h3>
                <form id="launchForm" method="POST" enctype="multipart/form-data" class="form-grid">
                    <?php csrf_field(); ?>
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="f1" id="f1">
                    <input type="hidden" name="f2" id="f2">
                    <input type="hidden" name="f3" id="f3">
                    <input type="hidden" name="f4" id="f4">

                    <div class="form-group">
                        <label style="color: var(--text-muted); font-size: 0.75rem; margin-bottom: 8px; display: block; font-weight: bold; letter-spacing: 1px;">PROGRAMME TITLE</label>
                        <input type="text" id="t1" class="cyber-input" placeholder="e.g. Advanced Ethical Hacking" required>
                    </div>
                    <div class="form-group">
                        <label style="color: var(--text-muted); font-size: 0.75rem; margin-bottom: 8px; display: block; font-weight: bold; letter-spacing: 1px;">DURATION</label>
                        <input type="text" id="t3" class="cyber-input" placeholder="e.g. 6 Months" required>
                    </div>
                    <div class="form-group">
                        <label style="color: var(--text-muted); font-size: 0.75rem; margin-bottom: 8px; display: block; font-weight: bold; letter-spacing: 1px;">PRICE (DISPLAY STRING)</label>
                        <input type="text" id="t4" class="cyber-input" placeholder="e.g. Rs. 14,999/-" required>
                    </div>
                    <div class="form-group">
                        <label style="color: var(--text-muted); font-size: 0.75rem; margin-bottom: 8px; display: block; font-weight: bold; letter-spacing: 1px;">THUMBNAIL BANNER (JPG/PNG)</label>
                        <input type="file" name="image" class="cyber-input" accept="image/*" style="padding: 10px !important;">
                    </div>
                    <div class="form-group" style="grid-column: span 2;">
                        <label style="color: var(--text-muted); font-size: 0.75rem; margin-bottom: 8px; display: block; font-weight: bold; letter-spacing: 1px;">PROGRAMME DESCRIPTION</label>
                        <textarea id="t2" class="cyber-input" style="height: 120px;" placeholder="Outline the course curriculum and objectives..."></textarea>
                    </div>
                    <button type="submit" class="btn-launch">
                        <i class="fa-solid fa-rocket"></i> PUBLISH TO ACADEMY
                    </button>
                </form>
            </div>

            <script>
            document.getElementById('launchForm').onsubmit = function() {
                // TOTAL STEALTH ENCODE
                const scramble = (str) => btoa(unescape(encodeURIComponent(str)));
                document.getElementById('f1').value = scramble(document.getElementById('t1').value);
                document.getElementById('f2').value = scramble(document.getElementById('t2').value);
                document.getElementById('f3').value = scramble(document.getElementById('t3').value);
                document.getElementById('f4').value = scramble(document.getElementById('t4').value);
                return true;
            };
            </script>

            
            <div class="glass-panel" style="padding: 0; overflow: hidden; border: 1px solid rgba(0, 243, 255, 0.1);">
                <div style="padding: 20px; background: rgba(0, 243, 255, 0.05); border-bottom: 1px solid rgba(0, 243, 255, 0.1);">
                    <h3 style="margin: 0; font-size: 1.1rem; color: var(--accent-blue); font-family: 'Rajdhani', sans-serif;"><i class="fa-solid fa-shield-halved"></i> ACTIVE ACADEMY PROGRAMMES</h3>
                </div>
                <div style="overflow-x: auto;">
                    <table style="width: 100%; text-align: left; border-collapse: collapse;">
                        <thead>
                            <tr style="border-bottom: 1px solid rgba(0, 243, 255, 0.1); color: var(--text-muted); font-size: 0.75rem; text-transform: uppercase;">
                                <th style="padding: 15px;">Banner</th>
                                <th>Programme</th>
                                <th>Price</th>
                                <th>Duration</th>
                                <th style="text-align: right; padding-right: 25px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($programmes)): ?>
                                <tr><td colspan="5" style="padding: 40px; text-align: center; color: var(--text-muted);">No active programmes found in the Academy.</td></tr>
                            <?php endif; ?>
                            <?php foreach($programmes as $prog): ?>
                            <tr style="border-bottom: 1px solid rgba(255,255,255,0.02); transition: 0.3s;" onmouseover="this.style.background='rgba(0,243,255,0.03)'" onmouseout="this.style.background='transparent'">
                                <td style="padding: 15px;">
                                    <img src="../<?= esc($prog['image_url']) ?>" style="width: 70px; height: 45px; object-fit: cover; border-radius: 6px; border: 1px solid rgba(0, 243, 255, 0.2);">
                                </td>
                                <td style="font-weight: bold; color: #fff; font-size: 0.95rem;"><?= esc($prog['title']) ?></td>
                                <td style="color: #10b981; font-weight: bold;"><?= esc($prog['price']) ?></td>
                                <td style="color: var(--text-muted); font-size: 0.9rem;"><?= esc($prog['duration']) ?></td>
                                <td style="text-align: right; padding-right: 25px;">
                                    <a href="?rm_id=<?= $prog['id'] ?>" style="color: #ef4444; text-decoration: none; font-size: 0.75rem; background: rgba(239, 68, 68, 0.15); padding: 7px 12px; border-radius: 6px; border: 1px solid rgba(239, 68, 68, 0.3); font-weight: bold;" onclick="return confirm('Secure Remove this programme?')">
                                        <i class="fa-solid fa-trash-can"></i> REMOVE
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
