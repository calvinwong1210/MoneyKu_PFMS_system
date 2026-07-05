<?php
// 获取当前运行的脚本文件名（例如：dashboard.php 或 user_profile.php）
$current_page = basename($_SERVER['PHP_SELF']);
?>
<aside class="sidebar">
    <div class="logo">
        <a href="dashboard.php">
            <img src="../../images/logo.png" alt="PFMS Logo" class="logo-img">
        </a>
    </div>        
    <nav class="menu">
        <a href="dashboard.php" class="menu-item <?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>">
            <span class="icon">📊</span> Dashboard
        </a>
        <a href="#transactions" class="menu-item">
            <span class="icon">💸</span> Transactions
        </a>
        <a href="#budgets" class="menu-item">
            <span class="icon">🎯</span> Budgets
        </a>
        <a href="#analytics" class="menu-item">
            <span class="icon">📈</span> Analytics
        </a>
    </nav>
    <div class="sidebar-footer">
        <a href="../../public/logout.php" class="btn-logout">
            <span class="icon">🚪</span> Sign Out
        </a>
    </div>
</aside>