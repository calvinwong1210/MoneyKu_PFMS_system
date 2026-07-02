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
    <title>PFMS - Take Control of Your Financial Future</title>
    <link rel="stylesheet" href="../css/index.css">
</head>
<body class="landing-body">

    <header class="navbar">
        <div class="logo">PFMS<span>.</span></div>
        <nav class="nav-links">
            <a href="#features">Features</a>
            <a href="login.php" class="btn-login">Sign In</a>
        </nav>
    </header>

    <main class="hero">
        <div class="hero-content">
            <span class="badge">✦ Smart Financial Management</span>
            <h1>Smart budgeting for your <span>next milestones.</span></h1>
            <p>Track your daily expenses, monitor your subscriptions, set savings targets, and visualize your financial health with our all-in-one personal finance hub built for students and professionals.</p>
            
            <div class="cta-group">
                <a href="register.php" class="btn-primary">Get Started — It's Free</a>
                <a href="#features" class="btn-secondary">Learn More</a>
            </div>
        </div>

        <div class="hero-graphic" id="tiltCard">
            <svg width="420" height="360" viewBox="0 0 400 350" fill="none" xmlns="http://www.w3.org/2000/svg">
                <rect width="400" height="350" rx="20" fill="#0f172a"/>
                <rect x="30" y="40" width="340" height="60" rx="12" fill="#1e293b"/>
                <circle cx="65" cy="70" r="15" fill="#10b981"/>
                <rect x="100" y="60" width="120" height="8" rx="4" fill="#64748b"/>
                <rect x="100" y="74" width="60" height="6" rx="3" fill="#334155"/>
                <rect x="290" y="58" width="50" height="24" rx="12" fill="#10b981" fill-opacity="0.2"/>
                <path d="M40 280 L120 220 L200 250 L280 160 L360 190" stroke="#10b981" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
                <circle cx="280" cy="160" r="6" fill="#ffffff"/>
            </svg>
        </div>
    </main>

    <section id="features" class="features-grid">
        <div class="feature-card">
            <div class="feature-icon">📊</div>
            <h3>Expense Analytics</h3>
            <p>Categorize your spending habits automatically. Visualize where your money goes with clear, interactive micro-charts.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon">🎯</div>
            <h3>Savings Goals</h3>
            <p>Planning for tuition, gadgets, or travel? Set milestones and watch your progress update in real time.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon">🔒</div>
            <h3>Secure &amp; Reliable</h3>
            <p>Your security is our priority. Your password and personal account profiles are protected using industry-grade encryption.</p>
        </div>
    </section>

    <script>
        // 1. Mouse Tilt Effect for Hero Graphic (高级3D卡片倾斜交互)
        const card = document.getElementById('tiltCard');
        if(card) {
            document.addEventListener('mousemove', (e) => {
                const cx = window.innerWidth / 2;
                const cy = window.innerHeight / 2;
                const dx = e.clientX - cx;
                const dy = e.clientY - cy;
                const tiltX = (dy / cy) * 15; // 倾斜角度系数
                const tiltY = -(dx / cx) * 15;
                card.style.transform = `rotateX(${tiltX}deg) rotateY(${tiltY}deg)`;
            });
        }

        // 2. Intersection Observer (滚动到特定视口时卡片平滑淡入)
        const cards = document.querySelectorAll('.feature-card');
        const observerOptions = {
            threshold: 0.1,
            rootMargin: "0px 0px -50px 0px"
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry, index) => {
                if (entry.isIntersecting) {
                    // 增加级联延迟（Staggered Delay），让卡片一个接一个浮现
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