<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$current_page = basename($_SERVER['PHP_SELF']);
$username = $_SESSION['username'] ?? 'User';
$role = $_SESSION['role'] ?? 'Member';
?>
    <link rel="stylesheet" href="../css/user_sidebar.css">

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
        <a href="user_transaction.php" class="menu-item <?php echo ($current_page == 'user_transaction.php') ? 'active' : ''; ?>">
            <span class="icon">💸</span> Transactions
        </a>
        <a href="user_budget.php" class="menu-item <?php echo ($current_page == 'user_budget.php') ? 'active' : ''; ?>">
            <span class="icon">💰</span> Budgets
        </a>
        <a href="user_saving_goal.php" class="menu-item <?php echo ($current_page == 'user_saving_goal.php') ? 'active' : ''; ?>">
            <span class="icon">🎯</span> Savings
        </a>
        <a href="ptptn_dashboard.php" class="menu-item <?php echo ($current_page == 'ptptn_dashboard.php') ? 'active' : ''; ?>">
            <span class="icon">🎓</span> PTPTN Loan
        </a>
        <a href="financial_simulation.php" class="menu-item <?php echo ($current_page == 'financial_simulation.php') ? 'active' : ''; ?>">
            <span class="icon">🧪</span> Simulation
        </a>
    </nav>
    <div class="sidebar-profile">
        <a href="user_profile.php" class="sidebar-profile-link" style="text-decoration: none; color: inherit; display: block; width: 100%;">
            <div class="user-profile">
                <div class="avatar">
                    <?php if (!empty($_SESSION['profile_picture']) && file_exists(__DIR__ . '/uploads/avatars/' . $_SESSION['profile_picture'])): ?>
                        <img src="../uploads/avatars/<?php echo htmlspecialchars($_SESSION['profile_picture']); ?>" class="avatar-img" alt="Avatar">
                    <?php else: ?>
                        <?php echo strtoupper(substr($username, 0, 1)); ?>
                    <?php endif; ?>
                </div>
                <div class="info">
                    <span class="name"><?php echo htmlspecialchars($username); ?></span>
                    <span class="role"><?php echo ucfirst($role); ?></span>
                </div>
            </div>
        </a>
    </div>
    <div class="sidebar-footer">
        <a href="../../public/logout.php" class="btn-logout">
            <span class="icon">🚪</span> Sign Out
        </a>
    </div>
</aside>