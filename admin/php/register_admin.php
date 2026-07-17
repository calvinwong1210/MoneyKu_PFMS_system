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

// Handle POST Admin Registration
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest') {
    header('Content-Type: application/json');

    $new_user = trim($_POST['username'] ?? '');
    $new_email = trim($_POST['email'] ?? '');
    $new_pass = $_POST['password'] ?? '';
    $conf_pass = $_POST['confirm_password'] ?? '';

    if (empty($new_user) || empty($new_email) || empty($new_pass) || empty($conf_pass)) {
        echo json_encode(["status" => "error", "message" => "All fields are required!"]);
        exit;
    }

    if ($new_pass !== $conf_pass) {
        echo json_encode(["status" => "error", "message" => "Passwords do not match!"]);
        exit;
    }

    if (strlen($new_pass) < 6) {
        echo json_encode(["status" => "error", "message" => "Password must be at least 6 characters long!"]);
        exit;
    }

    // Check if username already exists
    $chk_user = $conn->prepare("SELECT user_id FROM users WHERE username = ?");
    $chk_user->bind_param("s", $new_user);
    $chk_user->execute();
    $chk_user->store_result();
    if ($chk_user->num_rows > 0) {
        echo json_encode(["status" => "error", "message" => "Username is already taken!"]);
        $chk_user->close();
        exit;
    }
    $chk_user->close();

    // Check if email already exists
    $chk_email = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
    $chk_email->bind_param("s", $new_email);
    $chk_email->execute();
    $chk_email->store_result();
    if ($chk_email->num_rows > 0) {
        echo json_encode(["status" => "error", "message" => "Email is already registered!"]);
        $chk_email->close();
        exit;
    }
    $chk_email->close();

    // Insert new admin
    $hash = password_hash($new_pass, PASSWORD_DEFAULT);
    $ins = $conn->prepare("INSERT INTO users (username, email, password_hash, role, account_status) VALUES (?, ?, ?, 'admin', 'active')");
    $ins->bind_param("sss", $new_user, $new_email, $hash);

    if ($ins->execute()) {
        echo json_encode(["status" => "success", "message" => "New admin account registered successfully!"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to register. Server error."]);
    }
    $ins->close();
    $conn->close();
    exit;
}

include '../view/register_admin_view.php';
?>
