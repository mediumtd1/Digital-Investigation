<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
check_auth('Officer');

$portal = 'officer';
$page_title = 'Circulars & Announcements | ' . SITE_NAME;

$stmt = $pdo->prepare("SELECT * FROM announcements WHERE target_role IN ('All', 'Officers_Only') ORDER BY created_at DESC");
$stmt->execute();
$announcements = $stmt->fetchAll();

?>

<?php require_once __DIR__ . '/../includes/header.php'; ?>

<div class="dashboard-wrapper">
    <?php include_once __DIR__ . '/_sidebar.php'; ?>

    <main class="main-content">
        <header class="topbar">
            <div class="topbar-left">
                <h2>HQ Circulars & Announcements</h2>
            </div>
            <div class="topbar-right">
                <div class="user-profile">
                    <img src="../<?= esc($_SESSION['user_photo'] ?? 'assets/images/default-avatar.png') ?>" alt="Profile" class="profile-pic">
                    <div class="user-info">
                        <span class="user-name"><?= esc($_SESSION['user_name']) ?></span>
                        <span class="user-role" style="color: #60a5fa;">Officer • <?= esc($_SESSION['user_mapped_id']) ?></span>
                    </div>
                </div>
            </div>
        </header>

        <div class="dashboard-content">
            <div class="glass-panel" style="border-top: 4px solid #f59e0b;">
                <div style="display: flex; gap: 15px; margin-bottom: 25px; border-bottom: 1px solid var(--border-color); padding-bottom: 15px; align-items: center;">
                    <i class="fa-solid fa-satellite-dish" style="font-size: 2rem; color: #f59e0b;"></i>
                    <div>
                        <h3 style="margin: 0; color: #f8fafc;">Official Communications</h3>
                        <p style="margin: 5px 0 0 0; color: var(--text-muted); font-size: 0.9rem;">Important headquarters updates, directives, and operational materials.</p>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 20px;">
                    <?php if(count($announcements) > 0): ?>
                        <?php foreach($announcements as $ann): 
                            $bg_border = '1px solid rgba(255,255,255,0.05)';
                            $priority_color = '#94a3b8'; 
                            $shadow = '0 4px 6px rgba(0,0,0,0.1)';
                            if ($ann['priority'] == 'Info') { $bg_border = '1px solid rgba(96, 165, 250, 0.4)'; $priority_color = '#60a5fa'; $shadow = '0 0 15px rgba(96,165,250,0.1)'; }
                            if ($ann['priority'] == 'Urgent') { $bg_border = '1px solid rgba(239, 68, 68, 0.4)'; $priority_color = '#ef4444'; $shadow = '0 0 15px rgba(239,68,68,0.1)';}
                        ?>
                        <div style="background: rgba(0,0,0,0.2); border: <?= $bg_border ?>; padding: 20px; border-radius: 8px; box-shadow: <?= $shadow ?>; transition: transform 0.2s;">
                            
                            <div style="display: flex; justify-content: space-between; margin-bottom: 15px; align-items: flex-start;">
                                <span class="badge" style="background: <?= $priority_color ?>; color: #fff; padding: 4px 8px;"><i class="fa-solid fa-circle-info"></i> <?= esc($ann['priority']) ?></span>
                                <span style="font-size: 0.8rem; color: var(--text-muted); text-align: right;"><i class="fa-regular fa-clock"></i><br><?= date('d M, Y', strtotime($ann['created_at'])) ?></span>
                            </div>
                            
                            <h4 style="margin: 0 0 15px 0; font-size: 1.2rem; color: #f8fafc; border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: 10px;"><?= esc($ann['title']) ?></h4>
                            
                            <div style="margin: 0 0 20px 0; font-size: 0.95rem; color: #cbd5e1; line-height: 1.6; white-space: pre-wrap; height: 100px; overflow-y: auto; padding-right: 5px;"><?= esc($ann['message']) ?></div>
                            
                            <?php if(!empty($ann['attachment'])): 
                                $ext = pathinfo($ann['attachment'], PATHINFO_EXTENSION);
                                $icon = 'fa-file';
                                if(in_array($ext, ['pdf'])) $icon = 'fa-file-pdf';
                                if(in_array($ext, ['doc','docx'])) $icon = 'fa-file-word';
                                if(in_array($ext, ['jpg','png'])) $icon = 'fa-file-image';
                                if(in_array($ext, ['zip'])) $icon = 'fa-file-zipper';
                            ?>
                            <div style="margin-top: auto; border-top: 1px solid rgba(255,255,255,0.05); padding-top: 15px; text-align: center;">
                                <a href="../<?= esc($ann['attachment']) ?>" download class="btn-primary" style="display: block; width: 100%; padding: 8px 12px; font-size: 0.9rem; background-color: #3b82f6;"><i class="fa-solid <?= $icon ?>"></i> Review Attached Document</a>
                            </div>
                            <?php else: ?>
                            <div style="margin-top: auto; border-top: 1px solid rgba(255,255,255,0.05); padding-top: 15px; text-align: center; color: var(--text-muted); font-size: 0.85rem;">
                                No attachments linked.
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div style="grid-column: 1 / -1; padding: 50px; text-align: center; color: var(--text-muted);">
                            <i class="fa-solid fa-tower-broadcast" style="font-size: 3rem; margin-bottom: 15px; opacity: 0.5;"></i>
                            <p>No circulars or announcements are currently active from HQ.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
