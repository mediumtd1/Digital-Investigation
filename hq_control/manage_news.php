<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
check_auth('Admin');

$portal = 'admin';
$page_title = 'Manage Cyber News | ' . SITE_NAME;

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM cyber_news WHERE id = ?");
    if ($stmt->execute([$id])) {
        $_SESSION['toast_msg'] = "News item deleted successfully.";
        $_SESSION['toast_type'] = "success";
    }
    header("Location: manage_news.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_news'])) {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $news_date = trim($_POST['news_date']);
    $url = trim($_POST['url']);

    
    if (empty($news_date)) {
        $news_date = strtoupper(date('d M Y'));
    }

    $attachment = null;
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = __DIR__ . '/../uploads/news/';
        $file_ext = pathinfo($_FILES['attachment']['name'], PATHINFO_EXTENSION);
        $file_name = time() . '_' . uniqid() . '.' . $file_ext;
        $target_file = $upload_dir . $file_name;
        if (move_uploaded_file($_FILES['attachment']['tmp_name'], $target_file)) {
            $attachment = 'uploads/news/' . $file_name;
        }
    }

    if (!empty($title) && !empty($description)) {
        $stmt = $pdo->prepare("INSERT INTO cyber_news (title, description, news_date, url, attachment) VALUES (?, ?, ?, ?, ?)");
        if ($stmt->execute([$title, $description, $news_date, $url, $attachment])) {
            $_SESSION['toast_msg'] = "News item added successfully.";
            $_SESSION['toast_type'] = "success";
        }
    }
    header("Location: manage_news.php");
    exit;
}

$news_items = $pdo->query("SELECT * FROM cyber_news ORDER BY created_at DESC")->fetchAll();
?>

<?php require_once __DIR__ . '/../includes/header.php'; ?>

<div class="portal-container">
    <?php include '_sidebar.php'; ?>

    <main class="main-content">
        <header class="topbar">
            <div class="topbar-left">
                <h2>Manage Cyber News</h2>
            </div>
            <div class="topbar-right">
                <button class="btn-primary" onclick="document.getElementById('addNewsModal').style.display='flex'">
                    <i class="fa-solid fa-plus"></i> Add News
                </button>
            </div>
        </header>

        <div class="dashboard-content">
            <div class="glass-panel">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Title</th>
                            <th>Description</th>
                            <th>Links / Docs</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($news_items)): ?>
                            <tr>
                                <td colspan="5" style="text-align: center; color: var(--text-muted);">No news items found.</td>
                            </tr>
                        <?php else: foreach ($news_items as $item): ?>
                            <tr>
                                <td><?= esc($item['news_date']) ?></td>
                                <td style="font-weight: bold;"><?= esc($item['title']) ?></td>
                                <td style="max-width: 300px; font-size: 0.85rem; color: var(--text-muted);">
                                    <?php 
                                        $desc = esc($item['description']);
                                        if (strlen($desc) > 100): 
                                            echo substr($desc, 0, 100) . '... ';
                                    ?>
                                        <a href="javascript:void(0)" class="cyan" onclick="openViewModal(`<?= addslashes(esc($item['title'])) ?>`, `<?= addslashes(esc($item['description'])) ?>`)">Read More</a>
                                    <?php else: echo $desc; endif; ?>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                                        <?php if($item['url']): ?>
                                            <a href="<?= esc($item['url']) ?>" target="_blank" class="cyan" title="Source Link"><i class="fa-solid fa-link"></i></a>
                                        <?php endif; ?>
                                        <?php if($item['attachment']): 
                                            $ext = strtolower(pathinfo($item['attachment'], PATHINFO_EXTENSION));
                                            $is_image = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                                        ?>
                                            <?php if($is_image): ?>
                                                <a href="<?= BASE_URL . '/' . esc($item['attachment']) ?>" target="_blank" title="View Full Image">
                                                    <img src="<?= BASE_URL . '/' . esc($item['attachment']) ?>" style="width: 40px; height: 40px; object-fit: cover; border-radius: 4px; border: 1px solid var(--border-color);">
                                                </a>
                                            <?php else: ?>
                                                <a href="<?= BASE_URL . '/' . esc($item['attachment']) ?>" target="_blank" class="cyan" style="color: #10b981;" title="Download Document"><i class="fa-solid fa-file-arrow-down"></i></a>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <a href="?delete=<?= $item['id'] ?>" class="status-badge" style="background: rgba(239, 68, 68, 0.1); color: #ef4444; text-decoration: none;" onclick="return confirm('Are you sure?')">
                                        <i class="fa-solid fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<div id="addNewsModal" class="cyber-modal" style="display: none;">
    <div class="glass-panel modal-content" style="max-width: 500px;">
        <div class="modal-header">
            <h3>Add Cyber News</h3>
            <span class="close" onclick="this.closest('.cyber-modal').style.display='none'">&times;</span>
        </div>
        <form method="POST" class="cyber-form" enctype="multipart/form-data">
            <input type="hidden" name="add_news" value="1">
            <div class="form-group">
                <label class="form-label">News Title</label>
                <input type="text" name="title" class="form-control" required placeholder="e.g. New deepfake scam alert">
            </div>
            <div class="form-group">
                <label class="form-label">Date (Optional - Defaults to Today)</label>
                <input type="text" name="news_date" class="form-control" placeholder="e.g. 21 APR 2026">
            </div>
            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="5" required placeholder="Full content of the news..."></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">Source URL (Optional)</label>
                <input type="url" name="url" class="form-control" placeholder="https://...">
            </div>
            <div class="form-group">
                <label class="form-label">Attach Image or Document</label>
                <input type="file" name="attachment" class="form-control" style="padding: 8px;">
            </div>
            <button type="submit" class="btn-primary" style="width: 100%; margin-top: 10px;">Post News Item</button>
        </form>
    </div>
</div>

<div id="viewContentModal" class="cyber-modal" style="display: none;">
    <div class="glass-panel modal-content" style="max-width: 700px; width: 90%;">
        <div class="modal-header">
            <h3 id="viewModalTitle">Content Detail</h3>
            <span class="close" onclick="this.closest('.cyber-modal').style.display='none'">&times;</span>
        </div>
        <div id="viewModalBody" style="margin-top: 15px; color: var(--text-main); line-height: 1.6; white-space: pre-wrap; max-height: 70vh; overflow-y: auto; padding-right: 10px;">
            
        </div>
    </div>
</div>

<script>
function openViewModal(title, content) {
    document.getElementById('viewModalTitle').innerText = title;
    document.getElementById('viewModalBody').innerText = content;
    document.getElementById('viewContentModal').style.display = 'flex';
}

// Close modal when clicking outside
window.onclick = function(event) {
    if (event.target.classList.contains('cyber-modal')) {
        event.target.style.display = "none";
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
