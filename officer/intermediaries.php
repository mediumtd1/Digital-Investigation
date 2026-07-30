<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
check_auth('Officer');

$portal = 'officer';
$page_title = 'Nodal Officer Intermediaries | ' . SITE_NAME;

$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$directories = [];
if ($search) {
    $query = "SELECT * FROM directories WHERE (operator_name LIKE ? OR nodal_name LIKE ? OR email LIKE ? OR mobile LIKE ?)";
    $params = [
        "%$search%",
        "%$search%",
        "%$search%",
        "%$search%"
    ];
    $query .= " ORDER BY operator_name ASC";
    $items = $pdo->prepare($query);
    $items->execute($params);
    $directories = $items->fetchAll();
}
?>

<?php require_once __DIR__ . '/../includes/header.php'; ?>

<div class="portal-container">
    <?php include '_sidebar.php'; ?>

    <main class="main-content no-select">
        <header class="topbar">
            <div class="topbar-left">
                <h2>Nodal Officer Intermediaries</h2>
            </div>
            <?php if ($search): ?>
            <div class="topbar-right">
                <div class="status-badge" style="background: rgba(0, 243, 255, 0.1); color: #00f3ff;">
                    <i class="fa-solid fa-address-book"></i> <?= count($directories) ?> Intermediaries
                </div>
            </div>
            <?php endif; ?>
        </header>

        <div class="dashboard-content">
            
            <div class="glass-panel" style="margin-bottom: 25px; padding: 20px;">
                <form method="GET" style="display: flex; gap: 15px; align-items: center; flex-wrap: wrap;">
                    <div style="flex: 1; min-width: 300px; position: relative;">
                        <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: var(--text-muted);"></i>
                        <input type="text" name="search" class="form-control" style="padding-left: 45px;" placeholder="Search by Company, Nodal Name, Email, or Phone..." value="<?= esc($search) ?>">
                    </div>
                    <button type="submit" class="btn-primary">
                        Search
                    </button>
                    <?php if($search): ?>
                        <a href="intermediaries.php" class="btn-primary" style="background: rgba(255,255,255,0.05); text-decoration: none; border: 1px solid var(--border-color);">Reset</a>
                    <?php endif; ?>
                </form>
            </div>

            
            <div style="display: flex; flex-direction: column; gap: 10px;">
                <?php if (!$search): ?>
                    <div class="glass-panel" style="text-align: center; padding: 50px;">
                        <i class="fa-solid fa-magnifying-glass" style="font-size: 3rem; color: var(--text-muted); margin-bottom: 15px;"></i>
                        <p style="color: var(--text-muted);">Please enter a query in the search bar above to search Intermediaries.</p>
                    </div>
                <?php elseif (empty($directories)): ?>
                    <div class="glass-panel" style="text-align: center; padding: 50px;">
                        <i class="fa-solid fa-ghost" style="font-size: 3rem; color: var(--text-muted); margin-bottom: 15px;"></i>
                        <p style="color: var(--text-muted);">No intermediaries found matching your search.</p>
                    </div>
                <?php else: ?>
                    <div class="glass-panel" style="display: flex; padding: 12px 20px; background: rgba(0,0,0,0.4); border-bottom: 2px solid rgba(0, 243, 255, 0.3); font-size: 0.8rem; font-weight: bold; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px;">
                        <div style="width: 60px;">Logo</div>
                        <div style="flex: 2; min-width: 200px;">Operator</div>
                        <div style="flex: 2; min-width: 150px;">Nodal Officer</div>
                        <div style="flex: 2; min-width: 200px;">Contact Info</div>
                        <div style="width: 50px; text-align: center;">Action</div>
                    </div>
                <?php foreach ($directories as $item): ?>
                    <div class="glass-panel dir-card" style="padding: 15px 20px; transition: all 0.2s; border: 1px solid rgba(255,255,255,0.05); cursor: pointer; display: flex; align-items: center; gap: 15px;" onclick='openVisitingCard(<?= htmlspecialchars(json_encode($item), ENT_QUOTES, "UTF-8") ?>)'>
                        
                        <div style="width: 60px; display: flex; justify-content: center;">
                            <?php if($item['logo']): ?>
                                <img src="<?= BASE_URL . '/' . esc($item['logo']) ?>" style="width: 45px; height: 45px; object-fit: contain; background: white; padding: 3px; border-radius: 6px;">
                            <?php else: ?>
                                <div style="width: 45px; height: 45px; background: rgba(0, 243, 255, 0.05); border-radius: 6px; display: flex; align-items: center; justify-content: center; color: #00f3ff; border: 1px solid rgba(0, 243, 255, 0.2);">
                                    <i class="fa-solid fa-building" style="font-size: 1.2rem;"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div style="flex: 2; min-width: 200px;">
                            <div style="color: #fff; font-size: 1.1rem; font-weight: 600;"><?= esc($item['operator_name']) ?></div>
                        </div>

                        <div style="flex: 2; min-width: 150px;">
                            <div style="font-weight: 600; color: #eab308; font-size: 0.95rem;"><?= esc($item['nodal_name'] ?: 'Not Provided') ?></div>
                        </div>

                        <div style="flex: 2; min-width: 200px; display: flex; flex-direction: column; gap: 4px;">
                            <div style="display: flex; align-items: center; gap: 8px; font-size: 0.85rem; color: #e2e8f0;">
                                <i class="fa-solid fa-envelope" style="color: #00f3ff; width: 14px;"></i>
                                <span style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 200px;"><?= esc($item['email']) ?></span>
                            </div>
                            <div style="display: flex; align-items: center; gap: 8px; font-size: 0.85rem; color: #e2e8f0;">
                                <i class="fa-solid fa-phone" style="color: #eab308; width: 14px;"></i>
                                <span><?= esc($item['mobile']) ?></span>
                            </div>
                        </div>

                        <div style="width: 50px; text-align: center; color: var(--text-muted);">
                            <i class="fa-solid fa-chevron-right"></i>
                        </div>
                    </div>
                <?php endforeach; endif; ?>
            </div>
        </div>
    </main>
</div>

<div id="visitingCardModal" class="cyber-modal" style="display: none;">
    <div class="modal-content" style="background: transparent; border: none; box-shadow: none; max-width: 500px;">
        <div class="visiting-card shadow-lg" id="visitingCard">
            <span class="close" style="position: absolute; right: 20px; top: 15px; color: #fff; z-index: 10;" onclick="closeVisitingCard()">&times;</span>
            
            <div class="card-header-accent"></div>
            
            <div class="card-body-content">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 25px;">
                    <div id="vc_logo_container">
                        <img id="vc_logo" src="" style="max-height: 60px; max-width: 120px; background: white; padding: 5px; border-radius: 5px; border: 1px solid rgba(255,255,255,0.1);">
                    </div>
                    <div style="text-align: right;">
                        <div style="color: rgba(255,255,255,0.5); font-size: 0.7rem; margin-top: 2px;">CERTIFIED PARTNER</div>
                    </div>
                </div>

                <h2 id="vc_operator" style="color: #fff; margin: 0 0 5px 0; font-size: 1.6rem; letter-spacing: -0.5px;"></h2>
                <div style="width: 40px; height: 3px; background: #00f3ff; margin-bottom: 20px;"></div>

                <div style="margin-bottom: 25px;">
                    <div style="color: rgba(255,255,255,0.5); font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 5px;">Nodal Officer</div>
                    <div id="vc_nodal" style="color: #eab308; font-size: 1.2rem; font-weight: 600;"></div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr; gap: 15px;">
                    <div class="vc-info-item">
                        <i class="fa-solid fa-envelope"></i>
                        <span id="vc_email"></span>
                    </div>
                    <div class="vc-info-item">
                        <i class="fa-solid fa-phone"></i>
                        <span id="vc_mobile"></span>
                    </div>
                    <div class="vc-info-item">
                        <i class="fa-solid fa-location-dot"></i>
                        <span id="vc_address" style="font-size: 0.8rem; line-height: 1.4;"></span>
                    </div>
                </div>
            </div>
            
            <div class="card-footer-branding">
                <span>DIGITAL INVESTIGATION PORTAL</span>
                <i class="fa-solid fa-shield-halved"></i>
            </div>
        </div>
    </div>
</div>

<style>
.visiting-card {
    background: linear-gradient(135deg, #1a1c2c 0%, #0d0e17 100%);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 15px;
    width: 100%;
    position: relative;
    overflow: hidden;
    color: #fff;
    padding: 0;
    font-family: 'Inter', sans-serif;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
}

.card-header-accent {
    height: 10px;
    background: linear-gradient(90deg, #00f3ff, #eab308);
}

.card-body-content {
    padding: 40px;
}

.vc-info-item {
    display: flex;
    align-items: center;
    gap: 15px;
}

.vc-info-item i {
    color: #00f3ff;
    width: 20px;
    font-size: 1.1rem;
}

.card-footer-branding {
    background: rgba(255, 255, 255, 0.03);
    padding: 15px 40px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.7rem;
    color: rgba(255, 255, 255, 0.3);
    letter-spacing: 2px;
}

.visiting-card::after {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(0, 243, 255, 0.05) 0%, transparent 70%);
    pointer-events: none;
}

.dir-card:hover {
    transform: translateY(-5px);
    border-color: rgba(0, 243, 255, 0.3) !important;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
}
</style>

<script>
function openVisitingCard(data) {
    document.getElementById('vc_operator').innerText = data.operator_name;
    document.getElementById('vc_nodal').innerText = data.nodal_name || 'Not Available';
    document.getElementById('vc_email').innerText = data.email || 'N/A';
    document.getElementById('vc_mobile').innerText = data.mobile || 'N/A';
    document.getElementById('vc_address').innerText = data.address || 'Address not listed';
    
    if(data.logo) {
        document.getElementById('vc_logo').src = '<?= BASE_URL ?>/' + data.logo;
        document.getElementById('vc_logo_container').style.display = 'block';
    } else {
        document.getElementById('vc_logo_container').style.display = 'none';
    }
    
    document.getElementById('visitingCardModal').style.display = 'flex';
}

function closeVisitingCard() {
    document.getElementById('visitingCardModal').style.display = 'none';
}

window.onclick = function(event) {
    if (event.target.classList.contains('cyber-modal')) {
        closeVisitingCard();
    }
}
</script>
