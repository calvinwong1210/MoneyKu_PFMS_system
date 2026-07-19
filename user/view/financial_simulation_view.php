<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MoneyKu - Financial Simulation</title>
    <link rel="stylesheet" href="../css/user_sidebar.css">
    <link rel="stylesheet" href="../css/financial_simulation.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="simulation-body">

<?php require_once '../sidebar.php'; ?>

    <main class="main-content">
        <header class="main-header">
            <div class="welcome-text">
                <h1>Financial Simulation Laboratory</h1>
                <p>Simulate loan payment options, check purchase affordability, plan savings goals, and view your financial health score.</p>
            </div>
        </header>

        <!-- Simulation Tabs Navigation -->
        <div class="tabs-nav">
            <button class="tab-btn active" onclick="switchTab('ptptn-tab')">🎓 PTPTN Simulator</button>
            <button class="tab-btn" onclick="switchTab('afford-tab')">🛍️ Can I Afford It?</button>
            <button class="tab-btn" onclick="switchTab('savings-tab')">🎯 Savings Planner</button>
            <button class="tab-btn" onclick="switchTab('health-tab')">🩺 Health Score Simulator</button>
        </div>

        <!-- Simulation Panels -->
        <div class="panels-container">
            
            <!-- Simulation  1: PTPTN Early Repayment Simulation                     
            Handles inputs for PTPTN loan amortization speed calculations. -->
            <div id="ptptn-tab" class="tab-panel active">
                <?php if (!$has_loan): ?>
                    <div class="empty-state-card">
                        <div class="empty-icon">🎓</div>
                        <h3>No PTPTN Loan Profile Configured</h3>
                        <p>You need to set up your PTPTN student loan details first before you can run repayment simulations.</p>
                        <a href="ptptn_dashboard.php" class="btn-redirect">Add Loan Profile Now</a>
                    </div>
                <?php else: ?>
                    <div class="simulation-grid">
                        <section class="simulation-card input-section">
                            <h2>Repayment Optimizer</h2>
                            <p class="subtitle">Simulate adding extra monthly payments to shorten your loan period and save interest.</p>
                            
                            <div class="loan-quick-summary">
                                <div class="sum-item"><span>Current Balance</span><strong>RM <?php echo number_format($loan_profile['remaining_balance'], 2); ?></strong></div>
                                <div class="sum-item"><span>Standard Monthly Payment</span><strong>RM <?php echo number_format($loan_profile['monthly_payment'], 2); ?></strong></div>
                            </div>
                            
                            <form id="ptptnSimForm" style="margin-top: 24px;">
                                <input type="hidden" name="action" value="simulate_ptptn">
                                
                                <div class="form-group">
                                    <label for="extra_amount_input" style="font-weight: 600; font-size: 14px; margin-bottom: 8px; display: block; color: var(--text-main);">
                                        Extra Monthly Repayment (RM):
                                    </label>
                                    <input type="number" id="extra_amount_input" name="extra_amount" step="0.01" min="1" max="2000"  style="width: 100%; padding: 12px; border-radius: 8px; border: 1px solid var(--borders); font-size: 15px; font-weight: 600;" oninput="syncSlider(this.value)">
                                </div>
                                
                                <div class="form-group" style="margin-top: 16px;">
                                    <input type="range" id="extra_amount_slider" min="10" max="1000" step="10" value="100" style="width: 100%; accent-color: var(--primary);" oninput="syncInput(this.value)">
                                    <div style="display: flex; justify-content: space-between; font-size: 11px; color: var(--text-muted); margin-top: 4px;">
                                        <span>RM 10</span>
                                        <span>RM 500</span>
                                        <span>RM 1,000</span>
                                    </div>
                                </div>
                                
                                <button type="submit" class="btn-simulate" id="btnPtptnSim">Run Repayment Simulator</button>
                            </form>
                        </section>
                        
                        <section class="simulation-card results-section" id="ptptnResults" style="display: none;">
                            <h2>Simulation Results</h2>
                            <p class="subtitle">Here's how much you save with your accelerated plan.</p>
                            
                            <div class="metrics-output-grid">
                                <div class="output-card success">
                                    <span class="lbl">Months Saved</span>
                                    <strong id="outMonthsSaved">0 months</strong>
                                </div>
                                <div class="output-card success">
                                    <span class="lbl">Total Interest Saved</span>
                                    <strong id="outInterestSaved">RM 0.00</strong>
                                </div>
                            </div>
                            
                            <div class="comparisons-table-container">
                                <h3>Repayment Schedule Comparison</h3>
                                <table class="comparison-table">
                                    <thead>
                                        <tr>
                                            <th>Plan Metric</th>
                                            <th>Default Plan</th>
                                            <th class="highlight">Accelerated Plan</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>Monthly Repayment</td>
                                            <td>RM <?php echo number_format($loan_profile['monthly_payment'], 2); ?></td>
                                            <td class="highlight font-semibold" id="tdNewPayment">RM 0.00</td>
                                        </tr>
                                        <tr>
                                            <td>Remaining Period</td>
                                            <td id="tdCurrentMonths">0 months</td>
                                            <td class="highlight font-semibold" id="tdNewMonths">0 months</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </section>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Simulation 2: Can I Afford This? Spending Impact Calculator        
            Handles input forms and output details for purchase liquidity checking.   -->
            <div id="afford-tab" class="tab-panel">
                <div class="simulation-grid">
                    <section class="simulation-card input-section">
                        <h2>Purchase Analyser</h2>
                        <p class="subtitle">Enter the item details to test if you can afford it immediately without risking deficit.</p>
                        
                        <div class="loan-quick-summary" style="grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));">
                            <div class="sum-item"><span>Cash Balance</span><strong>RM <?php echo number_format($wallet_balance, 2); ?></strong></div>
                            <div class="sum-item"><span>Disposable Savings</span><strong style="color: <?php echo $monthly_income - $monthly_expense >= 0 ? '#10b981' : '#dc2626'; ?>;">RM <?php echo number_format($monthly_income - $monthly_expense, 2); ?></strong></div>
                        </div>
                        
                        <form id="affordSimForm" style="margin-top: 24px;">
                            <input type="hidden" name="action" value="simulate_affordability">
                            
                            <div class="form-group">
                                <label for="item_name">Item Name / Description</label>
                                <input type="text" id="item_name" name="item_name" placeholder="e.g. iPhone 17, Gaming Laptop" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="item_price">Purchase Price (RM)</label>
                                <input type="number" id="item_price" name="item_price" step="0.01" min="1" placeholder="e.g. 3500.00" required>
                            </div>
                            
                            <button type="submit" class="btn-simulate" id="btnAffordSim">Analyse Purchase Impact</button>
                        </form>
                    </section>
                    
                    <section class="simulation-card results-section" id="affordResults" style="display: none;">
                        <h2>Affordability Assessment</h2>
                        <p class="subtitle">Analysis based on current income, cash reserves, and savings rate.</p>
                        
                        <div class="risk-indicator-banner" id="riskBanner">
                            <div style="font-size: 20px; font-weight: 700;" id="riskBadge">SAFE</div>
                            <p style="font-size: 13.5px; margin-top: 6px; line-height: 1.5;" id="riskAdvice"></p>
                        </div>
                        
                        <div class="timeline-insights" style="margin-top: 20px;">
                            <h3>Saving & Recovery Timeline</h3>
                            <div class="insight-row">
                                <span>Months to save up for this (without touching current balance):</span>
                                <strong id="outTimeToSave">0 months</strong>
                            </div>
                            <div class="insight-row">
                                <span>Months to recover spent cash (if purchased immediately):</span>
                                <strong id="outTimeToRecover">0 months</strong>
                            </div>
                        </div>
                        
                        <div class="health-impact-box" style="margin-top: 20px;">
                            <h3>Financial Health Score Impact (Savings Ratio)</h3>
                            <div style="display: flex; gap: 16px; align-items: center; justify-content: center; margin-top: 12px; padding: 12px; border-radius: 8px; background: var(--borders);">
                                <div style="text-align: center; flex: 1;">
                                    <span style="font-size: 12px; color: var(--text-muted); display: block;">Before Purchase</span>
                                    <strong style="font-size: 18px; color: var(--text-main);" id="pointsBefore">0/25</strong>
                                </div>
                                <div style="font-size: 18px; color: var(--text-muted);">➡️</div>
                                <div style="text-align: center; flex: 1;">
                                    <span style="font-size: 12px; color: var(--text-muted); display: block;">After Purchase</span>
                                    <strong style="font-size: 18px;" id="pointsAfter">0/25</strong>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>
            </div>

            <!-- Simulation 3: Savings Goal Simulator                              
            Handles target amount, current savings, and contributions timeline.       -->
            <div id="savings-tab" class="tab-panel">
                <div class="simulation-grid">
                    <section class="simulation-card input-section">
                        <h2>Goal Simulator</h2>
                        <p class="subtitle">Simulate how long it will take to achieve your target based on monthly saving capabilities.</p>
                        
                        <form id="savingsSimForm" style="margin-top: 16px;">
                            <input type="hidden" name="action" value="simulate_savings_goal">
                            
                            <div class="form-group">
                                <label for="target_amount">Target Savings Goal Amount (RM)</label>
                                <input type="number" id="target_amount" name="target_amount" placeholder="e.g. 10000" step="0.01" min="0.00" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="current_savings">Current Existing Savings (RM)</label>
                                <input type="number" id="current_savings" name="current_savings" step="0.01" min="0.00" placeholder="e.g. 2000" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="monthly_savings">Monthly Contribution Ability (RM)</label>
                                <input type="number" id="monthly_savings" name="monthly_savings" step="0.01" min="0.00"  placeholder="e.g. 500" required>
                            </div>
                            
                            <button type="submit" class="btn-simulate" id="btnSavingsSim">Simulate Goal Timeline</button>
                        </form>
                    </section>
                    
                    <section class="simulation-card results-section" id="savingsResults" style="display: none;">
                        <h2>Timeline Output</h2>
                        <p class="subtitle">Timeline results and different package strategies to hit your target faster.</p>
                        
                        <div class="metrics-output-grid" style="grid-template-columns: 1fr;">
                            <div class="output-card success" style="align-items: center; justify-content: center; text-align: center;">
                                <span class="lbl">Estimated Default Timeline</span>
                                <strong id="outGoalMonths" style="font-size: 32px; color: var(--primary);">0 months</strong>
                            </div>
                        </div>
                        
                        <div class="timeline-insights" style="margin-top: 20px;">
                            <h3>Different Savings Scenarios</h3>
                            
                            <div class="insight-row">
                                <span>Option A: +20% Savings (<strong>+20%</strong> = RM <span id="lblPlanAContrib">0</span>/mo)</span>
                                <div><strong id="outPlanAMonths" style="color: #10b981;">0 months</strong> <span style="font-size:12px; color: var(--text-muted);">(-<span id="outPlanASaved">0</span>m)</span></div>
                            </div>
                            
                            <div class="insight-row">
                                <span>Option B: +50% Savings (<strong>+50%</strong> = RM <span id="lblPlanBContrib">0</span>/mo)</span>
                                <div><strong id="outPlanBMonths" style="color: #3b82f6;">0 months</strong> <span style="font-size:12px; color: var(--text-muted);">(-<span id="outPlanBSaved">0</span>m)</span></div>
                            </div>
                            
                            <div class="insight-row">
                                <span>Option C: -20% Savings (<strong>-20%</strong> = RM <span id="lblPlanCContrib">0</span>/mo)</span>
                                <div><strong id="outPlanCMonths" style="color: #ea580c;">0 months</strong> <span style="font-size:12px; color: var(--text-muted);">(+<span id="outPlanCExtra">0</span>m)</span></div>
                            </div>
                        </div>
                    </section>
                </div>
            </div>

            <!-- Simulation 4: Financial Health Score Simulation                    
            Handles income, expense, and budget adjustments to show simulated scores.  -->
            <div id="health-tab" class="tab-panel">
                <div class="simulation-grid">
                    <section class="simulation-card input-section">
                        <h2>Financial Health Simulator</h2>
                        <p class="subtitle">Adjust your monthly parameters below to see how changes dynamically improve or lower your overall 100-point Health Score.</p>
                        
                        <form id="healthSimForm" style="margin-top: 16px;">
                            <input type="hidden" name="action" value="simulate_health_score">
                            
                            <div class="form-group">
                                <label for="sim_income">Simulated Monthly Income (RM)</label>
                                <input type="number" id="sim_income" name="sim_income" step="0.01" min="0" value="<?php echo max(0, (float)$monthly_income); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="sim_expense">Simulated Monthly Expense (RM)</label>
                                <input type="number" id="sim_expense" name="sim_expense" step="0.01" min="0" value="<?php echo (float)$monthly_expense; ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="sim_budget_util">Simulated Budget Utilized (%)</label>
                                <input type="range" id="sim_budget_util" name="sim_budget_util" min="0" max="150" step="5" value="<?php echo round($budget_util_percentage); ?>" style="width: 100%; accent-color: var(--primary);" oninput="document.getElementById('lblBudgetUtil').innerText = this.value">
                                <div style="display: flex; justify-content: space-between; font-size: 12px; color: var(--text-muted); margin-top: 4px;">
                                    <span>0% (Empty)</span>
                                    <span>Current: <strong id="lblBudgetUtil"><?php echo round($budget_util_percentage); ?></strong>%</span>
                                    <span>150% (Exceeded)</span>
                                </div>
                            </div>
                            
                            <div class="form-group" style="margin-top: 24px;">
                                <label style="display: block; margin-bottom: 8px;">Simulated PTPTN Status</label>
                                <div style="display: flex; gap: 12px;">
                                    <label style="flex: 1; border: 1px solid var(--borders); padding: 10px; border-radius: 8px; display: flex; align-items: center; justify-content: center; gap: 6px; cursor: pointer; font-size:13.5px; font-weight:500;">
                                        <input type="radio" name="sim_ptptn_status" value="paid" checked> Paid
                                    </label>
                                    <label style="flex: 1; border: 1px solid var(--borders); padding: 10px; border-radius: 8px; display: flex; align-items: center; justify-content: center; gap: 6px; cursor: pointer; font-size:13.5px; font-weight:500;">
                                        <input type="radio" name="sim_ptptn_status" value="due_soon"> Due Soon
                                    </label>
                                    <label style="flex: 1; border: 1px solid var(--borders); padding: 10px; border-radius: 8px; display: flex; align-items: center; justify-content: center; gap: 6px; cursor: pointer; font-size:13.5px; font-weight:500;">
                                        <input type="radio" name="sim_ptptn_status" value="overdue"> Overdue
                                    </label>
                                </div>
                            </div>
                            
                            <div class="form-group" style="margin-top: 24px;">
                                <label for="sim_goal_progress">Simulated Savings Goal Progress (Due soon) (%)</label>
                                <input type="range" id="sim_goal_progress" name="sim_goal_progress" min="0" max="100" step="5" value="<?php echo $savings_goal_prog >= 0 ? round($savings_goal_prog * 100) : 0; ?>" style="width: 100%; accent-color: var(--primary);" oninput="document.getElementById('lblGoalProg').innerText = this.value">
                                <div style="display: flex; justify-content: space-between; font-size: 12px; color: var(--text-muted); margin-top: 4px;">
                                    <span>0% (Started)</span>
                                    <span>Current: <strong id="lblGoalProg"><?php echo $savings_goal_prog >= 0 ? round($savings_goal_prog * 100) : 0; ?></strong>%</span>
                                    <span>100% (Completed)</span>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn-simulate" id="btnHealthSim" style="margin-top: 16px;">Simulate Health Rating</button>
                        </form>
                    </section>
                    
                    <section class="simulation-card results-section" id="healthResults" style="display: none;">
                        <h2>Simulated Financial Score</h2>
                        <p class="subtitle">Calculated dynamically using the 4-quadrant MoneyKu scoring matrix.</p>
                        
                        <!-- Dial Gauge -->
                        <div style="display: flex; justify-content: center; margin-bottom: 24px;">
                            <div style="position: relative; width: 140px; height: 140px; display: flex; align-items: center; justify-content: center;">
                                <svg width="140" height="140" viewBox="0 0 140 140" style="transform: rotate(-90deg);">
                                    <circle cx="70" cy="70" r="58" stroke="#f1f5f9" stroke-width="12" fill="transparent" />
                                    <circle id="healthScoreCircle" cx="70" cy="70" r="58" stroke="#10b981" stroke-width="12" fill="transparent" 
                                            stroke-dasharray="364.4" stroke-dashoffset="364.4" 
                                            stroke-linecap="round" style="transition: stroke-dashoffset 0.8s ease-out, stroke 0.8s;" />
                                </svg>
                                <div style="position: absolute; text-align: center;">
                                    <span id="outHealthScore" style="font-size: 34px; font-weight: 800; color: var(--text-main); line-height: 1;">0</span>
                                    <span style="display: block; font-size: 11px; color: var(--text-muted); font-weight: 600; text-transform: uppercase;">Points</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="risk-indicator-banner" id="healthScoreBanner" style="margin-bottom: 24px;">
                            <div style="font-size: 20px; font-weight: 700;" id="healthScoreBadge">EXCELLENT</div>
                            <p style="font-size: 13.5px; margin-top: 6px; line-height: 1.5;" id="healthScoreAdvice"></p>
                        </div>
                        
                        <div class="timeline-insights">
                            <h3>Scoring Matrix Breakdown</h3>
                            
                            <div class="insight-row">
                                <span>Savings Ratio Category Score:</span>
                                <strong id="lblBrSavings">0/25</strong>
                            </div>
                            
                            <div class="insight-row">
                                <span>Budget Limits Category Score:</span>
                                <strong id="lblBrBudget">0/25</strong>
                            </div>
                            
                            <div class="insight-row">
                                <span>PTPTN Compliance Category Score:</span>
                                <strong id="lblBrPtptn">0/25</strong>
                            </div>
                            
                            <div class="insight-row">
                                <span>Savings Goal Category Score:</span>
                                <strong id="lblBrGoal">0/25</strong>
                            </div>
                        </div>
                    </section>
                </div>
            </div>

        </div>
    </main>

<script>
function switchTab(tabId) {
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    
    document.getElementById(tabId).classList.add('active');
    
    // Find matching button to activate
    const btnText = tabId === 'ptptn-tab' ? '🎓 PTPTN' : (tabId === 'afford-tab' ? '🛍️ Can I Afford It?' : (tabId === 'savings-tab' ? '🎯 Savings' : '🩺 Health'));
    document.querySelectorAll('.tab-btn').forEach(b => {
        if (b.innerText.includes(btnText)) {
            b.classList.add('active');
        }
    });
}

function syncSlider(val) {
    const slider = document.getElementById('extra_amount_slider');
    if (slider) {
        slider.value = val;
    }
}

function syncInput(val) {
    const input = document.getElementById('extra_amount_input');
    if (input) {
        input.value = val;
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // 1. PTPTN Sim Form Submission Handler
    const ptptnForm = document.getElementById('ptptnSimForm');
    if (ptptnForm) {
        ptptnForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = document.getElementById('btnPtptnSim');
            btn.innerText = 'Calculating...';
            btn.disabled = true;
            
            const formData = new FormData(this);
            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'financial_simulation.php', true);
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    btn.innerText = 'Run Repayment Simulator';
                    btn.disabled = false;
                    
                    try {
                        const res = JSON.parse(xhr.responseText);
                        if (res.status === 'success') {
                            document.getElementById('outMonthsSaved').innerText = res.months_saved + ' months';
                            document.getElementById('outInterestSaved').innerText = 'RM ' + parseFloat(res.interest_saved).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                            document.getElementById('tdNewPayment').innerText = 'RM ' + parseFloat(res.new_monthly_payment).toLocaleString('en-US', {minimumFractionDigits: 2});
                            document.getElementById('tdCurrentMonths').innerText = res.current_remaining_months + ' months';
                            document.getElementById('tdNewMonths').innerText = res.new_remaining_months + ' months';
                            
                            document.getElementById('ptptnResults').style.display = 'block';
                        } else {
                            alert(res.message || 'Error executing simulation.');
                        }
                    } catch(err) {
                        alert('Response parse error.');
                    }
                }
            };
            xhr.send(formData);
        });
    }

    // 2. Affordability Sim Form Submission Handler
    const affordForm = document.getElementById('affordSimForm');
    if (affordForm) {
        affordForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = document.getElementById('btnAffordSim');
            btn.innerText = 'Analyzing...';
            btn.disabled = true;
            
            const formData = new FormData(this);
            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'financial_simulation.php', true);
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    btn.innerText = 'Analyse Purchase Impact';
                    btn.disabled = false;
                    
                    try {
                        const res = JSON.parse(xhr.responseText);
                        if (res.status === 'success') {
                            const banner = document.getElementById('riskBanner');
                            const badge = document.getElementById('riskBadge');
                            const advice = document.getElementById('riskAdvice');
                            
                            badge.innerText = res.risk_level.toUpperCase() + ' RISK';
                            advice.innerText = res.advice;
                            
                            // Style banner color dynamically
                            banner.style.background = res.color + '15';
                            banner.style.border = '1px solid ' + res.color + '40';
                            badge.style.color = res.color;
                            
                            // Time constraints outputs
                            document.getElementById('outTimeToSave').innerText = res.months_to_save === -1 ? 'Not Available (Cash Flow Deficit)' : res.months_to_save + ' months';
                            document.getElementById('outTimeToRecover').innerText = res.months_to_recover === -1 ? 'Not Available (Cash Flow Deficit)' : res.months_to_recover + ' months';
                            
                            // Health Score points impact display
                            const ptsBefore = document.getElementById('pointsBefore');
                            const ptsAfter = document.getElementById('pointsAfter');
                            ptsBefore.innerText = res.before_points + '/25';
                            ptsAfter.innerText = res.after_points + '/25';
                            
                            if (res.after_points < res.before_points) {
                                ptsAfter.style.color = '#dc2626';
                                ptsAfter.style.fontWeight = '700';
                            } else {
                                ptsAfter.style.color = '#10b981';
                                ptsAfter.style.fontWeight = '700';
                            }
                            
                            document.getElementById('affordResults').style.display = 'block';
                        } else {
                            alert(res.message || 'Error executing affordability assessment.');
                        }
                    } catch(err) {
                        alert('Response parse error.');
                    }
                }
            };
            xhr.send(formData);
        });
    }

    // 3. Savings Goal Sim Form Submission Handler
    const savingsForm = document.getElementById('savingsSimForm');
    if (savingsForm) {
        savingsForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = document.getElementById('btnSavingsSim');
            btn.innerText = 'Simulating...';
            btn.disabled = true;
            
            const formData = new FormData(this);
            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'financial_simulation.php', true);
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    btn.innerText = 'Simulate Goal Timeline';
                    btn.disabled = false;
                    
                    try {
                        const res = JSON.parse(xhr.responseText);
                        if (res.status === 'success') {
                            document.getElementById('outGoalMonths').innerText = res.months_needed + ' months';
                            
                            document.getElementById('lblPlanAContrib').innerText = parseFloat(res.plans.plan_a.contrib).toLocaleString('en-US');
                            document.getElementById('outPlanAMonths').innerText = res.plans.plan_a.months + ' months';
                            document.getElementById('outPlanASaved').innerText = res.plans.plan_a.saved;
                            
                            document.getElementById('lblPlanBContrib').innerText = parseFloat(res.plans.plan_b.contrib).toLocaleString('en-US');
                            document.getElementById('outPlanBMonths').innerText = res.plans.plan_b.months + ' months';
                            document.getElementById('outPlanBSaved').innerText = res.plans.plan_b.saved;
                            
                            document.getElementById('lblPlanCContrib').innerText = parseFloat(res.plans.plan_c.contrib).toLocaleString('en-US');
                            document.getElementById('outPlanCMonths').innerText = res.plans.plan_c.months + ' months';
                            document.getElementById('outPlanCExtra').innerText = res.plans.plan_c.extra;
                            
                            document.getElementById('savingsResults').style.display = 'block';
                        } else {
                            alert(res.message || 'Error executing savings simulation.');
                        }
                    } catch(err) {
                        alert('Response parse error.');
                    }
                }
            };
            xhr.send(formData);
        });
    }

    // 4. Health Score Sim Form Submission Handler
    const healthForm = document.getElementById('healthSimForm');
    if (healthForm) {
        healthForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = document.getElementById('btnHealthSim');
            btn.innerText = 'Modeling...';
            btn.disabled = true;
            
            const formData = new FormData(this);
            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'financial_simulation.php', true);
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    btn.innerText = 'Simulate Health Rating';
                    btn.disabled = false;
                    
                    try {
                        const res = JSON.parse(xhr.responseText);
                        if (res.status === 'success') {
                            const badge = document.getElementById('healthScoreBadge');
                            const advice = document.getElementById('healthScoreAdvice');
                            const scoreText = document.getElementById('outHealthScore');
                            const circle = document.getElementById('healthScoreCircle');
                            const banner = document.getElementById('healthScoreBanner');
                            
                            scoreText.innerText = res.sim_score;
                            badge.innerText = res.sim_grade.toUpperCase();
                            advice.innerText = res.sim_advice;
                            
                            // Style dial progress
                            const offset = 364.4 - (364.4 * res.sim_score / 100);
                            circle.style.strokeDashoffset = offset;
                            circle.style.stroke = res.sim_color;
                            
                            // Style banner color
                            banner.style.background = res.sim_color + '15';
                            banner.style.border = '1px solid ' + res.sim_color + '40';
                            badge.style.color = res.sim_color;
                            
                            // Details breakdown values
                            document.getElementById('lblBrSavings').innerText = res.breakdown.savings + '/25';
                            document.getElementById('lblBrBudget').innerText = res.breakdown.budget + '/25';
                            document.getElementById('lblBrPtptn').innerText = res.breakdown.ptptn + '/25';
                            document.getElementById('lblBrGoal').innerText = res.breakdown.goal + '/25';
                            
                            document.getElementById('healthResults').style.display = 'block';
                        } else {
                            alert(res.message || 'Error executing health score modeler.');
                        }
                    } catch(err) {
                        alert('Response parse error.');
                    }
                }
            };
            xhr.send(formData);
        });
    }
});
</script>
</body>
</html>
