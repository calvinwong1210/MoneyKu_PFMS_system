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

// 3. Handle AJAX POST Requests (Adding a new transaction)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest') {
    header('Content-Type: application/json');

    $type        = $_POST['transaction_type'] ?? '';
    $category    = trim($_POST['category'] ?? '');
    $amount      = $_POST['amount'] ?? '';
    $date        = $_POST['transaction_date'] ?? '';
    $description = trim($_POST['description'] ?? '');

    if (empty($type) || empty($category) || empty($amount) || empty($date)) {
        echo json_encode(["status" => "error", "message" => "Please fill in all required fields!"]);
        exit;
    }

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
}

// 4. Handle standard GET Requests (Fetch Transaction Log)
$tx_sql = "SELECT transaction_id, transaction_type, category, amount, description, transaction_date FROM user_transactions WHERE user_id = ? ORDER BY transaction_date DESC, transaction_id DESC";
$stmt = $conn->prepare($tx_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$user_transactions = [];
while ($row = $result->fetch_assoc()) {
    $user_transactions[] = $row;
}
$stmt->close();
$conn->close();

// 5. Inject decoupled View
include '../view/user_transaction_view.php';
?>