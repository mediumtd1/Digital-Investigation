<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
check_auth('Officer');

$portal = 'officer';
$page_title = 'Email Header Analysis | ' . SITE_NAME;

$analysis_result = '';
if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($_POST['header'])) {
    $email_header = $_POST['header'];
    $lines = explode("\n", $email_header);
    $header_fields = [];

    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line)) continue;

        if (preg_match("/^From:/i", $line)) {
            $header_fields['From'] = trim(substr($line, 5));
        } elseif (preg_match("/^To:/i", $line)) {
            $header_fields['To'] = trim(substr($line, 3));
        } elseif (preg_match("/^Subject:/i", $line)) {
            $header_fields['Subject'] = trim(substr($line, 8));
        } elseif (preg_match("/^Date:/i", $line)) {
            $header_fields['Date'] = trim(substr($line, 5));
        } elseif (preg_match("/^Received:/i", $line)) {
            $header_fields['Received'][] = trim(substr($line, 9));
        } elseif (preg_match("/^X-Originating-IP:/i", $line)) {
            $header_fields['Origin IP'] = trim(substr($line, 17));
        }
    }

    ob_start();
    ?>
    <div class="result-box" style="margin-top: 20px; padding: 20px; background: rgba(0, 242, 255, 0.05); border: 1px solid rgba(0, 242, 255, 0.2); border-radius: 12px;">
        <h3 style="color: var(--accent-blue); margin-bottom: 15px;"><i class="fa-solid fa-microscope"></i> Analysis Results</h3>
        <div style="display: grid; gap: 10px;">
            <?php if (isset($header_fields['From'])): ?>
                <p><strong>From:</strong> <span style="color: #fff;"><?= htmlspecialchars($header_fields['From']) ?></span></p>
            <?php endif; ?>
            <?php if (isset($header_fields['To'])): ?>
                <p><strong>To:</strong> <span style="color: #fff;"><?= htmlspecialchars($header_fields['To']) ?></span></p>
            <?php endif; ?>
            <?php if (isset($header_fields['Subject'])): ?>
                <p><strong>Subject:</strong> <span style="color: #fff;"><?= htmlspecialchars($header_fields['Subject']) ?></span></p>
            <?php endif; ?>
            <?php if (isset($header_fields['Date'])): ?>
                <p><strong>Date:</strong> <span style="color: #fff;"><?= htmlspecialchars($header_fields['Date']) ?></span></p>
            <?php endif; ?>
            <?php if (isset($header_fields['Origin IP'])): ?>
                <p><strong>Originating IP:</strong> <span style="color: var(--accent-blue); font-weight: bold;"><?= htmlspecialchars($header_fields['Origin IP']) ?></span></p>
            <?php endif; ?>
            
            <?php if (isset($header_fields['Received'])): ?>
                <div style="margin-top: 15px;">
                    <strong>Hops (Received Chain):</strong>
                    <div style="margin-top: 10px; font-size: 0.85rem; color: var(--text-muted); background: rgba(0,0,0,0.3); padding: 10px; border-radius: 8px; font-family: monospace; border: 1px solid rgba(255,255,255,0.05);">
                        <?php foreach ($header_fields['Received'] as $index => $received): ?>
                            <div style="margin-bottom: 8px; padding-bottom: 8px; border-bottom: 1px solid rgba(255,255,255,0.05);">
                                <span style="color: var(--accent-blue);">Hop #<?= count($header_fields['Received']) - $index ?>:</span><br>
                                <?= htmlspecialchars($received) ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php
    $analysis_result = ob_get_clean();
}

require_once __DIR__ . '/../includes/header.php'; ?>

<div class="portal-container">
    <?php include '_sidebar.php'; ?>

    <main class="main-content">
        <header class="topbar">
            <div class="topbar-left">
                <h2>Email Header Analysis</h2>
                <p style="color: var(--text-muted); font-size: 0.85rem;">Trace the origin and path of fraudulent or malicious emails</p>
            </div>
            <?php if (!empty($analysis_result)): ?>
            <div class="topbar-right no-print">
                <button onclick="window.print()" class="btn-primary" style="font-size: 0.75rem; padding: 7px 18px; clip-path: polygon(5% 0, 100% 0, 95% 100%, 0% 100%); border: none; cursor: pointer; color: #000; background-color: var(--accent-blue);">
                    <i class="fa-solid fa-file-pdf"></i> Export PDF
                </button>
            </div>
            <?php endif; ?>
        </header>

        <div class="dashboard-content">
            <div class="glass-panel" style="max-width: 900px; margin: 0 auto;">
                <form method="POST" class="cyber-form no-print">
                    <div class="form-group">
                        <label class="form-label">Paste Full Email Header</label>
                        <textarea name="header" class="form-control" rows="12" placeholder="Paste the raw headers here (e.g. Delivered-To, Received, From, etc.)..." required style="font-family: monospace; font-size: 0.85rem; line-height: 1.4;"><?= isset($_POST['header']) ? htmlspecialchars($_POST['header']) : '' ?></textarea>
                    </div>
                    <button type="submit" class="btn-primary" style="width: 100%;">
                        <i class="fa-solid fa-magnifying-glass-chart"></i> Analyze Headers
                    </button>
                </form>

                <?= $analysis_result ?>
                
                <div class="no-print" style="margin-top: 30px; padding: 20px; border-top: 1px solid rgba(255,255,255,0.05);">
                    <h4 style="color: var(--text-muted); margin-bottom: 10px;">How to get headers?</h4>
                    <ul style="color: var(--text-muted); font-size: 0.85rem; padding-left: 20px; line-height: 1.6;">
                        <li><strong>Gmail:</strong> Open email -> More (three dots) -> "Show original"</li>
                        <li><strong>Outlook:</strong> Open email -> More actions -> View -> "Message details"</li>
                        <li><strong>Yahoo:</strong> Open email -> More (three dots) -> "View raw message"</li>
                    </ul>
                </div>
            </div>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
