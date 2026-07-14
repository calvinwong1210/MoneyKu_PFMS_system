<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../public/login.php");
    exit();
}

require_once '../../config/db_config.php';

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$role = $_SESSION['role'];

if ($_SERVER["REQUEST_METHOD"] == "POST" 
&& isset($_SERVER['HTTP_X_REQUESTED_WITH']) 
&& $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest') 
{
    header('Content-Type: application/json');

    $full_name    = trim($_POST['full_name'] ?? '');
    $gender       = $_POST['gender'] ?? NULL;
    $dob          = $_POST['date_of_birth'] ?? NULL;
    $phone        = trim($_POST['phone_number'] ?? '');
    $occupation   = trim($_POST['occupation'] ?? '');

    if (empty($full_name)) {
        echo json_encode(["status" => "error", "message" => "Full Name is required!"]);
        exit;
    }

    $check_sql = "SELECT profile_id FROM user_profiles WHERE user_id = ?";
    $stmt = $conn->prepare($check_sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->store_result();
    $has_profile = $stmt->num_rows > 0;
    $stmt->close();

    if ($has_profile) {
        $update_sql = "UPDATE user_profiles SET full_name = ?, gender = ?, date_of_birth = ?, phone_number = ?, occupation = ? WHERE user_id = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("sssssi", $full_name, $gender, $dob, $phone, $occupation, $user_id);
    } else {
        $insert_sql = "INSERT INTO user_profiles (user_id, full_name, gender, date_of_birth, phone_number, occupation) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($insert_sql);
        $stmt->bind_param("isssss", $user_id, $full_name, $gender, $dob, $phone, $occupation);
    }

    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Profile updated successfully!"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to update profile. Server error."]);
    }
    $stmt->close();
    $conn->close();
    exit;
}

$profile_sql = "SELECT full_name, gender, date_of_birth, phone_number, occupation, profile_picture FROM user_profiles WHERE user_id = ?";
$stmt = $conn->prepare($profile_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$profile = $result->fetch_assoc();
$stmt->close();

if (!$profile) {
    $profile = [
        'full_name' => '',
        'gender' => '',
        'date_of_birth' => '',
        'phone_number' => '',
        'occupation' => '',
        'profile_picture' => NULL
    ];
}

include '../view/user_profile_view.php';
?>