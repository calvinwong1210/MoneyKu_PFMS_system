<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest') {
    header('Content-Type: application/json');
    require_once '../config/db_config.php';

    $username_or_email = trim($_POST['username_or_email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username_or_email) || empty($password)) {
        echo json_encode(["status" => "error", "message" => "Please fill in all fields!"]);
        exit;
    }

    $login_sql = "SELECT user_id, username, password_hash, role, account_status FROM users WHERE username = ? OR email = ?";
    $stmt = $conn->prepare($login_sql);
    $stmt->bind_param("ss", $username_or_email, $username_or_email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if ($user['account_status'] !== 'active') {
            echo json_encode(["status" => "error", "message" => "Account is inactive. Contact admin."]);
        } elseif (password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            echo json_encode(["status" => "success", "message" => "Login successful! Redirecting..."]);
        } else {
            echo json_encode(["status" => "error", "message" => "Invalid credentials!"]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "Account does not exist!"]);
    }
    $stmt->close();
    $conn->close();
    exit;
}

// if (isset($_SESSION['user_id'])) {
//     header("Location: ../user/php/dashboard.php");
//     exit();
// }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MoneyKu - Sign In</title>
    <link rel="stylesheet" href="../css/loginRegister.css">
</head>
<body class="auth-page">

    <div id="toast" class="toast"></div>

    <div class="auth-container">
        <div class="logo">
            <a href="index.php">
                <img src="../images/logo.png" alt="PFMS Logo" class="logo-img">
            </a>
        </div>
        <h2>Welcome Back</h2>
        <p class="subtitle">Enter your credentials to access MoneyKu</p>

        <form id="loginForm">
            <div class="form-group">
                <input type="text" id="username_or_email" name="username_or_email" placeholder=" " required>
                <label Kakao for="username_or_email">Username or Email</label>
            </div>
            
            <div class="form-group password-wrapper">
                <input type="password" id="password" name="password" placeholder=" " required>
                <label for="password">Password</label>
                <span class="toggle-password" id="togglePassword">
                    <img src="../images/hide_password.png" alt="Toggle Password">
                </span>            
            </div>
            
            <div class="forgot-password">
                <a href="forgot_password.php">Forgot Password?</a>
            </div>
            
            <button type="submit" class="btn-submit" id="submitBtn">Sign In</button>
        </form>

        <p class="footer-text">
            New to MoneyKu? <a href="register.php">Create an account</a>
        </p>
    </div>

    <script>
        function showHidePassword(toggleId, inputId) {
            const toggleElement = document.getElementById(toggleId);
            const inputElement = document.getElementById(inputId);
            const imgElement = toggleElement.querySelector('img');
            
            toggleElement.addEventListener('click', function () {
                const isPassword = inputElement.getAttribute('type') === 'password';
                inputElement.setAttribute('type', isPassword ? 'text' : 'password');
                imgElement.src = isPassword ? '../images/show_password.png' : '../images/hide_password.png';
            });
        }
        showHidePassword('togglePassword', 'password');

        // toast function
        function showToast(message, type = 'success') {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            toast.className = `toast show ${type}`;
            setTimeout(() => { toast.classList.remove('show'); }, 3000);
        }

        document.getElementById('loginForm').addEventListener('submit', function (e) {
            e.preventDefault(); /
            
            const btn = document.getElementById('submitBtn');
            btn.disabled = true;
            btn.textContent = 'Verifying...';

            const formData = new FormData(this);

            fetch('login.php', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' } 
            })
            .then(res => res.json())
            .then(data => {
                if(data.status === 'success') {
                    showToast(data.message, 'success');
                    setTimeout(() => { window.location.href = '../user/php/dashboard.php'; }, 1000);
                } else {
                    showToast(data.message, 'error');
                    btn.disabled = false;
                    btn.textContent = 'Sign In';
                }
            })
            .catch(() => {
                showToast('Network error, please try again.', 'error');
                btn.disabled = false;
                btn.textContent = 'Sign In';
            });
        });
    </script>
</body>
</html>