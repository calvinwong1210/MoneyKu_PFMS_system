<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Admin - MoneyKu</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/admin_sidebar.css">
    <link rel="stylesheet" href="../css/admin_register.css">
</head>
<body>

    <!-- Admin Sidebar navigation -->
    <?php require_once '../admin_sidebar.php'; ?>

    <main class="main-content">
        <header class="main-header">
            <div class="welcome-text">
                <h1>Administrative Credentials</h1>
                <p>Configure and register a new administrator account with root dashboard access privileges.</p>
            </div>
        </header>

        <section class="register-container">
            <div class="register-card-header">
                <h2>Register New Admin</h2>
                <p>Please enter details to set up another security administrator credentials.</p>
            </div>

            <form id="registerAdminForm">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="username">Username *</label>
                        <input type="text" id="username" name="username" required placeholder="Enter administrative username" autocomplete="off">
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address *</label>
                        <input type="email" id="email" name="email" required placeholder="admin@moneyku.com" autocomplete="off">
                    </div>

                    <div class="form-group password-wrapper">
                        <label for="password">Password *</label>
                        <div class="input-with-eye">
                            <input type="password" id="password" name="password" required placeholder="Minimum 6 characters">
                            <span class="toggle-password-eye" onclick="togglePasswordVisibility('password', this)">
                                <img src="../../images/hide_password.png" alt="Toggle Password">
                            </span>
                        </div>
                    </div>

                    <div class="form-group password-wrapper">
                        <label for="confirm_password">Confirm Password *</label>
                        <div class="input-with-eye">
                            <input type="password" id="confirm_password" name="confirm_password" required placeholder="Confirm administrative password">
                            <span class="toggle-password-eye" onclick="togglePasswordVisibility('confirm_password', this)">
                                <img src="../../images/hide_password.png" alt="Toggle Password">
                            </span>
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-submit" id="registerBtn">Create Admin Account</button>
                </div>
            </form>
        </section>
    </main>

    <!-- Toast alert message element -->
    <div id="toast" class="toast"></div>

    <script>
        function showToast(message, type = 'success') {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            toast.className = `toast show ${type}`;
            setTimeout(() => { toast.classList.remove('show'); }, 3000);
        }

        function togglePasswordVisibility(inputId, eyeEl) {
            const input = document.getElementById(inputId);
            const img = eyeEl.querySelector('img');
            if (input.type === 'password') {
                input.type = 'text';
                img.src = '../../images/show_password.png';
            } else {
                input.type = 'password';
                img.src = '../../images/hide_password.png';
            }
        }

        document.getElementById('registerAdminForm').addEventListener('submit', function (e) {
            e.preventDefault();
            const btn = document.getElementById('registerBtn');
            const password = document.getElementById('password').value;
            const confirm_password = document.getElementById('confirm_password').value;

            if (password !== confirm_password) {
                showToast('Passwords do not match!', 'error');
                return;
            }

            if (password.length < 6) {
                showToast('Password must be at least 6 characters long!', 'error');
                return;
            }

            btn.disabled = true;
            btn.textContent = 'Registering Account...';

            const formData = new FormData(this);

            fetch('admin_register.php', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    showToast(data.message, 'success');
                    document.getElementById('registerAdminForm').reset();
                    // Reset all eye toggles to password type
                    ['password', 'confirm_password'].forEach(id => {
                        const input = document.getElementById(id);
                        input.type = 'password';
                        const img = input.parentElement.querySelector('.toggle-password-eye img');
                        if (img) img.src = '../../images/hide_password.png';
                    });
                } else {
                    showToast(data.message, 'error');
                }
                btn.disabled = false;
                btn.textContent = 'Create Admin Account';
            })
            .catch(() => {
                showToast('Network error, please try again.', 'error');
                btn.disabled = false;
                btn.textContent = 'Create Admin Account';
            });
        });
    </script>
</body>
</html>
