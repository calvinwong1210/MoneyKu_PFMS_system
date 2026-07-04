<?php
session_start();

// If the user is already logged in, seamlessly bypass the landing page
// if (isset($_SESSION['user_id'])) {
//     header("Location: dashboard.php");
//     exit();
// }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MoneyKu - Take Control of Your Financial Future</title>
    <link rel="stylesheet" href="../css/index.css">
</head>
<body class="landing-body">

    <header class="navbar">
        <div class="logo">
            <a href="index.php">
                <img src="../images/logo.png" alt="PFMS Logo" class="logo-img">
            </a>
        </div>
        <nav class="nav-links">
            <a href="#features">Features</a>
            <a href="login.php" class="btn-login">Sign In</a>
        </nav>
    </header>

    <main class="hero">
        <div class="hero-content">
            <h1>Smart personal finance management <span>for young adults</span></h1>
            <p>Track your income and expenses, manage your student loan, plan your budget, and achieve your financial goals with one integrated personal finance system.</p>
            
            <div class="cta-group">
                <a href="register.php" class="btn-primary">Manage Your Finances Now</a>
                <a href="#features" class="btn-secondary">Learn More</a>
            </div>
        </div>

        <div class="hero-graphic" id="tiltCard">
            <svg width="420" height="360" viewBox="0 0 400 350" xmlns="http://www.w3.org/2000/svg">
                <rect width="400" height="350" rx="20" fill="#0f172a"/>
                <rect x="25" y="25" width="350" height="80" rx="16" fill="#1e293b"/>
                <text x="45" y="52" fill="#94a3b8" font-size="13" font-family="Arial">Total Balance</text>
                <text x="45" y="82" fill="#ffffff" font-size="28" font-weight="bold" font-family="Arial">RM 12,450</text>
                <rect x="30" y="125" width="100" height="70" rx="12" fill="#1e293b"/>
                <text x="45" y="150" fill="#94a3b8" font-size="11">Income</text>
                <text x="45" y="175" fill="#10b981" font-size="18">+3000</text>
                <rect x="150" y="125" width="100" height="70" rx="12" fill="#1e293b"/>
                <text x="165" y="150" fill="#94a3b8" font-size="11">Expense</text>
                <text x="165" y="175" fill="#ef4444" font-size="18">-1000</text>
                <rect x="270" y="125" width="100" height="70" rx="12" fill="#1e293b"/>
                <text x="285" y="150" fill="#94a3b8" font-size="11">Savings</text>
                <text x="285" y="175" fill="#10b981" font-size="18">2000</text>

                <path d="M40 285 L100 245 L170 260 L240 190 L320 210 L360 170" stroke="#10b981" stroke-width="4" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
                <circle cx="320" cy="210" r="6" fill="#ffffff"/>

                <text x="40" y="320" fill="#94a3b8" font-size="12">Savings Goal</text>
                <rect x="145" y="309" width="180" height="12" rx="6" fill="#334155"/>
                <rect x="145" y="309" width="140" height="12" rx="6" fill="#10b981"/>
            </svg>
        </div>
    </main>

    <section id="features" class="features-grid">
        <div class="feature-card">
            <div class="feature-icon">📊</div>
            <h3>Budget Management</h3>
            <p>Track your income and expenses with clear spending insights.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon">🎯</div>
            <h3>Savings Goals</h3>
            <p>Set savings targets and monitor your progress toward future goals.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon">🔒</div>
            <h3>Financial Dashboard</h3>
            <p>View your financial overview, spending trends, and savings performance in one place.</p>
        </div>
    </section>

    <script>
        // 1. Mouse Tilt Effect for Hero Graphic
        const card = document.getElementById('tiltCard');
        if(card) {
            document.addEventListener('mousemove', (e) => {
                const cx = window.innerWidth / 2;
                const cy = window.innerHeight / 2;
                const dx = e.clientX - cx;
                const dy = e.clientY - cy;
                const tiltX = (dy / cy) * 15;
                const tiltY = -(dx / cx) * 15;
                card.style.transform = `rotateX(${tiltX}deg) rotateY(${tiltY}deg)`;
            });
        }

        // 2. Intersection Observer
        const cards = document.querySelectorAll('.feature-card');
        const observerOptions = {
            threshold: 0.1,
            rootMargin: "0px 0px -50px 0px"
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry, index) => {
                if (entry.isIntersecting) {
                    setTimeout(() => {
                        entry.target.classList.add('visible');
                    }, index * 150); 
                    observer.unobserve(entry.target);
                }
            });
        }, observerOptions);

        cards.forEach(card => observer.observe(card));
    </script>
</body>
</html>