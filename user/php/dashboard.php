<?php
session_start();

// 1. 安全拦截：检查用户是否已登录
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// 2. 引入数据库连接
require_once '../../config/db_config.php';

// 获取当前登录用户的信息
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$role = $_SESSION['role'];

/**
 * 💡 提示：这里未来可以编写你的 SQL 查询语句
 * 例如：
 * $balance_query = "SELECT SUM(amount) FROM wallets WHERE user_id = $user_id";
 * * 为了让 Dashboard 立即有高级感，下面我们先模拟一组符合理财平台高定风格的演示数据：
 */
$mock_data = [
    'total_balance' => 12450.80,
    'monthly_spent' => 1820.45,
    'savings_target' => 5000.00,
    'savings_current' => 3200.00,
    'recent_transactions' => [
        ['date' => '2026-07-02', 'desc' => 'Starbucks Coffee', 'category' => 'Food', 'amount' => -6.80],
        ['date' => '2026-07-01', 'desc' => 'Freelance Design Payment', 'category' => 'Income', 'amount' => 850.00],
        ['date' => '2026-06-30', 'desc' => 'Netflix Subscription', 'category' => 'Entertainment', 'amount' => -14.99],
        ['date' => '2026-06-28', 'desc' => 'Campus Bookstore', 'category' => 'Education', 'amount' => -45.00],
    ]
];

// 计算储蓄百分比
$savings_percentage = round(($mock_data['savings_current'] / $mock_data['savings_target']) * 100);

// 3. 引入纯 HTML 视图层文件
include '../html/dashboard_view.php';
?>