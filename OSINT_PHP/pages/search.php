<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>OSINT Threat Search Engine</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
  <script>
    tailwind.config = {
      theme: { extend: { colors: {
        'cyber-bg':      '#0a0e1a',
        'cyber-surface': '#0d1526',
        'cyber-card':    '#111827',
        'cyber-border':  '#1e2d4a',
        'cyber-cyan':    '#00d4ff',
        'cyber-blue':    '#0066ff',
        'cyber-green':   '#00ff88',
        'cyber-red':     '#ff3366',
        'cyber-orange':  '#ff6b35',
        'cyber-yellow':  '#ffd60a',
        'cyber-text':    '#e2e8f0',
        'cyber-muted':   '#64748b',
      }}}
    }
  </script>
  <style>
    body { font-family: 'Inter', sans-serif; background: #0a0e1a; color: #e2e8f0; }
    .glass { background: rgba(13,21,38,0.85); backdrop-filter: blur(12px); border: 1px solid rgba(30,45,74,0.8); border-radius: 12px; }
    .cyber-input { background: rgba(13,21,38,0.9); border: 1px solid rgba(30,45,74,0.8); color: #e2e8f0; border-radius: 8px; padding: 12px 16px; font-size: 14px; transition: all .3s; width: 100%; outline: none; }
    .cyber-input:focus { border-color: rgba(0,212,255,0.6); box-shadow: 0 0 0 3px rgba(0,212,255,0.1); }
    .cyber-btn { background: linear-gradient(135deg,#00d4ff,#0066ff); color:#fff; border:none; padding:12px 28px; border-radius:8px; font-weight:600; cursor:pointer; transition:all .3s; }
    .cyber-btn:hover { box-shadow: 0 0 25px rgba(0,212,255,.5); transform: translateY(-1px); }
    .cyber-btn:disabled { opacity:.5; cursor:not-allowed; transform:none; }
    .badge { display:inline-flex; align-items:center; padding:3px 10px; border-radius:20px; font-size:11px; font-weight:700; letter-spacing:.05em; }
    .badge-safe     { background:rgba(0,255,136,.15); color:#00ff88; border:1px solid rgba(0,255,136,.3); }
    .badge-low      { background:rgba(0,212,255,.15); color:#00d4ff; border:1px solid rgba(0,212,255,.3); }
    .badge-medium   { background:rgba(255,214,10,.15); color:#ffd60a; border:1px solid rgba(255,214,10,.3); }
    .badge-high     { background:rgba(255,107,53,.15); color:#ff6b35; border:1px solid rgba(255,107,53,.3); }
    .badge-critical { background:rgba(255,51,102,.15); color:#ff3366; border:1px solid rgba(255,51,102,.3); animation:pulse 2s infinite; }
    @keyframes pulse { 0%,100%{opacity:1} 50%{opacity:.6} }
    .meter-bar { height:8px; border-radius:4px; background:rgba(30,45,74,0.8); overflow:hidden; }
    .meter-fill { height:100%; border-radius:4px; transition:width 1.2s cubic-bezier(.34,1.56,.64,1); }
    .leaflet-container { background:#0a0e1a !important; border-radius:10px; }
    .leaflet-tile { filter:invert(1) hue-rotate(180deg) brightness(.7) saturate(1.5) !important; }
    ::-webkit-scrollbar { width:5px; } ::-webkit-scrollbar-track { background:#0d1526; } ::-webkit-scrollbar-thumb { background:#1e2d4a; border-radius:3px; }
    .spin { animation: spin 1s linear infinite; } @keyframes spin { to { transform: rotate(360deg); } }
    nav a { display:flex; align-items:center; gap:10px; padding:10px 14px; border-radius:8px; font-size:13px; font-weight:500; color:#64748b; transition:all .2s; text-decoration:none; }
    nav a:hover { color:#e2e8f0; background:rgba(255,255,255,.05); }
    nav a.active { color:#00d4ff; background:rgba(0,212,255,.1); border-right:2px solid #00d4ff; }
  </style>
</head>
<body class="flex h-screen overflow-hidden">

  <!-- Sidebar -->
  <aside class="w-16 lg:w-56 flex flex-col border-r border-cyber-border bg-cyber-surface flex-shrink-0 transition-all duration-300">
    <nav class="flex-1 p-3 pt-6 space-y-1">
      <a href="search.php" class="active">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
        <span class="hidden lg:inline">Threat Search</span>
      </a>
      <a href="assistant.php">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 8V4H8"/><rect width="16" height="12" x="4" y="8" rx="2"/><path d="M2 14h2M20 14h2M15 13v2M9 13v2"/></svg>
        <span class="hidden lg:inline">AI Assistant</span>
      </a>
    </nav>
  </aside>

  <!-- Main -->
  <div class="flex-1 flex flex-col overflow-hidden">
    <!-- Navbar -->
    <header class="flex items-center justify-between px-6 h-14 bg-cyber-surface border-b border-cyber-border flex-shrink-0">
      <div>
        <h1 class="text-sm font-semibold text-cyber-text">Threat Search Engine</h1>
        <div id="clock" class="text-[10px] text-cyber-muted font-mono"></div>
      </div>
    </header>

    <!-- Content -->
    <main class="flex-1 overflow-y-auto p-4 md:p-6 space-y-5">

      <!-- Search box -->
      <div class="glass p-5">
        <h2 class="text-base font-bold text-cyber-text mb-1">🔍 Threat Intelligence Search</h2>
        <p class="text-xs text-cyber-muted mb-4">Enter an IP address, URL, email address, or domain name</p>
        <div class="flex gap-3 flex-col sm:flex-row">
          <input id="queryInput" type="text" class="cyber-input" placeholder="e.g. 8.8.8.8  |  https://example.com  |  user@email.com  |  example.com" />
          <button id="analyzeBtn" class="cyber-btn whitespace-nowrap" onclick="analyze()">
            Analyze
          </button>
        </div>
        <div class="flex gap-2 mt-3 flex-wrap">
          <?php
          $examples = ['8.8.8.8','https://google.com','test@gmail.com','suspicious-free-prize.tk'];
          foreach ($examples as $ex): ?>
            <button onclick="document.getElementById('queryInput').value='<?= $ex ?>'; analyze();"
              class="text-[11px] px-2 py-1 rounded bg-cyber-border text-cyber-muted hover:text-cyber-cyan hover:border-cyber-cyan border border-transparent transition-all font-mono">
              <?= $ex ?>
            </button>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Loading -->
      <div id="loadingState" class="hidden glass p-8 text-center">
        <div class="inline-block w-8 h-8 border-2 border-cyber-cyan border-t-transparent rounded-full spin mb-3"></div>
        <p class="text-cyber-muted text-sm">Querying threat intelligence sources...</p>
      </div>

      <!-- Results -->
      <div id="results" class="hidden space-y-4"></div>

    </main>
  </div>

<script>
// Clock
setInterval(() => {
  document.getElementById('clock').textContent = new Date().toLocaleTimeString('en-IN') + ' IST';
}, 1000);

const THREAT_COLORS = { SAFE:'#00ff88', LOW:'#00d4ff', MEDIUM:'#ffd60a', HIGH:'#ff6b35', CRITICAL:'#ff3366' };
let map = null;

async function analyze() {
  const query = document.getElementById('queryInput').value.trim();
  if (!query) return;

  const btn = document.getElementById('analyzeBtn');
  btn.disabled = true; btn.textContent = 'Analyzing...';
  document.getElementById('loadingState').classList.remove('hidden');
  document.getElementById('results').classList.add('hidden');
  document.getElementById('results').innerHTML = '';

  try {
    const res = await fetch('../api/threat.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ query }),
    });
    const data = await res.json();
    if (!res.ok) { alert(data.detail || 'Analysis failed'); return; }
    renderResults(data);
  } catch (e) {
    alert('Could not reach the backend. Make sure the PHP server is running.');
  } finally {
    btn.disabled = false; btn.textContent = 'Analyze';
    document.getElementById('loadingState').classList.add('hidden');
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
    <div class="glass p-5">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
        <div>
          <div class="flex items-center gap-3">
            <span class="${badgeClass}">${level}</span>
            <span class="text-cyber-muted text-xs capitalize">${d.query_type}</span>
          </div>
          <h3 class="font-mono text-sm text-cyber-text mt-1 break-all">${escHtml(d.query)}</h3>
        </div>
        <div class="text-right">
          <div class="text-3xl font-bold" style="color:${color}">${score}</div>
          <div class="text-[10px] text-cyber-muted">Risk Score / 100</div>
        </div>
      </div>
      <div class="meter-bar mb-4">
        <div class="meter-fill" id="meterFill" style="width:0%;background:linear-gradient(90deg,${color},${color}88)"></div>
      </div>
      <p class="text-sm text-cyber-muted">${escHtml(ai.summary || '')}</p>
    </div>`;

  if (ai.indicators?.length) {
    html += `<div class="glass p-4"><h4 class="text-xs font-semibold text-cyber-muted uppercase tracking-widest mb-3">⚠️ Threat Indicators</h4><ul class="space-y-1">`;
    ai.indicators.forEach(ind => { html += `<li class="text-sm text-cyber-text flex items-start gap-2"><span class="text-cyber-red mt-0.5">•</span>${escHtml(ind)}</li>`; });
    html += '</ul></div>';
  }

  if (ai.recommendations?.length) {
    html += `<div class="glass p-4"><h4 class="text-xs font-semibold text-cyber-muted uppercase tracking-widest mb-3">💡 Recommendations</h4><ul class="space-y-1">`;
    ai.recommendations.forEach(rec => { html += `<li class="text-sm text-cyber-text flex items-start gap-2"><span class="text-cyber-cyan mt-0.5">→</span>${escHtml(rec)}</li>`; });
    html += '</ul></div>';
  }

  // Source data panels
  if (d.geolocation?.data) {
    const g = d.geolocation.data;
    html += `<div class="glass p-4">
      <h4 class="text-xs font-semibold text-cyber-muted uppercase tracking-widest mb-3">🌍 Geolocation</h4>
      <div class="grid grid-cols-2 md:grid-cols-3 gap-3 mb-4">
        ${infoChip('Country', g.country)} ${infoChip('City', g.city)} ${infoChip('Region', g.region)}
        ${infoChip('ISP', g.org)} ${infoChip('Timezone', g.timezone)}
      </div>
      <div id="osintMap" style="height:200px;border-radius:10px"></div>
    </div>`;
  }

  if (d.abuseipdb?.data) {
    const a = d.abuseipdb.data;
    html += `<div class="glass p-4"><h4 class="text-xs font-semibold text-cyber-muted uppercase tracking-widest mb-3">🛡️ AbuseIPDB</h4>
      <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
        ${infoChip('Abuse Score', a.abuseConfidenceScore + '%')}
        ${infoChip('Reports', a.totalReports)}
        ${infoChip('ISP', a.isp)}
        ${infoChip('Usage', a.usageType)}
        ${d.abuseipdb.mock ? '<span class="text-[10px] text-cyber-muted col-span-3">⚠ Demo data — add ABUSEIPDB_API_KEY for live results</span>' : ''}
      </div></div>`;
  }

  if (d.virustotal?.data) {
    const v = d.virustotal.data;
    html += `<div class="glass p-4"><h4 class="text-xs font-semibold text-cyber-muted uppercase tracking-widest mb-3">🦠 VirusTotal</h4>
      <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
        ${infoChip('Malicious', v.positives ?? 0)}
        ${infoChip('Total Scanners', v.total ?? 0)}
        ${d.virustotal.mock ? '<span class="text-[10px] text-cyber-muted col-span-3">⚠ Demo data — add VIRUSTOTAL_API_KEY for live results</span>' : ''}
      </div></div>`;
  }

  if (d.breaches) {
    html += `<div class="glass p-4"><h4 class="text-xs font-semibold text-cyber-muted uppercase tracking-widest mb-3">📧 Data Breaches</h4>
      <p class="text-sm font-semibold mb-2" style="color:${d.breaches.breach_count > 0 ? '#ff3366' : '#00ff88'}">${d.breaches.breach_count} breach(es) found</p>`;
    if (d.breaches.breaches?.length) {
      d.breaches.breaches.forEach(b => {
        html += `<div class="p-3 rounded-lg bg-cyber-card mb-2">
          <div class="flex justify-between"><span class="text-sm font-semibold text-cyber-text">${escHtml(b.name)}</span><span class="text-[10px] text-cyber-muted">${b.breach_date}</span></div>
          <div class="text-xs text-cyber-muted mt-1">${(b.data_classes||[]).join(', ')}</div>
        </div>`;
      });
    }
    html += '</div>';
  }

  const results = document.getElementById('results');
  results.innerHTML = html;
  results.classList.remove('hidden');

  setTimeout(() => {
    const fill = document.getElementById('meterFill');
    if (fill) fill.style.width = score + '%';
  }, 50);

  // Leaflet map
  if (d.geolocation?.data?.loc) {
    const [lat, lng] = d.geolocation.data.loc.split(',').map(Number);
    setTimeout(() => {
      if (map) { map.remove(); map = null; }
      map = L.map('osintMap', { zoomControl: false, scrollWheelZoom: false }).setView([lat, lng], 6);
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
      L.circle([lat, lng], { color, fillColor: color, fillOpacity: 0.2, radius: 50000 }).addTo(map);
      L.marker([lat, lng]).addTo(map).bindPopup(d.query).openPopup();
    }, 100);
  }
}

function infoChip(label, value) {
  return `<div class="bg-cyber-card p-2 rounded-lg">
    <div class="text-[10px] text-cyber-muted mb-0.5">${label}</div>
    <div class="text-xs font-mono text-cyber-text font-semibold truncate">${value ?? 'N/A'}</div>
  </div>`;
}

function escHtml(str) {
  return String(str || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
</script>
</body>
</html>
