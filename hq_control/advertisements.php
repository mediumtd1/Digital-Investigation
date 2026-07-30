<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

check_auth('Admin');

$portal = 'admin';
$page_title = 'Advertisement Manager | ' . SITE_NAME;

$pdo->exec("CREATE TABLE IF NOT EXISTS advertisements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    image_url VARCHAR(255) NOT NULL,
    link_url VARCHAR(255),
    display_width INT DEFAULT 400,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

try {
    $pdo->query("SELECT display_width FROM advertisements LIMIT 1");
} catch (Exception $e) {
    $pdo->exec("ALTER TABLE advertisements ADD COLUMN display_width INT DEFAULT 400");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] == 'add_ad') {
    $title = trim($_POST['title']);
    $link_url = trim($_POST['link_url'] ?? '');
    
    
    
    
    if (isset($_FILES['ad_image']) && $_FILES['ad_image']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['ad_image']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            $upload_dir = __DIR__ . '/../uploads/ads/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            $new_name = uniqid('ad_', true) . '.' . $ext;
            if (move_uploaded_file($_FILES['ad_image']['tmp_name'], $upload_dir . $new_name)) {
                $image_path = 'uploads/ads/' . $new_name;
                
                $display_width = (int)($_POST['display_width'] ?? 400);
                $stmt = $pdo->prepare("INSERT INTO advertisements (title, image_url, link_url, display_width, is_active) VALUES (?, ?, ?, ?, 1)");
                $stmt->execute([$title, $image_path, $link_url, $display_width]);
                
                $_SESSION['toast_msg'] = "Advertisement successfully published!";
                $_SESSION['toast_type'] = "success";
                header("Location: advertisements.php");
                exit;
            }
        } else {
            $_SESSION['toast_msg'] = "Invalid image format. Use JPG, PNG, GIF, or WebP.";
            $_SESSION['toast_type'] = "error";
        }
    } else {
        $_SESSION['toast_msg'] = "Please provide an image.";
        $_SESSION['toast_type'] = "error";
    }
}

if (isset($_GET['delete_id'])) {
    $id = (int)$_GET['delete_id'];
    $stmt = $pdo->prepare("DELETE FROM advertisements WHERE id = ?");
    if($stmt->execute([$id])) {
        $_SESSION['toast_msg'] = "Advertisement successfully removed.";
        $_SESSION['toast_type'] = "success";
    } else {
        $_SESSION['toast_msg'] = "Failed to remove advertisement.";
        $_SESSION['toast_type'] = "error";
    }
    header("Location: advertisements.php");
    exit;
}

try {
    $ads = $pdo->query("SELECT * FROM advertisements ORDER BY created_at DESC")->fetchAll();
} catch (Exception $e) {
    
    $ads = [];
}
?>

<?php require_once __DIR__ . '/../includes/header.php'; ?>

<div class="portal-container">
    <?php include_once __DIR__ . '/_sidebar.php'; ?>

    <main class="main-content">
        <header class="topbar">
            <div class="topbar-left">
                <h2>Manage Platform Advertisements</h2>
            </div>
        </header>

        <div class="dashboard-content" style="display: grid; grid-template-columns: 1fr 2fr; gap: 30px;">
            
            <div class="glass-panel" style="height: fit-content;">
                <h3 style="margin-bottom: 20px; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">
                    <i class="fa-solid fa-rectangle-ad" style="color: #00f3ff;"></i> Publish New Ad
                </h3>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="add_ad">
                    <div class="form-group">
                        <label class="form-label" style="font-size: 0.8rem; text-transform: uppercase;">Headline / Title</label>
                        <input type="text" name="title" class="form-control" required placeholder="e.g. Cyber Awareness Month Seminar">
                    </div>
                    <div class="form-group">
                        <label class="form-label" style="font-size: 0.8rem; text-transform: uppercase;">Advertisement Image (Landscape)</label>
                        <input type="file" name="ad_image" class="form-control" required accept="image/*" style="padding: 10px;">
                    </div>
                    <div class="form-group">
                        <label class="form-label" style="font-size: 0.8rem; text-transform: uppercase;">Display Width (Pixels)</label>
                        <input type="range" name="display_width" class="form-range" min="100" max="1000" step="50" value="400" oninput="this.nextElementSibling.value = this.value + 'px'">
                        <output style="color: #00f3ff; font-weight: bold; margin-left: 10px;">400px</output>
                        <small class="text-muted d-block">Adjust how large the ad appears for Officers.</small>
                    </div>
                    <div class="form-group">
                        <label class="form-label" style="font-size: 0.8rem; text-transform: uppercase;">Target Redirection URL (Optional)</label>
                        <input type="url" name="link_url" class="form-control" placeholder="https://...">
                    </div>
                    <button type="submit" class="btn-primary" style="width: 100%; margin-top: 10px; background-color: #3b82f6;">
                        <i class="fa-solid fa-cloud-arrow-up"></i> Publish Advertisement
                    </button>
                </form>
            </div>

            
            <div class="glass-panel">
                <h3 style="margin-bottom: 20px; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">
                    <i class="fa-solid fa-images" style="color: #10b981;"></i> Current Active Campaigns
                </h3>
                
                <?php if(empty($ads)): ?>
                    <p style="text-align: center; color: var(--text-muted); margin-top: 40px; font-style: italic;">No advertisements currently running in the system.</p>
                <?php else: ?>
                    <div style="display: flex; flex-direction: column; gap: 15px;">
                        <?php foreach($ads as $ad): ?>
                            <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 8px; display: flex; overflow: hidden; height: 120px;">
                                <div style="width: 180px; flex-shrink: 0; background: #000;">
                                    <img src="../<?= esc($ad['image_url']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                </div>
                                <div style="padding: 15px 20px; display: flex; flex-direction: column; justify-content: center; flex: 1;">
                                    <h4 style="margin: 0 0 5px 0; color: #e2e8f0;"><?= esc($ad['title']) ?></h4>
                                    <?php if ($ad['link_url']): ?>
                                        <a href="<?= esc($ad['link_url']) ?>" target="_blank" style="color: #60a5fa; font-size: 0.85rem; text-decoration: none; word-break: break-all; margin-bottom: 10px;">
                                            <i class="fa-solid fa-link"></i> <?= esc($ad['link_url']) ?>
                                        </a>
                                    <?php endif; ?>
                                    <span style="font-size: 0.75rem; color: var(--text-muted);"><i class="fa-regular fa-clock"></i> Uploaded: <?= date('d M Y', strtotime($ad['created_at'])) ?></span>
                                    <div style="margin-top: 5px; font-size: 0.7rem; color: #00f3ff;">
                                        <i class="fa-solid fa-arrows-left-right"></i> Configured Width: <b><?= $ad['display_width'] ?>px</b>
                                    </div>
                                </div>
                                <div style="padding: 15px; display: flex; align-items: center;">
                                    <a href="?delete_id=<?= $ad['id'] ?>" class="btn-primary" style="background: #ef4444; padding: 6px 12px; font-size: 0.8rem; border:none; text-decoration:none;" onclick="return confirm('Remove this advertisement?');">
                                        <i class="fa-solid fa-trash"></i> REMOVE
                                    </a>
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
