<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/db.php';

$is_admin = (strpos($_SERVER['PHP_SELF'], '/admin/') !== false);
check_auth($is_admin ? 'Admin' : 'Officer'); 
$portal = $is_admin ? 'admin' : 'officer';
$page_title = 'MAC Intelligence Lab | ' . SITE_NAME;
?>
<?php 
if (isset($_GET['iframe']) || isset($_GET['embed'])) {
    // Hide headers if embedded
} else {
    require_once __DIR__ . '/../../../includes/header.php';
}
?>

<!-- Custom Styles for MAC Lookup -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<style>
    :root {
        --mac-accent: #00f3ff;
        --mac-warning: #eab308;
        --mac-danger: #ef4444;
        --mac-bg-dark: #0b1120;
    }

    body {
        background: var(--mac-bg-dark) !important;
        color: #fff !important;
    }

    .mac-lab-wrapper {
        background: var(--mac-bg-dark);
        color: #fff;
        min-height: 100vh;
    }

    .mac-search-panel {
        background: rgba(30, 41, 59, 0.7) !important;
        border: 1px solid rgba(0, 243, 255, 0.2) !important;
    }

    .cyber-input {
        background: #0f172a !important;
        color: #fff !important;
        border: 1px solid #334155 !important;
    }

    /* Fix invisible words */
    .text-muted, .info-label, .stat-label {
        color: #94a3b8 !important;
    }
    
    .info-value, .stat-value, h3 {
        color: #fff !important;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* Stats Section */
    .mac-stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
    }

    .stat-card {
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 12px;
        padding: 20px;
        position: relative;
        overflow: hidden;
        transition: all 0.3s ease;
    }

    .stat-card:hover {
        background: rgba(255, 255, 255, 0.05);
        border-color: var(--mac-accent);
        transform: translateY(-3px);
    }

    .stat-card::before {
        content: '';
        position: absolute;
        top: 0; left: 0;
        width: 3px; height: 100%;
        background: var(--mac-accent);
        opacity: 0.5;
    }

    .stat-label {
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: var(--text-muted);
        margin-bottom: 5px;
    }

    .stat-value {
        font-size: 1.8rem;
        font-weight: 800;
        font-family: 'Rajdhani', sans-serif;
    }

    /* Search Section */
    .mac-search-panel {
        background: rgba(13, 17, 23, 0.6);
        backdrop-filter: blur(20px);
        border: 1px solid rgba(255, 255, 255, 0.05);
        border-radius: 16px;
        padding: 35px;
        box-shadow: 0 10px 40px rgba(0,0,0,0.4);
    }

    .search-title {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 25px;
    }

    .search-title i {
        font-size: 1.8rem;
        color: var(--mac-accent);
        text-shadow: 0 0 15px rgba(0, 243, 255, 0.4);
    }

    .search-title h3 {
        margin: 0;
        font-family: 'Rajdhani', sans-serif;
        font-weight: 700;
        letter-spacing: 1px;
    }

    .cyber-input-group {
        display: flex;
        gap: 10px;
        background: rgba(0,0,0,0.3);
        padding: 8px;
        border-radius: 12px;
        border: 1px solid rgba(255,255,255,0.1);
        transition: border-color 0.3s;
    }

    .cyber-input-group:focus-within {
        border-color: var(--mac-accent);
    }

    .cyber-input {
        flex: 1;
        background: transparent;
        border: none;
        color: #fff;
        padding: 12px 15px;
        font-family: 'JetBrains Mono', monospace;
        font-size: 1.1rem;
        outline: none;
    }

    .btn-analyze {
        background: var(--mac-accent);
        color: #000;
        border: none;
        padding: 0 30px;
        border-radius: 8px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 1px;
        transition: all 0.3s;
    }

    .btn-analyze:hover {
        background: #fff;
        box-shadow: 0 0 20px rgba(0, 243, 255, 0.4);
    }

    /* Result Panel */
    .result-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 25px;
        margin-top: 30px;
    }

    .info-block {
        padding: 15px;
        border-radius: 10px;
        background: rgba(255,255,255,0.02);
        border: 1px solid rgba(255,255,255,0.05);
    }

    .info-label {
        font-size: 0.7rem;
        color: var(--text-muted);
        text-transform: uppercase;
        margin-bottom: 4px;
    }

    .info-value {
        font-weight: 600;
        color: #fff;
    }

    /* Tabs */
    .nav-pills .nav-link {
        color: var(--text-muted);
        background: rgba(255,255,255,0.03);
        border: 1px solid transparent;
        margin-right: 10px;
        font-size: 0.8rem;
        font-weight: 600;
        padding: 8px 20px;
    }

    .nav-pills .nav-link.active {
        background: rgba(0, 243, 255, 0.1);
        color: var(--mac-accent);
        border-color: var(--mac-accent);
    }

    /* Activity Log */
    .activity-panel {
        background: rgba(13, 17, 23, 0.4);
        border-radius: 16px;
        border: 1px solid rgba(255, 255, 255, 0.05);
        overflow: hidden;
    }

    .activity-header {
        padding: 15px 25px;
        background: rgba(255,255,255,0.03);
        border-bottom: 1px solid rgba(255,255,255,0.05);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .activity-table {
        width: 100%;
        font-size: 0.85rem;
    }

    .activity-table th {
        padding: 12px 20px;
        color: var(--mac-accent);
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.7rem;
        border-bottom: 1px solid rgba(255,255,255,0.05);
    }

    .activity-table td {
        padding: 12px 20px;
        border-bottom: 1px solid rgba(255,255,255,0.02);
        color: #e0e0e0;
    }

    .font-mono {
        font-family: 'JetBrains Mono', monospace;
    }
</style>

<div class="portal-container">
    <?php if (!isset($_GET['iframe'])) include __DIR__ . '/../../_sidebar.php'; ?>

    <main class="main-content no-select">
        <?php if (!isset($_GET['iframe'])): ?>
        <header class="topbar">
            <div class="topbar-left">
                <div style="display: flex; align-items: center; gap: 15px;">
                    <div style="background: rgba(0, 243, 255, 0.1); padding: 10px; border-radius: 8px;">
                        <i class="fa-solid fa-microchip" style="font-size: 1.4rem; color: var(--mac-accent);"></i>
                    </div>
                    <div>
                        <h2 style="margin: 0; font-family: 'Rajdhani', sans-serif;">HARDWARE INTELLIGENCE LAB</h2>
                        <div id="system-time" style="color: var(--text-muted); font-size: 0.7rem; letter-spacing: 1px;"></div>
                    </div>
                </div>
            </div>
            <div class="topbar-right">
                <button class="btn-primary" style="background: rgba(0, 243, 255, 0.1); color: #00f3ff; border: 1px solid rgba(0,243,255,0.3); padding: 6px 20px; font-size: 0.8rem; border-radius: 4px;" onclick="toggleLanguage()">
                    <i class="bi bi-translate me-2"></i> <span id="current-lang">HINDI</span>
                </button>
            </div>
        </header>
        <?php endif; ?>

        <div class="mac-lab-wrapper">
            <!-- Stats Row -->
            <div class="mac-stats-grid">
                <div class="stat-card">
                    <div class="stat-label" data-i18n="total_lookups">Total Investigations</div>
                    <div class="stat-value" id="metric-total" style="color: var(--mac-accent);">0</div>
                </div>
                <div class="stat-card" style="--mac-accent: var(--mac-danger);">
                    <div class="stat-label" data-i18n="spoofed_detected">Anomalies Detected</div>
                    <div class="stat-value" id="metric-spoofed" style="color: var(--mac-danger);">0</div>
                </div>
                <div class="stat-card" style="--mac-accent: var(--mac-warning);">
                    <div class="stat-label" data-i18n="watchlist_hits">Target Hits</div>
                    <div class="stat-value" id="metric-watchlist" style="color: var(--mac-warning);">0</div>
                </div>
                <div class="stat-card" style="--mac-accent: #10b981;">
                    <div class="stat-label" data-i18n="active_investigations">Active Sessions</div>
                    <div class="stat-value" style="color: #10b981;">1</div>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-lg-8">
                    <!-- Search Panel -->
                    <div class="mac-search-panel">
                        <div class="search-title">
                            <i class="bi bi-broadcast-pin"></i>
                            <div>
                                <h3 data-i18n="mac_lookup_tool">MAC ADDRESS ANALYSIS</h3>
                                <p class="text-muted small mb-0">Identify hardware vendor, origin country, and spoofing indicators.</p>
                            </div>
                        </div>

                        <form id="lookup-form">
                            <div class="cyber-input-group">
                                <input type="text" class="cyber-input" id="mac-input" placeholder="00:1A:2B:3C:4D:5E" required>
                                <button type="submit" class="btn-analyze">ANALYZE</button>
                            </div>
                        </form>

                        <div class="mt-3 d-flex align-items-center gap-3">
                            <span class="text-muted small" data-i18n="batch_upload">Batch (CSV):</span>
                            <form id="batch-form" class="d-flex gap-2">
                                <input type="file" class="form-control form-control-sm" id="csv-file" accept=".csv" style="background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.05); color: #888;">
                                <button type="submit" class="btn btn-sm btn-outline-secondary" style="border-radius: 6px;">UPLOAD</button>
                            </form>
                        </div>
                    </div>

                    <!-- Result Panel -->
                    <div id="result-card" class="glass-panel p-0 mt-4 d-none" style="overflow: hidden; border-color: rgba(0, 243, 255, 0.2);">
                        <div style="background: rgba(0, 243, 255, 0.05); padding: 20px; border-bottom: 1px solid rgba(255,255,255,0.05); display: flex; justify-content: space-between; align-items: center;">
                            <h5 class="mb-0 font-mono" style="color: var(--mac-accent);">
                                <i class="bi bi-cpu-fill me-2"></i> <span id="res-mac">--</span>
                            </h5>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-info dropdown-toggle" data-bs-toggle="dropdown">
                                    <i class="bi bi-file-earmark-pdf me-1"></i> REPORT
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="#" onclick="exportResult('csv')">CSV Report</a></li>
                                    <li><a class="dropdown-item" href="#" onclick="exportResult('pdf')">Forensic PDF</a></li>
                                </ul>
                            </div>
                        </div>

                        <div class="p-4">
                            <ul class="nav nav-pills mb-4" id="resultTabs" role="tablist">
                                <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#vendor-info" data-i18n="vendor_info">VENDOR DETAILS</button></li>
                                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#timeline-info" data-i18n="timeline">TIMELINE</button></li>
                                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#forensic-notes" data-i18n="forensic_notes">FORENSIC DATA</button></li>
                            </ul>

                            <div class="tab-content">
                                <div class="tab-pane fade show active" id="vendor-info">
                                    <div class="result-grid">
                                        <div class="info-block">
                                            <div class="info-label" data-i18n="vendor">Manufacturer / Company</div>
                                            <div class="info-value" id="res-vendor" style="color: var(--mac-accent); font-size: 1.1rem;">--</div>
                                        </div>
                                        <div class="info-block">
                                            <div class="info-label" data-i18n="address_prefix">OUI Prefix</div>
                                            <div class="info-value font-mono" id="res-prefix">--</div>
                                        </div>
                                        <div class="info-block">
                                            <div class="info-label" data-i18n="network_correlation">Origin Country</div>
                                            <div class="info-value" id="res-location">--</div>
                                        </div>
                                        <div class="info-block">
                                            <div class="info-label" data-i18n="is_private">Device Scope</div>
                                            <div class="info-value" id="res-scope">--</div>
                                        </div>
                                    </div>
                                    <div class="info-block mt-4">
                                        <div class="info-label">Corporate Registered Address</div>
                                        <div class="info-value small text-muted" id="res-address">--</div>
                                    </div>
                                    <div id="res-ip-row" class="info-block mt-3 d-none" style="border-color: var(--mac-warning);">
                                        <div class="info-label" data-i18n="linked_ip" style="color: var(--mac-warning);">Linked Network IP</div>
                                        <div class="info-value font-mono" id="res-ip">--</div>
                                        <div id="res-ip-geo" class="small text-muted mt-1"></div>
                                    </div>
                                </div>

                                <div class="tab-pane fade" id="timeline-info">
                                    <div class="row g-4">
                                        <div class="col-md-6">
                                            <h6 class="text-info small mb-2" data-i18n="last_updated">IEEE Registry Update</h6>
                                            <div id="res-updated" class="font-mono" style="font-size: 1.1rem;">--</div>
                                        </div>
                                        <div class="col-md-6">
                                            <h6 class="text-info small mb-2" data-i18n="year_of_detection">Approx. Manufacture Year</h6>
                                            <div id="res-year" class="font-mono" style="font-size: 1.1rem;">--</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="tab-pane fade" id="forensic-notes">
                                    <div class="mb-4">
                                        <label class="info-label" data-i18n="spoof_status">Hardware Integrity</label>
                                        <div id="res-spoof">--</div>
                                    </div>
                                    <div>
                                        <label class="info-label" data-i18n="historical_records">Investigation History</label>
                                        <div id="res-history" class="small"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="p-3" style="background: rgba(0,0,0,0.2); border-top: 1px solid rgba(255,255,255,0.05); display: flex; justify-content: flex-end; gap: 10px;">
                            <button class="btn btn-sm btn-outline-danger" onclick="addToWatchlist('BLACKLIST')" style="font-size: 0.7rem; padding: 5px 15px;">
                                <i class="bi bi-shield-slash me-1"></i> BLACKLIST
                            </button>
                            <button class="btn btn-sm btn-outline-success" onclick="addToWatchlist('WHITELIST')" style="font-size: 0.7rem; padding: 5px 15px;">
                                <i class="bi bi-shield-check me-1"></i> WHITELIST
                            </button>
                        </div>
                    </div>

                    <!-- Batch Result Table (Hidden) -->
                    <div id="batch-result-card" class="activity-panel mt-4 d-none">
                        <div class="activity-header">
                            <h5 class="mb-0 small fw-bold text-white"><i class="bi bi-layers me-2"></i> BATCH RESULTS</h5>
                        </div>
                        <div style="max-height: 400px; overflow-y: auto;">
                            <table class="activity-table">
                                <thead>
                                    <tr>
                                        <th>MAC Address</th>
                                        <th>Hardware Vendor</th>
                                        <th style="text-align: right;">Status</th>
                                    </tr>
                                </thead>
                                <tbody id="batch-results-body"></tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <!-- History Log -->
                    <div class="activity-panel">
                        <div class="activity-header">
                            <h5 class="mb-0 small fw-bold text-white"><i class="bi bi-clock-history me-2"></i> ACTIVITY LOG</h5>
                            <div class="d-flex gap-2">
                                <input type="text" id="history-search" class="form-control form-control-sm" placeholder="Filter..." style="width: 100px; background: rgba(0,0,0,0.3); border: none; color: #fff;">
                                <button class="btn btn-sm btn-outline-info" onclick="exportSelected('csv')"><i class="bi bi-download"></i></button>
                            </div>
                        </div>
                        <div style="max-height: 600px; overflow-y: auto;">
                            <table class="activity-table">
                                <thead>
                                    <tr>
                                        <th style="width: 40px; text-align: center;"><input type="checkbox" id="select-all-history" class="form-check-input"></th>
                                        <th>MAC IDENTITY</th>
                                        <th style="text-align: right;">TIME</th>
                                    </tr>
                                </thead>
                                <tbody id="recent-activity-body"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- Notifications -->
<div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 9999;"></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="static/js/lang.js?v=<?= time() ?>"></script>
<script src="static/js/app.js?v=<?= time() ?>"></script>

<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>