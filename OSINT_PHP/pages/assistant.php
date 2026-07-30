<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>AI Investigation Assistant</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet"/>
  <script>
    tailwind.config = {
      theme: { extend: { colors: {
        'cyber-bg':'#0a0e1a','cyber-surface':'#0d1526','cyber-card':'#111827',
        'cyber-border':'#1e2d4a','cyber-cyan':'#00d4ff','cyber-blue':'#0066ff',
        'cyber-green':'#00ff88','cyber-red':'#ff3366','cyber-text':'#e2e8f0','cyber-muted':'#64748b',
      }}}
    }
  </script>
  <style>
    body { font-family:'Inter',sans-serif; background:#0a0e1a; color:#e2e8f0; }
    ::-webkit-scrollbar{width:4px} ::-webkit-scrollbar-track{background:#0d1526} ::-webkit-scrollbar-thumb{background:#1e2d4a;border-radius:3px}
    nav a{display:flex;align-items:center;gap:10px;padding:10px 14px;border-radius:8px;font-size:13px;font-weight:500;color:#64748b;transition:all .2s;text-decoration:none}
    nav a:hover{color:#e2e8f0;background:rgba(255,255,255,.05)}
    nav a.active{color:#00d4ff;background:rgba(0,212,255,.1);border-right:2px solid #00d4ff}
    .glass{background:rgba(13,21,38,0.85);backdrop-filter:blur(12px);border:1px solid rgba(30,45,74,0.8);border-radius:12px}
    .chat-input{background:rgba(13,21,38,0.9);border:1px solid rgba(30,45,74,0.8);color:#e2e8f0;border-radius:8px;padding:12px 16px;font-size:14px;transition:all .3s;width:100%;outline:none;resize:none}
    .chat-input:focus{border-color:rgba(0,212,255,.6);box-shadow:0 0 0 3px rgba(0,212,255,.1)}
    .send-btn{background:linear-gradient(135deg,#00d4ff,#0066ff);color:#fff;border:none;padding:12px 20px;border-radius:8px;font-weight:600;cursor:pointer;transition:all .3s;white-space:nowrap}
    .send-btn:hover{box-shadow:0 0 20px rgba(0,212,255,.4);transform:translateY(-1px)}
    .send-btn:disabled{opacity:.5;cursor:not-allowed;transform:none}
    .msg-user{background:linear-gradient(135deg,rgba(0,212,255,.15),rgba(0,102,255,.1));border:1px solid rgba(0,212,255,.2);border-radius:12px 12px 4px 12px;padding:10px 14px;max-width:75%;margin-left:auto;text-align:right}
    .msg-bot{background:rgba(17,24,39,0.8);border:1px solid rgba(30,45,74,0.6);border-radius:12px 12px 12px 4px;padding:10px 14px;max-width:80%}
    .typing-dot{width:6px;height:6px;border-radius:50%;background:#00d4ff;animation:bounce .9s infinite}
    .typing-dot:nth-child(2){animation-delay:.15s}.typing-dot:nth-child(3){animation-delay:.3s}
    @keyframes bounce{0%,60%,100%{transform:translateY(0)}30%{transform:translateY(-6px)}}
    .spin{animation:spin 1s linear infinite}@keyframes spin{to{transform:rotate(360deg)}}
  </style>
</head>
<body class="flex h-screen overflow-hidden">

  <!-- Sidebar -->
  <aside class="w-16 lg:w-56 flex flex-col border-r border-cyber-border bg-cyber-surface flex-shrink-0">
    <nav class="flex-1 p-3 pt-6 space-y-1">
      <a href="search.php">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
        <span class="hidden lg:inline">Threat Search</span>
      </a>
      <a href="assistant.php" class="active">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 8V4H8"/><rect width="16" height="12" x="4" y="8" rx="2"/><path d="M2 14h2M20 14h2M15 13v2M9 13v2"/></svg>
        <span class="hidden lg:inline">AI Assistant</span>
      </a>
    </nav>
  </aside>

  <!-- Main -->
  <div class="flex-1 flex flex-col overflow-hidden">
    <header class="flex items-center justify-between px-6 h-14 bg-cyber-surface border-b border-cyber-border flex-shrink-0">
      <div>
        <h1 class="text-sm font-semibold text-cyber-text">AI Investigation Assistant</h1>
        <div id="clock" class="text-[10px] text-cyber-muted font-mono"></div>
      </div>
      <button onclick="clearChat()" class="text-xs text-cyber-muted hover:text-cyber-red transition-colors px-3 py-1.5 rounded-lg hover:bg-white/5">Clear Chat</button>
    </header>

    <main class="flex-1 flex flex-col overflow-hidden p-4 md:p-6 gap-4">

      <!-- Chat window -->
      <div id="chatWindow" class="glass flex-1 overflow-y-auto p-4 space-y-4">
        <!-- Welcome message -->
        <div class="flex gap-3">
          <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-cyber-cyan to-cyber-blue flex items-center justify-center text-white text-xs font-bold flex-shrink-0">AI</div>
          <div class="msg-bot">
            <p class="text-sm text-cyber-text font-semibold mb-1">OSINT AI Assistant</p>
            <p class="text-sm text-cyber-muted">Hello! I'm your cybersecurity intelligence assistant. Ask me about:</p>
            <ul class="text-sm text-cyber-muted mt-1 space-y-0.5 list-none">
              <li>🔍 Phishing, malware, ransomware</li>
              <li>🛡️ Firewalls, VPNs, 2FA</li>
              <li>💻 SQL injection, DDoS, brute force</li>
              <li>🌐 OSINT techniques and tools</li>
            </ul>
          </div>
        </div>
      </div>

      <!-- Suggested prompts -->
      <div id="suggestions" class="flex gap-2 flex-wrap">
        <?php
        $prompts = ['What is phishing?', 'How to detect malware?', 'What is OSINT?', 'Explain ransomware', 'How does a VPN work?'];
        foreach ($prompts as $p): ?>
          <button onclick="sendPrompt('<?= htmlspecialchars($p) ?>')"
            class="text-[11px] px-3 py-1.5 rounded-full border border-cyber-border text-cyber-muted hover:text-cyber-cyan hover:border-cyber-cyan/50 transition-all">
            <?= htmlspecialchars($p) ?>
          </button>
        <?php endforeach; ?>
      </div>

      <!-- Input bar -->
      <div class="glass p-3 flex gap-3 items-end">
        <textarea id="msgInput" class="chat-input" rows="1" placeholder="Ask about cybersecurity, threats, OSINT..." onkeydown="handleKey(event)" oninput="autoResize(this)"></textarea>
        <button id="sendBtn" class="send-btn" onclick="sendMessage()">Send</button>
      </div>
    </main>
  </div>

<script>
setInterval(() => { document.getElementById('clock').textContent = new Date().toLocaleTimeString('en-IN') + ' IST'; }, 1000);

function autoResize(el) { el.style.height = 'auto'; el.style.height = Math.min(el.scrollHeight, 120) + 'px'; }
function handleKey(e) { if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMessage(); } }
function sendPrompt(text) { document.getElementById('msgInput').value = text; sendMessage(); }

function clearChat() {
  const win = document.getElementById('chatWindow');
  win.innerHTML = `<div class="flex gap-3"><div class="w-8 h-8 rounded-lg bg-gradient-to-br from-cyber-cyan to-cyber-blue flex items-center justify-center text-white text-xs font-bold flex-shrink-0">AI</div>
    <div class="msg-bot"><p class="text-sm text-cyber-text font-semibold mb-1">OSINT AI Assistant</p><p class="text-sm text-cyber-muted">Chat cleared. Ask me anything about cybersecurity!</p></div></div>`;
  document.getElementById('suggestions').style.display = 'flex';
}

async function sendMessage() {
  const input = document.getElementById('msgInput');
  const message = input.value.trim();
  if (!message) return;

  document.getElementById('suggestions').style.display = 'none';
  addMessage('user', message);
  input.value = ''; input.style.height = 'auto';

  const btn = document.getElementById('sendBtn');
  btn.disabled = true; btn.textContent = '...';

  const typingId = addTyping();

  try {
    const res = await fetch('../api/chatbot.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ message }),
    });
    const data = await res.json();
    removeTyping(typingId);
    addMessage('bot', data.response || 'Sorry, I could not generate a response.');
  } catch (e) {
    removeTyping(typingId);
    addMessage('bot', '⚠️ Could not reach the backend. Make sure the PHP server is running.');
  } finally {
    btn.disabled = false; btn.textContent = 'Send';
  }
}

function addMessage(role, text) {
  const win = document.getElementById('chatWindow');
  const div = document.createElement('div');
  div.className = 'flex gap-3' + (role === 'user' ? ' flex-row-reverse' : '');

  const avatar = role === 'user'
    ? `<div class="w-8 h-8 rounded-lg bg-gradient-to-br from-cyber-muted to-slate-600 flex items-center justify-center text-white text-xs font-bold flex-shrink-0">U</div>`
    : `<div class="w-8 h-8 rounded-lg bg-gradient-to-br from-cyber-cyan to-cyber-blue flex items-center justify-center text-white text-xs font-bold flex-shrink-0">AI</div>`;

  const bubble = role === 'user'
    ? `<div class="msg-user"><p class="text-sm text-cyber-text">${escHtml(text)}</p></div>`
    : `<div class="msg-bot"><p class="text-sm text-cyber-text whitespace-pre-wrap">${escHtml(text)}</p></div>`;

  div.innerHTML = avatar + bubble;
  win.appendChild(div);
  win.scrollTop = win.scrollHeight;
  return div;
}

function addTyping() {
  const win = document.getElementById('chatWindow');
  const id = 'typing-' + Date.now();
  const div = document.createElement('div');
  div.id = id;
  div.className = 'flex gap-3';
  div.innerHTML = `<div class="w-8 h-8 rounded-lg bg-gradient-to-br from-cyber-cyan to-cyber-blue flex items-center justify-center text-white text-xs font-bold flex-shrink-0">AI</div>
    <div class="msg-bot flex items-center gap-1.5 py-3 px-4">
      <div class="typing-dot"></div><div class="typing-dot"></div><div class="typing-dot"></div>
    </div>`;
  win.appendChild(div);
  win.scrollTop = win.scrollHeight;
  return id;
}

function removeTyping(id) {
  const el = document.getElementById(id);
  if (el) el.remove();
}

function escHtml(str) {
  return String(str||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
</script>
</body>
</html>
