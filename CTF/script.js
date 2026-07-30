// ██████╗ ████████╗███████╗
// ██╔════╝ ╚══██╔══╝██╔════╝
// ██║         ██║   █████╗
// ██║         ██║   ██╔══╝
// ╚██████╗    ██║   ██║
//  ╚═════╝    ╚═╝   ╚═╝
// CTF Challenge — script.js

// Hint left in plain sight for curious eyes...
// EMP_ID for admin portal: Base64(RElHSVQyMDI2)

function checkLogin() {
  const user = document.getElementById('username').value.trim();
  const pass = document.getElementById('password').value.trim();
  const msg  = document.getElementById('msg');
  const form = document.getElementById('loginForm');

  if (!user || !pass) {
    msg.textContent = '⚠ All fields required.';
    msg.style.color = '#ffd700';
    return;
  }

  // Obfuscated checks
  if (btoa(user) === 'YWRtaW4=' && btoa(pass) === 'RElHSVQyMDI2') {
    msg.textContent = '✔ ACCESS GRANTED — Redirecting...';
    msg.style.color = '#00ff41';
    setTimeout(() => { window.location.href = 'admin.html?auth=true'; }, 900);
  } else {
    msg.textContent = '✖ ACCESS DENIED — Invalid credentials.';
    msg.style.color = '#ff3b3b';
    form.classList.remove('shake');
    void form.offsetWidth; // reflow to restart animation
    form.classList.add('shake');
  }
}

// Allow Enter key
document.addEventListener('DOMContentLoaded', () => {
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Enter') checkLogin();
  });
});
