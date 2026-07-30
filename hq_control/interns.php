<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

check_auth('Admin');

$portal     = 'admin';
$page_title = 'All Interns | ' . SITE_NAME;

try { $pdo->exec("ALTER TABLE interns ADD COLUMN batch VARCHAR(50) DEFAULT NULL"); } catch(PDOException $e) {}

try {
    $pdo->exec("UPDATE interns SET batch = CONCAT(batch, ' Batch') WHERE batch IS NOT NULL AND batch != '' AND LOWER(batch) NOT LIKE '%batch%'");
} catch(PDOException $e) {}

$filter_search      = trim($_GET['q']           ?? '');
$filter_batch       = trim($_GET['batch']        ?? '');
$filter_status      = trim($_GET['status']       ?? '');
$filter_gender      = trim($_GET['gender']       ?? '');
$filter_country     = trim($_GET['country_type'] ?? ''); 
$filter_sort        = trim($_GET['sort']         ?? 'newest');

$batches = [];
try {
    $batches = $pdo->query("SELECT DISTINCT batch FROM interns WHERE batch IS NOT NULL AND batch != '' ORDER BY batch ASC")->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {}

$interns  = [];
$db_error = false;
$total_count = 0;
$indian_count = 0;
$intl_count = 0;
$active_count = 0;
$referred_count = 0;

// ─── PAGINATION ───────────────────────────────────────────────────────────────
$per_page  = 50;
$page_num  = max(1, (int)($_GET['page'] ?? 1));
$offset    = ($page_num - 1) * $per_page;

// ─── STEP 1: build WHERE clause ───────────────────────────────────────────────
$where  = [];
$params = [];

if ($filter_search !== '') {
    $like = '%' . $filter_search . '%';
    $where[]  = "(name LIKE ? OR email LIKE ? OR intern_id LIKE ? OR college LIKE ? OR mobile LIKE ?)";
    array_push($params, $like, $like, $like, $like, $like);
}
if ($filter_batch !== '') {
    $where[]  = "(batch = ? OR batch = CONCAT(?, ' Batch'))";
    $params[] = $filter_batch;
    $params[] = rtrim($filter_batch, ' Batch');
}
if ($filter_status !== '') {
    $where[]  = "status = ?";
    $params[] = $filter_status;
}
if ($filter_gender !== '') {
    $where[]  = "gender = ?";
    $params[] = $filter_gender;
}
if ($filter_country === 'India') {
    $where[] = "(country = 'India' OR country IS NULL OR country = '')";
} elseif ($filter_country === 'International') {
    $where[] = "(country != 'India' AND country IS NOT NULL AND country != '')";
}
$where_sql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// ─── STEP 2: stat chip counts (isolated — failure is safe) ────────────────────
try {
    $ci = $pdo->prepare("SELECT COUNT(*) FROM interns " . (!empty($where) ? $where_sql . " AND " : "WHERE ") . "(country = 'India' OR country IS NULL OR country = '')");
    $ci->execute($params);
    $indian_count = (int)$ci->fetchColumn();
    
    $ct = $pdo->prepare("SELECT COUNT(*) FROM interns $where_sql");
    $ct->execute($params);
    $total_count = (int)$ct->fetchColumn();
    
    $intl_count   = $total_count - $indian_count;
} catch (Exception $e) { $indian_count = 0; $intl_count = 0; }

try {
    $ca = $pdo->prepare("SELECT COUNT(*) FROM interns " . (!empty($where) ? $where_sql . " AND " : "WHERE ") . "status = 'Active'");
    $ca->execute($params);
    $active_count = (int)$ca->fetchColumn();
} catch (Exception $e) { $active_count = 0; }

try {
    $cr = $pdo->prepare("SELECT COUNT(*) FROM interns " . (!empty($where) ? $where_sql . " AND " : "WHERE ") . "referral_id IS NOT NULL AND referral_id != ''");
    $cr->execute($params);
    $referred_count = (int)$cr->fetchColumn();
} catch (Exception $e) { $referred_count = 0; }

// ── DATA FETCH (paginated) ────────────────────────────────────────────────────
try {

    // Sort order
    if ($filter_sort === 'oldest') {
        $order = 'ORDER BY created_at ASC';
    } elseif ($filter_sort === 'name') {
        $order = 'ORDER BY name ASC';
    } else {
        $order = 'ORDER BY created_at DESC';
    }

    // Total count for pagination
    $count_sql  = "SELECT COUNT(*) FROM interns $where_sql";
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($params);
    $total_count = (int)$count_stmt->fetchColumn();
    $total_pages = (int)ceil($total_count / $per_page);
    $page_num    = min($page_num, max(1, $total_pages));

    // Fetch only columns needed for the table (avoid loading heavy blobs)
    $sql = "SELECT id, name, intern_id, email, mobile, college, city, state, country,
                   profile_photo, batch, status, gender, referral_id, created_at
            FROM interns $where_sql $order LIMIT $per_page OFFSET $offset";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $interns = $stmt->fetchAll();

} catch (Exception $e) {
    $db_error = true;
    $total_pages = 1;
}

require_once __DIR__ . '/../includes/header.php';
?>

<style>
.filter-bar {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    align-items: center;
    margin-bottom: 20px;
}
.filter-bar .form-control {
    padding: 8px 12px;
    font-size: 0.85rem;
    background: rgba(255,255,255,0.04);
    border: 1px solid rgba(0,243,255,0.2);
    color: #fff;
    border-radius: 6px;
}
.filter-bar .form-control:focus {
    outline: none;
    border-color: rgba(0,243,255,0.5);
    background: rgba(255,255,255,0.07);
}
.search-input-wrap {
    position: relative;
    flex: 1;
    min-width: 200px;
}
.search-input-wrap i {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #475569;
    font-size: 0.85rem;
}
.search-input-wrap input {
    padding-left: 34px !important;
    width: 100%;
}
.country-toggle {
    display: flex;
    gap: 0;
    border: 1px solid rgba(0,243,255,0.2);
    border-radius: 6px;
    overflow: hidden;
}
.country-toggle a {
    padding: 8px 14px;
    font-size: 0.8rem;
    font-weight: 600;
    text-decoration: none;
    color: #64748b;
    transition: 0.2s;
    white-space: nowrap;
}
.country-toggle a:hover { background: rgba(255,255,255,0.05); color: #94a3b8; }
.country-toggle a.active-all  { background: rgba(0,243,255,0.12); color: #00f3ff; }
.country-toggle a.active-india { background: rgba(16,185,129,0.12); color: #10b981; }
.country-toggle a.active-intl  { background: rgba(168,85,247,0.12); color: #a855f7; }
.clear-link {
    font-size: 0.78rem; color: #64748b; text-decoration: none;
    display: flex; align-items: center; gap: 5px; white-space: nowrap;
}
.clear-link:hover { color: #94a3b8; }
.stat-chip {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 0.78rem;
    font-weight: 700;
    border: 1px solid;
}
.dataTable td { padding: 14px 12px !important; }
.country-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 0.7rem;
    padding: 2px 8px;
    border-radius: 4px;
    font-weight: 600;
}
.referral-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 0.68rem;
    padding: 2px 7px;
    border-radius: 4px;
    font-weight: 600;
    background: rgba(168,85,247,0.1);
    color: #a855f7;
    border: 1px solid rgba(168,85,247,0.25);
    margin-top: 3px;
}
</style>

<div class="portal-container">
    <?php include_once __DIR__ . '/_sidebar.php'; ?>

    <main class="main-content">
        <header class="topbar">
            <div class="topbar-left">
                <h2><i class="fa-solid fa-users" style="color:var(--accent-highlight);"></i> Intern Directory</h2>
            </div>
            <div class="topbar-right">
                <span class="user-status"><i class="fa-solid fa-circle-check" style="color:var(--success);"></i> System Admin</span>
            </div>
        </header>

        <div class="dashboard-content">

            
            <div style="display:flex; flex-wrap:wrap; gap:10px; margin-bottom:18px;">
                <span class="stat-chip" style="background:rgba(0,243,255,0.07); color:#00f3ff; border-color:rgba(0,243,255,0.2);">
                    <i class="fa-solid fa-users"></i> <?= $total_count ?> Shown
                </span>
                <span class="stat-chip" style="background:rgba(16,185,129,0.07); color:#10b981; border-color:rgba(16,185,129,0.2);">
                    <i class="fa-solid fa-flag"></i> <?= $indian_count ?> Indian
                </span>
                <span class="stat-chip" style="background:rgba(168,85,247,0.07); color:#a855f7; border-color:rgba(168,85,247,0.2);">
                    <i class="fa-solid fa-globe"></i> <?= $intl_count ?> International
                </span>
                <span class="stat-chip" style="background:rgba(34,197,94,0.07); color:#22c55e; border-color:rgba(34,197,94,0.2);">
                    <i class="fa-solid fa-circle-check"></i> <?= $active_count ?> Active
                </span>
                <span class="stat-chip" style="background:rgba(168,85,247,0.07); color:#a855f7; border-color:rgba(168,85,247,0.2);">
                    <i class="fa-solid fa-user-plus"></i> <?= $referred_count ?> via Referral
                </span>
            </div>

            <div class="glass-panel">
                
                <form method="GET" id="filterForm">
                    <div style="display: flex; flex-direction: column; gap: 15px; margin-bottom: 25px;">
                        
                        
                        <div style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
                            
                            <div class="search-input-wrap" style="flex-grow: 1; min-width: 250px;">
                                <i class="fa-solid fa-magnifying-glass"></i>
                                <input type="text" name="q" class="form-control" id="searchInput"
                                    value="<?= esc($filter_search) ?>"
                                    placeholder="Search by name, email, intern ID, college, mobile..."
                                    autocomplete="off">
                            </div>

                            
                            <div class="country-toggle">
                                <a href="?<?= http_build_query(array_merge($_GET, ['country_type'=>''])) ?>"
                                   class="<?= $filter_country === '' ? 'active-all' : '' ?>">
                                    <i class="fa-solid fa-earth-asia"></i> All
                                </a>
                                <a href="?<?= http_build_query(array_merge($_GET, ['country_type'=>'India'])) ?>"
                                   class="<?= $filter_country === 'India' ? 'active-india' : '' ?>">
                                    <i class="fa-solid fa-flag"></i> Indian
                                </a>
                                <a href="?<?= http_build_query(array_merge($_GET, ['country_type'=>'International'])) ?>"
                                   class="<?= $filter_country === 'International' ? 'active-intl' : '' ?>">
                                    <i class="fa-solid fa-globe"></i> International
                                </a>
                            </div>

                            
                            <button type="submit" class="btn-primary" style="padding: 10px 24px; font-size: 0.85rem; display: flex; align-items: center; gap: 8px; font-weight: bold; background: linear-gradient(135deg, #00f3ff, #6366f1); border: none; color: #020617; box-shadow: 0 0 15px rgba(0, 243, 255, 0.25);">
                                <i class="fa-solid fa-magnifying-glass"></i> Apply Filters
                            </button>
                        </div>

                        
                        <div style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center; background: rgba(255,255,255,0.02); padding: 12px 16px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.04);">
                            <span style="font-size: 0.72rem; color: #64748b; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; margin-right: 5px;">
                                <i class="fa-solid fa-sliders"></i> Filter By:
                            </span>

                            
                            <div style="display: flex; flex-direction: column; gap: 4px;">
                                <select name="batch" class="form-control" style="min-width:160px; background: #0f172a; border-color: rgba(255,255,255,0.1); font-size:0.8rem; padding: 6px 12px;">
                                    <option value="">All Batches</option>
                                    <?php foreach ($batches as $b): ?>
                                    <option value="<?= esc($b) ?>" <?= $filter_batch === $b ? 'selected' : '' ?>><?= esc($b) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            
                            <div style="display: flex; flex-direction: column; gap: 4px;">
                                <select name="status" class="form-control" style="min-width:130px; background: #0f172a; border-color: rgba(255,255,255,0.1); font-size:0.8rem; padding: 6px 12px;">
                                    <option value="">All Status</option>
                                    <option value="Active"    <?= $filter_status === 'Active'    ? 'selected' : '' ?>>Active</option>
                                    <option value="Inactive"  <?= $filter_status === 'Inactive'  ? 'selected' : '' ?>>Inactive</option>
                                    <option value="Suspended" <?= $filter_status === 'Suspended' ? 'selected' : '' ?>>Suspended</option>
                                </select>
                            </div>

                            
                            <div style="display: flex; flex-direction: column; gap: 4px;">
                                <select name="gender" class="form-control" style="min-width:120px; background: #0f172a; border-color: rgba(255,255,255,0.1); font-size:0.8rem; padding: 6px 12px;">
                                    <option value="">All Gender</option>
                                    <option value="Male"        <?= $filter_gender === 'Male'        ? 'selected' : '' ?>>Male</option>
                                    <option value="Female"      <?= $filter_gender === 'Female'      ? 'selected' : '' ?>>Female</option>
                                    <option value="Transgender" <?= $filter_gender === 'Transgender' ? 'selected' : '' ?>>Transgender</option>
                                </select>
                            </div>

                            
                            <div style="display: flex; flex-direction: column; gap: 4px; margin-left: auto;">
                                <select name="sort" class="form-control" style="min-width:130px; background: #0f172a; border-color: rgba(255,255,255,0.1); font-size:0.8rem; padding: 6px 12px;">
                                    <option value="newest" <?= $filter_sort === 'newest' ? 'selected' : '' ?>>Newest First</option>
                                    <option value="oldest" <?= $filter_sort === 'oldest' ? 'selected' : '' ?>>Oldest First</option>
                                    <option value="name"   <?= $filter_sort === 'name'   ? 'selected' : '' ?>>Name A–Z</option>
                                </select>
                            </div>

                            
                            <input type="hidden" name="country_type" value="<?= esc($filter_country) ?>">

                            
                            <?php if ($filter_search || $filter_batch || $filter_status || $filter_gender || $filter_country || $filter_sort !== 'newest'): ?>
                            <a href="interns.php" class="clear-link" style="margin-left: 10px; border-left: 1px solid rgba(255,255,255,0.1); padding-left: 15px;">
                                <i class="fa-solid fa-rotate-left"></i> Reset
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>

                <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid var(--border-color); padding-bottom:14px; margin-bottom:20px;">
                    <h3 style="margin:0; color:var(--text-primary); font-size:1rem;">
                        <i class="fa-solid fa-table-list" style="color:var(--accent-highlight);"></i>
                        <?php if ($filter_search || $filter_batch || $filter_status || $filter_gender || $filter_country): ?>
                            Filtered Results — <span style="color:#00f3ff;"><?= $total_count ?></span> intern<?= $total_count !== 1 ? 's' : '' ?> found
                        <?php else: ?>
                            All Registered Interns — <span style="color:#00f3ff;"><?= $total_count ?></span>
                        <?php endif; ?>
                    </h3>
                    <div style="font-size:0.78rem; color:#64748b; display:flex; gap:15px; align-items:center;">
                        <?php if ($filter_search): ?>
                            Searching: <span style="color:#00f3ff;">"<?= esc($filter_search) ?>"</span>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($db_error): ?>
                    <div style="background:rgba(239,68,68,0.1); border:1px solid #ef4444; color:#f87171; padding:20px; border-radius:8px; display:flex; align-items:center; gap:15px;">
                        <i class="fa-solid fa-triangle-exclamation" style="font-size:2rem;"></i>
                        <div>
                            <strong>Database Error</strong><br>
                            <span style="font-size:0.85rem;">Could not load intern records. Please check the database connection.</span>
                        </div>
                    </div>
                <?php elseif (empty($interns)): ?>
                    <div style="text-align:center; padding:60px 20px; color:#475569;">
                        <i class="fa-solid fa-user-slash" style="font-size:3rem; margin-bottom:15px; display:block; opacity:0.35;"></i>
                        <div style="font-size:1rem; font-weight:600; margin-bottom:6px;">No interns found</div>
                        <div style="font-size:0.85rem;">Try adjusting your search or filters.</div>
                        <?php if ($filter_search || $filter_batch || $filter_status || $filter_gender || $filter_country): ?>
                        <a href="interns.php" style="display:inline-block; margin-top:16px; color:#00f3ff; font-size:0.85rem; text-decoration:none;">
                            <i class="fa-solid fa-rotate-left"></i> Clear all filters
                        </a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div style="overflow-x:auto; width:100%;">
                    <table class="dataTable" style="min-width:1150px;">
                        <thead>
                            <tr>
                                <th style="width:50px;">Photo</th>
                                <th><?= __('full_name') ?></th>
                                <th><?= __('college') ?></th>
                                <th>Location / Type</th>
                                <th><?= __('mobile') ?></th>
                                <th>Batch</th>
                                <th>Registered</th>
                                <th><?= __('status') ?></th>
                                <th><?= __('actions') ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($interns as $itn):
                                $name_enc  = urlencode($itn['name'] ?? 'Intern');
                                $avatar_url = "https://ui-avatars.com/api/?name={$name_enc}&background=0D8ABC&color=fff&size=150";
                                $photo_url  = !empty($itn['profile_photo']) ? '../' . ltrim($itn['profile_photo'], '/') : $avatar_url;
                                $is_india   = (empty($itn['country']) || $itn['country'] === 'India');
                                $location   = $is_india
                                    ? trim(($itn['city'] ?? '') . ($itn['state'] ? ', ' . $itn['state'] : ''), ', ')
                                    : trim(($itn['city'] ?? '') . ($itn['state'] ? ', ' . $itn['state'] : '') . ', ' . ($itn['country'] ?? ''), ', ');
                            ?>
                            <tr>
                                <td>
                                    <img src="<?= esc($photo_url) ?>" alt="Avatar" loading="lazy"
                                        style="width:42px; height:42px; border-radius:50%; object-fit:cover; border:2px solid <?= $is_india ? '#10b981' : '#a855f7' ?>;">
                                </td>
                                <td>
                                    <div style="font-weight:700; color:var(--text-primary); margin-bottom:2px;"><?= esc($itn['name']) ?></div>
                                    <div style="font-family:'Share Tech Mono',monospace; font-size:0.72rem; color:#00f3ff;"><?= esc($itn['intern_id'] ?? '') ?></div>
                                    <?php if (!empty($itn['referral_id'])): ?>
                                    <span class="referral-badge"><i class="fa-solid fa-user-plus"></i> via <?= esc($itn['referral_id']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td style="font-size:0.85rem; color:var(--accent-blue);"><?= esc($itn['college'] ?? '—') ?></td>
                                <td>
                                    <?php if ($is_india): ?>
                                    <span class="country-badge" style="background:rgba(16,185,129,0.1); color:#10b981; border:1px solid rgba(16,185,129,0.25);">
                                        <i class="fa-solid fa-flag"></i> India
                                    </span>
                                    <?php else: ?>
                                    <span class="country-badge" style="background:rgba(168,85,247,0.1); color:#a855f7; border:1px solid rgba(168,85,247,0.25);">
                                        <i class="fa-solid fa-globe"></i> <?= esc($itn['country'] ?? 'International') ?>
                                    </span>
                                    <?php endif; ?>
                                    <?php if ($location): ?>
                                    <div style="font-size:0.75rem; color:#64748b; margin-top:4px;">
                                        <i class="fa-solid fa-location-dot" style="font-size:0.65rem; margin-right:3px;"></i><?= esc($location) ?>
                                    </div>
                                    <?php endif; ?>
                                </td>
                                <td style="font-family:monospace; color:#94a3b8; font-size:0.85rem;"><?= esc($itn['mobile'] ?? '—') ?></td>
                                <td>
                                    <?php if (!empty($itn['batch'])): ?>
                                    <span style="background:rgba(0,243,255,0.08); color:#00f3ff; border:1px solid rgba(0,243,255,0.25); padding:3px 9px; border-radius:4px; font-size:0.75rem; font-weight:700; white-space:nowrap;">
                                        <i class="fa-solid fa-calendar-days" style="margin-right:3px;"></i><?= esc($itn['batch']) ?>
                                    </span>
                                    <?php else: ?>
                                    <span style="color:#475569; font-size:0.78rem; font-style:italic;">Not Assigned</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span style="color:#10b981; font-weight:600; font-size:0.82rem;"><?= !empty($itn['created_at']) ? date('d M Y', strtotime($itn['created_at'])) : '—' ?></span><br>
                                    <span style="color:#475569; font-size:0.72rem;"><?= !empty($itn['created_at']) ? date('h:i A', strtotime($itn['created_at'])) : '—' ?></span>
                                </td>
                                <td>
                                    <?php
                                    $itn_status = isset($itn['status']) ? $itn['status'] : 'Active';
                                    if ($itn_status === 'Active') {
                                        $sc = array('bg'=>'rgba(16,185,129,0.1)','color'=>'#10b981');
                                    } elseif ($itn_status === 'Suspended') {
                                        $sc = array('bg'=>'rgba(239,68,68,0.1)','color'=>'#ef4444');
                                    } else {
                                        $sc = array('bg'=>'rgba(234,179,8,0.1)','color'=>'#eab308');
                                    }
                                    ?>
                                    <span style="background:<?= $sc['bg'] ?>; color:<?= $sc['color'] ?>; padding:4px 10px; border-radius:4px; border:1px solid currentColor; font-size:0.78rem; font-weight:700;">
                                        <?= esc($itn['status'] ?? 'Active') ?>
                                    </span>
                                </td>
                                <td>
                                    <div style="display:flex; gap:7px; flex-wrap:wrap;">
                                        <a href="intern_detail.php?id=<?= $itn['id'] ?>" class="btn-primary"
                                           style="padding:6px 12px; font-size:0.75rem; background:#475569; border:none; border-radius:4px; color:white; text-decoration:none; display:inline-flex; align-items:center; gap:5px;">
                                            <i class="fa-solid fa-address-card"></i> Profile
                                        </a>
                                        <a href="tasks.php?intern_id=<?= $itn['id'] ?>" class="btn-primary"
                                           style="padding:6px 12px; font-size:0.75rem; background:#6366f1; border:none; border-radius:4px; color:white; text-decoration:none; display:inline-flex; align-items:center; gap:5px;">
                                            <i class="fa-solid fa-list-check"></i> Tasks
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    </div>

                    <?php if ($total_pages > 1): ?>
                    <div style="display:flex; justify-content:center; align-items:center; gap:8px; margin-top:28px; flex-wrap:wrap;">
                        <?php
                        $base_params = array_merge($_GET, []);
                        unset($base_params['page']);
                        $base_query = http_build_query($base_params);
                        $base_query = $base_query ? '?' . $base_query . '&' : '?';
                        ?>

                        <?php if ($page_num > 1): ?>
                        <a href="<?= $base_query ?>page=<?= $page_num - 1 ?>"
                           style="padding:7px 16px; background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.1); color:#94a3b8; border-radius:6px; text-decoration:none; font-size:0.82rem; transition:0.2s;"
                           onmouseover="this.style.background='rgba(0,243,255,0.08)';this.style.color='#00f3ff'"
                           onmouseout="this.style.background='rgba(255,255,255,0.06)';this.style.color='#94a3b8'">
                            <i class="fa-solid fa-chevron-left"></i> Prev
                        </a>
                        <?php endif; ?>

                        <?php
                        $start_p = max(1, $page_num - 2);
                        $end_p   = min($total_pages, $page_num + 2);
                        if ($start_p > 1): ?>
                            <a href="<?= $base_query ?>page=1" style="padding:7px 13px; background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.1); color:#94a3b8; border-radius:6px; text-decoration:none; font-size:0.82rem;">1</a>
                            <?php if ($start_p > 2): ?><span style="color:#475569; padding:0 4px;">…</span><?php endif; ?>
                        <?php endif; ?>

                        <?php for ($p = $start_p; $p <= $end_p; $p++): ?>
                        <a href="<?= $base_query ?>page=<?= $p ?>"
                           style="padding:7px 13px; background:<?= $p === $page_num ? 'rgba(0,243,255,0.15)' : 'rgba(255,255,255,0.06)' ?>; border:1px solid <?= $p === $page_num ? 'rgba(0,243,255,0.4)' : 'rgba(255,255,255,0.1)' ?>; color:<?= $p === $page_num ? '#00f3ff' : '#94a3b8' ?>; border-radius:6px; text-decoration:none; font-size:0.82rem; font-weight:<?= $p === $page_num ? '700' : '400' ?>;">
                            <?= $p ?>
                        </a>
                        <?php endfor; ?>

                        <?php if ($end_p < $total_pages): ?>
                            <?php if ($end_p < $total_pages - 1): ?><span style="color:#475569; padding:0 4px;">…</span><?php endif; ?>
                            <a href="<?= $base_query ?>page=<?= $total_pages ?>" style="padding:7px 13px; background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.1); color:#94a3b8; border-radius:6px; text-decoration:none; font-size:0.82rem;"><?= $total_pages ?></a>
                        <?php endif; ?>

                        <?php if ($page_num < $total_pages): ?>
                        <a href="<?= $base_query ?>page=<?= $page_num + 1 ?>"
                           style="padding:7px 16px; background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.1); color:#94a3b8; border-radius:6px; text-decoration:none; font-size:0.82rem; transition:0.2s;"
                           onmouseover="this.style.background='rgba(0,243,255,0.08)';this.style.color='#00f3ff'"
                           onmouseout="this.style.background='rgba(255,255,255,0.06)';this.style.color='#94a3b8'">
                            Next <i class="fa-solid fa-chevron-right"></i>
                        </a>
                        <?php endif; ?>

                        <div style="width:100%; text-align:center; font-size:0.75rem; color:#475569; margin-top:8px;">
                            Showing <?= (($page_num - 1) * $per_page) + 1 ?>–<?= min($page_num * $per_page, $total_count) ?> of <?= $total_count ?> interns
                        </div>
                    </div>
                    <?php endif; ?>

                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
