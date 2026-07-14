<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once '../../config/db_config.php';
$user_id = $_SESSION['user_id'];

// --- AJAX Operation Engine Router ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest') {
    header('Content-Type: application/json');
    
    $action = $_POST['action'] ?? '';

    // Handle Adding Fresh Targets
    if ($action === 'create') {
        $goal_name     = trim($_POST['goal_name'] ?? '');
        $target_amount = filter_var($_POST['target_amount'], FILTER_VALIDATE_FLOAT);
        $current_amount= filter_var($_POST['current_amount'], FILTER_VALIDATE_FLOAT);
        $target_date   = $_POST['target_date'] ?? null;

        if (empty($goal_name) || !$target_amount || $target_amount <= 0) {
            echo json_encode(["status" => "error", "message" => "Please configure a valid goal name and target limit."]);
            exit;
        }

        if ($current_amount === false || $current_amount < 0) {
            $current_amount = 0.0;
        }

        $conn->begin_transaction();

        try {
            $insert_sql = "INSERT INTO user_savings_goals (user_id, goal_name, target_amount, current_amount, target_date) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($insert_sql);
            $stmt->bind_param("isdds", $user_id, $goal_name, $target_amount, $current_amount, $target_date);
            $stmt->execute();
            $stmt->close();

            if ($current_amount > 0) {
                $today = date('Y-m-d');
                $desc = "Initial funds saved for goal: " . $goal_name;
                $category = "Savings";
                $type = "expense";

                $tx_sql = "INSERT INTO user_transactions (user_id, transaction_type, category, amount, description, transaction_date) VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($tx_sql);
                $stmt->bind_param("issdss", $user_id, $type, $category, $current_amount, $desc, $today);
                $stmt->execute();
                $stmt->close();
            }

            $conn->commit();
            echo json_encode(["status" => "success", "message" => "Savings goal initialized successfully!"]);
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(["status" => "error", "message" => "Database pipeline transmission error."]);
        }
        exit;
    }

    // Handle Current Balance Progression Updates (Edit Current Amount by ADDING funds)
    if ($action === 'update_progress') {
        $goal_id      = (int)($_POST['goal_id'] ?? 0);
        $added_amount = filter_var($_POST['added_amount'], FILTER_VALIDATE_FLOAT);

        if ($added_amount === false || $added_amount <= 0) {
            echo json_encode(["status" => "error", "message" => "Please supply a valid amount to save."]);
            exit;
        }

        // 1. Fetch current savings details (goal_name, current_amount)
        $fetch_sql = "SELECT goal_name, current_amount FROM user_savings_goals WHERE goal_id = ? AND user_id = ?";
        $stmt = $conn->prepare($fetch_sql);
        $stmt->bind_param("ii", $goal_id, $user_id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$res) {
            echo json_encode(["status" => "error", "message" => "Savings goal not found!"]);
            exit;
        }

        $goal_name = $res['goal_name'];
        $current_saved = (float)$res['current_amount'];
        $new_saved = $current_saved + $added_amount;

        // Start database transaction
        $conn->begin_transaction();

        try {
            // 2. Update current_amount
            $update_sql = "UPDATE user_savings_goals SET current_amount = ? WHERE goal_id = ? AND user_id = ?";
            $stmt = $conn->prepare($update_sql);
            $stmt->bind_param("dii", $new_saved, $goal_id, $user_id);
            $stmt->execute();
            $stmt->close();

            // 3. Create expense transaction to deduct from balance
            $today = date('Y-m-d');
            $desc = "Allocated to savings goal: " . $goal_name;
            $category = "Savings";
            $type = "expense";

            $tx_sql = "INSERT INTO user_transactions (user_id, transaction_type, category, amount, description, transaction_date) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($tx_sql);
            $stmt->bind_param("issdss", $user_id, $type, $category, $added_amount, $desc, $today);
            $stmt->execute();
            $stmt->close();

            // Commit changes
            $conn->commit();

            echo json_encode(["status" => "success", "message" => "Added RM " . number_format($added_amount, 2) . " successfully!"]);
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(["status" => "error", "message" => "Database error occurred."]);
        }
        exit;
    }

    // Handle Goal Removal Action
    if ($action === 'delete') {
        $goal_id = (int)($_POST['goal_id'] ?? 0);

        // 1. Fetch current savings details (goal_name, current_amount) before deleting
        $fetch_sql = "SELECT goal_name, current_amount FROM user_savings_goals WHERE goal_id = ? AND user_id = ?";
        $stmt = $conn->prepare($fetch_sql);
        $stmt->bind_param("ii", $goal_id, $user_id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$res) {
            echo json_encode(["status" => "error", "message" => "Goal not found."]);
            exit;
        }

        $goal_name = $res['goal_name'];
        $refund_amount = (float)$res['current_amount'];

        $conn->begin_transaction();

        try {
            // 2. Delete goal
            $delete_sql = "DELETE FROM user_savings_goals WHERE goal_id = ? AND user_id = ?";
            $stmt = $conn->prepare($delete_sql);
            $stmt->bind_param("ii", $goal_id, $user_id);
            $stmt->execute();
            $stmt->close();

            // 3. If refund_amount > 0, log an income transaction to return funds to balance
            if ($refund_amount > 0) {
                $today = date('Y-m-d');
                $desc = "Refund from deleted savings goal: " . $goal_name;
                $category = "Refund";
                $type = "income";

                $tx_sql = "INSERT INTO user_transactions (user_id, transaction_type, category, amount, description, transaction_date) VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($tx_sql);
                $stmt->bind_param("issdss", $user_id, $type, $category, $refund_amount, $desc, $today);
                $stmt->execute();
                $stmt->close();
            }

            $conn->commit();
            echo json_encode(["status" => "success", "message" => "Goal successfully removed. Refunded RM " . number_format($refund_amount, 2) . " to balance."]);
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(["status" => "error", "message" => "Failed to drop targeted data record."]);
        }
        exit;
    }
}

// --- Data Fetch Extraction Phase ---
$goals_sql = "SELECT goal_id, goal_name, target_amount, current_amount, target_date FROM user_savings_goals WHERE user_id = ? ORDER BY target_date ASC, goal_id DESC";
$stmt = $conn->prepare($goals_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$savings_goals = [];
while ($row = $result->fetch_assoc()) {
    $target  = (float)$row['target_amount'];
    $current = (float)$row['current_amount'];
    
    // Compute remaining margins and execution scales
    $percentage = $target > 0 ? min(round(($current / $target) * 100), 100) : 0;
    
    $row['percentage'] = $percentage;
    $savings_goals[] = $row;
}
$stmt->close();
$conn->close();

include '../view/user_saving_goal_view.php';
?>