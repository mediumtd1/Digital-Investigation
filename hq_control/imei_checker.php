<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
$is_admin = (strpos($_SERVER['PHP_SELF'], '/hq_control/') !== false);
check_auth($is_admin ? 'Admin' : 'Officer');
$portal = $is_admin ? 'admin' : 'officer';
$page_title = 'IMEI Check Digit | ' . SITE_NAME;
?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>

<style>
.tool-topbar { background: rgba(0,0,0,0.85); backdrop-filter: blur(15px); border-bottom: 1px solid var(--border-color); padding: 12px 25px; display: flex; align-items: center; justify-content: space-between; flex-shrink: 0; }
.tool-body { flex: 1; overflow-y: auto; padding: 30px; background: var(--bg-primary); }
.imei-card { max-width: 700px; margin: 0 auto; }
.imei-input-group { display: flex; gap: 12px; margin-bottom: 25px; }
.imei-input { flex: 1; background: rgba(0,243,255,0.04); border: 1px solid rgba(0,243,255,0.3); color: #fff; padding: 14px 20px; font-size: 1.1rem; font-family: 'Courier New', monospace; letter-spacing: 2px; border-radius: 4px; outline: none; }
.imei-input:focus { border-color: #00f3ff; box-shadow: 0 0 15px rgba(0,243,255,0.2); }
.imei-btn { background: linear-gradient(135deg, #00f3ff, #0ea5e9); color: #000; border: none; padding: 14px 28px; font-weight: bold; font-family: 'Rajdhani', sans-serif; font-size: 1rem; letter-spacing: 1px; cursor: pointer; border-radius: 4px; }
.imei-btn:hover { opacity: 0.85; }
.result-box { background: rgba(0,0,0,0.4); border: 1px solid rgba(255,255,255,0.08); border-radius: 8px; padding: 25px; margin-top: 20px; }
.result-row { display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-bottom: 1px solid rgba(255,255,255,0.05); font-size: 0.95rem; }
.result-row:last-child { border-bottom: none; }
.result-label { color: #94a3b8; }
.result-value { font-family: 'Courier New', monospace; font-weight: bold; color: #e2e8f0; }
.badge-valid { background: rgba(16,185,129,0.15); color: #10b981; border: 1px solid #10b981; padding: 4px 14px; border-radius: 20px; font-size: 0.8rem; font-weight: bold; }
.badge-invalid { background: rgba(239,68,68,0.15); color: #ef4444; border: 1px solid #ef4444; padding: 4px 14px; border-radius: 20px; font-size: 0.8rem; font-weight: bold; }
.digit-display { display: flex; gap: 6px; flex-wrap: wrap; margin-top: 20px; }
.digit-box { width: 42px; height: 50px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; font-weight: bold; font-family: monospace; border-radius: 4px; border: 1px solid rgba(0,243,255,0.2); background: rgba(0,243,255,0.03); color: #00f3ff; }
.digit-box.check-digit { border-color: #f59e0b; background: rgba(245,158,11,0.1); color: #f59e0b; }
.info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 20px; }
.info-item { background: rgba(0,0,0,0.3); padding: 15px; border-radius: 6px; border-left: 3px solid #00f3ff; }
.info-item h4 { color: #00f3ff; margin: 0 0 5px 0; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1px; }
.info-item p { color: #e2e8f0; margin: 0; font-size: 0.95rem; font-family: monospace; }
</style>

<div class="dashboard-wrapper">
    <?php include_once __DIR__ . '/_sidebar.php'; ?>
    <main class="main-content" style="padding:0; overflow:hidden; height:100vh; display:flex; flex-direction:column;">
        <header class="tool-topbar">
            <div style="display:flex; align-items:center; gap:15px;">
                <img src="../assets/images/logo.png" style="height:38px; width:38px; object-fit:contain;">
                <div>
                    <h2 style="margin:0; font-size:1.2rem; font-family:'Rajdhani',sans-serif; letter-spacing:1px;"><i class="fa-solid fa-mobile-screen" style="color:#00f3ff;"></i> IMEI Check Digit Validator</h2>
                    <small style="color:#94a3b8; font-size:0.7rem;">International Mobile Equipment Identity — Luhn Algorithm Verification</small>
                </div>
            </div>
        </header>
        <div class="tool-body">
            <div class="imei-card">
                <div class="glass-panel" style="padding:30px;">
                    <h3 style="color:#00f3ff; margin-bottom:20px; font-family:'Rajdhani',sans-serif; margin-top:0;"><i class="fa-solid fa-shield-halved"></i> IMEI Verification Engine</h3>
                    <div class="imei-input-group">
                        <input type="text" class="imei-input" id="imeiInput" placeholder="Enter 15-digit IMEI number..." maxlength="15" oninput="this.value=this.value.replace(/\D/g,'')">
                        <button class="imei-btn" onclick="validateIMEI()"><i class="fa-solid fa-magnifying-glass"></i> VERIFY</button>
                    </div>
                    <div id="digitDisplay" class="digit-display" style="display:none;"></div>
                    <div id="resultBox" class="result-box" style="display:none;"></div>
                    <div id="infoGrid" class="info-grid" style="display:none;"></div>
                </div>

                <div class="glass-panel" style="padding:25px; margin-top:20px;">
                    <h4 style="color:#94a3b8; margin-bottom:15px; margin-top:0; font-family:'Rajdhani',sans-serif;"><i class="fa-solid fa-circle-info"></i> About IMEI Check Digit</h4>
                    <p style="color:#64748b; font-size:0.9rem; line-height:1.7; margin:0;">The IMEI (International Mobile Equipment Identity) is a 15-digit number that uniquely identifies mobile devices. The 15th digit is a <strong style="color:#f59e0b;">Check Digit</strong> calculated using the <strong style="color:#00f3ff;">Luhn Algorithm</strong>. This tool verifies the structural integrity of any IMEI number.</p>
                </div>
            </div>
        </div>
    </main>
</div>

<script>
const TAC_DB = {
    "35": "Apple", "86": "Samsung", "35279": "OnePlus", "86800": "Xiaomi",
    "35388": "Huawei", "35617": "Google Pixel", "86780": "Vivo", "86839": "OPPO"
};

function validateIMEI() {
    const raw = document.getElementById('imeiInput').value.trim();
    const display = document.getElementById('digitDisplay');
    const result = document.getElementById('resultBox');
    const grid = document.getElementById('infoGrid');

    if (raw.length !== 15) {
        result.style.display = 'block'; display.style.display = 'none'; grid.style.display = 'none';
        result.innerHTML = `<div style="text-align:center; color:#ef4444;"><i class="fa-solid fa-triangle-exclamation" style="font-size:2rem; margin-bottom:10px; display:block;"></i>IMEI must be exactly 15 digits. You entered ${raw.length} digits.</div>`;
        return;
    }

    let sum = 0;
    for (let i = 0; i < 14; i++) {
        let d = parseInt(raw[i]);
        if (i % 2 === 1) { d *= 2; if (d > 9) d -= 9; }
        sum += d;
    }
    const computedCheck = (10 - (sum % 10)) % 10;
    const actualCheck = parseInt(raw[14]);
    const isValid = computedCheck === actualCheck;

    display.style.display = 'flex';
    display.innerHTML = raw.split('').map((d, i) =>
        `<div class="digit-box ${i === 14 ? 'check-digit' : ''}" title="${i===14?'Check Digit':'Digit '+(i+1)}">${d}</div>`
    ).join('');

    const tac = raw.substring(0, 8);
    const prefix2 = raw.substring(0, 2);
    const brand = TAC_DB[raw.substring(0,5)] || TAC_DB[prefix2] || 'Unknown Brand';

    result.style.display = 'block';
    result.innerHTML = `
        <div class="result-row">
            <span class="result-label">Validation Status</span>
            <span class="${isValid ? 'badge-valid' : 'badge-invalid'}">${isValid ? '✔ VALID IMEI' : '✘ INVALID IMEI'}</span>
        </div>
        <div class="result-row">
            <span class="result-label">Full IMEI</span>
            <span class="result-value">${raw}</span>
        </div>
        <div class="result-row">
            <span class="result-label">Check Digit (Actual)</span>
            <span class="result-value" style="color:#f59e0b;">${actualCheck}</span>
        </div>
        <div class="result-row">
            <span class="result-label">Check Digit (Computed)</span>
            <span class="result-value" style="color:${isValid?'#10b981':'#ef4444'};">${computedCheck}</span>
        </div>
        <div class="result-row">
            <span class="result-label">Luhn Sum</span>
            <span class="result-value">${sum}</span>
        </div>
    `;

    grid.style.display = 'grid';
    grid.innerHTML = `
        <div class="info-item">
            <h4><i class="fa-solid fa-barcode"></i> TAC (Type Allocation Code)</h4>
            <p>${tac}</p>
        </div>
        <div class="info-item" style="border-left-color:#a855f7;">
            <h4 style="color:#a855f7;"><i class="fa-solid fa-mobile"></i> Likely Brand</h4>
            <p>${brand}</p>
        </div>
        <div class="info-item" style="border-left-color:#f59e0b;">
            <h4 style="color:#f59e0b;"><i class="fa-solid fa-sim-card"></i> Serial Number</h4>
            <p>${raw.substring(8, 14)}</p>
        </div>
        <div class="info-item" style="border-left-color:#22c55e;">
            <h4 style="color:#22c55e;"><i class="fa-solid fa-shield-check"></i> Check Digit</h4>
            <p>${actualCheck} ${isValid ? '(Verified ✔)' : '(Mismatch ✘)'}</p>
        </div>
    `;
}

document.getElementById('imeiInput').addEventListener('keypress', e => { if(e.key==='Enter') validateIMEI(); });
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>