<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MoneyKu - Savings Targets</title>
    <link rel="stylesheet" href="../css/user_sidebar.css">
    <link rel="stylesheet" href="../css/user_saving_goal.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="dashboard-body">

    <?php require_once '../sidebar.php'; ?>

    <div id="toast" class="toast"></div>

    <main class="main-content">
        <header class="workspace-header">
            <h1>Savings Goals</h1>
            <p>Set your savings goals, track your progress, and manage your financial plans easily.</p>
        </header>

        <div class="workspace-layout">
            
            <!-- LEFT PANEL: Dynamic Target Setup Control Board -->
            <section class="form-card">
                <h2>Create your saving goal</h2>
                <form id="goalForm">
                    <input type="hidden" name="action" value="create">

                    <div class="form-group">
                        <input type="text" id="goal_name" name="goal_name" placeholder=" " required autocomplete="off" maxlength="100">
                        <label for="goal_name">Goal Name</label>
                    </div>

                    <div class="form-group">
                        <input type="number" id="target_amount" name="target_amount" step="0.01" min="1" placeholder=" " required>
                        <label for="target_amount">Target Amount (RM)</label>
                    </div>

                    <div class="form-group">
                        <input type="number" id="current_amount" name="current_amount" step="0.01" min="0" placeholder=" " required>
                        <label for="current_amount">Initial Funds Saved (RM)</label>
                    </div>

                    <div class="form-group has-select">
                        <input type="date" id="target_date" name="target_date" required min="<?php echo date('Y-m-d'); ?>">
                        <label for="target_date">Target Date Deadline</label>
                    </div>

                    <button type="submit" class="btn-submit" id="submitBtn">Save Goal</button>
                </form>
            </section>

            <!-- RIGHT PANEL: Dashboard Progress Matrix Monitors -->
            <section class="goals-panel">
                <h2>Active Targets Analytics</h2>

                <?php if (empty($savings_goals)): ?>
                    <div class="empty-state">
                        <div class="icon">🎯</div>
                        <h3>No savings targets deployed yet</h3>
                        <p>Configure your first financial milestone target utilizing the launch configuration board.</p>
                    </div>
                <?php else: ?>
                    <div class="goals-grid">
                        <?php foreach ($savings_goals as $g): ?>
                            <div class="goal-metric-card">
                                <div class="card-meta">
                                    <div class="title-group">
                                        <span class="goal-title"><?php echo htmlspecialchars($g['goal_name']); ?></span>
                                        <span class="deadline-lbl">Due by: <?php echo date($g['target_date']); ?></span>
                                    </div>
                                    <button class="btn-drop" onclick="dropGoal(<?php echo $g['goal_id']; ?>)">✕</button>
                                </div>

                                <div class="ratio-values-strip">
                                    <div class="saved-value">
                                        <span class="lbl">Saved</span>
                                        <strong>RM <?php echo number_format($g['current_amount'], 2); ?></strong>
                                    </div>
                                    <div class="target-value">
                                        <span class="lbl">Target</span>
                                        <span>RM <?php echo number_format($g['target_amount'], 2); ?></span>
                                    </div>
                                </div>

                                <div class="progress-track">
                                    <div class="progress-fill <?php echo $g['percentage'] == 100 ? 'complete' : ''; ?>" style="width: <?php echo $g['percentage']; ?>%"></div>
                                </div>

                                <div class="card-action-footer">
                                    <span class="pct-lbl"><?php echo $g['percentage']; ?>% Reached</span>
                                    
                                    <!-- Inline balance modification setup -->
                                    <form class="quick-update-form" onsubmit="updateProgress(event, <?php echo $g['goal_id']; ?>)">
                                        <input type="number" step="0.01" min="0.01" name="added_amount" placeholder="Add (RM)" required>
                                        <button type="submit" class="btn-text-action">Save</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
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

        // Action Router Helper for Fetch Async Channels
        function submitAsyncData(formData) {
            return fetch('user_saving_goal.php', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            }).then(res => res.json());
        }

        // Create Operations
        document.getElementById('goalForm').addEventListener('submit', function (e) {
            e.preventDefault();
            const btn = document.getElementById('submitBtn');
            btn.disabled = true;
            btn.textContent = 'Launching...';

            submitAsyncData(new FormData(this))
            .then(data => {
                if(data.status === 'success') {
                    showToast(data.message, 'success');
                    setTimeout(() => { window.location.reload(); }, 800);
                } else {
                    showToast(data.message, 'error');
                    btn.disabled = false;
                    btn.textContent = 'Save Goal';
                }
            });
        });

        // Update Balance Operations
        function updateProgress(e, goalId) {
            e.preventDefault();
            const form = e.target;
            const formData = new FormData(form);
            formData.append('action', 'update_progress');
            formData.append('goal_id', goalId);

            submitAsyncData(formData)
            .then(data => {
                if(data.status === 'success') {
                    showToast(data.message, 'success');
                    setTimeout(() => { window.location.reload(); }, 600);
                } else {
                    showToast(data.message, 'error');
                }
            });
        }

        // Delete Operations
        function dropGoal(goalId) {
            if(!confirm("Are you sure you want to drop this financial milestone target?")) return;

            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('goal_id', goalId);

            submitAsyncData(formData)
            .then(data => {
                if(data.status === 'success') {
                    showToast(data.message, 'success');
                    setTimeout(() => { window.location.reload(); }, 600);
                } else {
                    showToast(data.message, 'error');
                }
            });
        }
    </script>
</body>
</html>