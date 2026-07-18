<?php
require_once '../PHPMailer/src/Exception.php';
require_once '../PHPMailer/src/PHPMailer.php';
require_once '../PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// handle AJAX requests for sending OTP and register user
if ($_SERVER["REQUEST_METHOD"] == "POST" 
&& isset($_SERVER['HTTP_X_REQUESTED_WITH']) 
&& $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest') {
    header('Content-Type: application/json');
    require_once '../config/db_config.php';
    $action = $_POST['action'] ?? ''; 

    // send OTP
    if ($action === 'send_otp') {
        $email = trim($_POST['email'] ?? '');

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(["status" => "error", "message" => "Valid email address is required!"]);
            exit;
        }

        $check_sql = "SELECT user_id FROM users WHERE email = ?";
        $stmt = $conn->prepare($check_sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            echo json_encode(["status" => "error", "message" => "Email already registered!"]);
            $stmt->close();
            $conn->close();
            exit;
        }
        $stmt->close();

        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $otp_hash = password_hash($otp, PASSWORD_BCRYPT);
        $expired_at = date("Y-m-d H:i:s", strtotime("+5 minutes"));

        $action_type = 'register';
        $insert_otp_sql = "INSERT INTO user_otps (identifier, otp_code, action_type, expired_at) VALUES (?, ?, ?, ?)";
        $otp_stmt = $conn->prepare($insert_otp_sql);
        $otp_stmt->bind_param("ssss", $email, $otp_hash, $action_type, $expired_at);
        
        if (!$otp_stmt->execute()) {
            echo json_encode(["status" => "error", "message" => "Database error generating OTP."]);
            exit;
        }
        $otp_stmt->close();

        // use PHPMailer send email
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'moneyku6666@gmail.com';       
            $mail->Password   = 'rhdbitkdeozouibx';          
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            $mail->setFrom('moneyku6666@gmail.com', 'MoneyKu Administration');
            $mail->addAddress($email);

            $mail->isHTML(true);
            $mail->Subject = '[MoneyKu] Your Registration OTP Code';
            $mail->Body    = "Hello! Your registration code is: <b style='font-size: 24px; color: #ff0000;'>{$otp}</b>. It expires in 5 minutes.";
            $mail->AltBody = "Hello! Your registration code is: {$otp}. It expires in 5 minutes.";

            $mail->send();
            echo json_encode(["status" => "success", "message" => "OTP sent to your email!"]);
        } catch (Exception $e) {
            echo json_encode(["status" => "error", "message" => "Failed to send email. Mailer Error: " . $mail->ErrorInfo]);
        }
        $conn->close();
        exit;
    }

    // verify OTP and register user
    if ($action === 'register') {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $role = $_POST['role'] ?? 'user';
        $user_otp = trim($_POST['otp'] ?? '');

        if (empty($username) || empty($email) || empty($password) || empty($confirm_password) || empty($user_otp)) {
            echo json_encode(["status" => "error", "message" => "All fields including OTP are required!"]);
            exit;
        }

        if (strlen($password) < 6) {
            echo json_encode(["status" => "error", "message" => "Password must be at least 6 characters long!"]);
            exit;
        }

        if ($password !== $confirm_password) {
            echo json_encode(["status" => "error", "message" => "Passwords do not match!"]);
            exit;
        }

        $current_time = date("Y-m-d H:i:s");
        $otp_sql = "SELECT id, otp_code FROM user_otps WHERE identifier = ? AND action_type = 'register' AND is_used = 0 AND expired_at > ? ORDER BY id DESC LIMIT 1";
        $otp_stmt = $conn->prepare($otp_sql);
        $otp_stmt->bind_param("ss", $email, $current_time);
        $otp_stmt->execute();
        $otp_result = $otp_stmt->get_result();
       
        if ($otp_result->num_rows === 0) {
            echo json_encode(["status" => "error", "message" => "OTP expired or invalid! Please request a new one."]);
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

        // verify successful, and disable the OTP
        $update_otp_sql = "UPDATE user_otps SET is_used = 1 WHERE id = ?";
        $update_otp_stmt = $conn->prepare($update_otp_sql);
        $update_otp_stmt->bind_param("i", $otp_row['id']);
        $update_otp_stmt->execute();
        $update_otp_stmt->close();

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
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MoneyKu - Register</title>
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
        <h2>Get Started</h2>
        <p class="subtitle">Create your MoneyKu account</p>

        <form id="registerForm">
            <input type="hidden" id="formAction" name="action" value="register">

            <div class="form-group">
                <input type="text" id="username" name="username" placeholder=" " required maxlength="50">
                <label for="username">Username</label>
            </div>
            
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
                <input type="password" id="password" name="password" placeholder=" " required minlength="6">
                <label for="password">Password</label>
                <span class="toggle-password" id="togglePassword">
                    <img src="../images/hide_password.png" alt="Toggle Password">
                </span>
            </div>

            <div class="form-group password-wrapper">
                <input type="password" id="confirm_password" name="confirm_password" placeholder=" " required minlength="6">
                <label for="confirm_password">Confirm Password</label>
                <span class="toggle-password" id="toggleConfirmPassword">
                    <img src="../images/hide_password.png" alt="Toggle Password">
                </span>
            </div>
            
            <button type="submit" class="btn-submit" id="submit_button">Create Account</button>
        </form>

        <p class="footer-text">
            Already have an account? <a href="login.php">Sign In</a>
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
        showHidePassword('toggleConfirmPassword', 'confirm_password');

        function showToast(message, type = 'success') {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            toast.className = `toast show ${type}`;
            setTimeout(() => { toast.classList.remove('show'); }, 3000);
        }

        // send OTP button click event
        document.getElementById('sendOtpBtn').addEventListener('click', function () {
            const emailInput = document.getElementById('email');
            if (!emailInput.value || !emailInput.checkValidity()) {
                showToast('Please enter a valid email address first.', 'error');
                return;
            }

            const sendBtn = this;
            sendBtn.disabled = true;
            sendBtn.textContent = 'Sending...';

            const otpData = new FormData();
            otpData.append('action', 'send_otp');
            otpData.append('email', emailInput.value);

            fetch('register.php', {
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
                showToast('Failed to send OTP. Network error.', 'error');
                sendBtn.disabled = false;
                sendBtn.textContent = 'Send OTP';
            });
        });

        document.getElementById('registerForm').addEventListener('submit', function (e) {
            e.preventDefault();
            
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                showToast('Passwords do not match! Please check again.', 'error');
                return;
            }

            const btn = document.getElementById('submit_button');
            btn.disabled = true;
            btn.textContent = 'Creating...';

            const formData = new FormData(this);
            formData.set('action', 'register');

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