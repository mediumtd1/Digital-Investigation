/**
 * Hacker Vibes & 3D UI Effects
 * Digital Investigation
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Matrix Rain Background
    initMatrix();

    // 2. 3D Tilt Effect for Glass Panels
    init3DTilt();

    // 3. Global Fade-in on Scroll
    initScrollAnims();
});

function initMatrix() {
    const canvas = document.createElement('canvas');
    canvas.className = 'hacker-bg';
    document.body.appendChild(canvas);
    const ctx = canvas.getContext('2d');

    let width = canvas.width = window.innerWidth;
    let height = canvas.height = window.innerHeight;

    let columns = Math.floor(width / 20);
    const characters = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789@#$%^&*()*&^%";
    const charArray = characters.split("");
    let drops = [];

    for (let i = 0; i < columns; i++) {
        drops[i] = 1;
    }

    function draw() {
        ctx.fillStyle = "rgba(0, 0, 0, 0.15)"; // Slightly darker background for depth
        ctx.fillRect(0, 0, width, height);

        ctx.fillStyle = "rgba(0, 242, 255, 0.8)"; // More vibrant cyan with slight transparency
        ctx.font = "16px monospace";

        for (let i = 0; i < drops.length; i++) {
            const text = charArray[Math.floor(Math.random() * charArray.length)];
            ctx.fillText(text, i * 20, drops[i] * 20);

            if (drops[i] * 20 > height && Math.random() > 0.975) {
                drops[i] = 0;
            }
            drops[i]++;
        }
    }

    let matrixInterval = setInterval(draw, 40); // Slightly slower for better readability

    window.addEventListener('resize', () => {
        width = canvas.width = window.innerWidth;
        height = canvas.height = window.innerHeight;
        columns = Math.floor(width / 20);
        drops = [];
        for (let i = 0; i < columns; i++) drops[i] = 1;
    });
}

function init3DTilt() {
    const cards = document.querySelectorAll('.glass-panel');

    cards.forEach(card => {
        card.classList.add('glass-panel-3d');

        card.addEventListener('mousemove', (e) => {
            const rect = card.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;

            const centerX = rect.width / 2;
            const centerY = rect.height / 2;

            const rotateX = (y - centerY) / 10;
            const rotateY = (centerX - x) / 10;

            card.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) scale3d(1.02, 1.02, 1.02)`;
        });

        card.addEventListener('mouseleave', () => {
            card.style.transform = `perspective(1000px) rotateX(0deg) rotateY(0deg) scale3d(1, 1, 1)`;
        });
    });
}

function initScrollAnims() {
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('fade-in-up');
            }
        });
    }, { threshold: 0.1 });

    document.querySelectorAll('.glass-panel, .portal-card-new, .news-item, .stat-card').forEach(el => {
        observer.observe(el);
    });
}
