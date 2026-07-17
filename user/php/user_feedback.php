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

// Handle Feedback Submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest') {
    header('Content-Type: application/json');
    
    $feedback_type = $_POST['feedback_type'] ?? '';
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (empty($feedback_type) || empty($subject) || empty($message)) {
        echo json_encode(["status" => "error", "message" => "All feedback fields are required!"]);
        exit;
    }

    $allowed_types = ['Bug Report', 'Feature Request', 'General Feedback'];
    if (!in_array($feedback_type, $allowed_types)) {
        echo json_encode(["status" => "error", "message" => "Invalid feedback type!"]);
        exit;
    }

    $ins_feedback = $conn->prepare("INSERT INTO user_feedback (user_id, feedback_type, subject, message) VALUES (?, ?, ?, ?)");
    $ins_feedback->bind_param("isss", $user_id, $feedback_type, $subject, $message);

    if ($ins_feedback->execute()) {
        echo json_encode(["status" => "success", "message" => "Thank you for your feedback."]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to submit feedback. Server error."]);
    }
    $ins_feedback->close();
    $conn->close();
    exit;
}

// Prepare view
include '../view/user_feedback_view.php';
?>
