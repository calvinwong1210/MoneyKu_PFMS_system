<?php
session_start();

// 1. 安全拦截：检查用户是否已登录
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// 2. 引入数据库连接
require_once '../../config/db_config.php';

// 获取当前登录用户的信息
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

$goal_sql = "SELECT goal_name, target_amount, current_amount FROM user_savings_goals WHERE user_id = ? ORDER BY target_date ASC, goal_id DESC LIMIT 1";
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

$conn->close();

// 4. 引入纯 HTML 视图层文件
include '../view/dashboard_view.php';
?>