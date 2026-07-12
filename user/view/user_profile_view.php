<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PFMS - User Profile</title>
    <link rel="stylesheet" href="../css/user_sidebar.css">
    <link rel="stylesheet" href="../css/user_profile.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="dashboard-body">

    <div id="toast" class="toast"></div>

    <?php require_once '../sidebar.php'; ?>

    <main class="main-content">
        <header class="main-header">
            <div class="welcome-text">
                <h1>Account Settings</h1>
                <p>Manage your personal profile and identity credentials.</p>
            </div>
        </header>

        <section class="profile-container">
            <div class="profile-card-header">
                <h2>Personal Information</h2>
                <p>Update your details to keep your financial plan accurate.</p>
            </div>

            <form id="profileForm">
                <div class="form-grid">
                    <div class="form-group-p">
                        <label for="full_name">Full Name *</label>
                        <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($profile['full_name']); ?>" required placeholder="e.g. Calvin Wong">
                    </div>

                    <div class="form-group-p">
                        <label for="occupation">Occupation</label>
                        <input type="text" id="occupation" name="occupation" value="<?php echo htmlspecialchars($profile['occupation']); ?>" placeholder="e.g. Software Engineer">
                    </div>

                    <div class="form-group-p">
                        <label for="phone_number">Phone Number</label>
                        <input type="text" id="phone_number" name="phone_number" value="<?php echo htmlspecialchars($profile['phone_number']); ?>" placeholder="e.g. +60123456789">
                    </div>

                    <div class="form-group-p">
                        <label for="date_of_birth">Date of Birth</label>
                        <input type="date" id="date_of_birth" name="date_of_birth" value="<?php echo $profile['date_of_birth']; ?>">
                    </div>

                    <div class="form-group-p">
                        <label for="gender">Gender</label>
                        <select id="gender" name="gender">
                            <option value="">Select Gender</option>
                            <option value="Male" <?php echo $profile['gender'] === 'Male' ? 'selected' : ''; ?>>Male</option>
                            <option value="Female" <?php echo $profile['gender'] === 'Female' ? 'selected' : ''; ?>>Female</option>
                            <option value="Other" <?php echo $profile['gender'] === 'Other' ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-save" id="saveBtn">Save Changes</button>
                </div>
            </form>
        </section>
    </main>

    <script>
        function showToast(message, type = 'success') {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            toast.className = `toast show ${type}`;
            setTimeout(() => { toast.classList.remove('show'); }, 3000);
        }

        // AJAX 提交表单
        document.getElementById('profileForm').addEventListener('submit', function (e) {
            e.preventDefault();
            const btn = document.getElementById('saveBtn');
            btn.disabled = true;
            btn.textContent = 'Saving...';

            const formData = new FormData(this);

            fetch('user_profile.php', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(res => res.json())
            .then(data => {
                if(data.status === 'success') {
                    showToast(data.message, 'success');
                } else {
                    showToast(data.message, 'error');
                }
                btn.disabled = false;
                btn.textContent = 'Save Changes';
            })
            .catch(() => {
                showToast('Network error, please try again.', 'error');
                btn.disabled = false;
                btn.textContent = 'Save Changes';
            });
        });
    </script>
</body>
</html>