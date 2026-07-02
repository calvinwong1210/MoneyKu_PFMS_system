<?php
session_start();

// 1. 清空所有 Session 变量
$_SESSION = array();

// 2. 如果使用的是基于 Cookie 的 Session，清除对应的 Cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), 
        '', 
        time() - 42000,
        $params["path"], 
        $params["domain"],
        $params["secure"], 
        $params["httponly"]
    );
}

// 3. 彻底销毁服务器端的 Session 会话
session_destroy();

// 4. 清除浏览器缓存，防止用户点“后退”按钮还能看到 Dashboard 内容
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// 5. 重定向回到登录页面
header("Location: login.php");
exit();
?>