<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

require_once '../../config/db_config.php';
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$role = $_SESSION['role'];

// Handle AJAX POST actions (e.g. deleting feedback)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest') {
    header('Content-Type: application/json');

    $action = $_POST['action'] ?? '';

    if ($action === 'delete_feedback') {
        $feedback_id = filter_var($_POST['feedback_id'] ?? 0, FILTER_VALIDATE_INT);

        if (!$feedback_id) {
            echo json_encode(["status" => "error", "message" => "Invalid Feedback ID."]);
            exit;
        }

        $del = $conn->prepare("DELETE FROM user_feedback WHERE feedback_id = ?");
        $del->bind_param("i", $feedback_id);

        if ($del->execute()) {
            echo json_encode(["status" => "success", "message" => "Feedback deleted successfully!"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Failed to delete feedback. Server error."]);
        }
        $del->close();
        $conn->close();
        exit;
    }
}

// Fetch all feedbacks from the database
$feedbacks_query = "SELECT f.feedback_id, f.feedback_type, f.subject, f.message, f.created_at, u.username, u.email 
                    FROM user_feedback f 
                    INNER JOIN users u ON f.user_id = u.user_id 
                    ORDER BY f.created_at DESC";
$feedbacks = [];
$res = $conn->query($feedbacks_query);
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $feedbacks[] = $row;
    }
}

$conn->close();

include '../view/admin_feedback_view.php';
?>
