<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once '../../config/db_config.php';
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$role = $_SESSION['role'];

// --- AJAX POST SIMULATION PROCESSOR ENGINE ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest') {
    header('Content-Type: application/json');
    
    $action = $_POST['action'] ?? '';
    
    /* -------------------------------------------------------------------------- */
    /* Simulation Module 1: PTPTN Early Repayment Simulation                     */
    /* Used for calculating interest and duration savings from extra repayments.  */
    /* -------------------------------------------------------------------------- */
    if ($action === 'simulate_ptptn') {
        $extra_amount = filter_var($_POST['extra_amount'] ?? 0, FILTER_VALIDATE_FLOAT) ?: 0.00;
        
        if ($extra_amount <= 0) {
            echo json_encode(["status" => "error", "message" => "Please enter a valid additional monthly amount."]);
            exit;
        }
        
        // Fetch loan profile
        $loan_sql = "SELECT total_loan, remaining_balance, monthly_payment, repayment_period_months, repayment_start_date FROM student_loans WHERE user_id = ?";
        $stmt = $conn->prepare($loan_sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $loan = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if (!$loan) {
            echo json_encode(["status" => "error", "message" => "No PTPTN Loan profile found."]);
            exit;
        }
        
        $total_loan = (float)$loan['total_loan'];
        $remaining_balance = (float)$loan['remaining_balance'];
        $monthly_payment = (float)$loan['monthly_payment'];
        $period_months = (int)$loan['repayment_period_months'];
        
        // Reverse engineer principal
        $P = $total_loan / (1 + ($period_months / 1200));
        $I_old = $total_loan - $P;
        
        $N_old_rem = ceil($remaining_balance / $monthly_payment);
        $N_new_rem = ceil($remaining_balance / ($monthly_payment + $extra_amount));
        $months_saved = max(0, $N_old_rem - $N_new_rem);
        
        $months_already_paid = max(0, $period_months - $N_old_rem);
        $N_new_total = $months_already_paid + $N_new_rem;
        
        $I_new = $P * 0.01 * ($N_new_total / 12);
        $interest_saved = max(0.00, $I_old - $I_new);
        
        echo json_encode([
            "status" => "success",
            "months_saved" => $months_saved,
            "interest_saved" => round($interest_saved, 2),
            "new_remaining_months" => $N_new_rem,
            "current_remaining_months" => $N_old_rem,
            "new_monthly_payment" => round($monthly_payment + $extra_amount, 2)
        ]);
        exit;
    }
    
    /* -------------------------------------------------------------------------- */
    /* Simulation Module 2: Can I Afford This? Spending Impact Calculator         */
    /* Used for checking liquidity and savings percentage impact of a purchase.  */
    /* -------------------------------------------------------------------------- */
    if ($action === 'simulate_affordability') {
        $item_name = trim($_POST['item_name'] ?? '');
        $item_price = filter_var($_POST['item_price'] ?? 0, FILTER_VALIDATE_FLOAT) ?: 0.00;
        
        if (empty($item_name)) {
            echo json_encode(["status" => "error", "message" => "Please enter the item name."]);
            exit;
        }
        if ($item_price <= 0) {
            echo json_encode(["status" => "error", "message" => "Please enter a valid item price."]);
            exit;
        }
        
        // Fetch financial data
        // 1. Wallet Balance (total cash reserves)
        $total_income = 0.0;
        $total_expense = 0.0;
        
        $inc_sql = "SELECT SUM(amount) AS total FROM user_transactions WHERE user_id = ? AND transaction_type = 'income'";
        $stmt = $conn->prepare($inc_sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        if ($res && $res['total'] !== null) {
            $total_income = (float)$res['total'];
        }
        $stmt->close();
        
        $exp_sql = "SELECT SUM(amount) AS total FROM user_transactions WHERE user_id = ? AND transaction_type = 'expense'";
        $stmt = $conn->prepare($exp_sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        if ($res && $res['total'] !== null) {
            $total_expense = (float)$res['total'];
        }
        $stmt->close();
        
        $wallet_balance = $total_income - $total_expense;
        
        // 2. Current Month's Cashflow
        $current_month_str = date('Y-m');
        $monthly_income = 0.0;
        $monthly_expense = 0.0;
        
        $m_inc_sql = "SELECT SUM(amount) AS total FROM user_transactions WHERE user_id = ? AND transaction_type = 'income' AND DATE_FORMAT(transaction_date, '%Y-%m') = ?";
        $stmt = $conn->prepare($m_inc_sql);
        $stmt->bind_param("is", $user_id, $current_month_str);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        if ($res && $res['total'] !== null) {
            $monthly_income = (float)$res['total'];
        }
        $stmt->close();
        
        $m_exp_sql = "SELECT SUM(amount) AS total FROM user_transactions WHERE user_id = ? AND transaction_type = 'expense' AND DATE_FORMAT(transaction_date, '%Y-%m') = ?";
        $stmt = $conn->prepare($m_exp_sql);
        $stmt->bind_param("is", $user_id, $current_month_str);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        if ($res && $res['total'] !== null) {
            $monthly_expense = (float)$res['total'];
        }
        $stmt->close();
        
        $monthly_savings = $monthly_income - $monthly_expense;
        
        // Immediate Affordability
        $can_afford_now = ($wallet_balance >= $item_price);
        $depletion_ratio = $wallet_balance > 0 ? ($item_price / $wallet_balance) * 100 : 100;
        
        $risk_level = "safe";
        $color = "#10b981";
        $advice = "";
        
        if (!$can_afford_now) {
            $risk_level = "critical";
            $color = "#dc2626";
            $advice = "You do not have enough cash reserves to purchase this item immediately. We strongly recommend saving up first.";
        } elseif ($depletion_ratio > 50) {
            $risk_level = "warning";
            $color = "#ea580c";
            $advice = "Buying this immediately will deplete more than 50% of your total savings. This leaves you vulnerable to emergency expenses.";
        } elseif ($depletion_ratio > 20) {
            $risk_level = "caution";
            $color = "#eab308";
            $advice = "This purchase is affordable, but it consumes a noticeable portion (" . round($depletion_ratio, 1) . "%) of your cash reserves. Think carefully before spending.";
        } else {
            $risk_level = "safe";
            $color = "#10b981";
            $advice = "This purchase is fully safe. It consumes only " . round($depletion_ratio, 1) . "% of your total balance and won't affect your emergency buffer.";
        }
        
        // Saving & Recovery timeline
        $months_to_save = 0;
        $months_to_recover = 0;
        if ($monthly_savings > 0) {
            $months_to_save = ceil($item_price / $monthly_savings);
            $months_to_recover = ceil($item_price / $monthly_savings);
        } else {
            $months_to_save = -1; // Deficit
            $months_to_recover = -1;
        }
        
        // Savings ratio score points check
        // Before points
        $before_points = 5;
        if ($monthly_income > 0) {
            $expense_ratio_before = $monthly_expense / $monthly_income;
            if ($expense_ratio_before <= 0.6) {
                $before_points = 25;
            } elseif ($expense_ratio_before <= 0.8) {
                $before_points = 18;
            }
        }
        
        // After points (simulate purchase as an expense this month)
        $simulated_expense = $monthly_expense + $item_price;
        $after_points = 5;
        if ($monthly_income > 0) {
            $expense_ratio_after = $simulated_expense / $monthly_income;
            if ($expense_ratio_after <= 0.6) {
                $after_points = 25;
            } elseif ($expense_ratio_after <= 0.8) {
                $after_points = 18;
            }
        }
        
        echo json_encode([
            "status" => "success",
            "item_name" => $item_name,
            "item_price" => $item_price,
            "wallet_balance" => $wallet_balance,
            "can_afford_now" => $can_afford_now,
            "depletion_ratio" => round($depletion_ratio, 1),
            "risk_level" => ucfirst($risk_level),
            "color" => $color,
            "advice" => $advice,
            "months_to_save" => $months_to_save,
            "months_to_recover" => $months_to_recover,
            "before_points" => $before_points,
            "after_points" => $after_points
        ]);
        exit;
    }
    
    /* -------------------------------------------------------------------------- */
    /* Simulation Module 3: Savings Goal Simulator                               */
    /* Used for calculating months required and comparing saving plan packages.  */
    /* -------------------------------------------------------------------------- */
    if ($action === 'simulate_savings_goal') {
        $target = filter_var($_POST['target_amount'] ?? 0, FILTER_VALIDATE_FLOAT) ?: 0.00;
        $current = filter_var($_POST['current_savings'] ?? 0, FILTER_VALIDATE_FLOAT) ?: 0.00;
        $monthly = filter_var($_POST['monthly_savings'] ?? 0, FILTER_VALIDATE_FLOAT) ?: 0.00;
        
        if ($target <= 0) {
            echo json_encode(["status" => "error", "message" => "Please enter a valid target goal amount."]);
            exit;
        }
        if ($monthly <= 0) {
            echo json_encode(["status" => "error", "message" => "Please enter a valid monthly savings amount."]);
            exit;
        }
        
        if ($target <= $current) {
            echo json_encode([
                "status" => "success",
                "months_needed" => 0,
                "plans" => [
                    "plan_a" => ["months" => 0, "saved" => 0],
                    "plan_b" => ["months" => 0, "saved" => 0],
                    "plan_c" => ["months" => 0, "extra" => 0]
                ]
            ]);
            exit;
        }
        
        $net_needed = $target - $current;
        $default_months = ceil($net_needed / $monthly);
        
        // Plan A: +20% Contribution
        $monthly_a = $monthly * 1.20;
        $months_a = ceil($net_needed / $monthly_a);
        $saved_a = max(0, $default_months - $months_a);
        
        // Plan B: +50% Contribution
        $monthly_b = $monthly * 1.50;
        $months_b = ceil($net_needed / $monthly_b);
        $saved_b = max(0, $default_months - $months_b);
        
        // Plan C: -20% Contribution
        $monthly_c = $monthly * 0.80;
        $months_c = ceil($net_needed / $monthly_c);
        $extra_c = max(0, $months_c - $default_months);
        
        echo json_encode([
            "status" => "success",
            "months_needed" => $default_months,
            "plans" => [
                "plan_a" => ["months" => $months_a, "saved" => $saved_a, "contrib" => round($monthly_a, 2)],
                "plan_b" => ["months" => $months_b, "saved" => $saved_b, "contrib" => round($monthly_b, 2)],
                "plan_c" => ["months" => $months_c, "extra" => $extra_c, "contrib" => round($monthly_c, 2)]
            ]
        ]);
        exit;
    }
    
    /* -------------------------------------------------------------------------- */
    /* Simulation Module 4: Financial Health Score Simulation                     */
    /* Used for calculating simulated overall health score out of 100.           */
    /* -------------------------------------------------------------------------- */
    if ($action === 'simulate_health_score') {
        $sim_income = filter_var($_POST['sim_income'] ?? 0, FILTER_VALIDATE_FLOAT) ?: 0.00;
        $sim_expense = filter_var($_POST['sim_expense'] ?? 0, FILTER_VALIDATE_FLOAT) ?: 0.00;
        $sim_budget_util = filter_var($_POST['sim_budget_util'] ?? 0, FILTER_VALIDATE_FLOAT) ?: 0.00;
        $sim_ptptn_status = $_POST['sim_ptptn_status'] ?? 'paid';
        $sim_goal_progress = filter_var($_POST['sim_goal_progress'] ?? 0, FILTER_VALIDATE_FLOAT) ?: 0.00;
        
        if ($sim_income < 0 || $sim_expense < 0) {
            echo json_encode(["status" => "error", "message" => "Simulated values must be non-negative."]);
            exit;
        }
        
        // 1. Savings Ratio Points (max 25)
        $ratio_points = 5;
        if ($sim_income > 0) {
            $exp_ratio = $sim_expense / $sim_income;
            if ($exp_ratio <= 0.6) {
                $ratio_points = 25;
            } elseif ($exp_ratio <= 0.8) {
                $ratio_points = 18;
            }
        }
        
        // 2. Budget Limits Points (max 25)
        $budget_points = 5;
        if ($sim_budget_util <= 80) {
            $budget_points = 25;
        } elseif ($sim_budget_util <= 100) {
            $budget_points = 18;
        }
        
        // 3. PTPTN Compliance Points (max 25)
        $ptptn_points = 5;
        if ($sim_ptptn_status === 'paid') {
            $ptptn_points = 25;
        } elseif ($sim_ptptn_status === 'due_soon') {
            $ptptn_points = 14;
        }
        
        // 4. Savings Goal Progress Points (max 25)
        $goal_points = 18; // default if no goal / skip
        if ($sim_goal_progress >= 0.0) {
            if ($sim_goal_progress >= 0.7) {
                $goal_points = 25;
            } elseif ($sim_goal_progress >= 0.4) {
                $goal_points = 14;
            } else {
                $goal_points = 5;
            }
        }
        
        $total_score = $ratio_points + $budget_points + $ptptn_points + $goal_points;
        
        $grade = "Poor";
        $color = "#f43f5e";
        $advice = "Your simulated health score needs work. Keep expenses low and clear PTPTN arrears to boost your score.";
        
        if ($total_score >= 80) {
            $grade = "Excellent";
            $color = "#10b981";
            $advice = "Excellent simulated health score! Keep up this high-performance financial strategy!";
        } elseif ($total_score >= 60) {
            $grade = "Good";
            $color = "#3b82f6";
            $advice = "Good simulated score. This demonstrates stable and sustainable cashflow management.";
        } elseif ($total_score >= 40) {
            $grade = "Fair";
            $color = "#eab308";
            $advice = "Fair simulated score. Try decreasing category expenditures to build a stronger safety net.";
        }
        
        echo json_encode([
            "status" => "success",
            "sim_score" => $total_score,
            "sim_grade" => $grade,
            "sim_color" => $color,
            "sim_advice" => $advice,
            "breakdown" => [
                "savings" => $ratio_points,
                "budget" => $budget_points,
                "ptptn" => $ptptn_points,
                "goal" => $goal_points
            ]
        ]);
        exit;
    }
}

// --- DATA PREPARATION FOR PAGE LOAD (GET) ---
$loan_sql = "SELECT total_loan, remaining_balance, monthly_payment, repayment_period_months, repayment_start_date FROM student_loans WHERE user_id = ?";
$stmt = $conn->prepare($loan_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$loan_profile = $stmt->get_result()->fetch_assoc();
$stmt->close();

$has_loan = ($loan_profile !== null);

// Fetch current month's income and expenses
$current_month_str = date('Y-m');
$monthly_income = 0.0;
$monthly_expense = 0.0;

$m_inc_sql = "SELECT SUM(amount) AS total FROM user_transactions WHERE user_id = ? AND transaction_type = 'income' AND DATE_FORMAT(transaction_date, '%Y-%m') = ?";
$stmt = $conn->prepare($m_inc_sql);
$stmt->bind_param("is", $user_id, $current_month_str);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
if ($res && $res['total'] !== null) {
    $monthly_income = (float)$res['total'];
}
$stmt->close();

$m_exp_sql = "SELECT SUM(amount) AS total FROM user_transactions WHERE user_id = ? AND transaction_type = 'expense' AND DATE_FORMAT(transaction_date, '%Y-%m') = ?";
$stmt = $conn->prepare($m_exp_sql);
$stmt->bind_param("is", $user_id, $current_month_str);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
if ($res && $res['total'] !== null) {
    $monthly_expense = (float)$res['total'];
}
$stmt->close();

// Fetch overall wallet balance
$total_income = 0.0;
$total_expense = 0.0;

$inc_sql = "SELECT SUM(amount) AS total FROM user_transactions WHERE user_id = ? AND transaction_type = 'income'";
$stmt = $conn->prepare($inc_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
if ($res && $res['total'] !== null) {
    $total_income = (float)$res['total'];
}
$stmt->close();

$exp_sql = "SELECT SUM(amount) AS total FROM user_transactions WHERE user_id = ? AND transaction_type = 'expense'";
$stmt = $conn->prepare($exp_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
if ($res && $res['total'] !== null) {
    $total_expense = (float)$res['total'];
}
$stmt->close();

$wallet_balance = $total_income - $total_expense;

// Fetch current month's budget utilization percentage
$total_allocated = 0.0;
$total_spent = 0.0;

$current_month = (int)date('m');
$current_year = (int)date('Y');

$budget_sql = "SELECT category, budget_amount FROM user_budgets WHERE user_id = ? AND budget_month = ? AND budget_year = ?";
$b_stmt = $conn->prepare($budget_sql);
$b_stmt->bind_param("iii", $user_id, $current_month, $current_year);
$b_stmt->execute();
$b_result = $b_stmt->get_result();

while ($row = $b_result->fetch_assoc()) {
    $category = $row['category'];
    $limit = (float)$row['budget_amount'];
    $total_allocated += $limit;

    if ($category === 'Essential') {
        $sub_categories = ['Food', 'Transport', 'Bills', 'Healthcare', 'Education', 'Housing', 'Insurance', 'Student Loan'];
    } elseif ($category === 'Lifestyle') {
        $sub_categories = ['Shopping', 'Entertainment', 'Travel'];
    } else {
        $sub_categories = ['Others'];
    }

    $placeholders = implode(',', array_fill(0, count($sub_categories), '?'));
    $spent_sql = "SELECT SUM(amount) as total_spent FROM user_transactions 
                  WHERE user_id = ? 
                  AND transaction_type = 'expense' 
                  AND DATE_FORMAT(transaction_date, '%Y-%m') = ? 
                  AND category IN ($placeholders)";
                  
    $s_stmt = $conn->prepare($spent_sql);
    $bind_types = "is" . str_repeat("s", count($sub_categories));
    $bind_params = array_merge([$user_id, $current_month_str], $sub_categories);
    
    $s_stmt->bind_param($bind_types, ...$bind_params);
    $s_stmt->execute();
    $s_res = $s_stmt->get_result()->fetch_assoc();
    
    $total_spent += (float)($s_res['total_spent'] ?? 0.0);
    $s_stmt->close();
}
$b_stmt->close();

$budget_util_percentage = 0.0;
if ($total_allocated > 0) {
    $budget_util_percentage = ($total_spent / $total_allocated) * 100;
}

// Fetch current main savings goal details
$savings_goal_prog = -1.0;
$goals_check_stmt = $conn->prepare("SELECT target_amount, current_amount FROM user_savings_goals WHERE user_id = ? ORDER BY target_amount DESC, target_date ASC LIMIT 1");
$goals_check_stmt->bind_param("i", $user_id);
$goals_check_stmt->execute();
$goals_res = $goals_check_stmt->get_result()->fetch_assoc();
$goals_check_stmt->close();

if ($goals_res !== null && (float)$goals_res['target_amount'] > 0) {
    $savings_goal_prog = (float)$goals_res['current_amount'] / (float)$goals_res['target_amount'];
}

$conn->close();

include '../view/financial_simulation_view.php';
?>
