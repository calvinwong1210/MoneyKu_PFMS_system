<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MoneyKu - PTPTN Student Loan</title>
    <link rel="stylesheet" href="../css/user_sidebar.css">
    <link rel="stylesheet" href="../css/ptptn_dashboard.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="dashboard-body">

    <?php require_once '../sidebar.php'; ?>

    <div id="toast" class="toast"></div>

    <main class="main-content">
        <!-- Scrolling Disclaimer Banner -->
        <div class="disclaimer-banner">
            <div class="banner-track">
                <span>⚠️ Disclaimer: For reference only. Please refer to the official MyPTPTN app for official details.</span>
            </div>
        </div>

        <header class="workspace-header">
            <h1>PTPTN Loan Dashboard</h1>
            <p>Monitor your PTPTN student loan balance and repayment progress.</p>
        </header>

        <?php if (!$has_loan): ?>
            <div class="workspace-layout single-center">
                <section class="form-card">
                    <h2>Set Up PTPTN Loan Profile</h2>
                    <p class="warning-text-note">⚠️Check before submitting. No edits allowed!</p>
                    
                    <form id="loanForm">
                        <div class="form-group">
                            <input type="number" id="total_loan" name="total_loan" step="0.01" min="1" placeholder=" " required>
                            <label for="total_loan">Loan Amount (RM)</label>
                        </div>
                        <div class="form-group locked-field">
                            <input type="number" id="interest_rate" name="interest_rate" value="1" readonly>
                            <label for="interest_rate">Interest Rate (%)</label>
                        </div>
                        <div class="form-group has-select">
                            <input type="date" id="repayment_start_date" name="repayment_start_date" required>
                            <label for="repayment_start_date">Repayment Start Date</label>
                        </div>
                        <div class="form-group">
                            <input type="number" id="repayment_period_months" name="repayment_period_months" min="1" placeholder=" " required>
                            <label for="repayment_period_months">Repayment Period (Months)</label>
                        </div>
                        <div class="form-group locked-field">
                            <input type="number" id="monthly_payment" name="monthly_payment" step="0.01" placeholder=" " readonly required>
                            <label for="monthly_payment">Calculated Monthly Payment (RM)</label>
                        </div>

                        <!-- Immediate Repayment Option Selector -->
                        <div class="checkbox-group-container">
                            <label class="custom-checkbox-wrapper" style="display: flex; align-items: center; gap: 6px;">
                                <input type="checkbox" name="immediate_repayment_scheme" value="1">
                                <span class="checkbox-box"></span>
                                <span class="checkbox-label" style="font-size: 14.5px;">Immediate Repayment Scheme</span>
                            </label>
                            <p class="checkbox-help-text" style="display: flex; align-items: center; gap: 6px; font-size: 13px; color: var(--text-muted); margin-top: 6px;">
                                Enjoy 0% interest for the first 12 months
                                <span class="tooltip-wrapper">
                                    <span class="tooltip-icon">i</span>
                                    <span class="tooltip-text">
                                        Repayment starts immediately after the final PTPTN loan disbursement
                                    </span>
                                </span>
                            </p>
                        </div><br>

                        <button type="submit" class="btn-submit" id="submitBtn">Save Loan Profile</button>
                    </form>
                </section>
            </div>
        <?php else: ?>
            <div class="workspace-layout layout-dashboard">
                <section class="metrics-panel">
                    <div class="panel-header-action">
                        <h2>PTPTN Loan Summary 
                            <?php if($loan_profile['immediate_repayment_scheme'] == 1): ?>
                                <span class="scheme-badge">Scheme A Active</span>
                            <?php endif; ?>
                        </h2>
                        <a href="ptptn_repayment.php" class="btn-primary-action">Manage Repayments →</a>
                    </div>
                    <div class="summary-numbers-strip">
                        <div class="stat-node">
                            <span class="lbl">Total Repayment Owed</span>
                            <span class="val">RM <?php echo number_format($loan_profile['total_loan'], 2); ?></span>
                        </div>
                        <div class="stat-node">
                            <span class="lbl">Remaining Balance</span>
                            <span class="val deficit">RM <?php echo number_format($remaining_balance, 2); ?></span>
                        </div>
                        <div class="stat-node">
                            <span class="lbl">Total Paid</span>
                            <span class="val surplus">RM <?php echo number_format($paid_amount, 2); ?></span>
                        </div>
                    </div>
                    <div class="data-detail-list">
                        <div class="detail-row"><span>Interest Rate: </span><strong><?php echo number_format($loan_profile['interest_rate']); ?>%</strong></div>
                        <div class="detail-row"><span>Repayment Start Date: </span><strong><?php echo date('M d, Y', strtotime($loan_profile['repayment_start_date'])); ?></strong></div>
                        <div class="detail-row"><span>Payment Due Date: </span><strong><?php echo date('M d, Y', strtotime($loan_profile['repayment_start_date'] . ' + ' . $loan_profile['repayment_period_months'] . ' months')); ?></strong></div>
                        <div class="detail-row">
                            <span>Repayment Period (Months): </span>
                            <strong>
                                <?php echo $loan_profile['repayment_period_months']; ?> 
                                <button onclick="document.getElementById('editMonthsModal').classList.add('open')" style="border: none; background: transparent; cursor: pointer; font-size: 13px; margin-left: 6px;" title="Edit Repayment Period">✏️</button>
                            </strong>
                        </div>
                        <div class="detail-row"><span>Monthly Repayment Amount: </span><strong>RM <?php echo number_format($loan_profile['monthly_payment'], 2); ?></strong></div>
                        <?php if (isset($overdue_debt) && $overdue_debt > 0): ?>
                            <div class="detail-row"><span style="color: #dc2626; font-weight: 600;">Overdue Balance: </span><strong style="color: #dc2626;">RM <?php echo number_format($overdue_debt, 2); ?></strong></div>
                        <?php endif; ?>
                    </div>
                </section>

                <section class="chart-panel">
                    <h2>Repayment Progress</h2>
                    <div class="chart-container-frame">
                        <div class="donut-chart-mock" style="background: conic-gradient(var(--primary) 0% <?php echo $paid_percentage; ?>%, #e2e8f0 <?php echo $paid_percentage; ?>% 100%);">
                            <div class="inner-hole">
                                <strong><?php echo $paid_percentage; ?>%</strong>
                                <span>Total Paid</span>
                            </div>
                        </div>
                        <div class="chart-legend">
                            <div class="legend-item"><span class="dot primary"></span><span>Settled Funds (<?php echo $paid_percentage; ?>%)</span></div>
                            <div class="legend-item"><span class="dot gray"></span><span>Remaining Debt (<?php echo $unpaid_percentage; ?>%)</span></div>
                        </div>
                    </div>
                </section>
            </div>
        <?php endif; ?>
    </main>

    <script>
        function showToast(message, type = 'success') {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            toast.className = `toast show ${type}`;
            setTimeout(() => { toast.classList.remove('show'); }, 3000);
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Automatic Monthly Payment Calculation
            const totalLoanInput = document.getElementById('total_loan');
            const periodMonthsInput = document.getElementById('repayment_period_months');
            const monthlyPaymentInput = document.getElementById('monthly_payment');

            function calculateRepayment() {
                const totalLoan = parseFloat(totalLoanInput.value) || 0;
                const months = parseInt(periodMonthsInput.value) || 0;
                
                if (totalLoan > 0 && months > 0) {
                    const years = months / 12;
                    const Y = (totalLoan * 0.01 * years) + totalLoan;
                    const monthlyPayment = Y / months;
                    monthlyPaymentInput.value = monthlyPayment.toFixed(2);
                } else {
                    monthlyPaymentInput.value = '';
                }
            }

            if (totalLoanInput && periodMonthsInput && monthlyPaymentInput) {
                totalLoanInput.addEventListener('input', calculateRepayment);
                periodMonthsInput.addEventListener('input', calculateRepayment);
            }

            const form = document.getElementById('loanForm');
            if(form) {
                form.addEventListener('submit', function (e) {
                    e.preventDefault();
                    if(!confirm("Warning: Once this profile is locked, you cannot modify it. Are you sure you want to save?")) return;
                    
                    const btn = document.getElementById('submitBtn');
                    btn.disabled = true;
                    btn.textContent = 'Saving...';

                    fetch('ptptn_dashboard.php', {
                        method: 'POST',
                        body: new FormData(this),
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    })
                    .then(res => res.json())
                    .then(data => {
                        if(data.status === 'success') {
                            showToast(data.message, 'success');
                            setTimeout(() => { window.location.reload(); }, 1000);
                        } else {
                            showToast(data.message, 'error');
                            btn.disabled = false;
                            btn.textContent = 'Save Loan Profile';
                        }
                    });
                });
            }

            // Edit Months Form Submission
            const editMonthsForm = document.getElementById('editMonthsForm');
            if (editMonthsForm) {
                editMonthsForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const btn = document.getElementById('saveMonthsBtn');
                    btn.disabled = true;
                    btn.textContent = 'Saving...';

                    fetch('ptptn_dashboard.php', {
                        method: 'POST',
                        body: new FormData(this),
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.status === 'success') {
                            showToast(data.message, 'success');
                            setTimeout(() => { window.location.reload(); }, 800);
                        } else {
                            showToast(data.message, 'error');
                            btn.disabled = false;
                            btn.textContent = 'Save Changes';
                        }
                    });
                });
            }
        });
    </script>


    <!-- Due Date Notification Popup Modal -->
    <?php if (isset($show_due_warning) && $show_due_warning): ?>
        <div id="dueWarningModal" class="modal open">
            <div class="modal-content" style="max-width: 440px; text-align: center;">
                <div class="modal-header" style="justify-content: center; border-bottom: none; margin-bottom: 12px; padding: 0;">
                    <h2 style="color: #dc2626; font-size: 22px; display: flex; align-items: center; gap: 8px; margin: 0;">
                        ⚠️ Repayment Warning
                    </h2>
                </div>
                <div class="modal-body" style="margin-bottom: 24px;">
                    <p style="font-size: 14.5px; color: var(--text-muted); line-height: 1.6; margin-bottom: 12px;">
                        You have unpaid monthly repayments or have passed the 27th of <?php echo date('F Y'); ?> without payment.
                    </p>
                    <p style="font-size: 13.5px; color: #dc2626; font-weight: 600; margin: 0;">
                        Please pay as soon as possible to avoid penalties.
                    </p>
                </div>
                <div style="display: flex; flex-direction: column; gap: 10px; width: 100%;">
                    <a href="https://myptptn.ptptn.gov.my/" target="_blank" class="btn-primary" style="padding: 12px; border-radius: 10px; text-decoration: none; font-weight: 600; font-size: 14px; text-align: center; color: white; display: block; transition: background-color 0.2s;">Go to MyPTPTN</a>
                    <a href="ptptn_repayment.php" class="btn-secondary" style="padding: 12px; border-radius: 10px; text-decoration: none; font-weight: 600; font-size: 14px; text-align: center; color: var(--text-main); border: 1px solid var(--borders); display: block; background: #f8fafc; transition: all 0.2s;">Paid, Update Now</a>
                    <button type="button" class="btn-secondary" onclick="document.getElementById('dueWarningModal').classList.remove('open')" style="padding: 12px; border-radius: 10px; font-weight: 600; font-size: 14px; cursor: pointer; border: 1px solid var(--borders); background: transparent; transition: all 0.2s;">Dismiss</button>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Edit Repayment Period Modal -->
    <?php if ($has_loan): ?>
        <div id="editMonthsModal" class="modal">
            <div class="modal-content" style="max-width: 400px;">
                <div class="modal-header">
                    <h2>Edit Repayment Period</h2>
                    <button class="close-btn" onclick="document.getElementById('editMonthsModal').classList.remove('open')">&times;</button>
                </div>
                
                <form id="editMonthsForm">
                    <input type="hidden" name="action" value="edit_months">
                    <div class="form-group" style="margin-top: 12px;">
                        <input type="number" id="edit_period_months" name="repayment_period_months" step="12" value="<?php echo $loan_profile['repayment_period_months']; ?>" onkeydown="return false;" required>
                        <label for="edit_period_months">New Repayment Period (Months)</label>
                    </div>
                    <button type="submit" class="btn-submit" id="saveMonthsBtn">Save Changes</button>
                </form>
            </div>
        </div>
    <?php endif; ?>
</body>
</html>