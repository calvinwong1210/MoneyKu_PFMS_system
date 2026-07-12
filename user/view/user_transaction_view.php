<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PFMS - Manage Transactions</title>
    <link rel="stylesheet" href="../css/user_sidebar.css">
    <link rel="stylesheet" href="../css/user_transaction.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="dashboard-body">

    <!-- Global Layout Shared Navigation -->
    <?php require_once '../sidebar.php'; ?>

    <!-- Async Notification Toast Popup -->
    <div id="toast" class="toast"></div>

    <!-- Main Workspace Frame -->
    <main class="main-content">
        <header class="workspace-header">
            <h1>Transaction Manager</h1>
            <p>Log your daily financial cashflows and track your category streams.</p>
        </header>

        <div class="workspace-layout">
            
            <!-- Left Panel: Premium Card Form -->
            <section class="form-card">
                <h2>Add Transaction</h2>
                <form id="transactionForm">
                    
                    <div class="form-group has-select">
                        <select id="transaction_type" name="transaction_type" required>
                            <option value="expense">Expense (-)</option>
                            <option value="income">Income (+)</option>
                        </select>
                        <label for="transaction_type">Type</label>
                    </div>

                    <div class="form-group">
                        <input type="text" id="category" name="category" placeholder=" " required autocomplete="off" maxlength="50">
                        <label for="category">Category (e.g., Food, Tuition)</label>
                    </div>

                    <div class="form-group">
                        <input type="number" id="amount" name="amount" step="0.01" min="0.01" placeholder=" " required>
                        <label for="amount">Amount ($)</label>
                    </div>

                    <div class="form-group has-select">
                        <input type="date" id="transaction_date" name="transaction_date" required value="<?php echo date('Y-m-d'); ?>">
                        <label for="transaction_date">Date</label>
                    </div>

                    <div class="form-group">
                        <input type="text" id="description" name="description" placeholder=" " autocomplete="off" maxlength="255">
                        <label for="description">Optional Description</label>
                    </div>

                    <button type="submit" class="btn-submit" id="submitBtn">Save Transaction</button>
                </form>
            </section>

            <!-- Right Panel: Data Table Logs -->
            <section class="table-card">
                <h2>Transaction History</h2>
                <div class="table-wrapper">
                    <table class="transaction-table" id="txTable">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Category</th>
                                <th>Description</th>
                                <th class="text-right">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($user_transactions)): ?>
                                <tr class="empty-row"><td colspan="4" class="text-center">No transactions logged yet.</td></tr>
                            <?php else: ?>
                                <?php foreach ($user_transactions as $tx): ?>
                                <tr>
                                    <td class="tx-date"><?php echo htmlspecialchars($tx['transaction_date']); ?></td>
                                    <td><span class="badge badge-category"><?php echo htmlspecialchars($tx['category']); ?></span></td>
                                    <td class="tx-desc"><?php echo htmlspecialchars($tx['description'] ?: '—'); ?></td>
                                    <td class="text-right tx-amount <?php echo $tx['transaction_type'] === 'income' ? 'positive' : 'negative'; ?>">
                                        <?php echo ($tx['transaction_type'] === 'income' ? '+$' : '-$') . number_format($tx['amount'], 2); ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

        </div>
    </main>

    <!-- Async Form Controller AJAX Operations -->
    <script>
        function showToast(message, type = 'success') {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            toast.className = `toast show ${type}`;
            setTimeout(() => { toast.classList.remove('show'); }, 3000);
        }

        document.getElementById('transactionForm').addEventListener('submit', function (e) {
            e.preventDefault();
            const btn = document.getElementById('submitBtn');
            btn.disabled = true;
            btn.textContent = 'Saving...';

            const formData = new FormData(this);

            fetch('user_transaction.php', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(res => res.json())
            .then(data => {
                if(data.status === 'success') {
                    showToast(data.message, 'success');
                    // Fast dynamic client-side update to preserve fluid SPA feel
                    setTimeout(() => { window.location.reload(); }, 800);
                } else {
                    showToast(data.message, 'error');
                    btn.disabled = false;
                    btn.textContent = 'Save Transaction';
                }
            })
            .catch(() => {
                showToast('Network sync error.', 'error');
                btn.disabled = false;
                btn.textContent = 'Save Transaction';
            });
        });
    </script>
</body>
</html>