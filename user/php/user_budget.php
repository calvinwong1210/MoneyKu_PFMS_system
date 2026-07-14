<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once '../../config/db_config.php';
$user_id = $_SESSION['user_id'];

// Default parameters mapped automatically to active scopes
$current_month = (int)date('m');
$current_year  = (int)date('Y');

// Handle Active Filtering State
$filter_month = isset($_GET['filter_month']) ? (int)$_GET['filter_month'] : $current_month;
$filter_year  = isset($_GET['filter_year']) ? (int)$_GET['filter_year'] : $current_year;

// --- AJAX Submission Engine ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest') {
    header('Content-Type: application/json');

    $category      = $_POST['category'] ?? '';
    $budget_amount = filter_var($_POST['budget_amount'], FILTER_VALIDATE_FLOAT);

    // Enforce Domain Allowed Lists Verification
    $allowed_categories = ['Essential', 'Lifestyle', 'Others'];
    if (!in_array($category, $allowed_categories)) {
        echo json_encode(["status" => "error", "message" => "Invalid category specification selection."]);
        exit;
    }

    if (!$budget_amount || $budget_amount <= 0) {
        echo json_encode(["status" => "error", "message" => "Please type a valid currency framework amount limit."]);
        exit;
    }

    // Evaluate existing state records for current calendar period 
    $check_sql = "SELECT budget_id, edit_count FROM user_budgets WHERE user_id = ? AND category = ? AND budget_month = ? AND budget_year = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("isii", $user_id, $category, $current_month, $current_year);
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    if ($result->num_rows > 0) {
        $existing = $result->fetch_assoc();
        
        // Block edit execution pipelines if allowance thresholds have been exhausted
        if ($existing['edit_count'] >= 1) {
            echo json_encode(["status" => "error", "message" => "This budget has already been edited once!"]);
            $check_stmt->close();
            exit;
        }

        // Process Allowed Singular Revision Step Upward
        $update_sql = "UPDATE user_budgets SET budget_amount = ?, edit_count = edit_count + 1 WHERE budget_id = ?";
        $action_stmt = $conn->prepare($update_sql);
        $action_stmt->bind_param("di", $budget_amount, $existing['budget_id']);
    } else {
        // Log clean entry record initialization parameters
        $insert_sql = "INSERT INTO user_budgets (user_id, category, budget_amount, budget_month, budget_year, edit_count) VALUES (?, ?, ?, ?, ?, 0)";
        $action_stmt = $conn->prepare($insert_sql);
        $action_stmt->bind_param("isdii", $user_id, $category, $budget_amount, $current_month, $current_year);
    }
    $check_stmt->close();

    if ($action_stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Budget successfully synced!"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Database write channel pipeline error encountered."]);
    }
    $action_stmt->close();
    $conn->close();
    exit;
}

// --- Dynamic Year Selector Block ---
// Fetch distinct years from the user's transaction records
$year_query = "SELECT DISTINCT YEAR(transaction_date) as t_year FROM user_transactions WHERE user_id = ? ORDER BY t_year DESC";
$y_stmt = $conn->prepare($year_query);
$y_stmt->bind_param("i", $user_id);
$y_stmt->execute();
$y_result = $y_stmt->get_result();

$available_years = [];
while ($y_row = $y_result->fetch_assoc()) {
    $available_years[] = (int)$y_row['t_year'];
}
$y_stmt->close();

// Fallback: If no transactions exist, ensure the current year is available so the layout remains functional
if (!in_array($current_year, $available_years)) {
    $available_years[] = $current_year;
    sort($available_years);
}


// --- Data Assembly Query Phase Pipeline ---
$budget_sql = "SELECT category, budget_amount, edit_count FROM user_budgets WHERE user_id = ? AND budget_month = ? AND budget_year = ?";
$b_stmt = $conn->prepare($budget_sql);
$b_stmt->bind_param("iii", $user_id, $filter_month, $filter_year);
$b_stmt->execute();
$b_result = $b_stmt->get_result();

$budgets = [];
$total_allocated = 0;
$total_spent = 0;

while ($row = $b_result->fetch_assoc()) {
    $category = $row['category'];
    
    // Dynamic array mapping criteria based on category group requirements
    if ($category === 'Essential') {
        $sub_categories = ['Food', 'Transport', 'Bills', 'Healthcare', 'Education', 'Housing', 'Insurance', 'Student Loan'];
    } elseif ($category === 'Lifestyle') {
        $sub_categories = ['Shopping', 'Entertainment', 'Travel'];
    } else {
        $sub_categories = ['Others'];
    }

    // Generate dynamic SQL parameter structures placeholders (?,?,?...)
    $placeholders = implode(',', array_fill(0, count($sub_categories), '?'));
    
    // Process matching values dynamically while strictly checking transaction_type = 'expense'
    $spent_sql = "SELECT SUM(amount) as total_spent FROM user_transactions 
                  WHERE user_id = ? 
                  AND transaction_type = 'expense' 
                  AND MONTH(transaction_date) = ? 
                  AND YEAR(transaction_date) = ? 
                  AND category IN ($placeholders)";
                  
    $s_stmt = $conn->prepare($spent_sql);
    
    // Set up reference-based binding array for standard parameter packing
    $bind_types = "iii" . str_repeat("s", count($sub_categories));
    $bind_params = array_merge([$user_id, $filter_month, $filter_year], $sub_categories);
    
    $s_stmt->bind_param($bind_types, ...$bind_params);
    $s_stmt->execute();
    $s_res = $s_stmt->get_result()->fetch_assoc();
    
    $cat_spent = (float)($s_res['total_spent'] ?? 0.00);
    $limit = (float)$row['budget_amount'];
    
    $total_allocated += $limit;
    $total_spent += $cat_spent;
    
    $percentage = $limit > 0 ? min(round(($cat_spent / $limit) * 100), 100) : 0;
    
    $budgets[] = [
        'category' => $category,
        'limit' => $limit,
        'spent' => $cat_spent,
        'percentage' => $percentage,
        'edit_count' => (int)$row['edit_count']
    ];
    $s_stmt->close();
}
$b_stmt->close();
$conn->close();

include '../view/user_budget_view.php';
?>