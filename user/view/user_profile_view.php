<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Money'K'u - User Profile</title>
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
            <a href="user_feedback.php" class="btn-feedback-trigger">
                <span class="icon">💬</span> Share Feedback
            </a>
        </header>

        <section class="profile-container">
            <div class="profile-card-header">
                <h2>Personal Information</h2>
                <p>Update your details to keep your financial plan accurate.</p>
            </div>

            <form id="profileForm" enctype="multipart/form-data">
                <!-- Profile Picture Section -->
                <div class="profile-avatar-section">
                    <div class="avatar-container">
                        <?php if (!empty($profile['profile_picture']) && file_exists(__DIR__ . '/../uploads/avatars/' . $profile['profile_picture'])): ?>
                            <img id="avatarPreview" src="../uploads/avatars/<?php echo htmlspecialchars($profile['profile_picture']); ?>" alt="Avatar" class="profile-avatar-img">
                        <?php else: ?>
                            <div id="avatarPlaceholder" class="profile-avatar-placeholder"><?php echo strtoupper(substr($username, 0, 1)); ?></div>
                        <?php endif; ?>
                        
                        <!-- Circular camera badge trigger -->
                        <label for="profile_picture_input" class="avatar-edit-badge" title="Upload Profile Picture">
                            <svg class="camera-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path>
                                <circle cx="12" cy="13" r="4"></circle>
                            </svg>
                        </label>
                    </div>
                    <input type="file" id="profile_picture_input" name="profile_picture" accept="image/*" style="display: none;">
                    <p class="avatar-tip">Click the camera badge to choose a file</p>
                </div>

                <div class="form-grid">
                    <div class="form-group-p">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" value="<?php echo htmlspecialchars($profile['email']); ?>" disabled style="background-color: #f1f5f9; color: var(--text-muted); cursor: not-allowed; border-color: var(--borders);">
                    </div>

                    <div class="form-group-p">
                        <label for="full_name">Full Name *</label>
                        <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($profile['full_name']); ?>" required placeholder="e.g. John Doe">
                    </div>

                    <div class="form-group-p">
                        <label for="occupation">Occupation *</label>
                        <input type="text" id="occupation" name="occupation" value="<?php echo htmlspecialchars($profile['occupation']); ?>" required placeholder="e.g. Software Engineer">
                    </div>

                    <div class="form-group-p">
                        <label for="date_of_birth">Date of Birth *</label>
                        <input type="date" id="date_of_birth" required name="date_of_birth" value="<?php echo $profile['date_of_birth']; ?>">
                    </div>

                    <div class="form-group-p">
                        <label for="gender">Gender *</label>
                        <select id="gender" name="gender" required>
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
                <p>Update your password to keep your credentials secure.</p>
            </div>
            
            <form id="passwordForm">
                <div class="form-grid">
                    <div class="form-group-p password-wrapper-p">
                        <label for="current_password">Current Password *</label>
                        <div class="input-with-eye">
                            <input type="password" id="current_password" name="current_password" required placeholder="Enter current password">
                            <span class="toggle-password-eye" onclick="togglePasswordVisibility('current_password', this)">
                                <img src="../../images/hide_password.png" alt="Toggle Password">
                            </span>
                        </div>
                    </div>
                    
                    <div class="form-group-p password-wrapper-p">
                        <label for="new_password">New Password *</label>
                        <div class="input-with-eye">
                            <input type="password" id="new_password" name="new_password" required placeholder="At least 6 characters">
                            <span class="toggle-password-eye" onclick="togglePasswordVisibility('new_password', this)">
                                <img src="../../images/hide_password.png" alt="Toggle Password">
                            </span>
                        </div>
                    </div>
                    
                    <div class="form-group-p password-wrapper-p">
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

        <section class="danger-zone-container">
            <div class="danger-zone-header">
                <h2>Delete Account</h2>
            </div>
            <div class="danger-zone-content">
                <div class="danger-info">
                    <p>Delete your account and all data. This action cannot be undone.</p>
                </div>
                <button type="button" class="btn-delete-account" id="deleteAccountBtn">Delete Account</button>
            </div>
        </section>
    </main>

    <!-- Delete Account Custom Modal -->
    <div id="deleteModal" class="modal-overlay">
        <div class="modal-card">
            <button class="modal-close-btn" id="modalCloseBtn">&times;</button>
            
            <!-- Step 1: Confirmation -->
            <div id="deleteStep1" class="modal-step active">
                <h2>Delete Account?</h2>
                <p class="modal-warning-text">Are you sure? All your data will be permanently deleted.</p>
                <div class="modal-actions">
                    <button type="button" class="btn-modal-cancel" id="cancelBtnStep1">No, Keep Account</button>
                    <button type="button" class="btn-modal-danger" id="confirmBtnStep1">Yes, Delete</button>
                </div>
            </div>
            
             <!-- confirm password and delete -->
            <div id="deleteStep2" class="modal-step">
        
                <h2>Verify Password</h2>
                <p>Please enter your current password to authorize this action.</p>
                <div class="modal-input-wrapper">
                    <input type="password" id="deletePasswordInput" placeholder="Current Password" autocomplete="current-password">
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn-modal-cancel" id="cancelBtnStep2">Cancel</button>
                    <button type="button" class="btn-modal-confirm-delete" id="submitDeleteBtn">Confirm Permanent Deletion</button>
                </div>
            </div>
        </div>
    </div>



    <script>
        function showToast(message, type = 'success') {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            toast.className = `toast show ${type}`;
            setTimeout(() => { toast.classList.remove('show'); }, 3000);
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
                        previewImg.className = 'profile-avatar-img';
                        previewImg.alt = 'Avatar';
                        
                        if (placeholder) {
                            placeholder.parentNode.insertBefore(previewImg, placeholder);
                            placeholder.remove();
                        } else {
                            document.querySelector('.avatar-container').prepend(previewImg);
                        }
                    }
                    previewImg.src = event.target.result;
                };
                reader.readAsDataURL(file);
            }
        });

        // Save Changes Form Submit (AJAX)
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
                    // Automatically reload the sidebar profile if exists after 1 sec
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

        // Account Deletion Modal Controls
        const deleteModal = document.getElementById('deleteModal');
        const deleteAccountBtn = document.getElementById('deleteAccountBtn');
        const modalCloseBtn = document.getElementById('modalCloseBtn');
        const cancelBtnStep1 = document.getElementById('cancelBtnStep1');
        const confirmBtnStep1 = document.getElementById('confirmBtnStep1');
        const cancelBtnStep2 = document.getElementById('cancelBtnStep2');
        const submitDeleteBtn = document.getElementById('submitDeleteBtn');
        const deleteStep1 = document.getElementById('deleteStep1');
        const deleteStep2 = document.getElementById('deleteStep2');
        const deletePasswordInput = document.getElementById('deletePasswordInput');

        // Open Modal
        deleteAccountBtn.addEventListener('click', () => {
            deleteModal.classList.add('show');
            deleteStep1.classList.add('active');
            deleteStep2.classList.remove('active');
            deletePasswordInput.value = '';
        });

        // Close Modal Helper
        const closeModal = () => {
            deleteModal.classList.remove('show');
            setTimeout(() => {
                deleteStep1.classList.remove('active');
                deleteStep2.classList.remove('active');
            }, 300);
        };

        modalCloseBtn.addEventListener('click', closeModal);
        cancelBtnStep1.addEventListener('click', closeModal);
        cancelBtnStep2.addEventListener('click', closeModal);

        // Click outside to close
        deleteModal.addEventListener('click', (e) => {
            if (e.target === deleteModal) {
                closeModal();
            }
        });

        // Step 1 Yes -> Step 2
        confirmBtnStep1.addEventListener('click', () => {
            deleteStep1.classList.remove('active');
            deleteStep2.classList.add('active');
            deletePasswordInput.focus();
        });

        // Step 2 Submit Password via AJAX
        submitDeleteBtn.addEventListener('click', () => {
            const password = deletePasswordInput.value;
            if (!password) {
                showToast('Please enter your password!', 'error');
                return;
            }

            submitDeleteBtn.disabled = true;
            submitDeleteBtn.textContent = 'Deleting Account...';

            const formData = new FormData();
            formData.append('action', 'delete_account');
            formData.append('password', password);

            fetch('user_profile.php', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    showToast(data.message, 'success');
                    setTimeout(() => {
                        window.location.href = '../../public/login.php';
                    }, 1500);
                } else {
                    showToast(data.message, 'error');
                    submitDeleteBtn.disabled = false;
                    submitDeleteBtn.textContent = 'Confirm Permanent Deletion';
                }
            })
            .catch(() => {
                showToast('Network error, please try again.', 'error');
                submitDeleteBtn.disabled = false;
                submitDeleteBtn.textContent = 'Confirm Permanent Deletion';
            });
        });

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

        // Change Password Form Submit (AJAX)
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
            btn.textContent = 'Updating...';

            const formData = new FormData(this);
            formData.append('action', 'change_password');

            fetch('user_profile.php', {
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
                        if (img) img.src = '../images/hide_password.png';
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