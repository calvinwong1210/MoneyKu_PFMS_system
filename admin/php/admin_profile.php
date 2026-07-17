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

// --- AJAX POST PROCESSOR ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest') {
    header('Content-Type: application/json');

    $action = $_POST['action'] ?? '';

    // Handle Password Change
    if ($action === 'change_password') {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            echo json_encode(["status" => "error", "message" => "All password fields are required!"]);
            exit;
        }

        if ($new_password !== $confirm_password) {
            echo json_encode(["status" => "error", "message" => "New passwords do not match!"]);
            exit;
        }

        if (strlen($new_password) < 6) {
            echo json_encode(["status" => "error", "message" => "New password must be at least 6 characters long!"]);
            exit;
        }

        // Fetch current password hash
        $pwd_sql = "SELECT password_hash FROM users WHERE user_id = ?";
        $stmt = $conn->prepare($pwd_sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res->num_rows !== 1) {
            echo json_encode(["status" => "error", "message" => "User not found."]);
            exit;
        }
        $user_row = $res->fetch_assoc();
        $stmt->close();

        // Verify current password
        if (!password_verify($current_password, $user_row['password_hash'])) {
            echo json_encode(["status" => "error", "message" => "Incorrect current password!"]);
            exit;
        }

        // Hash new password and update
        $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
        $update_pwd = $conn->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?");
        $update_pwd->bind_param("si", $new_hash, $user_id);
        
        if ($update_pwd->execute()) {
            echo json_encode(["status" => "success", "message" => "Password updated successfully!"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Failed to update password. Server error."]);
        }
        $update_pwd->close();
        $conn->close();
        exit;
    }

    // Handle Profile Update
    $full_name    = trim($_POST['full_name'] ?? '');
    $gender       = $_POST['gender'] ?? NULL;
    $dob          = $_POST['date_of_birth'] ?? NULL;
    $occupation   = trim($_POST['occupation'] ?? '');

    if (empty($full_name)) {
        echo json_encode(["status" => "error", "message" => "Full Name is required!"]);
        exit;
    }

    // Process Profile Picture File Upload
    $profile_picture_filename = null;
    $has_new_upload = false;
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['profile_picture']['tmp_name'];
        $fileName = $_FILES['profile_picture']['name'];
        $fileSize = $_FILES['profile_picture']['size'];
        $fileType = $_FILES['profile_picture']['type'];
        $fileNameCmps = explode(".", $fileName);
        $fileExtension = strtolower(end($fileNameCmps));

        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (in_array($fileExtension, $allowedExtensions)) {
            if ($fileSize <= 5 * 1024 * 1024) {
                // Save avatars in admin/uploads/avatars/ or user/uploads/avatars/? Let's keep them in admin/uploads/avatars/ to isolate admin assets
                $newFileName = 'admin_avatar_' . $user_id . '_' . time() . '.' . $fileExtension;
                $uploadFileDir = '../uploads/avatars/';
                
                if (!is_dir($uploadFileDir)) {
                    mkdir($uploadFileDir, 0755, true);
                }

                $dest_path = $uploadFileDir . $newFileName;
                if(move_uploaded_file($fileTmpPath, $dest_path)) {
                    $profile_picture_filename = $newFileName;
                    $has_new_upload = true;

                    // Delete previous avatar file if exists
                    $old_avatar_sql = "SELECT profile_picture FROM user_profiles WHERE user_id = ?";
                    $stmt_old = $conn->prepare($old_avatar_sql);
                    $stmt_old->bind_param("i", $user_id);
                    $stmt_old->execute();
                    $res_old = $stmt_old->get_result();
                    if ($row_old = $res_old->fetch_assoc()) {
                        $old_avatar = $row_old['profile_picture'];
                        if (!empty($old_avatar) && file_exists($uploadFileDir . $old_avatar)) {
                            unlink($uploadFileDir . $old_avatar);
                        }
                    }
                    $stmt_old->close();
                } else {
                    echo json_encode(["status" => "error", "message" => "Error moving the uploaded file."]);
                    exit;
                }
            } else {
                echo json_encode(["status" => "error", "message" => "Upload failed. Image size must be under 5MB."]);
                exit;
            }
        } else {
            echo json_encode(["status" => "error", "message" => "Upload failed. Allowed formats: JPG, JPEG, PNG, GIF, WEBP."]);
            exit;
        }
    }

    $check_sql = "SELECT profile_id FROM user_profiles WHERE user_id = ?";
    $stmt = $conn->prepare($check_sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->store_result();
    $has_profile = $stmt->num_rows > 0;
    $stmt->close();

    if ($has_profile) {
        if ($has_new_upload) {
            $update_sql = "UPDATE user_profiles SET full_name = ?, gender = ?, date_of_birth = ?, occupation = ?, profile_picture = ? WHERE user_id = ?";
            $stmt = $conn->prepare($update_sql);
            $stmt->bind_param("sssssi", $full_name, $gender, $dob, $occupation, $profile_picture_filename, $user_id);
        } else {
            $update_sql = "UPDATE user_profiles SET full_name = ?, gender = ?, date_of_birth = ?, occupation = ? WHERE user_id = ?";
            $stmt = $conn->prepare($update_sql);
            $stmt->bind_param("ssssi", $full_name, $gender, $dob, $occupation, $user_id);
        }
    } else {
        if ($has_new_upload) {
            $insert_sql = "INSERT INTO user_profiles (user_id, full_name, gender, date_of_birth, occupation, profile_picture) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($insert_sql);
            $stmt->bind_param("isssss", $user_id, $full_name, $gender, $dob, $occupation, $profile_picture_filename);
        } else {
            $insert_sql = "INSERT INTO user_profiles (user_id, full_name, gender, date_of_birth, occupation) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($insert_sql);
            $stmt->bind_param("issss", $user_id, $full_name, $gender, $dob, $occupation);
        }
    }

    if ($stmt->execute()) {
        if ($has_new_upload) {
            $_SESSION['profile_picture'] = $profile_picture_filename;
        }
        echo json_encode(["status" => "success", "message" => "Profile updated successfully!"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to update profile. Server error."]);
    }
    $stmt->close();
    $conn->close();
    exit;
}

// Fetch current admin profile info
$profile_sql = "SELECT u.email, p.full_name, p.gender, p.date_of_birth, p.occupation, p.profile_picture FROM users u LEFT JOIN user_profiles p ON u.user_id = p.user_id WHERE u.user_id = ?";
$stmt = $conn->prepare($profile_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$profile = $result->fetch_assoc();
$stmt->close();

if (!$profile) {
    $profile = [
        'email' => '',
        'full_name' => '',
        'gender' => '',
        'date_of_birth' => '',
        'occupation' => '',
        'profile_picture' => NULL
    ];
} else {
    if ($profile['full_name'] === null) $profile['full_name'] = '';
    if ($profile['gender'] === null) $profile['gender'] = '';
    if ($profile['date_of_birth'] === null) $profile['date_of_birth'] = '';
    if ($profile['occupation'] === null) $profile['occupation'] = '';
}

$conn->close();

include '../view/admin_profile_view.php';
?>
