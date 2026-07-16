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

// 3. Fetch real financial metrics from the database (Balance = Income - Expense)
$current_month_str = date('Y-m');

// 3.1 Calculate Total Balance (Total Income - Total Expense)
$total_income = 0.0;
$total_expense = 0.0;

$income_sql = "SELECT SUM(amount) AS total FROM user_transactions WHERE user_id = ? AND transaction_type = 'income'";
$stmt = $conn->prepare($income_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
if ($res && $res['total'] !== null) {
    $total_income = (float)$res['total'];
}
$stmt->close();

$expense_sql = "SELECT SUM(amount) AS total FROM user_transactions WHERE user_id = ? AND transaction_type = 'expense'";
$stmt = $conn->prepare($expense_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
if ($res && $res['total'] !== null) {
    $total_expense = (float)$res['total'];
}
$stmt->close();

$total_balance = $total_income - $total_expense;

// 3.2 Calculate Current Month's Balance (Monthly Income - Monthly Expense)
$monthly_income = 0.0;
$monthly_expense = 0.0;

$monthly_income_sql = "SELECT SUM(amount) AS total FROM user_transactions WHERE user_id = ? AND transaction_type = 'income' AND DATE_FORMAT(transaction_date, '%Y-%m') = ?";
$stmt = $conn->prepare($monthly_income_sql);
$stmt->bind_param("is", $user_id, $current_month_str);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
if ($res && $res['total'] !== null) {
    $monthly_income = (float)$res['total'];
}
$stmt->close();

$monthly_expense_sql = "SELECT SUM(amount) AS total FROM user_transactions WHERE user_id = ? AND transaction_type = 'expense' AND DATE_FORMAT(transaction_date, '%Y-%m') = ?";
$stmt = $conn->prepare($monthly_expense_sql);
$stmt->bind_param("is", $user_id, $current_month_str);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
if ($res && $res['total'] !== null) {
    $monthly_expense = (float)$res['total'];
}
$stmt->close();

$monthly_balance = $monthly_income - $monthly_expense;

// 3.3 Fetch latest active savings goal
$savings_current = 0.0;
$savings_target = 0.0;
$savings_percentage = 0;
$goal_name = "No Goal Active";

$goal_sql = "SELECT goal_name, target_amount, current_amount FROM user_savings_goals WHERE user_id = ? ORDER BY target_amount DESC LIMIT 1";
$stmt = $conn->prepare($goal_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();

if ($res) {
    $goal_name = $res['goal_name'];
    $savings_target = (float)$res['target_amount'];
    $savings_current = (float)$res['current_amount'];
    if ($savings_target > 0) {
        $savings_percentage = round(($savings_current / $savings_target) * 100);
        if ($savings_percentage > 100) {
            $savings_percentage = 100;
        }
    }
}
$stmt->close();

// 3.4 Fetch last 5 recent transactions
$recent_tx_sql = "SELECT transaction_date, description, category, amount, transaction_type FROM user_transactions WHERE user_id = ? ORDER BY transaction_date DESC, transaction_id DESC LIMIT 5";
$stmt = $conn->prepare($recent_tx_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();

$recent_transactions = [];
while ($row = $res->fetch_assoc()) {
    $recent_transactions[] = $row;
}
$stmt->close();

// 3.5 Calculate Monthly Budget Summary
$current_month = (int)date('m');
$current_year = (int)date('Y');
$total_allocated = 0.0;
$total_spent = 0.0;

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
                  AND MONTH(transaction_date) = ? 
                  AND YEAR(transaction_date) = ? 
                  AND category IN ($placeholders)";
                  
    $s_stmt = $conn->prepare($spent_sql);
    $bind_types = "iii" . str_repeat("s", count($sub_categories));
    $bind_params = array_merge([$user_id, $current_month, $current_year], $sub_categories);
    
    $s_stmt->bind_param($bind_types, ...$bind_params);
    $s_stmt->execute();
    $s_res = $s_stmt->get_result()->fetch_assoc();
    
    $total_spent += (float)($s_res['total_spent'] ?? 0.0);
    $s_stmt->close();
}
$b_stmt->close();

$budget_percentage = 0;
if ($total_allocated > 0) {
    $budget_percentage = round(($total_spent / $total_allocated) * 100);
    if ($budget_percentage > 100) {
        $budget_percentage = 100;
    }
}

// Heath score calculation

$health_score = 0;

// savings score (25 points)
$savings_ratio_points = 0;
$balance_icon = "🟢";
$balance_sub_color = "#10b981";
$balance_score_text = "";

if ($monthly_income > 0) {
    $expense_ratio = $monthly_expense / $monthly_income;
    if ($expense_ratio <= 0.6) {
        $savings_ratio_points = 25;
        $balance_icon = "🟢";
        $balance_sub_color = "#10b981";
        $balance_score_text = "Good (25/25)";
    } elseif ($expense_ratio <= 1.0) {
        $savings_ratio_points = 14;
        $balance_icon = "🟡";
        $balance_sub_color = "#eab308";
        $balance_score_text = "Fair (14/25)";
    } else {
        $savings_ratio_points = 1;
        $balance_icon = "🔴";
        $balance_sub_color = "#f43f5e";
        $balance_score_text = "Poor (1/25)";
    }
} else {
    if ($monthly_expense > 0) {
        $savings_ratio_points = 1;
        $balance_icon = "🔴";
        $balance_sub_color = "#f43f5e";
        $balance_score_text = "No Income, Spending (1/25)";
    } else {
        $savings_ratio_points = 14;
        $balance_icon = "🟡";
        $balance_sub_color = "#eab308";
        $balance_score_text = "No Income (14/25)";
    }
}
$health_score += $savings_ratio_points;

// budget score (25 points)
$budget_points = 0;
$budget_icon = "🟢";
$budget_sub_color = "#10b981";
$budget_score_text = "";

if ($total_allocated > 0) {
    $spent_ratio = $total_spent / $total_allocated;
    if ($spent_ratio <= 0.80) {
        $budget_points = 25;
        $budget_icon = "🟢";
        $budget_sub_color = "#10b981";
        $budget_score_text = "Good (25/25)";
    } elseif ($spent_ratio <= 1.00) {
        $budget_points = 14;
        $budget_icon = "🟡";
        $budget_sub_color = "#eab308";
        $budget_score_text = "Fair (14/25)";
    } else {
        $budget_points = 1;
        $budget_icon = "🔴";
        $budget_sub_color = "#f43f5e";
        $budget_score_text = "Over budget (1/25)";
    }
} else {
    $budget_points = 14;
    $budget_icon = "🟡";
    $budget_sub_color = "#eab308";
    $budget_score_text = "No budget available (14/25)";
}
$health_score += $budget_points;

// PTPTN Repayment Compliance (25 points)
$ptptn_points = 0;
$ptptn_icon = "🟢";
$ptptn_sub_color = "#10b981";
$ptptn_score_text = "";

$loan_check_stmt = $conn->prepare("SELECT loan_id, remaining_balance, repayment_start_date, monthly_payment FROM student_loans WHERE user_id = ?");
$loan_check_stmt->bind_param("i", $user_id);
$loan_check_stmt->execute();
$loan_db = $loan_check_stmt->get_result()->fetch_assoc();
$loan_check_stmt->close();

if ($loan_db) {
    $loan_id = $loan_db['loan_id'];
    $rem_bal = (float)$loan_db['remaining_balance'];
    
    if ($rem_bal <= 0) {
        $ptptn_points = 25;
        $ptptn_icon = "🟢";
        $ptptn_sub_color = "#10b981";
        $ptptn_score_text = "Loan Cleared (25/25)";
    } else {
        // Find all calendar months from repayment_start_date to current month
        $start_date = $loan_db['repayment_start_date'];
        $start_ts = strtotime(date('Y-m-01', strtotime($start_date)));
        $current_ts = strtotime(date('Y-m-01'));
        
        $unpaid_months = [];
        $temp_ts = $start_ts;
        
        while ($temp_ts <= $current_ts) {
            $month_str = date('Y-m', $temp_ts);
            
            $chk_stmt = $conn->prepare("SELECT repayment_id FROM repayment_records WHERE loan_id = ? AND (target_month = ? OR (target_month IS NULL AND DATE_FORMAT(payment_date, '%Y-%m') = ?))");
            $chk_stmt->bind_param("iss", $loan_id, $month_str, $month_str);
            $chk_stmt->execute();
            $has_record = ($chk_stmt->get_result()->num_rows > 0);
            $chk_stmt->close();
            
            if (!$has_record) {
                $unpaid_months[] = $month_str;
            }
            
            $temp_ts = strtotime("+1 month", $temp_ts);
        }

        $current_month_str = date('Y-m');
        $day_of_month = (int)date('d');
        
        $has_past_due = false;
        $current_month_unpaid = false;
        
        foreach ($unpaid_months as $m) {
            if ($m < $current_month_str) {
                $has_past_due = true;
            }
            if ($m === $current_month_str) {
                $current_month_unpaid = true;
            }
        }
        
        if ($has_past_due) {
            $ptptn_points = 0;
            $ptptn_icon = "🔴";
            $ptptn_sub_color = "#f43f5e";
            $ptptn_score_text = "Overdue (" . count($unpaid_months) . "m) (0/25)";
        } else {
            if (!$current_month_unpaid) {
                $ptptn_points = 25;
                $ptptn_icon = "🟢";
                $ptptn_sub_color = "#10b981";
                $ptptn_score_text = "Paid (25/25)";
            } else {
                if ($day_of_month >= 17 || $day_of_month <= 27) {
                    $ptptn_points = 14;
                    $ptptn_icon = "🟡";
                    $ptptn_sub_color = "#eab308";
                    $ptptn_score_text = "Due Soon (14/25)";
                } else {
                    $ptptn_points = 5;
                    $ptptn_icon = "🔴";
                    $ptptn_sub_color = "#f43f5e";
                    $ptptn_score_text = "Overdue (5/25)";
                }
            }
        }
    }
} else {
    $ptptn_points = 25;
    $ptptn_icon = "🟢";
    $ptptn_sub_color = "#10b981";
    $ptptn_score_text = "No Loan Setup (25/25)";
}
$health_score += $ptptn_points;

// ssavings Goal Progress (25 points)
$saving_goal_points = 0;
$saving_goal_icon = "🟢";
$saving_goal_sub_color = "#10b981";
$saving_goal_score_text = "";

$goals_check_stmt = $conn->prepare("SELECT target_amount, current_amount FROM user_savings_goals WHERE user_id = ? ORDER BY target_amount DESC, target_date ASC LIMIT 1");
$goals_check_stmt->bind_param("i", $user_id);
$goals_check_stmt->execute();
$goals_res = $goals_check_stmt->get_result()->fetch_assoc();
$goals_check_stmt->close();

$avg_progress = ($goals_res !== null && (float)$goals_res['target_amount'] > 0) ? ((float)$goals_res['current_amount'] / (float)$goals_res['target_amount']) : -1.0;

if ($avg_progress >= 0.7) {
    $saving_goal_points = 25;
    $saving_goal_icon = "🟢";
    $saving_goal_sub_color = "#10b981";
    $saving_goal_score_text = "On Track (25/25)";
} elseif ($avg_progress >= 0.4) {
    $saving_goal_points = 14;
    $saving_goal_icon = "🟡";
    $saving_goal_sub_color = "#eab308";
    $saving_goal_score_text = "Needs Focus (14/25)";
} elseif ($avg_progress >= 0.0) {
    $saving_goal_points = 5;
    $saving_goal_icon = "🔴";
    $saving_goal_sub_color = "#f43f5e";
    $saving_goal_score_text = "Low Savings (5/25)";
} else {
    $saving_goal_points = 18;
    $saving_goal_icon = "🟡";
    $saving_goal_sub_color = "#eab308";
    $saving_goal_score_text = "No Active Goal (18/25)";
}
$health_score += $saving_goal_points;

// 5. Determine Grade and Advice
$health_grade = "";
$health_color = "";
$health_advice = "";

if ($health_score >= 80) {
    $health_grade = "Excellent";
    $health_color = "#10b981";
    $health_advice = "Excellent financial health. Keep up the great work!";
} elseif ($health_score >= 60) {
    $health_grade = "Good";
    $health_color = "#3b82f6";
    $health_advice = "Good financial habits. Keep saving and stay on track.";
} elseif ($health_score >= 40) {
    $health_grade = "Fair";
    $health_color = "#eab308";
    $health_advice = "Your finances are stable. Review your budget and spending.";
} else {
    $health_grade = "Poor";
    $health_color = "#f43f5e";
    $health_advice = "Your financial health needs improvement. Reduce spending and manage your budget carefully.";
}

$conn->close();

include '../view/dashboard_view.php';
?>