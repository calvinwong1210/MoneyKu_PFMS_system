<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Feedback - MoneyKu</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/admin_sidebar.css">
    <link rel="stylesheet" href="../css/admin_feedback.css">
</head>
<body>

    <!-- Admin Sidebar navigation -->
    <?php require_once '../admin_sidebar.php'; ?>

    <main class="main-content">
        <header class="main-header">
            <div class="welcome-text">
                <h1>User Feedback Logs</h1>
                <p>Monitor submissions, feature requests, and bug reports submitted by users.</p>
            </div>
        </header>

        <!-- Feedback List Card -->
        <section class="feedback-card">
            <div class="card-header-actions">
                <h2>Feedback Log Database</h2>
                <span class="count-badge"><?php echo count($feedbacks); ?> entries</span>
            </div>

            <div class="table-wrapper">
                <table class="feedback-table">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>User</th>
                            <th>Subject</th>
                            <th>Message Preview</th>
                            <th>Submitted Date</th>
                            <th style="text-align: center;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($feedbacks)): ?>
                            <tr>
                                <td colspan="6" class="empty-state">No feedback entries found in the system.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($feedbacks as $f): ?>
                                <tr id="row-<?php echo $f['feedback_id']; ?>">
                                    <td>
                                        <?php 
                                        $class = 'badge-general';
                                        if ($f['feedback_type'] === 'Bug Report') $class = 'badge-bug';
                                        if ($f['feedback_type'] === 'Feature Request') $class = 'badge-feature';
                                        ?>
                                        <span class="type-badge <?php echo $class; ?>"><?php echo htmlspecialchars($f['feedback_type']); ?></span>
                                    </td>
                                    <td>
                                        <div class="user-cell">
                                            <span class="username">@<?php echo htmlspecialchars($f['username']); ?></span>
                                            <span class="email"><?php echo htmlspecialchars($f['email']); ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="subject-text"><?php echo htmlspecialchars($f['subject']); ?></span>
                                    </td>
                                    <td>
                                        <span class="message-preview">
                                            <?php 
                                            $msg = $f['message'];
                                            echo htmlspecialchars(strlen($msg) > 60 ? substr($msg, 0, 60) . '...' : $msg);
                                            ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="date-text"><?php echo date('Y-m-d H:i', strtotime($f['created_at'])); ?></span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-action-view" onclick="viewFeedback(<?php echo htmlspecialchars(json_encode($f)); ?>)" title="View Full Details">👁️ View</button>
                                            <button class="btn-action-delete" onclick="confirmDeleteFeedback(<?php echo $f['feedback_id']; ?>)" title="Delete Feedback">🗑️ Delete</button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <!-- Details Modal overlay -->
    <div id="detailsModal" class="modal-overlay">
        <div class="modal-card">
            <button class="modal-close-btn" onclick="closeDetailsModal()">&times;</button>
            <h2 id="modalTitle">Feedback Details</h2>
            <div class="modal-metadata">
                <div class="meta-item">
                    <strong>Type:</strong> <span id="modalType" class="type-badge"></span>
                </div>
                <div class="meta-item">
                    <strong>User:</strong> <span id="modalUser"></span>
                </div>
                <div class="meta-item">
                    <strong>Submitted:</strong> <span id="modalDate"></span>
                </div>
            </div>
            <div class="modal-subject-box">
                <strong>Subject:</strong> <span id="modalSubject"></span>
            </div>
            <div class="modal-message-box">
                <p id="modalMessage"></p>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn-modal-close" onclick="closeDetailsModal()">Close Details</button>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal overlay -->
    <div id="deleteModal" class="modal-overlay">
        <div class="modal-card confirmation-card">
            <h2>Delete Feedback Entry?</h2>
            <p>Are you sure you want to delete this feedback log? This action is permanent.</p>
            <input type="hidden" id="deleteTargetId">
            <div class="modal-actions" style="margin-top: 24px;">
                <button type="button" class="btn-modal-cancel" onclick="closeDeleteModal()">Cancel</button>
                <button type="button" class="btn-modal-confirm-delete" id="deleteBtn" onclick="executeDeleteFeedback()">Confirm Delete</button>
            </div>
        </div>
    </div>

    <!-- Toast elements -->
    <div id="toast" class="toast"></div>

    <script>
        function showToast(message, type = 'success') {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            toast.className = `toast show ${type}`;
            setTimeout(() => { toast.classList.remove('show'); }, 3000);
        }

        // View feedback details
        function viewFeedback(feedback) {
            document.getElementById('modalType').textContent = feedback.feedback_type;
            
            // Set class
            let classType = 'badge-general';
            if (feedback.feedback_type === 'Bug Report') classType = 'badge-bug';
            if (feedback.feedback_type === 'Feature Request') classType = 'badge-feature';
            document.getElementById('modalType').className = `type-badge ${classType}`;

            document.getElementById('modalUser').textContent = `@${feedback.username} (${feedback.email})`;
            document.getElementById('modalDate').textContent = feedback.created_at;
            document.getElementById('modalSubject').textContent = feedback.subject;
            document.getElementById('modalMessage').textContent = feedback.message;

            document.getElementById('detailsModal').classList.add('show');
        }

        function closeDetailsModal() {
            document.getElementById('detailsModal').classList.remove('show');
        }

        // Delete feedback modal
        function confirmDeleteFeedback(id) {
            document.getElementById('deleteTargetId').value = id;
            document.getElementById('deleteModal').classList.add('show');
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.remove('show');
        }

        function executeDeleteFeedback() {
            const id = document.getElementById('deleteTargetId').value;
            const btn = document.getElementById('deleteBtn');
            
            btn.disabled = true;
            btn.textContent = 'Deleting...';

            const formData = new FormData();
            formData.append('action', 'delete_feedback');
            formData.append('feedback_id', id);

            fetch('admin_feedback.php', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    showToast(data.message, 'success');
                    // Remove row from table
                    const row = document.getElementById(`row-${id}`);
                    if (row) row.remove();
                    closeDeleteModal();
                } else {
                    showToast(data.message, 'error');
                }
                btn.disabled = false;
                btn.textContent = 'Confirm Delete';
            })
            .catch(() => {
                showToast('Network error, please try again.', 'error');
                btn.disabled = false;
                btn.textContent = 'Confirm Delete';
            });
        }

        // Click outside modal to close
        window.addEventListener('click', (e) => {
            const details = document.getElementById('detailsModal');
            const del = document.getElementById('deleteModal');
            if (e.target === details) closeDetailsModal();
            if (e.target === del) closeDeleteModal();
        });
    </script>
</body>
</html>
