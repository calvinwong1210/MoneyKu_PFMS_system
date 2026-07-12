<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PFMS - Dashboard</title>
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
            
            <div class="metric-card">
                <div class="card-header">
                    <span class="card-title">Total Balance</span>
                    <span class="card-icon-wrapper">💰</span>
                </div>
                <div class="card-value">$<?php echo number_format($mock_data['total_balance'], 2); ?></div>
                <div class="card-trend up">
                    <span class="trend-icon">↑</span> +12.4% <span class="trend-text">from last month</span>
                </div>
            </div>

            <div class="metric-card">
                <div class="card-header">
                    <span class="card-title">Monthly Expenses</span>
                    <span class="card-icon-wrapper">📉</span>
                </div>
                <div class="card-value">$<?php echo number_format($mock_data['monthly_spent'], 2); ?></div>
                <div class="card-trend down">
                    <span class="trend-icon">↓</span> -4.2% <span class="trend-text">vs average spending</span>
                </div>
            </div>

            <div class="metric-card">
                <div class="card-header">
                    <span class="card-title">Savings Goal</span>
                    <span class="card-icon-wrapper">🎯</span>
                </div>
                <div class="card-value">$<?php echo number_format($mock_data['savings_current'], 2); ?> <span class="target-total">/ $<?php echo number_format($mock_data['savings_target'], 0); ?></span></div>
                
                <div class="progress-container">
                    <div class="progress-bar" style="width: <?php echo $savings_percentage; ?>%"></div>
                </div>
                <div class="progress-label">
                    <span><?php echo $savings_percentage; ?>% Completed</span>
                </div>
            </div>

        </section>

        <section class="table-container">
            <div class="table-header">
                <h2>Recent Transactions</h2>
                <a href="#view-all" class="btn-text">View All</a>
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
                    <?php foreach ($mock_data['recent_transactions'] as $tx): ?>
                    <tr>
                        <td class="tx-date"><?php echo $tx['date']; ?></td>
                        <td class="tx-desc"><?php echo htmlspecialchars($tx['desc']); ?></td>
                        <td><span class="badge badge-category"><?php echo htmlspecialchars($tx['category']); ?></span></td>
                        <td class="text-right tx-amount <?php echo $tx['amount'] < 0 ? 'negative' : 'positive'; ?>">
                            <?php 
                                if ($tx['amount'] < 0) {
                                    echo '-$' . number_format(abs($tx['amount']), 2);
                                } else {
                                    echo '+$' . number_format($tx['amount'], 2);
                                }
                            ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>

    </main>

</body>
</html>