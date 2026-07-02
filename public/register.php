<?php
// 处理 AJAX 异步注册 POST 请求
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest') {
    header('Content-Type: application/json');
    require_once '../config/db_config.php';

    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'student';

    if (empty($username) || empty($email) || empty($password)) {
        echo json_encode(["status" => "error", "message" => "All fields are required!"]);
        exit;
    }

    // 检查冲突
    $check_sql = "SELECT user_id FROM users WHERE username = ? OR email = ?";
    $stmt = $conn->prepare($check_sql);
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        echo json_encode(["status" => "error", "message" => "Username or Email already registered!"]);
    } else {
        $password_hash = password_hash($password, PASSWORD_BCRYPT);
        $insert_sql = "INSERT INTO users (username, email, password_hash, role, account_status) VALUES (?, ?, ?, ?, 'active')";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("ssss", $username, $email, $password_hash, $role);

        if ($insert_stmt->execute()) {
            echo json_encode(["status" => "success", "message" => "Account created! Redirecting to Login..."]);
        } else {
            echo json_encode(["status" => "error", "message" => "Server error, please try again."]);
        }
        $insert_stmt->close();
    }
    $stmt->close();
    $conn->close();
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PFMS - Premium Register</title>
    <link rel="stylesheet" href="../css/loginRegister.css">
</head>
<body class="auth-page">

    <div id="toast" class="toast"></div>

    <div class="auth-container">
        <h2>Get Started</h2>
        <p class="subtitle">Create your premium finance system account</p>

        <form id="registerForm">
            <div class="form-group">
                <input type="text" id="username" name="username" placeholder=" " required maxlength="50" autocomplete="off">
                <label for="username">Username</label>
            </div>
            
            <div class="form-group">
                <input type="email" id="email" name="email" placeholder=" " required maxlength="100" autocomplete="off">
                <label for="email">Email Address</label>
            </div>
            
            <div class="form-group password-wrapper">
                <input type="password" id="password" name="password" placeholder=" " required minlength="6">
                <label for="password">Password</label>
                <span class="toggle-password" id="togglePassword">SHOW</span>
            </div>

            <div class="form-group has-select">
                <select id="role" name="role" required>
                    <option value="student">Student (Default)</option>
                    <option value="admin">Admin</option>
                </select>
                <label for="role">Account Role</label>
            </div>
            
            <button type="submit" class="btn-submit" id="submitBtn">Create Account</button>
        </form>

        <p class="footer-text">
            Already have an account? <a href="login.php">Sign In</a>
        </p>
    </div>

    <script>
        // 1. 密码切换
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');
        togglePassword.addEventListener('click', function () {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.textContent = type === 'password' ? 'SHOW' : 'HIDE';
        });

        // 2. 提示框
        function showToast(message, type = 'success') {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            toast.className = `toast show ${type}`;
            setTimeout(() => { toast.classList.remove('show'); }, 3000);
        }

        // 3. AJAX 注册逻辑
        document.getElementById('registerForm').addEventListener('submit', function (e) {
            e.preventDefault();
            const btn = document.getElementById('submitBtn');
            btn.disabled = true;
            btn.textContent = 'Creating...';

            const formData = new FormData(this);

            fetch('register.php', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(res => res.json())
            .then(data => {
                if(data.status === 'success') {
                    showToast(data.message, 'success');
                    setTimeout(() => { window.location.href = 'login.php'; }, 2000);
                } else {
                    showToast(data.message, 'error');
                    btn.disabled = false;
                    btn.textContent = 'Create Account';
                }
            })
            .catch(() => {
                showToast('Network connection failed.', 'error');
                btn.disabled = false;
                btn.textContent = 'Create Account';
            });
        });
    </script>
</body>
</html>