<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

require_once '../../config/db_config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once '../../PHPMailer/src/Exception.php';
require_once '../../PHPMailer/src/PHPMailer.php';
require_once '../../PHPMailer/src/SMTP.php';

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$role = $_SESSION['role'];

// Fetch current admin's email to verify if they have the specific privilege to ban admins
$admin_email = '';
$email_stmt = $conn->prepare("SELECT email FROM users WHERE user_id = ?");
$email_stmt->bind_param("i", $user_id);
$email_stmt->execute();
$email_res = $email_stmt->get_result();
if ($row = $email_res->fetch_assoc()) {
    $admin_email = $row['email'];
}
$email_stmt->close();

$isAdminBanningAllowed = ($admin_email === 'moneyku6666@gmail.com');

// --- AJAX POST PROCESSOR ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest') {
    header('Content-Type: application/json');

    $action = $_POST['action'] ?? '';

    // Action 1: Ban User/Admin Account
    if ($action === 'ban_user') {
        $email = trim($_POST['email'] ?? '');

        if (empty($email)) {
            echo json_encode(["status" => "error", "message" => "Please enter the registered email address."]);
            exit;
        }

        if ($isAdminBanningAllowed) {
            // ban both 'user' and 'admin' 
            $stmt = $conn->prepare("SELECT user_id, username, account_status FROM users WHERE email = ? AND user_id != ?");
            $stmt->bind_param("si", $email, $user_id);
        } else {
            // only ban 'user'
            $stmt = $conn->prepare("SELECT user_id, username, account_status FROM users WHERE email = ? AND role = 'user'");
            $stmt->bind_param("s", $email);
        }
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res->num_rows !== 1) {
            echo json_encode(["status" => "error", "message" => "No registered account found with this email address."]);
            $stmt->close();
            exit;
        }

        $target_user = $res->fetch_assoc();
        $stmt->close();

        if ($target_user['account_status'] === 'inactive') {
            echo json_encode(["status" => "error", "message" => "This account is already suspended."]);
            exit;
        }

        // Update status to inactive
        $upd = $conn->prepare("UPDATE users SET account_status = 'inactive' WHERE user_id = ?");
        $upd->bind_param("i", $target_user['user_id']);
        
        if ($upd->execute()) {
            $upd->close();

            // Send notification email using PHPMailer
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'moneyku6666@gmail.com';       
                $mail->Password   = 'rhdbitkdeozouibx';          
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;

                $mail->setFrom('moneyku6666@gmail.com', 'MoneyKu Administration');
                $mail->addAddress($email);

                $mail->isHTML(true);
                $mail->Subject = '[MoneyKu] Account Suspension Notice';
                
                $mail->Body    = "Hello <b>" . htmlspecialchars($target_user['username']) . "</b>,<br><br>"
                               . "We regret to inform you that your MoneyKu account has been suspended by administration due to terms of service violations (such as submitting spam feedbacks).<br><br>"
                               . "Your account status has been changed to <b>Inactive</b>, and you will no longer be allowed to log in.<br><br>"
                               . "If you believe this is a mistake, please contact support.<br><br>"
                               . "Sincerely,<br>"
                               . "MoneyKu Admin Team";

                $mail->AltBody = "Hello " . $target_user['username'] . ",\n\n"
                               . "We regret to inform you that your MoneyKu account has been suspended by administration due to terms of service violations (such as submitting spam feedbacks).\n\n"
                               . "Your account status has been changed to Inactive, and you will no longer be allowed to log in.\n\n"
                               . "Sincerely,\n"
                               . "MoneyKu Admin Team";

                $mail->send();
                echo json_encode(["status" => "success", "message" => "Account suspended and notification email sent!"]);
            } catch (Exception $e) {
                // Return success anyway since the DB update was completed, but notify about the email error
                echo json_encode(["status" => "success", "message" => "Account deactivated, but notification email failed to send: " . $mail->ErrorInfo]);
            }
        } else {
            echo json_encode(["status" => "error", "message" => "Database suspension failed. Server error."]);
            $upd->close();
        }
        $conn->close();
        exit;
    }

    // Action 2: Activate/Restore User/Admin Account
    if ($action === 'activate_user') {
        $target_user_id = filter_var($_POST['user_id'] ?? 0, FILTER_VALIDATE_INT);

        if (!$target_user_id) {
            echo json_encode(["status" => "error", "message" => "Invalid User ID."]);
            exit;
        }

        if ($isAdminBanningAllowed) {
            $upd = $conn->prepare("UPDATE users SET account_status = 'active' WHERE user_id = ?");
        } else {
            $upd = $conn->prepare("UPDATE users SET account_status = 'active' WHERE user_id = ? AND role = 'user'");
        }
        $upd->bind_param("i", $target_user_id);

        if ($upd->execute()) {
            echo json_encode(["status" => "success", "message" => "Account reactivated successfully!"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Failed to reactivate account. Server error."]);
        }
        $upd->close();
        $conn->close();
        exit;
    }
}

// --- FETCH SUSPENDED ACCOUNTS ---
$suspended_users = [];
if ($isAdminBanningAllowed) {
    $res = $conn->query("SELECT user_id, username, email, role, updated_at FROM users WHERE account_status = 'inactive' ORDER BY updated_at DESC");
} else {
    $res = $conn->query("SELECT user_id, username, email, role, updated_at FROM users WHERE role = 'user' AND account_status = 'inactive' ORDER BY updated_at DESC");
}
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $suspended_users[] = $row;
    }
}

$conn->close();

include '../view/admin_ban_account_view.php';
?>
