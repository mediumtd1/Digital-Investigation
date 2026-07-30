<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

check_auth('Intern');

$portal = 'intern';
$page_title = 'Announcements | ' . SITE_NAME;

$intern_batch = '';
try {
    $bs = $pdo->prepare("SELECT batch FROM interns WHERE id = ? LIMIT 1");
    $bs->execute([$_SESSION['user_id']]);
    $intern_batch = $bs->fetchColumn() ?: '';
} catch(Exception $e) {}

$announcement_id = (int)($_GET['id'] ?? 0);
try {
    $stmt = $pdo->query("SELECT * FROM announcements ORDER BY created_at DESC");
    $all_announcements = $stmt->fetchAll() ?: [];
    
    $announcements = [];
    foreach ($all_announcements as $ann) {
        $matches_role = in_array($ann['target_role'], ['All', 'Interns_Only']);
        $matches_batch = (!empty($ann['target_batch']) && !empty($intern_batch) && strcasecmp(trim($ann['target_batch']), trim($intern_batch)) === 0);
        
        
        $matches_id = ($announcement_id > 0 && (int)$ann['id'] === $announcement_id && ($matches_role || $matches_batch));
        
        if ($matches_role || $matches_batch || $matches_id) {
            $announcements[] = $ann;
        }
    }
} catch(Exception $e) {
    $announcements = [];
}
?>

<?php require_once __DIR__ . '/../includes/header.php'; ?>

<style>
/* Modern Tab Bar */
.announcement-tabs {
    display: flex;
    gap: 12px;
    margin-bottom: 25px;
    flex-wrap: wrap;
}
.announcement-tabs .tab-btn {
    background: rgba(255,255,255,0.03);
    color: var(--text-muted);
    border: 1px solid var(--border-color);
    padding: 10px 20px;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.25s ease;
    display: flex;
    align-items: center;
    gap: 8px;
}
.announcement-tabs .tab-btn:hover {
    background: rgba(255,255,255,0.08);
    color: #fff;
    border-color: rgba(255,255,255,0.2);
}
.announcement-tabs .tab-btn.active {
    background: linear-gradient(135deg, #00f3ff, #0984e3);
    color: #000;
    border-color: transparent;
    box-shadow: 0 0 15px rgba(0, 243, 255, 0.35);
}
.announcement-tabs .tab-btn.active-batch {
    background: linear-gradient(135deg, #a855f7, #6b21a8);
    color: #fff;
    border-color: transparent;
    box-shadow: 0 0 15px rgba(168, 85, 247, 0.4);
}
.announcement-card {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.announcement-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
}
@keyframes highlight-pulse {
    0% { box-shadow: 0 0 0 0 rgba(0, 243, 255, 0.6); border-color: #00f3ff; }
    50% { box-shadow: 0 0 25px 8px rgba(0, 243, 255, 0.3); border-color: #00f3ff; }
    100% { box-shadow: 0 0 0 0 rgba(0, 243, 255, 0); border-color: rgba(255,255,255,0.05); }
}
.card-highlight {
    animation: highlight-pulse 2s infinite;
    background: rgba(0, 243, 255, 0.08) !important;
}
</style>

<div class="dashboard-wrapper">
    <?php include_once __DIR__ . '/_sidebar.php'; ?>

    <main class="main-content">
        <header class="topbar">
            <div class="topbar-left">
                <h2>HQ Announcements &amp; Materials</h2>
            </div>
            <div class="topbar-right">
                <div class="user-profile">
                    <?php
                    $u_photo = $_SESSION['user_photo'] ?? '';
                    $u_name  = $_SESSION['user_name'] ?? 'U';
                    if (!empty($u_photo) && file_exists(__DIR__ . '/../' . $u_photo)) {
                        $photo_url = '../' . $u_photo;
                    } else {
                        $photo_url = "https://ui-avatars.com/api/?name=" . urlencode($u_name) . "&background=00f3ff&color=000&bold=true";
                    }
                    ?>
                    <img src="<?= esc($photo_url) ?>" alt="Profile" class="profile-pic" style="width:45px;height:45px;object-fit:cover;border-radius:50%;border:2px solid var(--accent-blue);">
                    <div class="user-info">
                        <span class="user-name"><?= esc($u_name) ?></span>
                        <span class="user-role">Intern &bull; <?= esc($_SESSION['user_mapped_id'] ?? '') ?></span>
                    </div>
                </div>
            </div>
        </header>

        <div class="dashboard-content">
            <div class="glass-panel">
                <div style="display: flex; gap: 15px; margin-bottom: 25px; border-bottom: 1px solid var(--border-color); padding-bottom: 15px; align-items: center;">
                    <i class="fa-solid fa-bullhorn" style="font-size: 2rem; color: var(--accent-highlight);"></i>
                    <div>
                        <h3 style="margin: 0;">Notice Board</h3>
                        <p style="margin: 5px 0 0 0; color: var(--text-muted); font-size: 0.9rem;">
                            Official updates, study materials, and batch-specific assignments from HQ Command.
                            <?php if (!empty($intern_batch)): ?>
                                <span style="background:rgba(168,85,247,0.15); color:#a855f7; font-size:0.75rem; padding:2px 10px; border-radius:20px; margin-left:8px; font-weight:bold; border:1px solid rgba(168,85,247,0.3);">
                                    <i class="fa-solid fa-layer-group"></i> <?= esc($intern_batch) ?>
                                </span>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>

                
                <div class="announcement-tabs">
                    <button class="tab-btn active" data-tab="all" onclick="switchTab('all')">
                        <i class="fa-solid fa-border-all"></i> All Updates
                    </button>
                    <button class="tab-btn" data-tab="general" onclick="switchTab('general')">
                        <i class="fa-solid fa-bullhorn"></i> Official Updates
                    </button>
                    <button class="tab-btn" data-tab="batch" onclick="switchTab('batch')">
                        <i class="fa-solid fa-layer-group"></i> Batch Announcements
                    </button>
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 20px;">
                    <?php if (count($announcements) > 0): ?>
                        <?php foreach ($announcements as $ann): 
                            $is_batch = (!empty($ann['target_batch']) || strcasecmp($ann['target_role'] ?? '', 'Batch') === 0);
                            $bg_border = '1px solid rgba(255,255,255,0.05)';
                            $priority_color = '#94a3b8';
                            $shadow = '0 4px 6px rgba(0,0,0,0.1)';
                            if ($ann['priority'] == 'Info')   { $bg_border = '1px solid rgba(96,165,250,0.4)';  $priority_color = '#60a5fa'; $shadow = '0 0 15px rgba(96,165,250,0.1)'; }
                            if ($ann['priority'] == 'Urgent') { $bg_border = '1px solid rgba(239,68,68,0.4)';   $priority_color = '#ef4444'; $shadow = '0 0 15px rgba(239,68,68,0.1)'; }
                            if ($is_batch)                    { $bg_border = '1px solid rgba(168,85,247,0.4)'; }
                        ?>
                        <div class="announcement-card" id="ann-<?= $ann['id'] ?>" data-category="<?= $is_batch ? 'batch' : 'general' ?>" style="background: rgba(0,0,0,0.2); border: <?= $bg_border ?>; padding: 20px; border-radius: 8px; box-shadow: <?= $shadow ?>; position: relative; overflow: hidden;">
                            
                            <?php if ($is_batch): ?>
                            
                            <div style="position:absolute; top:0; left:0; right:0; height:3px; background:linear-gradient(90deg,transparent,#a855f7,transparent);"></div>
                            <?php endif; ?>

                            <div style="display: flex; justify-content: justify-between; margin-bottom: 12px; align-items: flex-start; flex-wrap: wrap; gap: 8px;">
                                <div style="display: flex; gap: 8px; flex-wrap: wrap; align-items: center; width: 100%; justify-content: space-between;">
                                    <div style="display: flex; gap: 8px; align-items: center;">
                                        <span class="badge" style="background: <?= $priority_color ?>; color: #fff; padding: 4px 8px;">
                                            <i class="fa-solid fa-circle-info"></i> <?= esc($ann['priority']) ?>
                                        </span>
                                        <?php if ($is_batch): ?>
                                        <span style="background: rgba(168,85,247,0.15); color: #a855f7; font-size: 0.72rem; padding: 3px 8px; border-radius: 20px; font-weight: bold; border: 1px solid rgba(168,85,247,0.35);">
                                            <i class="fa-solid fa-layer-group"></i> <?= esc($ann['target_batch'] ?? '') ?>
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                    <span style="font-size: 0.8rem; color: var(--text-muted); text-align: right;">
                                        <i class="fa-regular fa-clock"></i> <?= date('d M, Y', strtotime($ann['created_at'])) ?>
                                    </span>
                                </div>
                            </div>

                            <h4 style="margin: 0 0 12px 0; font-size: 1.1rem; color: #f8fafc; border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: 10px;">
                                <?= esc($ann['title']) ?>
                            </h4>

                            <div style="margin: 0 0 20px 0; font-size: 0.92rem; color: #cbd5e1; line-height: 1.6; white-space: pre-wrap; max-height: 110px; overflow-y: auto; padding-right: 5px;">
                                <?= esc($ann['message']) ?>
                            </div>

                            <?php if (!empty($ann['attachment'])): 
                                $ext  = pathinfo($ann['attachment'], PATHINFO_EXTENSION);
                                $icon = 'fa-file';
                                if (in_array($ext, ['pdf']))        $icon = 'fa-file-pdf';
                                if (in_array($ext, ['doc','docx'])) $icon = 'fa-file-word';
                                if (in_array($ext, ['jpg','png']))  $icon = 'fa-file-image';
                                if (in_array($ext, ['zip']))        $icon = 'fa-file-zipper';
                            ?>
                            <div style="border-top: 1px solid rgba(255,255,255,0.05); padding-top: 15px; text-align: center;">
                                <a href="../<?= esc($ann['attachment']) ?>" download class="btn-primary" style="display:block; width:100%; padding:8px 12px; font-size:0.9rem;">
                                    <i class="fa-solid <?= $icon ?>"></i> View / Download Material
                                </a>
                            </div>
                            <?php else: ?>
                            <div style="border-top: 1px solid rgba(255,255,255,0.05); padding-top: 15px; text-align: center; color: var(--text-muted); font-size: 0.85rem;">
                                No attachments provided.
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div style="grid-column: 1 / -1; padding: 50px; text-align: center; color: var(--text-muted);">
                            <i class="fa-regular fa-folder-open" style="font-size: 3rem; margin-bottom: 15px; opacity: 0.5;"></i>
                            <p>No announcements or materials are available at the moment.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
</div>

<script>
function switchTab(category) {
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active', 'active-batch'));
    
    const activeBtn = document.querySelector(`.tab-btn[data-tab="${category}"]`);
    if (activeBtn) {
        if (category === 'batch') {
            activeBtn.classList.add('active-batch');
        } else {
            activeBtn.classList.add('active');
        }
    }
    
    document.querySelectorAll('.announcement-card').forEach(card => {
        if (category === 'all') {
            card.style.display = 'block';
        } else {
            if (card.getAttribute('data-category') === category) {
                card.style.display = 'block';
            } else {
                card.style.display = 'none';
            }
        }
    });
}

document.addEventListener("DOMContentLoaded", function() {
    const urlParams = new URLSearchParams(window.location.search);
    const annId = urlParams.get('id');
    if (annId) {
        const targetCard = document.getElementById('ann-' + annId);
        if (targetCard) {
            // Keep on 'all' tab so old/all announcements remain visible while highlighting the specific card
            switchTab('all');
            targetCard.classList.add('card-highlight');
            setTimeout(() => {
                targetCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }, 300);
            setTimeout(() => {
                targetCard.classList.remove('card-highlight');
            }, 6000);
        }
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

