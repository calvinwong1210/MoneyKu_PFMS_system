<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../public/login.php");
    exit();
}

require_once '../../config/db_config.php';
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$role = $_SESSION['role'];

$total_users = 0;
$res = $conn->query("SELECT COUNT(*) AS total FROM users WHERE role = 'user'");
if ($res && $row = $res->fetch_assoc()) {
    $total_users = (int)$row['total'];
}

// 2. Age Distribution Breakdown
$age_under_18 = 0;
$age_18_24 = 0;
$age_25_30 = 0;
$age_31_above = 0;
$age_unknown = 0;

// Query users age groups
$age_query = "SELECT 
    SUM(CASE WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) < 18 THEN 1 ELSE 0 END) AS under_18,
    SUM(CASE WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 18 AND 24 THEN 1 ELSE 0 END) AS group_18_24,
    SUM(CASE WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 25 AND 30 THEN 1 ELSE 0 END) AS group_25_30,
    SUM(CASE WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) >= 31 THEN 1 ELSE 0 END) AS group_31_above
FROM user_profiles p
INNER JOIN users u ON u.user_id = p.user_id
WHERE u.role = 'user'";

$res = $conn->query($age_query);
if ($res && $row = $res->fetch_assoc()) {
    $age_under_18 = (int)($row['under_18'] ?? 0);
    $age_18_24 = (int)($row['group_18_24'] ?? 0);
    $age_25_30 = (int)($row['group_25_30'] ?? 0);
    $age_31_above = (int)($row['group_31_above'] ?? 0);
}

// Calculate unknown age users (users without a profile or date of birth set)
$total_with_profile_age_query = "SELECT COUNT(*) AS total FROM user_profiles p INNER JOIN users u ON u.user_id = p.user_id WHERE u.role = 'user' AND p.date_of_birth IS NOT NULL AND p.date_of_birth != '0000-00-00'";
$res = $conn->query($total_with_profile_age_query);
$total_with_age = 0;
if ($res && $row = $res->fetch_assoc()) {
    $total_with_age = (int)$row['total'];
}
$age_unknown = max(0, $total_users - $total_with_age);

// 3. Gender Distribution
$gender_male = 0;
$gender_female = 0;
$gender_other = 0;
$gender_unknown = 0;

$gender_query = "SELECT 
    SUM(CASE WHEN gender = 'Male' THEN 1 ELSE 0 END) AS male,
    SUM(CASE WHEN gender = 'Female' THEN 1 ELSE 0 END) AS female,
    SUM(CASE WHEN gender = 'Other' THEN 1 ELSE 0 END) AS other
FROM user_profiles p
INNER JOIN users u ON u.user_id = p.user_id
WHERE u.role = 'user'";

$res = $conn->query($gender_query);
if ($res && $row = $res->fetch_assoc()) {
    $gender_male = (int)($row['male'] ?? 0);
    $gender_female = (int)($row['female'] ?? 0);
    $gender_other = (int)($row['other'] ?? 0);
}
$gender_unknown = max(0, $total_users - ($gender_male + $gender_female + $gender_other));

// 4. Other stats (Feedbacks)
$total_feedbacks = 0;
$res = $conn->query("SELECT COUNT(*) AS total FROM user_feedback");
if ($res && $row = $res->fetch_assoc()) {
    $total_feedbacks = (int)$row['total'];
}

// 5. Recent registered users list
$recent_users_query = "SELECT u.user_id, u.username, u.email, p.full_name, u.created_at, p.profile_picture 
                       FROM users u 
                       LEFT JOIN user_profiles p ON u.user_id = p.user_id 
                       WHERE u.role = 'user' 
                       ORDER BY u.created_at DESC 
                       LIMIT 5";
$recent_users = [];
$res = $conn->query($recent_users_query);
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $recent_users[] = $row;
    }
}

$conn->close();

include '../view/admin_dashboard_view.php';
?>
