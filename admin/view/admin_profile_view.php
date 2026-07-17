<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Profile - MoneyKu</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/admin_sidebar.css">
    <link rel="stylesheet" href="../css/admin_profile.css">
</head>
<body>

    <!-- Admin Sidebar navigation -->
    <?php require_once '../admin_sidebar.php'; ?>

    <main class="main-content">
        <header class="main-header">
            <div class="welcome-text">
                <h1>Profile Settings</h1>
                <p>Manage your administrator profile details and credentials.</p>
            </div>
        </header>

        <!-- Profile Information Card -->
        <section class="profile-container">
            <div class="profile-card-header">
                <h2>Personal Details</h2>
                <p>Manage your name, birthdate, and avatar details.</p>
            </div>

            <form id="profileForm" enctype="multipart/form-data">
                <!-- Avatar Upload Section -->
                <div class="avatar-upload-container">
                    <div class="avatar-preview-wrapper">
                        <div class="avatar-circle">
                            <?php if (!empty($profile['profile_picture']) && file_exists('../uploads/avatars/' . $profile['profile_picture'])): ?>
                                <img src="../uploads/avatars/<?php echo htmlspecialchars($profile['profile_picture']); ?>" id="avatarPreview" alt="Avatar">
                            <?php else: ?>
                                <div id="avatarPlaceholder"><?php echo strtoupper(substr($username, 0, 1)); ?></div>
                            <?php endif; ?>
                        </div>
                        <label for="profile_picture_input" class="avatar-edit-badge" title="Change Avatar">
                            <span class="camera-icon">📷</span>
                        </label>
                        <input type="file" id="profile_picture_input" name="profile_picture" accept="image/*" style="display: none;">
                    </div>
                    <div class="avatar-upload-info">
                        <h3>Your Avatar Image</h3>
                        <p>Allowed formats: JPG, PNG, GIF, WEBP. Under 5MB.</p>
                    </div>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" value="<?php echo htmlspecialchars($username); ?>" disabled class="input-disabled">
                    </div>

                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="text" value="<?php echo htmlspecialchars($profile['email']); ?>" disabled class="input-disabled">
                    </div>

                    <div class="form-group">
                        <label for="full_name">Full Name *</label>
                        <input type="text" id="full_name" name="full_name" required value="<?php echo htmlspecialchars($profile['full_name']); ?>" placeholder="Enter your full name">
                    </div>

                    <div class="form-group">
                        <label for="occupation">Occupation</label>
                        <input type="text" id="occupation" name="occupation" value="<?php echo htmlspecialchars($profile['occupation']); ?>" placeholder="Enter occupation">
                    </div>

                    <div class="form-group">
                        <label for="date_of_birth">Date of Birth</label>
                        <input type="date" id="date_of_birth" name="date_of_birth" value="<?php echo htmlspecialchars($profile['date_of_birth']); ?>">
                    </div>

                    <div class="form-group">
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

        <!-- Change Password Section -->
        <section class="profile-container password-change-container" style="margin-top: 32px;">
            <div class="profile-card-header">
                <h2>Change Password</h2>
                <p>Update your password to keep your administrator credentials secure.</p>
            </div>
            
            <form id="passwordForm">
                <div class="form-grid">
                    <div class="form-group password-wrapper">
                        <label for="current_password">Current Password *</label>
                        <div class="input-with-eye">
                            <input type="password" id="current_password" name="current_password" required placeholder="Enter current password">
                            <span class="toggle-password-eye" onclick="togglePasswordVisibility('current_password', this)">
                                <img src="../../images/hide_password.png" alt="Toggle Password">
                            </span>
                        </div>
                    </div>
                    
                    <div class="form-group password-wrapper">
                        <label for="new_password">New Password *</label>
                        <div class="input-with-eye">
                            <input type="password" id="new_password" name="new_password" required placeholder="At least 6 characters">
                            <span class="toggle-password-eye" onclick="togglePasswordVisibility('new_password', this)">
                                <img src="../../images/hide_password.png" alt="Toggle Password">
                            </span>
                        </div>
                    </div>
                    
                    <div class="form-group password-wrapper">
                        <label for="confirm_password">Confirm New Password *</label>
                        <div class="input-with-eye">
                            <input type="password" id="confirm_password" name="confirm_password" required placeholder="Re-enter new password">
                            <span class="toggle-password-eye" onclick="togglePasswordVisibility('confirm_password', this)">
                                <img src="../../images/hide_password.png" alt="Toggle Password">
                            </span>
                        </div>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn-save" id="changePasswordBtn">Update Password</button>
                </div>
            </form>
        </section>
    </main>

    <!-- Toast alert message container -->
    <div id="toast" class="toast"></div>

    <script>
        function showToast(message, type = 'success') {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            toast.className = `toast show ${type}`;
            setTimeout(() => { toast.classList.remove('show'); }, 3000);
        }

        // Toggle Password Visibility
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

        // Image Preview Handler
        document.getElementById('profile_picture_input').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                if (file.size > 5 * 1024 * 1024) {
                    showToast('Image size must be less than 5MB', 'error');
                    this.value = '';
                    return;
                }
                
                const reader = new FileReader();
                reader.onload = function(event) {
                    let previewImg = document.getElementById('avatarPreview');
                    const placeholder = document.getElementById('avatarPlaceholder');
                    
                    if (!previewImg) {
                        previewImg = document.createElement('img');
                        previewImg.id = 'avatarPreview';
                        previewImg.alt = 'Avatar';
                        placeholder.parentNode.replaceChild(previewImg, placeholder);
                    }
                    previewImg.src = event.target.result;
                };
                reader.readAsDataURL(file);
            }
        });

        // Submit Profile form
        document.getElementById('profileForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = document.getElementById('saveBtn');
            btn.disabled = true;
            btn.textContent = 'Saving Changes...';

            const formData = new FormData(this);

            fetch('admin_profile.php', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    showToast(data.message, 'success');
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    showToast(data.message, 'error');
                    btn.disabled = false;
                    btn.textContent = 'Save Changes';
                }
            })
            .catch(() => {
                showToast('Network error, please try again.', 'error');
                btn.disabled = false;
                btn.textContent = 'Save Changes';
            });
        });

        // Submit Password Form
        document.getElementById('passwordForm').addEventListener('submit', function (e) {
            e.preventDefault();
            const btn = document.getElementById('changePasswordBtn');
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;

            if (newPassword !== confirmPassword) {
                showToast('New passwords do not match!', 'error');
                return;
            }

            if (newPassword.length < 6) {
                showToast('New password must be at least 6 characters long!', 'error');
                return;
            }

            btn.disabled = true;
            btn.textContent = 'Updating Password...';

            const formData = new FormData(this);
            formData.append('action', 'change_password');

            fetch('admin_profile.php', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    showToast(data.message, 'success');
                    document.getElementById('passwordForm').reset();
                    // Reset all eye toggles to password type
                    ['current_password', 'new_password', 'confirm_password'].forEach(id => {
                        const input = document.getElementById(id);
                        input.type = 'password';
                        const wrapper = input.parentElement;
                        const img = wrapper.querySelector('.toggle-password-eye img');
                        if (img) img.src = '../../images/hide_password.png';
                    });
                } else {
                    showToast(data.message, 'error');
                }
                btn.disabled = false;
                btn.textContent = 'Update Password';
            })
            .catch(() => {
                showToast('Network error, please try again.', 'error');
                btn.disabled = false;
                btn.textContent = 'Update Password';
            });
        });
    </script>
</body>
</html>
