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

        <!-- Financial Health Score Section -->
        <section class="health-score-container" style="margin-bottom: 32px;">
            <div class="health-card" style="background: var(--card-bg); border: 1px solid var(--borders); border-radius: 20px; padding: 28px; box-shadow: var(--shadow-md); display: flex; gap: 32px; align-items: center; flex-wrap: wrap;">
                
                <!-- Score SVG Meter -->
                <div class="score-circle-wrapper" style="position: relative; width: 140px; height: 140px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <svg width="140" height="140" viewBox="0 0 140 140" style="transform: rotate(-90deg);">
                        <circle cx="70" cy="70" r="58" stroke="#f1f5f9" stroke-width="12" fill="transparent" />
                        <circle cx="70" cy="70" r="58" stroke="<?php echo $health_color; ?>" stroke-width="12" fill="transparent" 
                                stroke-dasharray="364.4" stroke-dashoffset="<?php echo 364.4 - (364.4 * $health_score / 100); ?>" 
                                stroke-linecap="round" style="transition: stroke-dashoffset 1s ease-out;" />
                    </svg>
                    <div style="position: absolute; text-align: center;">
                        <span style="font-size: 34px; font-weight: 800; color: var(--text-main); line-height: 1;"><?php echo $health_score; ?></span>
                        <span style="display: block; font-size: 11px; color: var(--text-muted); font-weight: 600; text-transform: uppercase; margin-top: 2px;">Score</span>
                    </div>
                </div>

                <!-- Status Feedback & Advice -->
                <div class="score-details" style="flex: 1; min-width: 280px;">
                    <div style="margin-bottom: 8px;">
                        <h2 style="font-size: 20px; font-weight: 700; margin: 0; color: var(--text-main);">
                            Financial Health Grade: <span style="color: <?php echo $health_color; ?>;"><?php echo $health_grade; ?></span>
                        </h2>
                    </div>
                    <p style="font-size: 14px; color: var(--text-muted); margin: 0 0 16px 0; line-height: 1.6;">
                        <?php echo $health_advice; ?>
                    </p>

                    <!-- Breakdown metrics strip -->
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; border-top: 1px solid var(--borders); padding-top: 16px;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span><?php echo $balance_icon; ?></span>
                            <span style="font-size: 13.5px; font-weight: 500; color: var(--text-main);">Savings Ratio: <strong style="color: <?php echo $balance_sub_color; ?>;"><?php echo $balance_score_text; ?></strong></span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span><?php echo $budget_icon; ?></span>
                            <span style="font-size: 13.5px; font-weight: 500; color: var(--text-main);">Budget Limits: <strong style="color: <?php echo $budget_sub_color; ?>;"><?php echo $budget_score_text; ?></strong></span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span><?php echo $ptptn_icon; ?></span>
                            <span style="font-size: 13.5px; font-weight: 500; color: var(--text-main);">PTPTN Repay: <strong style="color: <?php echo $ptptn_sub_color; ?>;"><?php echo $ptptn_score_text; ?></strong></span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span><?php echo $saving_goal_icon; ?></span>
                            <span style="font-size: 13.5px; font-weight: 500; color: var(--text-main);">Goal Progress: <strong style="color: <?php echo $saving_goal_sub_color; ?>;"><?php echo $saving_goal_score_text; ?></strong></span>
                        </div>
                    </div>
                </div>

            </div>
        </section>

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