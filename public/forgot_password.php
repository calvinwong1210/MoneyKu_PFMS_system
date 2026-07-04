<?php
// 引入 PHPMailer
require_once '../PHPMailer/src/Exception.php';
require_once '../PHPMailer/src/PHPMailer.php';
require_once '../PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest') {
    header('Content-Type: application/json');
    require_once '../config/db_config.php';

    $action = $_POST['action'] ?? '';

    // ==================== 1. 发送重置密码的 OTP ====================
    if ($action === 'send_reset_otp') {
        $email = trim($_POST['email'] ?? '');

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(["status" => "error", "message" => "Valid email address is required!"]);
            exit;
        }

        // 检查 Email 是否存在于系统
        $check_sql = "SELECT user_id FROM users WHERE email = ?";
        $stmt = $conn->prepare($check_sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows === 0) {
            echo json_encode(["status" => "error", "message" => "Email address not found in our system!"]);
            $stmt->close();
            $conn->close();
            exit;
        }
        $stmt->close();

        // 生成 6 位 OTP
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $otp_hash = password_hash($otp, PASSWORD_BCRYPT);
        $expired_at = date("Y-m-d H:i:s", strtotime("+5 minutes"));

        // 存入数据库，action_type 标记为 'reset'
        $action_type = 'reset';
        $insert_otp_sql = "INSERT INTO user_otps (identifier, otp_code, action_type, expired_at) VALUES (?, ?, ?, ?)";
        $otp_stmt = $conn->prepare($insert_otp_sql);
        $otp_stmt->bind_param("ssss", $email, $otp_hash, $action_type, $expired_at);
        $otp_stmt->execute();
        $otp_stmt->close();

        // 发送邮件
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'moneyku6666@gmail.com';       
            $mail->Password   = 'rhdbitkdeozouibx';          
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            $mail->setFrom('moneyku6666@gmail.com', 'MoneyKu');
            $mail->addAddress($email);

            $mail->isHTML(true);
            $mail->Subject = '【MoneyKu】Reset Your Password OTP Code';
            $mail->Body    = "Hello! You requested to reset your password. Your OTP code is: <b style='font-size: 24px; color: #dc3545;'>{$otp}</b>. It expires in 5 minutes. If you did not request this, please ignore this email.";
            $mail->AltBody = "Hello! Your OTP code is: {$otp}. It expires in 5 minutes.";

            $mail->send();
            echo json_encode(["status" => "success", "message" => "Reset OTP sent to your email!"]);
        } catch (Exception $e) {
            echo json_encode(["status" => "error", "message" => "Failed to send email. Mailer Error: " . $mail->ErrorInfo]);
        }
        $conn->close();
        exit;
    }

    // ==================== 2. 验证 OTP 并执行修改密码 ====================
    if ($action === 'reset_password') {
        $email = trim($_POST['email'] ?? '');
        $new_password = $_POST['new_password'] ?? '';
        $user_otp = trim($_POST['otp'] ?? '');

        if (empty($email) || empty($new_password) || empty($user_otp)) {
            echo json_encode(["status" => "error", "message" => "All fields are required!"]);
            exit;
        }

        // 使用 PHP 时间比对方法
        $current_time = date("Y-m-d H:i:s");

        // 校验 action_type = 'reset' 的验证码
        $otp_sql = "SELECT id, otp_code FROM user_otps WHERE identifier = ? AND action_type = 'reset' AND is_used = 0 AND expired_at > ? ORDER BY id DESC LIMIT 1";
        $otp_stmt = $conn->prepare($otp_sql);
        $otp_stmt->bind_param("ss", $email, $current_time);
        $otp_stmt->execute();
        $otp_result = $otp_stmt->get_result();
        
        if ($otp_result->num_rows === 0) {
            echo json_encode(["status" => "error", "message" => "OTP expired or invalid!"]);
            $otp_stmt->close();
            $conn->close();
            exit;
        }

        $otp_row = $otp_result->fetch_assoc();
        if (!password_verify($user_otp, $otp_row['otp_code'])) {
            echo json_encode(["status" => "error", "message" => "Incorrect OTP code!"]);
            $otp_stmt->close();
            $conn->close();
            exit;
        }
        $otp_stmt->close();

        // 验证码正确，作废它
        $update_otp_sql = "UPDATE user_otps SET is_used = 1 WHERE id = ?";
        $update_otp_stmt = $conn->prepare($update_otp_sql);
        $update_otp_stmt->bind_param("i", $otp_row['id']);
        $update_otp_stmt->execute();
        $update_otp_stmt->close();

        // 执行 UPDATE 更新用户密码
        $password_hash = password_hash($new_password, PASSWORD_BCRYPT);
        $update_user_sql = "UPDATE users SET password_hash = ? WHERE email = ?";
        $user_stmt = $conn->prepare($update_user_sql);
        $user_stmt->bind_param("ss", $password_hash, $email);

        if ($user_stmt->execute()) {
            echo json_encode(["status" => "success", "message" => "Password updated successfully! Redirecting to Login..."]);
        } else {
            echo json_encode(["status" => "error", "message" => "Failed to update password. Server error."]);
        }
        $user_stmt->close();
        $conn->close();
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MoneyKu - Forgot Password</title>
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
        <h2>Reset Password</h2>
        <p class="subtitle">Enter your email to reset your password</p>

        <form id="forgotForm">
            <div class="form-group">
                <input type="email" id="email" name="email" placeholder=" " required maxlength="100">
                <label for="email">Email Address</label>
                <div class="otp-group" style="position: absolute; right: 8px; top: 50%; transform: translateY(-50%); z-index: 5;">
                    <button type="button" class="btn-otp" id="sendOtpBtn" style="height: 38px; padding: 0 12px; border-radius: 8px;">Send OTP</button>
                </div>
            </div>

            <div class="form-group">
                <input type="text" id="otp" name="otp" placeholder=" " required maxlength="6" autocomplete="off">
                <label for="otp">Enter 6-Digit OTP</label>
            </div>
            
            <div class="form-group password-wrapper">
                <input type="password" id="new_password" name="new_password" placeholder=" " required>
                <label for="new_password">New Password</label>
                <span class="toggle-password" id="togglePassword">👁️</span>
            </div>
            
            <button type="submit" class="btn-submit" id="submitBtn">Reset Password</button>
        </form>

        <p class="footer-text">
            Remembered your password? <a href="login.php">Sign In</a>
        </p>
    </div>

    <script>
        // 密码隐藏/显示眼球控制
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('new_password');
        togglePassword.addEventListener('click', function () {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.textContent = type === 'password' ? '👁️' : '🔒';
        });

        // 提示框
        function showToast(message, type = 'success') {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            toast.className = `toast show ${type}`;
            setTimeout(() => { toast.classList.remove('show'); }, 3000);
        }

        // 发送重置密码的 OTP
        document.getElementById('sendOtpBtn').addEventListener('click', function () {
            const emailInput = document.getElementById('email');
            if (!emailInput.value || !emailInput.checkValidity()) {
                showToast('Please enter a valid email address.', 'error');
                return;
            }

            const sendBtn = this;
            sendBtn.disabled = true;
            sendBtn.textContent = 'Sending...';

            const otpData = new FormData();
            otpData.append('action', 'send_reset_otp');
            otpData.append('email', emailInput.value);

            fetch('forgot_password.php', {
                method: 'POST',
                body: otpData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    showToast(data.message, 'success');
                    let countdown = 60;
                    const timer = setInterval(() => {
                        countdown--;
                        sendBtn.textContent = `Resend (${countdown}s)`;
                        if (countdown <= 0) {
                            clearInterval(timer);
                            sendBtn.disabled = false;
                            sendBtn.textContent = 'Send OTP';
                        }
                    }, 1000);
                } else {
                    showToast(data.message, 'error');
                    sendBtn.disabled = false;
                    sendBtn.textContent = 'Send OTP';
                }
            })
            .catch(() => {
                showToast('Network error.', 'error');
                sendBtn.disabled = false;
                sendBtn.textContent = 'Send OTP';
            });
        });

        // 提交修改新密码
        document.getElementById('forgotForm').addEventListener('submit', function (e) {
            e.preventDefault();
            const btn = document.getElementById('submitBtn');
            btn.disabled = true;
            btn.textContent = 'Resetting...';

            const formData = new FormData(this);
            formData.append('action', 'reset_password');

            fetch('forgot_password.php', {
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
                    btn.textContent = 'Reset Password';
                }
            })
            .catch(() => {
                showToast('Network connection failed.', 'error');
                btn.disabled = false;
                btn.textContent = 'Reset Password';
            });
        });
    </script>
</body>
</html>