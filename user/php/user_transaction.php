<?php
session_start();

// 1. Guard Authorization
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// 2. Fetch dependencies
require_once '../../config/db_config.php';
$user_id = $_SESSION['user_id'];

// 3. Handle AJAX POST Requests
if ($_SERVER["REQUEST_METHOD"] == "POST" 
&& isset($_SERVER['HTTP_X_REQUESTED_WITH']) 
&& $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest') 
{
    header('Content-Type: application/json');

    $action = $_POST['action'] ?? 'add';

    if ($action === 'add') {
        $type        = $_POST['transaction_type'] ?? '';
        $category    = trim($_POST['category'] ?? '');
        $amount      = isset($_POST['amount']) ? floatval($_POST['amount']) : 0.0;
        $date        = $_POST['transaction_date'] ?? '';
        $description = trim($_POST['description'] ?? '');

        if (empty($type) || empty($category) || $amount <= 0 || empty($date)) {
            echo json_encode(["status" => "error", "message" => "Please fill in all required fields correctly!"]);
            exit;
        }

        // Prevent setting date into past calendar months
        // $tx_month = date('Y-m', strtotime($date));
        // $current_month = date('Y-m');
        // if ($tx_month < $current_month) {
        //     echo json_encode(["status" => "error", "message" => "Cannot add transactions in past calendar months!"]);
        //     exit;
        // }

        $insert_sql = "INSERT INTO user_transactions (user_id, transaction_type, category, amount, description, transaction_date) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($insert_sql);
        $stmt->bind_param("issdss", $user_id, $type, $category, $amount, $description, $date);

        if ($stmt->execute()) {
            echo json_encode(["status" => "success", "message" => "Transaction logged successfully!"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Failed to save data. Please try again."]);
        }
        $stmt->close();
        $conn->close();
        exit;

    } elseif ($action === 'edit') {
        $transaction_id = isset($_POST['transaction_id']) ? intval($_POST['transaction_id']) : 0;
        $type        = $_POST['transaction_type'] ?? '';
        $category    = trim($_POST['category'] ?? '');
        $amount      = isset($_POST['amount']) ? floatval($_POST['amount']) : 0.0;
        $date        = $_POST['transaction_date'] ?? '';
        $description = trim($_POST['description'] ?? '');

        if ($transaction_id <= 0) {
            echo json_encode(["status" => "error", "message" => "Invalid Transaction ID!"]);
            exit;
        }

        if (empty($type) || empty($category) || $amount <= 0 || empty($date)) {
            echo json_encode(["status" => "error", "message" => "Please fill in all required fields correctly!"]);
            exit;
        }

        // 1. Fetch original transaction to check ownership and date
        $check_sql = "SELECT transaction_date, user_id, category FROM user_transactions WHERE transaction_id = ?";
        $stmt = $conn->prepare($check_sql);
        $stmt->bind_param("i", $transaction_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $tx = $res->fetch_assoc();
        $stmt->close();

        if (!$tx) {
            echo json_encode(["status" => "error", "message" => "Transaction not found!"]);
            exit;
        }

        if ($tx['user_id'] != $user_id) {
            echo json_encode(["status" => "error", "message" => "Unauthorized access!"]);
            exit;
        }

        // Block manual updates to system-generated savings or loan logs
        if ($tx['category'] === 'Savings' || $tx['category'] === 'Refund' || $tx['category'] === 'Student Loan') {
            echo json_encode(["status" => "error", "message" => "Cannot edit system-generated savings or student loan transactions manually!"]);
            exit;
        }

        $current_month = date('Y-m');

        // 2. Check if original transaction date is in a past month
        $original_tx_month = date('Y-m', strtotime($tx['transaction_date']));
        if ($original_tx_month < $current_month) {
            echo json_encode(["status" => "error", "message" => "Cannot edit transactions from past calendar months!"]);
            exit;
        }

        // 3. Check if new transaction date is in a past month
        $new_tx_month = date('Y-m', strtotime($date));
        if ($new_tx_month < $current_month) {
            echo json_encode(["status" => "error", "message" => "Cannot set transaction date to a past calendar month!"]);
            exit;
        }

        // 4. Update transaction
        $update_sql = "UPDATE user_transactions SET transaction_type = ?, category = ?, amount = ?, transaction_date = ?, description = ? WHERE transaction_id = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("ssdssi", $type, $category, $amount, $date, $description, $transaction_id);

        if ($stmt->execute()) {
            echo json_encode(["status" => "success", "message" => "Transaction updated successfully!"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Failed to update transaction."]);
        }
        $stmt->close();
        $conn->close();
        exit;

    } elseif ($action === 'delete') {
        $transaction_id = isset($_POST['transaction_id']) ? intval($_POST['transaction_id']) : 0;

        if ($transaction_id <= 0) {
            echo json_encode(["status" => "error", "message" => "Invalid Transaction ID!"]);
            exit;
        }

        // 1. Fetch transaction to verify ownership and date
        $check_sql = "SELECT transaction_date, user_id, category FROM user_transactions WHERE transaction_id = ?";
        $stmt = $conn->prepare($check_sql);
        $stmt->bind_param("i", $transaction_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $tx = $res->fetch_assoc();
        $stmt->close();

        if (!$tx) {
            echo json_encode(["status" => "error", "message" => "Transaction not found!"]);
            exit;
        }

        if ($tx['user_id'] != $user_id) {
            echo json_encode(["status" => "error", "message" => "Unauthorized access!"]);
            exit;
        }

        // Block manual deletion of system-generated savings, refund, or loan logs
        if ($tx['category'] === 'Savings' || $tx['category'] === 'Refund' || $tx['category'] === 'Student Loan') {
            echo json_encode(["status" => "error", "message" => "Cannot delete system-generated savings, refund, or student loan transactions manually!"]);
            exit;
        }

        // 2. Check if transaction date is from a past month
        $current_month = date('Y-m');
        $tx_month = date('Y-m', strtotime($tx['transaction_date']));
        if ($tx_month < $current_month) {
            echo json_encode(["status" => "error", "message" => "Cannot delete transactions from past calendar months!"]);
            exit;
        }

        // 3. Perform delete
        $delete_sql = "DELETE FROM user_transactions WHERE transaction_id = ?";
        $stmt = $conn->prepare($delete_sql);
        $stmt->bind_param("i", $transaction_id);
        if ($stmt->execute()) {
            echo json_encode(["status" => "success", "message" => "Transaction deleted successfully!"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Failed to delete transaction."]);
        }
        $stmt->close();
        $conn->close();
        exit;
    }
}

// 4. Handle standard GET Requests (Fetch Transaction Log with simple Pagination)
$limit = 6;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
if ($page < 1) {
    $page = 1;
}
$offset = ($page - 1) * $limit;

// Get filter parameters
$filter_year = isset($_GET['filter_year']) && $_GET['filter_year'] !== '' ? intval($_GET['filter_year']) : null;
$filter_month = isset($_GET['filter_month']) && $_GET['filter_month'] !== '' ? intval($_GET['filter_month']) : null;
$filter_category = isset($_GET['filter_category']) && $_GET['filter_category'] !== '' ? trim($_GET['filter_category']) : null;

// Fetch distinct years dynamically for filter options
$years_sql = "SELECT DISTINCT YEAR(transaction_date) AS tx_year FROM user_transactions WHERE user_id = ? ORDER BY tx_year DESC";
$years_stmt = $conn->prepare($years_sql);
$years_stmt->bind_param("i", $user_id);
$years_stmt->execute();
$years_result = $years_stmt->get_result();
$available_years = [];
while ($row = $years_result->fetch_assoc()) {
    if ($row['tx_year']) {
        $available_years[] = intval($row['tx_year']);
    }
}
$years_stmt->close();

// Fetch distinct categories dynamically for filter options
$categories_sql = "SELECT DISTINCT category FROM user_transactions WHERE user_id = ? ORDER BY category ASC";
$categories_stmt = $conn->prepare($categories_sql);
$categories_stmt->bind_param("i", $user_id);
$categories_stmt->execute();
$categories_result = $categories_stmt->get_result();
$available_categories = [];
while ($row = $categories_result->fetch_assoc()) {
    if ($row['category']) {
        $available_categories[] = $row['category'];
    }
}
$categories_stmt->close();

// 4.1 Count total rows to compute total pages
$count_sql = "SELECT COUNT(*) FROM user_transactions WHERE user_id = ?";
$count_params = [$user_id];
$count_types = "i";

if ($filter_year !== null) {
    $count_sql .= " AND YEAR(transaction_date) = ?";
    $count_params[] = $filter_year;
    $count_types .= "i";
}
if ($filter_month !== null) {
    $count_sql .= " AND MONTH(transaction_date) = ?";
    $count_params[] = $filter_month;
    $count_types .= "i";
}
if ($filter_category !== null) {
    $count_sql .= " AND category = ?";
    $count_params[] = $filter_category;
    $count_types .= "s";
}

$count_stmt = $conn->prepare($count_sql);
$count_stmt->bind_param($count_types, ...$count_params);
$count_stmt->execute();
$count_stmt->bind_result($total_rows);
$count_stmt->fetch();
$count_stmt->close();

$total_pages = ceil($total_rows / $limit);

// 4.2 Fetch limited rows
$tx_sql = "SELECT transaction_id, transaction_type, category, amount, description, transaction_date FROM user_transactions WHERE user_id = ?";
$tx_params = [$user_id];
$tx_types = "i";

if ($filter_year !== null) {
    $tx_sql .= " AND YEAR(transaction_date) = ?";
    $tx_params[] = $filter_year;
    $tx_types .= "i";
}
if ($filter_month !== null) {
    $tx_sql .= " AND MONTH(transaction_date) = ?";
    $tx_params[] = $filter_month;
    $tx_types .= "i";
}
if ($filter_category !== null) {
    $tx_sql .= " AND category = ?";
    $tx_params[] = $filter_category;
    $tx_types .= "s";
}

$tx_sql .= " ORDER BY transaction_date DESC, transaction_id DESC LIMIT ? OFFSET ?";
$tx_params[] = $limit;
$tx_params[] = $offset;
$tx_types .= "ii";

$stmt = $conn->prepare($tx_sql);
$stmt->bind_param($tx_types, ...$tx_params);
$stmt->execute();
$result = $stmt->get_result();

$user_transactions = [];
while ($row = $result->fetch_assoc()) {
    $user_transactions[] = $row;
}
$stmt->close();
$conn->close();

// Prepare clean query parameters for pagination URLs
$url_params = $_GET;
unset($url_params['page']);
$pagination_query = http_build_query($url_params);
$pagination_query_str = $pagination_query ? '&' . $pagination_query : '';

// 5. Inject decoupled View
include '../view/user_transaction_view.php';
?>