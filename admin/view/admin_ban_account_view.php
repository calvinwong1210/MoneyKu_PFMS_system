<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ban Account - MoneyKu Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/admin_sidebar.css">
    <link rel="stylesheet" href="../css/admin_ban_account.css">
</head>
<body>

    <!-- Admin Sidebar navigation -->
    <?php require_once '../admin_sidebar.php'; ?>

    <main class="main-content">
        <header class="main-header">
            <div class="welcome-text">
                <h1>Ban Account Settings</h1>
                <p>Restrict spam accounts and de-authorize users violating MoneyKu terms of use.</p>
            </div>
        </header>

        <!-- Ban User Form Card -->
        <section class="ban-form-container">
            <div class="card-header">
                <h2>Suspend User Account</h2>
                <p>Enter the email address of the account you wish to deactivate. They will be notified via email.</p>
            </div>

            <form id="banForm">
                <div class="form-group-row">
                    <div class="form-group">
                        <label for="ban_email">User Email Address *</label>
                        <input type="email" id="ban_email" name="email" required placeholder="user@example.com" autocomplete="off">
                    </div>
                    <button type="submit" class="btn-ban-submit" id="banBtn">Ban User</button>
                </div>
            </form>
        </section>

        <!-- Suspended Users List Card -->
        <section class="ban-form-container" style="margin-top: 32px;">
            <div class="card-header-actions">
                <h2>Suspended User Database</h2>
                <span class="count-badge"><?php echo count($suspended_users); ?> accounts</span>
            </div>

            <div class="table-wrapper">
                <table class="suspended-table">
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Email Address</th>
                            <th>Suspension Date</th>
                            <th style="text-align: center;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="suspendedTableBody">
                        <?php if (empty($suspended_users)): ?>
                            <tr id="emptyRow">
                                <td colspan="4" class="empty-state">No suspended accounts found in the database.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($suspended_users as $su): ?>
                                <tr id="row-<?php echo $su['user_id']; ?>">
                                    <td>
                                        <span class="username-text">@<?php echo htmlspecialchars($su['username']); ?></span>
                                    </td>
                                    <td>
                                        <span class="email-text"><?php echo htmlspecialchars($su['email']); ?></span>
                                    </td>
                                    <td>
                                        <span class="date-text"><?php echo date('Y-m-d H:i', strtotime($su['updated_at'])); ?></span>
                                    </td>
                                    <td>
                                        <div class="action-cell">
                                            <button class="btn-activate" onclick="activateAccount(<?php echo $su['user_id']; ?>)" id="btn-act-<?php echo $su['user_id']; ?>">Activate</button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <!-- Toast alerts -->
    <div id="toast" class="toast"></div>

    <script>
        function showToast(message, type = 'success') {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            toast.className = `toast show ${type}`;
            setTimeout(() => { toast.classList.remove('show'); }, 3000);
        }

        // Action: Ban User
        document.getElementById('banForm').addEventListener('submit', function (e) {
            e.preventDefault();
            const btn = document.getElementById('banBtn');
            btn.disabled = true;
            btn.textContent = 'Suspending...';

            const formData = new FormData(this);
            formData.append('action', 'ban_user');

            fetch('admin_ban_account.php', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    showToast(data.message, 'success');
                    document.getElementById('banForm').reset();
                    // Reload to update list
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    showToast(data.message, 'error');
                }
                btn.disabled = false;
                btn.textContent = 'Ban User';
            })
            .catch(() => {
                showToast('Network error, please try again.', 'error');
                btn.disabled = false;
                btn.textContent = 'Ban User';
            });
        });

        // Action: Activate User
        function activateAccount(userId) {
            const btn = document.getElementById(`btn-act-${userId}`);
            btn.disabled = true;
            btn.textContent = 'Activating...';

            const formData = new FormData();
            formData.append('action', 'activate_user');
            formData.append('user_id', userId);

            fetch('admin_ban_account.php', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    showToast(data.message, 'success');
                    // Remove row from table
                    const row = document.getElementById(`row-${userId}`);
                    if (row) row.remove();

                    // Check if table is empty now
                    const tbody = document.getElementById('suspendedTableBody');
                    if (tbody.querySelectorAll('tr').length === 0) {
                        tbody.innerHTML = `<tr id="emptyRow"><td colspan="4" class="empty-state">No suspended accounts found in the database.</td></tr>`;
                    }
                } else {
                    showToast(data.message, 'error');
                    btn.disabled = false;
                    btn.textContent = 'Activate';
                }
            })
            .catch(() => {
                showToast('Network error, please try again.', 'error');
                btn.disabled = false;
                btn.textContent = 'Activate';
            });
        }
    </script>
</body>
</html>
