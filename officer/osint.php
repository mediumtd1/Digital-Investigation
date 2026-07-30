<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

$is_admin = (strpos($_SERVER['PHP_SELF'], '/admin/') !== false);
check_auth($is_admin ? 'Admin' : 'Officer'); 
$portal = $is_admin ? 'admin' : 'officer';

$page_title = 'OSINT Threat Search Engine | ' . SITE_NAME;
?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.css" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.js"></script>

<style>
    :root { --cyber-accent: #00d4ff; --panel-bg: rgba(13, 21, 38, 0.85); }
    .ip-wrapper { padding: 30px; animation: slideUp 0.4s ease; }
    
    .glass-card {
        background: var(--panel-bg);
        border: 1px solid rgba(30,45,74,0.8);
        border-radius: 12px;
        padding: 25px;
        backdrop-filter: blur(12px);
        margin-bottom: 25px;
        box-shadow: 0 10px 40px rgba(0,0,0,0.4);
    }

    .search-bar { display: flex; gap: 15px; margin-bottom: 20px; flex-wrap: wrap; }
    .ip-input {
        flex: 1; background: rgba(13,21,38,0.9); border: 1px solid rgba(30,45,74,0.8);
        padding: 15px 25px; border-radius: 8px; color: #e2e8f0; font-family: 'Share Tech Mono', monospace;
        font-size: 1.1rem; outline: none; transition: 0.3s;
    }
    .ip-input:focus { border-color: rgba(0,212,255,0.6); box-shadow: 0 0 0 3px rgba(0,212,255,0.1); }
    
    .btn-pulse {
        background: linear-gradient(135deg,#00d4ff,#0066ff); color: #fff; border: none; padding: 0 40px;
        border-radius: 8px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px;
        cursor: pointer; transition: 0.3s; display: flex; align-items: center; justify-content: center; min-height: 52px;
    }
    .btn-pulse:hover { box-shadow: 0 0 25px rgba(0,212,255,.5); transform: translateY(-1px); }
    .btn-pulse:disabled { opacity: 0.5; cursor: not-allowed; transform: none; box-shadow: none; }

    .badge { display:inline-flex; align-items:center; padding:3px 10px; border-radius:20px; font-size:11px; font-weight:700; letter-spacing:.05em; }
    .badge-safe     { background:rgba(0,255,136,.15); color:#00ff88; border:1px solid rgba(0,255,136,.3); }
    .badge-low      { background:rgba(0,212,255,.15); color:#00d4ff; border:1px solid rgba(0,212,255,.3); }
    .badge-medium   { background:rgba(255,214,10,.15); color:#ffd60a; border:1px solid rgba(255,214,10,.3); }
    .badge-high     { background:rgba(255,107,53,.15); color:#ff6b35; border:1px solid rgba(255,107,53,.3); }
    .badge-critical { background:rgba(255,51,102,.15); color:#ff3366; border:1px solid rgba(255,51,102,.3); animation:pulse 2s infinite; }
    @keyframes pulse { 0%,100%{opacity:1} 50%{opacity:.6} }
    
    .meter-bar { height:8px; border-radius:4px; background:rgba(30,45,74,0.8); overflow:hidden; }
    .meter-fill { height:100%; border-radius:4px; transition:width 1.2s cubic-bezier(.34,1.56,.64,1); }
    
    .spin { animation: spin 1s linear infinite; } 
    @keyframes spin { to { transform: rotate(360deg); } }

    .example-btn {
        font-size: 11px; padding: 4px 8px; border-radius: 4px; background: rgba(30,45,74,0.5);
        color: #64748b; border: 1px solid transparent; cursor: pointer; transition: 0.3s; font-family: monospace;
    }
    .example-btn:hover { color: #00d4ff; border-color: #00d4ff; }

    .info-chip {
        background: #111827; padding: 10px; border-radius: 8px;
    }

    @keyframes slideUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
</style>

<div class="portal-container">
    <?php include '_sidebar.php'; ?>

    <main class="main-content">
        <header class="topbar">
            <div class="topbar-left">
                <h2 style="font-family: 'Rajdhani', sans-serif; letter-spacing: 2px; text-transform: uppercase;">
                    <i class="fa-solid fa-radar" style="color: var(--cyber-accent);"></i> OSINT Threat Search Engine
                </h2>
            </div>
            <div class="topbar-right no-print" id="osintExportBtn" style="display: none;">
                <button onclick="window.print()" class="btn-primary" style="font-size: 0.75rem; padding: 7px 18px; clip-path: polygon(5% 0, 100% 0, 95% 100%, 0% 100%); border: none; cursor: pointer; color: #000;">
                    <i class="fa-solid fa-file-pdf"></i> Export PDF
                </button>
            </div>
        </header>

        <div class="ip-wrapper">
            
            <div class="glass-card">
                <p style="font-size: 0.85rem; color: #64748b; margin-bottom: 15px;">Enter an IP address, URL, email address, or domain name</p>
                <div class="search-bar">
                    <input id="queryInput" type="text" class="ip-input" placeholder="e.g. 8.8.8.8  |  https://example.com  |  user@email.com  |  example.com" />
                    <button id="analyzeBtn" class="btn-pulse" onclick="analyze()">Analyze</button>
                </div>
                <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                    <?php
                    $examples = ['8.8.8.8','https://google.com','test@gmail.com','suspicious-free-prize.tk'];
                    foreach ($examples as $ex): ?>
                        <button onclick="document.getElementById('queryInput').value='<?= $ex ?>'; analyze();" class="example-btn">
                            <?= $ex ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>

            
            <div id="loadingState" class="glass-card" style="display: none; text-align: center; padding: 40px;">
                <i class="fa-solid fa-circle-notch fa-spin fa-2x" style="color: #00d4ff; margin-bottom: 15px;"></i>
                <p style="color: #64748b; font-size: 0.9rem;">Querying threat intelligence sources...</p>
            </div>

            
            <div id="results" style="display: none; flex-direction: column; gap: 20px;"></div>

        </div>
    </main>
</div>

<script>
const THREAT_COLORS = { SAFE:'#00ff88', LOW:'#00d4ff', MEDIUM:'#ffd60a', HIGH:'#ff6b35', CRITICAL:'#ff3366' };
let map = null;

async function analyze() {
  const query = document.getElementById('queryInput').value.trim();
  if (!query) return;

  const btn = document.getElementById('analyzeBtn');
  btn.disabled = true; btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> &nbsp; Analyzing...';
  
  document.getElementById('osintExportBtn').style.display = 'none';
  document.getElementById('loadingState').style.display = 'block';
  const resultsDiv = document.getElementById('results');
  resultsDiv.style.display = 'none';
  resultsDiv.innerHTML = '';

  try {
    const res = await fetch('../OSINT_PHP/api/threat.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ query }),
    });
    
    const text = await res.text();
    let data;
    try {
      data = JSON.parse(text);
    } catch (err) {
      console.error("Backend Error Response:", text);
      alert('Backend Error: Server returned invalid data (likely a PHP error). Response: ' + text.substring(0, 150) + '...');
      return;
    }

    if (!res.ok) { alert(data.detail || 'Analysis failed'); return; }
    renderResults(data);
  } catch (e) {
    alert('Could not reach the backend API. Error: ' + e.message);
  } finally {
    btn.disabled = false; btn.innerHTML = 'Analyze';
    document.getElementById('loadingState').style.display = 'none';
  }
}

document.getElementById('queryInput').addEventListener('keydown', e => { if (e.key === 'Enter') analyze(); });

function renderResults(d) {
  const ai = d.ai_analysis || {};
  const level = ai.threat_level || 'UNKNOWN';
  const score = ai.risk_score ?? 0;
  const color = THREAT_COLORS[level] || '#64748b';
  const badgeClass = 'badge badge-' + level.toLowerCase();

  let html = `
    <div class="glass-card">
      <div style="display: flex; justify-content: space-between; flex-wrap: wrap; gap: 15px; margin-bottom: 20px;">
        <div>
          <div style="display: flex; align-items: center; gap: 10px;">
            <span class="${badgeClass}">${level}</span>
            <span style="color: #64748b; font-size: 0.75rem; text-transform: uppercase;">${d.query_type}</span>
          </div>
          <h3 style="font-family: monospace; font-size: 1.1rem; color: #e2e8f0; margin-top: 10px; word-break: break-all;">${escHtml(d.query)}</h3>
        </div>
        <div style="text-align: right;">
          <div style="font-size: 2rem; font-weight: bold; color: ${color};">${score}</div>
          <div style="font-size: 0.7rem; color: #64748b;">Risk Score / 100</div>
        </div>
      </div>
      <div class="meter-bar" style="margin-bottom: 20px;">
        <div class="meter-fill" id="meterFill" style="width: 0%; background: linear-gradient(90deg, ${color}, ${color}88);"></div>
      </div>
      <p style="font-size: 0.9rem; color: #94a3b8; line-height: 1.5;">${escHtml(ai.summary || '')}</p>
    </div>`;

  if (ai.indicators?.length) {
    html += `<div class="glass-card"><h4 style="font-size: 0.75rem; color: #64748b; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 15px;">⚠️ Threat Indicators</h4><ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 8px;">`;
    ai.indicators.forEach(ind => { html += `<li style="font-size: 0.9rem; color: #e2e8f0; display: flex; align-items: flex-start; gap: 10px;"><span style="color: #ff3366;">•</span>${escHtml(ind)}</li>`; });
    html += '</ul></div>';
  }

  if (ai.recommendations?.length) {
    html += `<div class="glass-card"><h4 style="font-size: 0.75rem; color: #64748b; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 15px;">💡 Recommendations</h4><ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 8px;">`;
    ai.recommendations.forEach(rec => { html += `<li style="font-size: 0.9rem; color: #e2e8f0; display: flex; align-items: flex-start; gap: 10px;"><span style="color: #00d4ff;">→</span>${escHtml(rec)}</li>`; });
    html += '</ul></div>';
  }

  // Source data panels
  if (d.geolocation?.data) {
    const g = d.geolocation.data;
    html += `<div class="glass-card">
      <h4 style="font-size: 0.75rem; color: #64748b; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 15px;">🌍 Geolocation</h4>
      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin-bottom: 20px;">
        ${infoChip('Country', g.country)} ${infoChip('City', g.city)} ${infoChip('Region', g.region)}
        ${infoChip('ISP', g.org)} ${infoChip('Timezone', g.timezone)}
      </div>
      <div id="osintMap" style="height: 300px; border-radius: 8px; border: 1px solid rgba(30,45,74,0.8);"></div>
    </div>`;
  }

  if (d.abuseipdb?.data) {
    const a = d.abuseipdb.data;
    html += `<div class="glass-card"><h4 style="font-size: 0.75rem; color: #64748b; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 15px;">🛡️ AbuseIPDB</h4>
      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px;">
        ${infoChip('Abuse Score', a.abuseConfidenceScore + '%')}
        ${infoChip('Reports', a.totalReports)}
        ${infoChip('ISP', a.isp)}
        ${infoChip('Usage', a.usageType)}
      </div></div>`;
  }

  if (d.virustotal?.data) {
    const v = d.virustotal.data;
    html += `<div class="glass-card"><h4 style="font-size: 0.75rem; color: #64748b; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 15px;">🦠 VirusTotal</h4>
      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px;">
        ${infoChip('Malicious', v.positives ?? 0)}
        ${infoChip('Total Scanners', v.total ?? 0)}
      </div></div>`;
  }

  if (d.breaches) {
    html += `<div class="glass-card"><h4 style="font-size: 0.75rem; color: #64748b; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 15px;">📧 Data Breaches</h4>
      <p style="font-size: 0.9rem; font-weight: bold; margin-bottom: 15px; color: ${d.breaches.breach_count > 0 ? '#ff3366' : '#00ff88'};">${d.breaches.breach_count} breach(es) found</p>`;
    if (d.breaches.breaches?.length) {
      d.breaches.breaches.forEach(b => {
        html += `<div class="info-chip" style="margin-bottom: 10px;">
          <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
            <span style="font-size: 0.9rem; font-weight: bold; color: #e2e8f0;">${escHtml(b.name)}</span>
            <span style="font-size: 0.7rem; color: #64748b;">${b.breach_date}</span>
          </div>
          <div style="font-size: 0.75rem; color: #94a3b8;">${(b.data_classes||[]).join(', ')}</div>
        </div>`;
      });
    }
    html += '</div>';
  }

  const resultsDiv = document.getElementById('results');
  resultsDiv.innerHTML = html;
  resultsDiv.style.display = 'flex';
  document.getElementById('osintExportBtn').style.display = 'flex';

  setTimeout(() => {
    const fill = document.getElementById('meterFill');
    if (fill) fill.style.width = score + '%';
  }, 50);

  // Leaflet map
  if (d.geolocation?.data?.loc) {
    const [lat, lng] = d.geolocation.data.loc.split(',').map(Number);
    setTimeout(() => {
      if (map) { map.remove(); map = null; }
      map = L.map('osintMap', { zoomControl: true, scrollWheelZoom: false }).setView([lat, lng], 6);
      
      var cyberNight = L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
          attribution: '&copy; CartoDB'
      });
      cyberNight.addTo(map);

      L.circle([lat, lng], { color, fillColor: color, fillOpacity: 0.2, radius: 50000 }).addTo(map);
      
      var markerIcon = L.divIcon({
          className: 'custom-div-icon',
          html: `<div style='width: 30px; height: 30px; background: ${color}33; border: 2px solid ${color}; border-radius: 50%; box-shadow: 0 0 20px ${color}; display:flex; align-items:center; justify-content:center;'><div style='width:8px; height:8px; background:#fff; border-radius:50%;'></div></div>`,
          iconSize: [30, 30], iconAnchor: [15, 15]
      });
      L.marker([lat, lng], {icon: markerIcon}).addTo(map).bindPopup("<div style='color:#000; font-family:Rajdhani; font-weight:bold;'>TARGET LOCATION</div>").openPopup();
    }, 100);
  }
}

function infoChip(label, value) {
  return `<div class="info-chip">
    <div style="font-size: 0.7rem; color: #64748b; margin-bottom: 5px; text-transform: uppercase;">${label}</div>
    <div style="font-size: 0.95rem; font-family: monospace; color: #e2e8f0; font-weight: bold; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">${value ?? 'N/A'}</div>
  </div>`;
}

function escHtml(str) {
  return String(str || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
