<?php
date_default_timezone_set('Asia/Kuala_Lumpur');
if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    die('Not allowed to access this file directly');
}

$host    = 'localhost';
$db_user = 'root';        
$db_pass = '';           
$db_name = 'pfms_db';

$conn = new mysqli($host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    die("Database connection failes: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");
?>