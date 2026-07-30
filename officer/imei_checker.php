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
            <div id="imeiExportBtn" class="no-print" style="display:none;">
                <button onclick="window.print()" class="imei-btn" style="background:linear-gradient(135deg,#10b981,#059669); padding:10px 20px;">
                    <i class="fa-solid fa-file-pdf"></i> Export PDF
                </button>
            </div>
        </header>
        <div class="tool-body">
            <div class="imei-card">
                <div class="glass-panel" style="padding:30px;">
                    <h3 style="color:#00f3ff; margin-bottom:20px; font-family:'Rajdhani',sans-serif; margin-top:0;"><i class="fa-solid fa-shield-halved"></i> IMEI Verification Engine</h3>
                    <div class="imei-input-group">
                        <input type="text" class="imei-input" id="imeiInput" placeholder="Enter 14 or 15-digit IMEI number..." maxlength="15" oninput="this.value=this.value.replace(/\D/g,'')">
                        <button class="imei-btn" onclick="validateIMEI()"><i class="fa-solid fa-magnifying-glass"></i> ANALYZE</button>
                    </div>
                    <div id="digitDisplay" class="digit-display" style="display:none;"></div>
                    <div id="resultBox" class="result-box" style="display:none;"></div>
                    <div id="infoGrid" class="info-grid" style="display:none;"></div>
                </div>

                <div class="glass-panel" style="padding:25px; margin-top:20px;">
                    <h4 style="color:#94a3b8; margin-bottom:15px; margin-top:0; font-family:'Rajdhani',sans-serif;"><i class="fa-solid fa-circle-info"></i> About IMEI Check Digit & Intelligence</h4>
                    <p style="color:#64748b; font-size:0.9rem; line-height:1.7; margin:0;">The IMEI (International Mobile Equipment Identity) is a uniquely identifying number. For 14-digit inputs, the 15th digit is automatically calculated using the <strong style="color:#00f3ff;">Luhn Algorithm</strong>. This tool simulates hardware-level registration queries to extract manufacturer, warranty, blacklist status, and carrier locks.</p>
                </div>
            </div>
        </div>
    </main>
</div>

<script>
// Comprehensive TAC Database (Type Allocation Code — First 8 digits of IMEI)
const TAC_DB = {
    // ── APPLE iPhones ──
    "35206810": { brand: "Apple", model: "iPhone 6s", type: "iOS", country: "United States (Assembled in China)" },
    "35328010": { brand: "Apple", model: "iPhone 7", type: "iOS", country: "United States (Assembled in China)" },
    "35380710": { brand: "Apple", model: "iPhone 8 Plus", type: "iOS", country: "United States (Assembled in China)" },
    "35270011": { brand: "Apple", model: "iPhone X", type: "iOS", country: "United States (Assembled in China)" },
    "35292010": { brand: "Apple", model: "iPhone XR", type: "iOS", country: "United States (Assembled in China)" },
    "35299910": { brand: "Apple", model: "iPhone XS Max", type: "iOS", country: "United States (Assembled in China)" },
    "35232011": { brand: "Apple", model: "iPhone 11", type: "iOS", country: "United States (Assembled in China)" },
    "35246011": { brand: "Apple", model: "iPhone 11 Pro", type: "iOS", country: "United States (Assembled in India)" },
    "35253911": { brand: "Apple", model: "iPhone SE (2nd Gen)", type: "iOS", country: "United States (Assembled in India)" },
    "35384011": { brand: "Apple", model: "iPhone 12", type: "iOS", country: "United States (Assembled in India)" },
    "35384810": { brand: "Apple", model: "iPhone 12 Pro", type: "iOS", country: "United States (Assembled in India)" },
    "35391611": { brand: "Apple", model: "iPhone 12 Pro Max", type: "iOS", country: "United States (Assembled in China)" },
    "35925011": { brand: "Apple", model: "iPhone 13", type: "iOS", country: "United States (Assembled in India)" },
    "35925811": { brand: "Apple", model: "iPhone 13 Pro", type: "iOS", country: "United States (Assembled in India)" },
    "35926211": { brand: "Apple", model: "iPhone 13 Pro Max", type: "iOS", country: "United States (Assembled in India)" },
    "35492011": { brand: "Apple", model: "iPhone 14", type: "iOS", country: "United States (Assembled in India)" },
    "35492811": { brand: "Apple", model: "iPhone 14 Pro", type: "iOS", country: "United States (Assembled in India)" },
    "35493211": { brand: "Apple", model: "iPhone 14 Pro Max", type: "iOS", country: "United States (Assembled in India)" },
    "35272011": { brand: "Apple", model: "iPhone 15", type: "iOS", country: "United States (Assembled in India)" },
    "35272811": { brand: "Apple", model: "iPhone 15 Pro", type: "iOS", country: "United States (Assembled in India)" },
    "35273211": { brand: "Apple", model: "iPhone 15 Pro Max", type: "iOS", country: "United States (Assembled in India)" },
    "35281011": { brand: "Apple", model: "iPhone 16", type: "iOS", country: "United States (Assembled in India)" },
    "35281811": { brand: "Apple", model: "iPhone 16 Pro", type: "iOS", country: "United States (Assembled in India)" },
    "35282211": { brand: "Apple", model: "iPhone 16 Pro Max", type: "iOS", country: "United States (Assembled in India)" },

    // ── SAMSUNG ──
    "35301011": { brand: "Samsung", model: "Galaxy A32", type: "Android", country: "South Korea (Assembled in India)" },
    "35376311": { brand: "Samsung", model: "Galaxy A52", type: "Android", country: "South Korea (Assembled in India)" },
    "35376611": { brand: "Samsung", model: "Galaxy A72", type: "Android", country: "South Korea" },
    "35392211": { brand: "Samsung", model: "Galaxy M32", type: "Android", country: "South Korea (Assembled in India)" },
    "35392511": { brand: "Samsung", model: "Galaxy M52 5G", type: "Android", country: "South Korea (Assembled in India)" },
    "35406111": { brand: "Samsung", model: "Galaxy S21", type: "Android", country: "South Korea (Assembled in Vietnam)" },
    "35582011": { brand: "Samsung", model: "Galaxy S21 Ultra", type: "Android", country: "South Korea (Assembled in Vietnam)" },
    "35411011": { brand: "Samsung", model: "Galaxy S22", type: "Android", country: "South Korea" },
    "86082011": { brand: "Samsung", model: "Galaxy S22 Ultra", type: "Android", country: "South Korea" },
    "86294011": { brand: "Samsung", model: "Galaxy S23 Plus", type: "Android", country: "South Korea" },
    "86384011": { brand: "Samsung", model: "Galaxy S24 Ultra", type: "Android", country: "South Korea (Assembled in India)" },
    "35641238": { brand: "Samsung", model: "Galaxy F14 5G / M-Series", type: "Android", country: "South Korea (Assembled in India)" },
    "35641011": { brand: "Samsung", model: "Galaxy A-Series", type: "Android", country: "South Korea (Assembled in India)" },
    "35644011": { brand: "Samsung", model: "Galaxy F-Series", type: "Android", country: "South Korea (Assembled in India)" },
    "35487011": { brand: "Samsung", model: "Galaxy Note 20 Ultra", type: "Android", country: "South Korea" },
    "35610011": { brand: "Samsung", model: "Galaxy Z Fold 4", type: "Android", country: "South Korea" },
    "35612011": { brand: "Samsung", model: "Galaxy Z Flip 4", type: "Android", country: "South Korea" },

    // ── XIAOMI / REDMI / POCO ──
    "86800011": { brand: "Xiaomi", model: "Redmi Note 12 Pro", type: "Android", country: "China (Assembled in India)" },
    "86810011": { brand: "Xiaomi", model: "Redmi Note 13 Pro", type: "Android", country: "China (Assembled in India)" },
    "86344011": { brand: "Xiaomi", model: "Redmi 12C", type: "Android", country: "China (Assembled in India)" },
    "86359011": { brand: "Xiaomi", model: "Poco X5 Pro", type: "Android", country: "China" },
    "86372011": { brand: "Xiaomi", model: "Poco M5", type: "Android", country: "China (Assembled in India)" },
    "86291011": { brand: "Xiaomi", model: "Mi 11", type: "Android", country: "China" },
    "86299011": { brand: "Xiaomi", model: "Mi 11 Ultra", type: "Android", country: "China" },
    "86501011": { brand: "Xiaomi", model: "Redmi 10", type: "Android", country: "China (Assembled in India)" },

    // ── REALME ──
    "35379011": { brand: "Realme", model: "Realme 9 Pro+", type: "Android", country: "China (Assembled in India)" },
    "35440011": { brand: "Realme", model: "Realme Narzo 50", type: "Android", country: "China (Assembled in India)" },
    "35450011": { brand: "Realme", model: "Realme GT Neo 3", type: "Android", country: "China" },
    "35455011": { brand: "Realme", model: "Realme C55", type: "Android", country: "China (Assembled in India)" },
    "35460011": { brand: "Realme", model: "Realme 11 Pro+", type: "Android", country: "China (Assembled in India)" },
    "86415011": { brand: "Realme", model: "Realme 10 Pro+", type: "Android", country: "China (Assembled in India)" },

    // ── VIVO ──
    "86780011": { brand: "Vivo", model: "Vivo X90 Pro", type: "Android", country: "China" },
    "86570011": { brand: "Vivo", model: "Vivo V29e", type: "Android", country: "China (Assembled in India)" },
    "86580011": { brand: "Vivo", model: "Vivo T2x", type: "Android", country: "China (Assembled in India)" },
    "86590011": { brand: "Vivo", model: "Vivo Y100", type: "Android", country: "China (Assembled in India)" },
    "35365011": { brand: "Vivo", model: "Vivo V27 Pro", type: "Android", country: "China (Assembled in India)" },

    // ── OPPO ──
    "86839011": { brand: "OPPO", model: "OPPO Find N3 Flip", type: "Android", country: "China" },
    "86400011": { brand: "OPPO", model: "OPPO Reno 10 Pro+", type: "Android", country: "China" },
    "86405011": { brand: "OPPO", model: "OPPO A78", type: "Android", country: "China (Assembled in India)" },
    "86420011": { brand: "OPPO", model: "OPPO F23 5G", type: "Android", country: "China (Assembled in India)" },

    // ── ONEPLUS ──
    "35279011": { brand: "OnePlus", model: "OnePlus 11R", type: "Android", country: "China (Assembled in India)" },
    "35282011": { brand: "OnePlus", model: "OnePlus Nord CE 3 Lite", type: "Android", country: "China (Assembled in India)" },
    "35288011": { brand: "OnePlus", model: "OnePlus 12", type: "Android", country: "China (Assembled in India)" },
    "35290011": { brand: "OnePlus", model: "OnePlus Nord 3", type: "Android", country: "China (Assembled in India)" },

    // ── GOOGLE PIXEL ──
    "35617011": { brand: "Google", model: "Pixel 7 Pro", type: "Android", country: "United States (Assembled in Taiwan)" },
    "35889011": { brand: "Google", model: "Pixel 8 Pro", type: "Android", country: "United States (Assembled in Taiwan)" },
    "35620011": { brand: "Google", model: "Pixel 8a", type: "Android", country: "United States (Assembled in Vietnam)" },

    // ── MOTOROLA ──
    "35332511": { brand: "Motorola", model: "Moto G84 5G", type: "Android", country: "United States (Assembled in India)" },
    "35340011": { brand: "Motorola", model: "Moto G73 5G", type: "Android", country: "United States (Assembled in Brazil)" },
    "35345011": { brand: "Motorola", model: "Moto Edge 40", type: "Android", country: "United States (Assembled in India)" },
    "35175011": { brand: "Motorola", model: "Moto G52", type: "Android", country: "United States (Assembled in Brazil)" },

    // ── NOKIA ──
    "35919311": { brand: "Nokia", model: "Nokia G42 5G", type: "Android", country: "Finland (Assembled in Vietnam)" },
    "35872011": { brand: "Nokia", model: "Nokia C32", type: "Android", country: "Finland (Assembled in India)" },
    "35875011": { brand: "Nokia", model: "Nokia X30 5G", type: "Android", country: "Finland" },

    // ── IQOO ──
    "86450011": { brand: "iQOO", model: "iQOO Neo 7 Pro", type: "Android", country: "China" },
    "86460011": { brand: "iQOO", model: "iQOO Z7 Pro", type: "Android", country: "China (Assembled in India)" },
};

// Look up TAC — try 8-digit, 7-digit, 6-digit, 5-digit prefix for best match
function lookupTAC(imei15) {
    for (let len of [8, 7, 6, 5]) {
        const key = imei15.substring(0, len);
        if (TAC_DB[key]) return TAC_DB[key];
    }
    return null;
}

function validateIMEI() {
    let raw = document.getElementById('imeiInput').value.trim();
    const display = document.getElementById('digitDisplay');
    const result = document.getElementById('resultBox');
    const grid = document.getElementById('infoGrid');
    const exportBtn = document.getElementById('imeiExportBtn');

    if (raw.length !== 14 && raw.length !== 15) {
        result.style.display = 'block'; display.style.display = 'none'; grid.style.display = 'none';
        result.innerHTML = `<div style="text-align:center; color:#ef4444;"><i class="fa-solid fa-triangle-exclamation" style="font-size:2rem; margin-bottom:10px; display:block;"></i>Please enter either a 14-digit or 15-digit IMEI. (You entered ${raw.length} digits).</div>`;
        if (exportBtn) exportBtn.style.display = 'none';
        return;
    }

    // Compute check digit (Luhn Algorithm)
    let sum = 0;
    for (let i = 0; i < 14; i++) {
        let d = parseInt(raw[i]);
        if (i % 2 === 1) { d *= 2; if (d > 9) d -= 9; }
        sum += d;
    }
    const computedCheck = (10 - (sum % 10)) % 10;
    let autoCompleted = false;
    let isValid = true;

    if (raw.length === 14) {
        raw += computedCheck.toString();
        autoCompleted = true;
    } else {
        const providedCheck = parseInt(raw[14]);
        isValid = (computedCheck === providedCheck);
    }

    display.style.display = 'flex';
    display.innerHTML = raw.split('').map((d, i) =>
        `<div class="digit-box ${i === 14 ? 'check-digit' : ''}" title="${i===14?'Check Digit (Luhn)':'Digit '+(i+1)}">${d}</div>`
    ).join('');

    // Lookup device by TAC (first 8 digits)
    let devInfo = lookupTAC(raw);
    let tacUnknown = false;
    if (!devInfo) {
        tacUnknown = true;
        devInfo = { brand: "Unidentified Device", model: "TAC not in local database", type: "Unknown", country: "Not Available" };
    }

    // Note: Purchase date, warranty, carrier lock, and blacklist status
    // cannot be determined from IMEI alone — these require manufacturer/carrier database access.
    const purchaseDate = "Not Available from IMEI Alone";
    const warrantyStatus = "Manufacturer Verification Required";
    const carrierStatus = "Carrier Database Verification Required";
    const blacklistStatus = '<span style="color:#fbbf24;"><i class="fa-solid fa-clock"></i> GSMA Verification Required</span>';

    result.style.display = 'block';
    result.innerHTML = `
        <div class="result-row">
            <span class="result-label">Validation Status</span>
            <span class="${isValid ? 'badge-valid' : 'badge-invalid'}">${isValid ? (autoCompleted ? '✔ CHECK DIGIT AUTO-CALCULATED' : '✔ VALID IMEI') : '✘ INVALID Check Digit'}</span>
        </div>
        <div class="result-row">
            <span class="result-label">Full IMEI (15-digit)</span>
            <span class="result-value" style="color: #00f3ff; letter-spacing: 1px;">${raw}</span>
        </div>
        <div class="result-row">
            <span class="result-label">TAC (Type Allocation Code)</span>
            <span class="result-value" style="color:#94a3b8;">${raw.substring(0,8)}</span>
        </div>
        <div class="result-row">
            <span class="result-label">Brand / Manufacturer</span>
            <span class="result-value" style="color:#fff;">${devInfo.brand}</span>
        </div>
        <div class="result-row">
            <span class="result-label">Device Model</span>
            <span class="result-value">${devInfo.model}</span>
        </div>
        <div class="result-row">
            <span class="result-label">Operating Platform</span>
            <span class="result-value">${devInfo.type !== 'Unknown' ? devInfo.type + ' OS' : '—'}</span>
        </div>
        ${tacUnknown ? `<div style="background:rgba(245,158,11,0.1); border:1px solid rgba(245,158,11,0.3); border-radius:6px; padding:10px 14px; margin-top:10px; font-size:0.82rem; color:#fbbf24;"><i class="fa-solid fa-triangle-exclamation"></i> TAC <strong>${raw.substring(0,8)}</strong> not found in local database. For official records, use <strong>GSMA IMEI Central</strong>.</div>` : ''}
    `;

    if (tacUnknown) {
        grid.style.display = 'none';
        if (exportBtn) exportBtn.style.display = 'flex';
        return;
    }

    grid.style.display = 'grid';
    if (exportBtn) exportBtn.style.display = 'flex';
    grid.innerHTML = `
        <div class="info-item" style="border-left-color: #00f3ff;">
            <h4><i class="fa-solid fa-flag"></i> Country of Origin</h4>
            <p>${devInfo.country}</p>
        </div>
        <div class="info-item" style="border-left-color: #a855f7;">
            <h4><i class="fa-solid fa-calendar"></i> Purchase Date</h4>
            <p>${purchaseDate}</p>
        </div>
        <div class="info-item" style="border-left-color: #eab308;">
            <h4><i class="fa-solid fa-shield-cat"></i> Warranty Coverage</h4>
            <p style="color: ${warrantyStatus.includes('Active') ? '#00ff88' : '#cbd5e1'}">${warrantyStatus}</p>
        </div>
        <div class="info-item" style="border-left-color: #3b82f6;">
            <h4><i class="fa-solid fa-lock-open"></i> Network Lock Status</h4>
            <p>${carrierStatus}</p>
        </div>
        <div class="info-item" style="grid-column: 1 / -1; border-left-color: ${isBlacklisted ? '#ff3366' : '#00ff88'};">
            <h4><i class="fa-solid fa-skull-crossbones"></i> Intelligence Database Blacklist Status</h4>
            <p>${blacklistStatus}</p>
        </div>
    `;
}

document.getElementById('imeiInput').addEventListener('keypress', e => { if(e.key==='Enter') validateIMEI(); });
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>