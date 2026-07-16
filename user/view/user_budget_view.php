<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MoneyKu - Manage Budget</title>
    <link rel="stylesheet" href="../css/user_sidebar.css">
    <link rel="stylesheet" href="../css/user_budget.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="dashboard-body">

    <?php require_once '../sidebar.php'; ?>

    <div id="toast" class="toast"></div>

    <main class="main-content">
        <header class="workspace-header">
            <h1>Manage your budget</h1>
            <?php if (!empty($budgets) && $total_spent > $total_allocated): ?>
                <div class="budget-alert-card animate-fade-in">
                    <div class="alert-details">
                        <h3 style="color: #ff0000;">⚠️⚠️Budget Limit Exceeded⚠️⚠️</h3>
                    </div>
                </div>
            <?php endif; ?>
        </header>

        <div class="workspace-layout">
            
            <!-- LEFT PANEL: Dynamic Constraints Entry Interface -->
            <section class="form-card">
                <h2>Set a budget for <?php echo date('F Y'); ?></h2>
                <form id="budgetForm">
                    <div class="form-group has-select">
                        <select id="category" name="category" required>
                            <option value="" disabled selected hidden></option>
                            <option value="Essential">Essential</option>
                            <option value="Lifestyle">Lifestyle</option>
                            <option value="Others">Others</option>
                        </select>
                        <label for="category">Category</label>
                    </div>

                    <div class="form-group">
                        <input type="number" id="budget_amount" name="budget_amount" step="0.01" min="0" placeholder=" " required>
                        <label for="budget_amount">Monthly Budget Limit (RM)</label>
                    </div>

                    <div class="info-alert-notice">
                        ** Your monthly limit will be saved for <strong><?php echo date('F, Y'); ?></strong>. You can only update this limit once per category.
                    </div>

                    <button type="submit" class="btn-submit" id="submitBtn">Save</button>
                </form>
            </section>

            <!-- RIGHT PANEL: Custom Filter Framework Analytics Controls -->
            <section class="budget-panel">
                <div class="panel-header-action">
                    <h2>Monthly Budget Summary</h2>
                    
                    <!-- Dynamic Filtration Controller Panel Form Interface -->
                    <form method="GET" action="user_budget.php" class="filter-controls-group">
                        <select name="filter_month" onchange="this.form.submit()">
                            <?php for($m=1; $m<=12; $m++): ?>
                                <option value="<?php echo $m; ?>" <?php echo $m == $filter_month ? 'selected' : ''; ?>>
                                    <?php echo date('F', mktime(0, 0, 0, $m, 1)); ?>
                                </option>
                            <?php endfor; ?>
                        </select>

                        <select name="filter_year" onchange="this.form.submit()">
                            <?php foreach ($available_years as $year): ?>
                                <option value="<?php echo $year; ?>" <?php echo $year == $filter_year ? 'selected' : ''; ?>>
                                    <?php echo $year; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                </div>

                <?php if (empty($budgets)): ?>
                    <div class="empty-state">
                        <div class="icon">📊</div>
                        <h3>No budget limits have been set for this period.</h3>
                        <p>Set your budget limits using the panel on the left to start tracking your spending.</p>
                    </div>
                <?php else: ?>
                    
                    <!-- Data-Driven Semantic Abstraction Calculation Model Visualization Container -->
                    <div class="summary-numbers-strip">
                        <div class="stat-node">
                            <span class="lbl">Total Budget</span>
                            <span class="val">RM <?php echo number_format($total_allocated, 2); ?></span>
                        </div>
                        <div class="stat-node">
                            <span class="lbl">Total Spent</span>
                            <span class="val">RM <?php echo number_format($total_spent, 2); ?></span>
                        </div>
                        <div class="stat-node">
                            <span class="lbl">Remaining Balance</span>
                            <span class="val <?php echo ($total_allocated - $total_spent) < 0 ? 'deficit' : 'surplus'; ?>">
                                RM <?php echo number_format($total_allocated - $total_spent, 2); ?>
                            </span>
                        </div>
                    </div>

                    <!-- Generate HTML Semantic Representation of Data Matrix Distributions -->
                    <div class="visualizer-framework-box">
                        <h3>Budget Usage</h3>
                        <div class="budgets-container">
                            <?php foreach ($budgets as $b): ?>
                                <div class="budget-track-card">
                                    <div class="card-meta">
                                        <div class="meta-left">
                                            <div class="title-group">
                                                <span class="category-title"><?php echo htmlspecialchars($b['category']); ?></span>
                                                 <!-- php will based on the category to outpu the subcategory -->
                                                <span class="category-subtext">
                                                    <?php 
                                                        if ($b['category'] === 'Essential') {
                                                            echo '(Food, Transport, Bills, Healthcare, Education, Housing, Insurance, Student Loan)';
                                                        } elseif ($b['category'] === 'Lifestyle') {
                                                            echo '(Shopping, Entertainment, Travel)';
                                                        } else {
                                                            echo '(Others)';
                                                        }
                                                    ?>
                                                </span>
                                            </div>
                                            <?php if($b['edit_count'] >= 1): ?>
                                                <span class="lock-pill">Edits Locked</span>
                                            <?php endif; ?>
                                        </div>
                                        <span class="ratio-values">
                                            <strong>RM <?php echo number_format($b['spent'], 2); ?></strong> 
                                            <span class="muted-limit">/ RM <?php echo number_format($b['limit'], 2); ?></span>
                                        </span>
                                    </div>
                                    
                                    <div class="progress-track">
                                        <div class="progress-fill <?php echo $b['percentage'] >= 90 ? 'danger' : ($b['percentage'] >= 70 ? 'warning' : ''); ?>" 
                                             style="width: <?php echo $b['percentage']; ?>%">
                                        </div>
                                    </div>

                                    <div class="card-footer">
                                        <span><?php echo $b['percentage']; ?>% consumed</span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </section>
        </div>
    </main>

    <script>
        function showToast(message, type = 'success') {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            toast.className = `toast show ${type}`;
            setTimeout(() => { toast.classList.remove('show'); }, 3000);
        }

        document.getElementById('budgetForm').addEventListener('submit', function (e) {
            e.preventDefault();
            const btn = document.getElementById('submitBtn');
            btn.disabled = true;
            btn.textContent = 'Syncing...';

            const formData = new FormData(this);

            fetch('user_budget.php', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(res => res.json())
            .then(data => {
                if(data.status === 'success') {
                    showToast(data.message, 'success');
                    setTimeout(() => { window.location.reload(); }, 800);
                } else {
                    showToast(data.message, 'error');
                    btn.disabled = false;
                    btn.textContent = 'Save';
                }
            })
            .catch(() => {
                showToast('Network interface timeout error.', 'error');
                btn.disabled = false;
                btn.textContent = 'Save';
            });
        });
    </script>
</body>
</html>