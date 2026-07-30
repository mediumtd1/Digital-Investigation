# 🕵️ DIGIT Corp — Web CTF Challenge

A beginner-friendly web security challenge teaching core recon skills.

## 📁 Files
```
index.html   ← Start here
login.html   ← Admin login portal
admin.html   ← Flag lives here
robots.txt   ← Path discovery clue
style.css    ← Styling
script.js    ← Login logic (has a secret...)
```

## 🚀 Setup
Just open `index.html` in any browser. No server needed for basic play.
For `robots.txt` to work properly, serve with a local server:
```bash
npx serve .
# OR
python3 -m http.server 8080
```
Then visit: http://localhost:8080

---

## 🔍 Solution Walkthrough (SPOILERS)

| Step | Action | Skill |
|------|--------|-------|
| 1 | Read homepage hint | Basic recon |
| 2 | View Page Source → find `<!-- Maybe robots can help -->` | Source inspection |
| 3 | Visit `/robots.txt` → find `Disallow: /admin.html` | robots.txt recon |
| 4 | Visit `/login.html` → see "Employee ID is the password" hint | Enumeration |
| 5 | View Source on login.html → find `<!-- EMP_ID: DIGIT2026 -->` | Source inspection |
| 6 | Login: username=`admin` password=`DIGIT2026` | Authentication bypass |
| 🚩 | Flag: `flag{intern_detective_master}` | ✅ |

---

## 🎓 Concepts Taught
- Page source inspection (Ctrl+U / Right-click → View Source)
- robots.txt enumeration
- Hidden HTML comments
- Basic login mechanics
- Sequential puzzle-solving mindset

## ⚡ Difficulty: Beginner
Perfect for first-time CTF participants, cybersecurity workshops, and school/college labs.
