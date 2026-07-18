<?php
// Extract current page filename
$current_page = basename($_SERVER['PHP_SELF']);
$username = $_SESSION['username'] ?? 'Admin';
$role = $_SESSION['role'] ?? 'admin';
$profile_picture = $_SESSION['profile_picture'] ?? '';
?>
<aside class="sidebar">
    <div class="logo">
        <a href="admin_dashboard.php">
            <img src="../../images/admin_logo.png" alt="PFMS Logo" class="logo-img">
        </a>
    </div>
    <nav class="menu">
        <a href="admin_dashboard.php" class="menu-item <?php echo ($current_page == 'admin_dashboard.php') ? 'active' : ''; ?>">
            <span class="icon">📊</span> Dashboard
        </a>
        <a href="admin_register.php" class="menu-item <?php echo ($current_page == 'admin_register.php') ? 'active' : ''; ?>">
            <span class="icon">➕</span> Register Admin
        </a>
        <a href="admin_ban_account.php" class="menu-item <?php echo ($current_page == 'admin_ban_account.php') ? 'active' : ''; ?>">
            <span class="icon">🚫</span> Ban Account
        </a>
        <a href="admin_feedback.php" class="menu-item <?php echo ($current_page == 'admin_feedback.php') ? 'active' : ''; ?>">
            <span class="icon">💬</span> User Feedback
        </a>
    </nav>
    <div class="sidebar-profile">
        <a href="admin_profile.php" class="sidebar-profile-link" style="text-decoration: none; color: inherit; display: block; width: 100%;">
            <div class="user-profile">
                <div class="avatar">
                    <?php if (!empty($profile_picture) && file_exists(__DIR__ . '/uploads/avatars/' . $profile_picture)): ?>
                        <img src="../uploads/avatars/<?php echo htmlspecialchars($profile_picture); ?>" class="avatar-img" alt="Avatar">
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
