<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MoneyKu - PTPTN Repayments</title>
    <link rel="stylesheet" href="../css/user_sidebar.css">
    <link rel="stylesheet" href="../css/ptptn_repayment.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="dashboard-body">

    <?php require_once '../sidebar.php'; ?>

    <div id="toast" class="toast"></div>

    <main class="main-content">
        <!-- Scrolling Disclaimer Banner -->
        <div class="disclaimer-banner">
            <div class="banner-track">
                <span>⚠️ Disclaimer: For reference only. Please refer to the official MyPTPTN app for official details..</span>
            </div>
        </div>

        <header class="workspace-header">
            <div class="nav-breadcrumbs">
                <a href="ptptn_dashboard.php" class="back-link">← Back to PTPTN Dashboard</a>
            </div>
            <h1>Repayment History</h1>
            <p>Remaining Balance: <strong>RM <?php echo number_format($loan['remaining_balance'], 2); ?></strong></p>
        </header>

        <div class="workspace-layout">
            
            <section class="form-card">
                <h2>Standard Monthly Repayment</h2>
                <?php if ($has_past_unpaid): ?>
                    <p class="period-tracker-lbl" style="color: #dc2626; font-weight: 600;">
                        Target Period: <span style="background: #fef2f2; color: #dc2626; padding: 2px 8px; border-radius: 4px; font-size: 13px; border: 1px solid #fee2e2;">Outstanding Balance Clearance</span>
                    </p>
                    <div style="background: rgba(220, 38, 38, 0.05); border: 1px solid rgba(220, 38, 38, 0.15); border-radius: 8px; padding: 12px 14px; margin-bottom: 18px; font-size: 13px; color: #b91c1c; line-height: 1.5;">
                        ⚠️ You have previous unpaid balances. Payments will clear older balances first before covering the current month (<strong><?php echo date('F Y'); ?></strong>).
                    </div>
                <?php else: ?>
                    <p class="period-tracker-lbl">Current Month: <strong><?php echo date('F Y'); ?></strong></p>
                <?php endif; ?>
                
                <?php if ($already_paid_this_month && !$has_past_unpaid): ?>
                    <div class="success-status-tag">✓ Monthly Repayment Paid</div>
                <?php else: ?>
                    <form id="standardRepaymentForm">
                        <input type="hidden" name="mode" value="standard">
                        <div class="form-group">
                            <input type="number" id="std_amount" name="payment_amount" step="0.01" value="<?php echo $loan['monthly_payment']; ?>" min="<?php echo $loan['monthly_payment']; ?>" required>
                            <label for="std_amount">Repayment Amount (RM, Min: RM <?php echo number_format($loan['monthly_payment'], 2); ?>)</label>
                        </div>
                        <button type="submit" class="btn-submit" id="stdBtn">Pay Repayment</button>
                    </form>
                <?php endif; ?>
            </section>

            <section class="table-card">
                <h2>Repayment Logs</h2>
                <div class="table-wrapper">
                    <table class="ledger-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Payment Amount</th>
                                <th class="text-right">Remaining Balance</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($records)): ?>
                                <tr><td colspan="3" class="text-center empty-msg">No repayment records logged yet.</td></tr>
                            <?php else: ?>
                                <?php foreach ($records as $r): ?>
                                     <tr>
                                         <td class="tx-date">
                                             <?php echo date('M d, Y', strtotime($r['payment_date'])); ?>
                                             <?php if (!empty($r['target_month'])): ?>
                                                 <span class="target-month-hint" style="font-size: 11px; color: var(--text-muted); background: #f1f5f9; padding: 2px 6px; border-radius: 4px; margin-left: 6px; font-weight: 500;">
                                                     For <?php echo date('M Y', strtotime($r['target_month'] . '-01')); ?>
                                                 </span>
                                             <?php else: ?>
                                                 <span class="target-month-hint" style="font-size: 11px; color: var(--text-muted); background: #f1f5f9; padding: 2px 6px; border-radius: 4px; margin-left: 6px; font-weight: 500;">
                                                     Prior Override
                                                 </span>
                                             <?php endif; ?>
                                         </td>
                                         <td class="tx-amt">RM <?php echo number_format($r['payment_amount'], 2); ?></td>
                                         <td class="text-right tx-bal">RM <?php echo number_format($r['remaining_balance'], 2); ?></td>
                                     </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

        </div>
    </main>

    <!-- Historical Repayments Dialog/Modal -->
    <?php if ($loan['initial_override_locked'] == 0): ?>
        <div id="historicalModal" class="modal open">
            <div class="modal-content" style="max-width: 500px;">
                <div class="modal-header">
                    <h2>Prior Repayment Check</h2>
                </div>
                
                <form id="initialRepaymentForm" style="padding-top: 8px;">
                    <input type="hidden" name="mode" value="initial">
                    
                    <!-- Question 1: Prior Payments -->
                    <div style="margin-bottom: 20px; border-bottom: 1px solid var(--borders); padding-bottom: 16px;">
                        <label style="font-size: 14.5px; font-weight: 600; display: block; margin-bottom: 8px; color: var(--text-main);">
                            Have you made any PTPTN repayment records before using this application?
                        </label>
                        <div style="display: flex; gap: 12px; margin-top: 8px; margin-bottom: 12px;">
                            <button type="button" class="btn-secondary" id="btnYesPrior" style="flex: 1; padding: 10px; border-radius: 8px; font-weight: 600; cursor: pointer;">Yes</button>
                            <button type="button" class="btn-secondary" id="btnNoPrior" style="flex: 1; padding: 10px; border-radius: 8px; font-weight: 600; cursor: pointer;">No</button>
                        </div>
                        
                        <!-- Cumulative Input (hidden by default) -->
                        <div class="form-group" id="priorInputGroup" style="display: none; margin-top: 16px;">
                            <input type="number" id="initial_amount" name="payment_amount" step="0.01" min="0" placeholder=" " value="0.00">
                            <label for="initial_amount">Total Cumulative Amount Paid (RM)</label>
                        </div>
                    </div>

                    <!-- Question 2: This Month's Payment -->
                    <div style="margin-bottom: 24px;">
                        <label class="custom-checkbox-wrapper" style="display: flex; align-items: center; gap: 8px; font-size: 14.5px; font-weight: 600; cursor: pointer; user-select: none;">
                            <input type="checkbox" name="this_month_paid" id="this_month_paid" value="1">
                            <!-- <span class="checkbox-box" style="margin-top: 0; width: 18px; height: 18px; border: 1px solid var(--borders); border-radius: 4px; display: inline-block; position: relative;"></span> -->
                            <span class="label" style="font-size: 14.5px;">Did you also pay for the current month (<?php echo date('F Y'); ?>)?</span>
                        </label>
                        
                        <!-- Current Month Amount Input (hidden by default) -->
                        <div class="form-group" id="thisMonthInputGroup" style="display: none; margin-top: 16px;">
                            <input type="number" id="this_month_amount" name="this_month_amount" step="0.01" min="0" placeholder=" " value="<?php echo $loan['monthly_payment']; ?>">
                            <label for="this_month_amount">Amount Paid for This Month (RM)</label>
                        </div>
                    </div>

                    <!-- Form Submit and Skip Buttons -->
                    <div style="display: flex; gap: 12px;">
                        <button type="submit" class="btn-submit" id="initBtn" style="flex: 2;">Save</button>
                        <button type="button" class="btn-secondary" id="btnSkipAll" style="flex: 3; color: var(--text-muted);">No Payment History Available</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <script>
        function showToast(message, type = 'success') {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            toast.className = `toast show ${type}`;
            setTimeout(() => { toast.classList.remove('show'); }, 3000);
        }

        // Modal Toggle Javascript
        const btnYesPrior = document.getElementById('btnYesPrior');
        const btnNoPrior = document.getElementById('btnNoPrior');
        const priorInputGroup = document.getElementById('priorInputGroup');
        const initialAmountInput = document.getElementById('initial_amount');
        const thisMonthPaidCheckbox = document.getElementById('this_month_paid');
        const thisMonthInputGroup = document.getElementById('thisMonthInputGroup');
        const btnSkipAll = document.getElementById('btnSkipAll');

        if (btnYesPrior && btnNoPrior && priorInputGroup) {
            btnYesPrior.addEventListener('click', function() {
                priorInputGroup.style.display = 'block';
                initialAmountInput.value = '';
                initialAmountInput.focus();
                
                btnYesPrior.className = 'btn-primary';
                btnNoPrior.className = 'btn-secondary';
            });

            btnNoPrior.addEventListener('click', function() {
                priorInputGroup.style.display = 'none';
                initialAmountInput.value = '0.00';
                
                btnYesPrior.className = 'btn-secondary';
                btnNoPrior.className = 'btn-primary';
            });
        }

        if (thisMonthPaidCheckbox && thisMonthInputGroup) {
            thisMonthPaidCheckbox.addEventListener('change', function() {
                if (this.checked) {
                    thisMonthInputGroup.style.display = 'block';
                } else {
                    thisMonthInputGroup.style.display = 'none';
                }
            });
        }

        if (btnSkipAll) {
            btnSkipAll.addEventListener('click', function() {
                if (confirm("Are you sure you do not want to log prior payments or overrides?")) {
                    const formData = new FormData();
                    formData.append('action', 'no_override');
                    
                    fetch('ptptn_repayment.php', {
                        method: 'POST',
                        body: formData,
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.status === 'success') {
                            showToast(data.message, 'success');
                            setTimeout(() => { window.location.reload(); }, 600);
                        } else {
                            showToast(data.message, 'error');
                        }
                    });
                }
            });
        }

        function handleFormPost(formId, buttonId) {
            const form = document.getElementById(formId);
            if(!form) return;
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                const btn = document.getElementById(buttonId);
                btn.disabled = true;
                
                fetch('ptptn_repayment.php', {
                    method: 'POST',
                    body: new FormData(this),
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
                    }
                });
            });
        }

        handleFormPost('initialRepaymentForm', 'initBtn');
        handleFormPost('standardRepaymentForm', 'stdBtn');
    </script>
</body>
</html>