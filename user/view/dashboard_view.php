<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MoneyKu - Dashboard</title>
    <link rel="stylesheet" href="../css/user_sidebar.css">
    <link rel="stylesheet" href="../css/dashboard.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="dashboard-body">

<?php require_once '../sidebar.php'; ?>

    <main class="main-content">
        
        <header class="main-header">
            <div class="welcome-text">
                <h1>Welcome back, <?php echo htmlspecialchars($username); ?></h1>
                <p>Here's your financial overview for today.</p>
            </div>
        </header>

        <section class="cards-grid">
            
            <!-- Card 1: Total Balance -->
            <div class="metric-card">
                <div class="card-header">
                    <span class="card-title">Total Balance</span>
                    <span class="card-icon-wrapper">💰</span>
                </div>
                <div class="card-value">RM <?php echo number_format($total_balance, 2); ?></div>
                <div class="card-trend <?php echo $total_balance >= 0 ? 'up' : 'down'; ?>">
                    <span class="trend-icon"><?php echo $total_balance >= 0 ? '↑' : '↓'; ?></span>
                    Net Cashflow
                </div>
            </div>

            <!-- Card 2: This Month's Balance -->
            <div class="metric-card">
                <div class="card-header">
                    <span class="card-title">This Month's Balance</span>
                    <span class="card-icon-wrapper">📈</span>
                </div>
                <div class="card-value">RM <?php echo number_format($monthly_balance, 2); ?></div>
                <div class="card-trend <?php echo $monthly_balance >= 0 ? 'up' : 'down'; ?>">
                    <span class="trend-icon"><?php echo $monthly_balance >= 0 ? '↑' : '↓'; ?></span>
                    <?php echo date('F Y'); ?>
                </div>
            </div>

            <!-- Card 3: Savings Goal Progress -->
            <div class="metric-card">
                <div class="card-header">
                    <span class="card-title">Savings Target: <?php echo htmlspecialchars($goal_name); ?></span>
                    <a href="user_saving_goal.php" class="btn-text">View All</a>
                    <span class="card-icon-wrapper">🎯</span>
                </div>
                <div class="card-value">RM <?php echo number_format($savings_current, 2); ?> <span class="target-total">/ RM <?php echo number_format($savings_target, 0); ?></span></div>
                
                <div class="progress-container">
                    <div class="progress-bar" style="width: <?php echo $savings_percentage; ?>%"></div>
                </div>
                <div class="progress-label">
                    <span><?php echo $savings_percentage; ?>% Completed</span>
                </div>
            </div>

            <!-- Card 4: Monthly Budget Summary -->
            <div class="metric-card">
                <div class="card-header">
                    <span class="card-title">Monthly Budget</span>
                    <a href="user_budget.php" class="btn-text">View All</a>
                    <span class="card-icon-wrapper">🛡️</span>
                </div>
                <div class="card-value">RM <?php echo number_format($total_spent, 2); ?> <span class="target-total">/ RM <?php echo number_format($total_allocated, 0); ?></span></div>
                
                <div class="progress-container">
                    <div class="progress-bar" style="width: <?php echo $budget_percentage; ?>%"></div>
                </div>
                <div class="progress-label">
                    <span><?php echo $budget_percentage; ?>% Spent</span>
                </div>
            </div>

        </section>

        <!-- Recent Transactions Log -->
        <section class="table-container">
            <div class="table-header">
                <h2>Recent Transactions</h2>
                <a href="user_transaction.php" class="btn-text">View All</a>
            </div>
            <table class="transaction-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Description</th>
                        <th>Category</th>
                        <th class="text-right">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recent_transactions)): ?>
                        <tr class="empty-row"><td colspan="4" class="text-center">No transactions logged yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($recent_transactions as $tx): ?>
                        <tr>
                            <td class="tx-date"><?php echo $tx['transaction_date']; ?></td>
                            <td class="tx-desc"><?php echo htmlspecialchars($tx['description'] ?: '—'); ?></td>
                            <td><span class="badge badge-category"><?php echo htmlspecialchars($tx['category']); ?></span></td>
                            <td class="text-right tx-amount <?php echo $tx['transaction_type'] === 'income' ? 'positive' : 'negative'; ?>">
                                <?php 
                                    if ($tx['transaction_type'] === 'income') {
                                        echo '+RM' . number_format($tx['amount'], 2);
                                    } else {
                                        echo '-RM' . number_format($tx['amount'], 2);
                                    }
                                ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>

    </main>

</body>
</html>