    <script src="../assets/js/main.js"></script>
    
    <?php
    
    if (isset($_SESSION['toast_msg'])) {
        $type = $_SESSION['toast_type'] ?? 'success';
        echo "<script>showToast('" . addslashes($_SESSION['toast_msg']) . "', '{$type}');</script>";
        unset($_SESSION['toast_msg'], $_SESSION['toast_type']);
    }
    ?>
    
    <?php if (isset($_SESSION['user_id']) && isset($_SESSION['user_role']) && !in_array(basename($_SERVER['PHP_SELF']), ['login.php', 'signup.php', 'index.php', 'otp_verify.php', 'forgot_password.php', 'reset_password.php', 'faq.php'])): ?>
    <div id="ai-chat-widget-container" style="position: fixed; bottom: 20px; right: 20px; z-index: 99999; font-family: 'Inter', sans-serif;">
        
        <button id="ai-chat-btn" onclick="toggleAIChat()" style="width: 55px; height: 55px; border-radius: 50%; background: linear-gradient(135deg, #00d4ff, #0066ff); border: none; box-shadow: 0 5px 20px rgba(0, 212, 255, 0.4); cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.3s; color: #fff; font-size: 24px;">
            <i class="fa-solid fa-robot"></i>
        </button>

        
        <div id="ai-chat-window" style="display: none; position: absolute; bottom: 70px; right: 0; width: 350px; height: 500px; background: rgba(13, 21, 38, 0.95); backdrop-filter: blur(12px); border: 1px solid rgba(0, 212, 255, 0.3); border-radius: 12px; box-shadow: 0 10px 40px rgba(0,0,0,0.5); flex-direction: column; overflow: hidden; transform-origin: bottom right; transition: all 0.3s;">
            
            <div style="padding: 15px; background: rgba(0, 212, 255, 0.1); border-bottom: 1px solid rgba(0, 212, 255, 0.2); display: flex; justify-content: space-between; align-items: center;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div style="width: 30px; height: 30px; border-radius: 8px; background: linear-gradient(135deg, #00d4ff, #0066ff); display: flex; align-items: center; justify-content: center; color: #fff; font-weight: bold; font-size: 12px;">AI</div>
                    <span style="color: #fff; font-weight: 600; font-size: 14px;">OSINT Assistant</span>
                </div>
                <button onclick="toggleAIChat()" style="background: none; border: none; color: #64748b; cursor: pointer; font-size: 16px;"><i class="fa-solid fa-xmark"></i></button>
            </div>
            
            
            <div id="ai-chat-messages" style="flex: 1; padding: 15px; overflow-y: auto; display: flex; flex-direction: column; gap: 15px;">
                <div style="display: flex; gap: 10px;">
                    <div style="width: 28px; height: 28px; border-radius: 6px; background: linear-gradient(135deg, #00d4ff, #0066ff); display: flex; align-items: center; justify-content: center; color: #fff; font-size: 10px; font-weight: bold; flex-shrink: 0;">AI</div>
                    <div style="background: rgba(17, 24, 39, 0.8); border: 1px solid rgba(30, 45, 74, 0.6); border-radius: 12px 12px 12px 4px; padding: 10px 14px; max-width: 85%;">
                        <p style="color: #e2e8f0; font-size: 13px; margin: 0;">Hello! I'm your cybersecurity intelligence assistant. Ask me anything about OSINT, threats, or investigations!</p>
                    </div>
                </div>
            </div>
            
            
            <div style="padding: 15px; background: rgba(13, 21, 38, 0.9); border-top: 1px solid rgba(30, 45, 74, 0.8); display: flex; gap: 10px;">
                <input type="text" id="ai-chat-input" placeholder="Type a message..." onkeydown="handleAIChatKey(event)" style="flex: 1; background: rgba(0,0,0,0.4); border: 1px solid rgba(30, 45, 74, 0.8); border-radius: 8px; padding: 10px 15px; color: #e2e8f0; font-size: 13px; outline: none;">
                <button id="ai-chat-send" onclick="sendAIChatMessage()" style="background: linear-gradient(135deg, #00d4ff, #0066ff); border: none; border-radius: 8px; width: 40px; color: #fff; cursor: pointer; display: flex; align-items: center; justify-content: center;"><i class="fa-solid fa-paper-plane"></i></button>
            </div>
        </div>
    </div>
    
    <style>
        #ai-chat-btn:hover { transform: scale(1.05); }
        .ai-msg-user { background: linear-gradient(135deg, rgba(0, 212, 255, 0.15), rgba(0, 102, 255, 0.1)); border: 1px solid rgba(0, 212, 255, 0.2); border-radius: 12px 12px 4px 12px; padding: 10px 14px; max-width: 85%; margin-left: auto; color: #e2e8f0; font-size: 13px; }
        .ai-msg-bot { background: rgba(17, 24, 39, 0.8); border: 1px solid rgba(30, 45, 74, 0.6); border-radius: 12px 12px 12px 4px; padding: 10px 14px; max-width: 85%; color: #e2e8f0; font-size: 13px; white-space: pre-wrap; }
        .ai-typing-dot { width: 5px; height: 5px; border-radius: 50%; background: #00d4ff; animation: ai-bounce 0.9s infinite; display: inline-block; margin-right: 3px; }
        .ai-typing-dot:nth-child(2) { animation-delay: 0.15s; } .ai-typing-dot:nth-child(3) { animation-delay: 0.3s; margin-right: 0; }
        @keyframes ai-bounce { 0%, 60%, 100% { transform: translateY(0); } 30% { transform: translateY(-4px); } }
        
        #ai-chat-messages::-webkit-scrollbar { width: 4px; }
        #ai-chat-messages::-webkit-scrollbar-track { background: transparent; }
        #ai-chat-messages::-webkit-scrollbar-thumb { background: rgba(0, 212, 255, 0.3); border-radius: 4px; }
    </style>
    
    <script>
        function toggleAIChat() {
            const win = document.getElementById('ai-chat-window');
            win.style.display = win.style.display === 'none' ? 'flex' : 'none';
            if (win.style.display === 'flex') {
                document.getElementById('ai-chat-input').focus();
            }
        }
        
        function handleAIChatKey(e) {
            if (e.key === 'Enter') { sendAIChatMessage(); }
        }
        
        async function sendAIChatMessage() {
            const input = document.getElementById('ai-chat-input');
            const msg = input.value.trim();
            if (!msg) return;
            
            input.value = '';
            appendAIChat('user', msg);
            
            const sendBtn = document.getElementById('ai-chat-send');
            sendBtn.disabled = true;
            sendBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';
            
            const typingId = 'typing-' + Date.now();
            appendAIChatTyping(typingId);
            
            try {
                const res = await fetch('../OSINT_PHP/api/chatbot.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ message: msg })
                });
                const data = await res.json();
                removeAITyping(typingId);
                appendAIChat('bot', data.response || 'Sorry, I could not generate a response.');
            } catch (e) {
                removeAITyping(typingId);
                appendAIChat('bot', '⚠️ Connection error. Ensure the API is accessible.');
            } finally {
                sendBtn.disabled = false;
                sendBtn.innerHTML = '<i class="fa-solid fa-paper-plane"></i>';
            }
        }
        
        function appendAIChat(role, text) {
            const container = document.getElementById('ai-chat-messages');
            const div = document.createElement('div');
            
            if (role === 'user') {
                div.style.display = 'flex'; div.style.justifyContent = 'flex-end';
                div.innerHTML = `<div class="ai-msg-user">${escAIChatHtml(text)}</div>`;
            } else {
                div.style.display = 'flex'; div.style.gap = '10px';
                div.innerHTML = `
                    <div style="width: 28px; height: 28px; border-radius: 6px; background: linear-gradient(135deg, #00d4ff, #0066ff); display: flex; align-items: center; justify-content: center; color: #fff; font-size: 10px; font-weight: bold; flex-shrink: 0;">AI</div>
                    <div class="ai-msg-bot">${escAIChatHtml(text)}</div>
                `;
            }
            container.appendChild(div);
            container.scrollTop = container.scrollHeight;
        }
        
        function appendAIChatTyping(id) {
            const container = document.getElementById('ai-chat-messages');
            const div = document.createElement('div');
            div.id = id;
            div.style.display = 'flex'; div.style.gap = '10px';
            div.innerHTML = `
                <div style="width: 28px; height: 28px; border-radius: 6px; background: linear-gradient(135deg, #00d4ff, #0066ff); display: flex; align-items: center; justify-content: center; color: #fff; font-size: 10px; font-weight: bold; flex-shrink: 0;">AI</div>
                <div class="ai-msg-bot" style="padding: 15px 14px;"><div class="ai-typing-dot"></div><div class="ai-typing-dot"></div><div class="ai-typing-dot"></div></div>
            `;
            container.appendChild(div);
            container.scrollTop = container.scrollHeight;
        }
        
        function removeAITyping(id) {
            const el = document.getElementById(id);
            if (el) el.remove();
        }
        
        function escAIChatHtml(str) {
            return String(str||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
        }
    </script>
    <?php endif; ?>

    
    <?php if (!in_array(basename($_SERVER['PHP_SELF']), ['login.php', 'signup.php', 'otp_verify.php', 'forgot_password.php', 'reset_password.php'])): ?>
    <footer id="site-footer" style="
        margin-top: auto;
        background: rgba(7, 11, 22, 0.95);
        border-top: 1px solid rgba(0,243,255,0.08);
        padding: 18px 30px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 12px;
        font-size: 0.8rem;
        color: #475569;
        font-family: 'Inter', sans-serif;
        position: relative;
        z-index: 100;
    ">
        <span style="color:#334155;">
            &copy; <?= date('Y') ?> <strong style="color:#64748b;">Digital Investigation</strong>. All Rights Reserved.
        </span>
        <div style="display:flex; align-items:center; gap:20px; flex-wrap:wrap;">
            <a href="<?= BASE_URL ?>/verify_certificate.php" style="color:#64748b; text-decoration:none; transition:color 0.2s; display:flex; align-items:center; gap:6px;"
               onmouseover="this.style.color='#00f3ff'" onmouseout="this.style.color='#64748b'">
                <i class="fa-solid fa-certificate" style="color:#a855f7; font-size:0.85rem;"></i> Verify Certificate
            </a>
            <span style="color:#1e293b;">|</span>
            <span style="font-family:'Share Tech Mono', monospace; color:#1e3a4a; font-size:0.72rem;">
                <i class="fa-solid fa-shield-halved" style="color:#1e3a4a;"></i> SECURED PLATFORM
            </span>
        </div>
    </footer>
    <?php endif; ?>
</body>
</html>
