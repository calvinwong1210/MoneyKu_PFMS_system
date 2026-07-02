<?php
// 防止此文件被直接从浏览器访问（可选的安全操作）
if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    die('禁止直接访问此脚本。');
}

// 数据库配置信息
$host    = 'localhost';
$db_user = 'root';        // 替换为你的数据库用户名
$db_pass = '';            // 替换为你的数据库密码
$db_name = 'pfms_db';

// 创建 MySQLi 连接
$conn = new mysqli($host, $db_user, $db_pass, $db_name);

// 检查连接是否成功
if ($conn->connect_error) {
    // 实际生产环境中建议换成日志记录，避免向用户暴露敏感的数据库报错信息
    die("数据库连接失败: " . $conn->connect_error);
}

// 设置编码，防止中文乱码（对应你数据库的 utf8mb4）
$conn->set_charset("utf8mb4");

// 之后其他文件只要 require 这个文件，就可以直接使用 $conn 变量了
?>