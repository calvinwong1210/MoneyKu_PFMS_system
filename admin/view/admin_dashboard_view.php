<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - MoneyKu</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/admin_sidebar.css">
    <link rel="stylesheet" href="../css/admin_dashboard.css">
</head>
<body>

    <?php
    function maskEmail($email) {
        $parts = explode('@', $email);
        if (count($parts) !== 2) return $email;
        $name = $parts[0];
        $domain = $parts[1];
        $len = strlen($name);
        if ($len <= 2) {
            return $name . '***@' . $domain;
        }
        return substr($name, 0, 2) . str_repeat('*', max(3, $len - 2)) . '@' . $domain;
    }
    ?>

    <!-- Admin Sidebar navigation -->
    <?php require_once '../admin_sidebar.php'; ?>

    <main class="main-content">
        <header class="main-header">
            <div class="welcome-text">
                <h1>Overview Dashboard</h1>
                <p>Welcome back, <?php echo htmlspecialchars($username); ?>. Here is the active system overview.</p>
            </div>
            <div class="date-badge">
                📅 System Date: <?php echo date('Y-m-d'); ?>
            </div>
        </header>

        <!-- Metrics Grid -->
        <div class="metrics-grid">
            <div class="metric-card">
                <div class="metric-info">
                    <span class="metric-title">Total Users</span>
                    <span class="metric-value"><?php echo number_format($total_users); ?></span>
                </div>
                <div class="metric-icon bg-primary-glow">👥</div>
            </div>

            <div class="metric-card">
                <div class="metric-info">
                    <span class="metric-title">User Feedback</span>
                    <span class="metric-value"><?php echo number_format($total_feedbacks); ?></span>
                </div>
                <div class="metric-icon bg-success-glow">💬</div>
            </div>
        </div>

        <!-- Demographics Section -->
        <div class="dashboard-grid">
            <!-- Age Distribution Card -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h2>Age Distribution</h2>
                    <p>Age groups of registered user base</p>
                </div>
                <div class="card-body">
                    <?php
                    $groups = [
                        ['label' => 'Under 18', 'value' => $age_under_18],
                        ['label' => '18 - 24', 'value' => $age_18_24],
                        ['label' => '25 - 34', 'value' => $age_25_34],
                        ['label' => '35 and Above', 'value' => $age_35_above],
                        ['label' => 'Not Provided', 'value' => $age_unknown],
                    ];
                    
                    foreach ($groups as $g) {
                        $pct = $total_users > 0 ? round(($g['value'] / $total_users) * 100) : 0;
                        ?>
                        <div class="stat-progress-bar-group">
                            <div class="stat-info">
                                <span class="stat-label"><?php echo $g['label']; ?></span>
                                <span class="stat-val"><?php echo $g['value']; ?> users (<?php echo $pct; ?>%)</span>
                            </div>
                            <div class="progress-bar-track">
                                <div class="progress-bar-fill" style="width: <?php echo $pct; ?>%;"></div>
                            </div>
                        </div>
                    <?php } ?>
                </div>
            </div>

            <!-- Gender Distribution Card -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h2>Gender Distribution</h2>
                    <p>Gender identity of registered user base</p>
                </div>
                <div class="card-body">
                    <?php
                    $gender_groups = [
                        ['label' => 'Male', 'value' => $gender_male, 'color' => '#3b82f6'],
                        ['label' => 'Female', 'value' => $gender_female, 'color' => '#ec4899'],
                        ['label' => 'Other', 'value' => $gender_other, 'color' => '#10b981'],
                        ['label' => 'Not Provided', 'value' => $gender_unknown, 'color' => '#64748b'],
                    ];
                    
                    foreach ($gender_groups as $g) {
                        $pct = $total_users > 0 ? round(($g['value'] / $total_users) * 100) : 0;
                        ?>
                        <div class="stat-progress-bar-group">
                            <div class="stat-info">
                                <span class="stat-label" style="display: flex; align-items: center; gap: 8px;">
                                    <span style="display: inline-block; width: 10px; height: 10px; border-radius: 50%; background: <?php echo $g['color']; ?>;"></span>
                                    <?php echo $g['label']; ?>
                                </span>
                                <span class="stat-val"><?php echo $g['value']; ?> users (<?php echo $pct; ?>%)</span>
                            </div>
                            <div class="progress-bar-track">
                                <div class="progress-bar-fill" style="width: <?php echo $pct; ?>%; background: <?php echo $g['color']; ?>;"></div>
                            </div>
                        </div>
                    <?php } ?>
                </div>
            </div>
        </div>

        <!-- Recent Registered Users Table -->
        <div class="dashboard-card" style="margin-top: 32px;">
            <div class="card-header">
                <h2>Recent Users</h2>
                <p>Latest 5 users who created an account on MoneyKu</p>
            </div>
            <div class="table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Email Address</th>
                            <th>Full Name</th>
                            <th>Registered Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recent_users)): ?>
                            <tr>
                                <td colspan="4" class="empty-state">No users registered yet.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recent_users as $u): ?>
                                <tr>
                                    <td>
                                        <div class="table-user-cell">
                                            <div class="table-avatar">
                                                <?php if (!empty($u['profile_picture']) && file_exists('../../user/uploads/avatars/' . $u['profile_picture'])): ?>
                                                    <img src="../../user/uploads/avatars/<?php echo htmlspecialchars($u['profile_picture']); ?>" class="table-avatar-img" alt="Avatar">
                                                <?php else: ?>
                                                    <?php echo strtoupper(substr($u['username'], 0, 1)); ?>
                                                <?php endif; ?>
                                            </div>
                                            <span class="table-username"><?php echo htmlspecialchars($u['username']); ?></span>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars(maskEmail($u['email'])); ?></td>
                                    <td><?php echo htmlspecialchars($u['full_name'] ?? 'Not setup yet'); ?></td>
                                    <td><?php echo date('Y-m-d H:i', strtotime($u['created_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

</body>
</html>
