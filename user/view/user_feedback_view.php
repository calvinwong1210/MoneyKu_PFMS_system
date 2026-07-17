<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Share Feedback - MoneyKu</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/user_sidebar.css">
    <link rel="stylesheet" href="../css/user_feedback.css">
</head>
<body>

    <!-- Sidebar navigation -->
    <?php require_once '../sidebar.php'; ?>

    <main class="main-content">
        <header class="main-header">
            <div class="welcome-text">
                <h1>User Feedback</h1>
                <p>Help us improve MoneyKu by reporting bugs, requesting features, or sharing general feedback.</p>
            </div>
            <a href="user_profile.php" class="btn-back-link">
                &larr; Back to Profile
            </a>
        </header>

        <section class="feedback-container">
            <div class="feedback-card-header">
                <h2>Submit Feedback</h2>
                <p>Please fill out the form below. We read all submissions!</p>
            </div>

            <form id="feedbackForm">
                <div class="feedback-form-group">
                    <label for="feedback_type">Feedback Type *</label>
                    <select id="feedback_type" name="feedback_type" required>
                        <option value="General Feedback">General Feedback</option>
                        <option value="Bug Report">Bug Report</option>
                        <option value="Feature Request">Feature Request</option>
                    </select>
                </div>

                <div class="feedback-form-group">
                    <label for="subject">Subject *</label>
                    <input type="text" id="subject" name="subject" required placeholder="A brief summary of your feedback">
                </div>

                <div class="feedback-form-group">
                    <label for="message">Message *</label>
                    <textarea id="message" name="message" rows="6" required placeholder="Please describe your feedback in detail..."></textarea>
                </div>

                <div class="feedback-actions">
                    <button type="submit" class="btn-submit" id="submitFeedbackBtn">Submit Feedback</button>
                </div>
            </form>
        </section>
    </main>

    <!-- Toast message container -->
    <div id="toast" class="toast"></div>

    <script>
        function showToast(message, type = 'success') {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            toast.className = `toast show ${type}`;
            setTimeout(() => { toast.classList.remove('show'); }, 3000);
        }

        document.getElementById('feedbackForm').addEventListener('submit', function (e) {
            e.preventDefault();
            const btn = document.getElementById('submitFeedbackBtn');
            btn.disabled = true;
            btn.textContent = 'Submitting...';

            const formData = new FormData(this);

            fetch('user_feedback.php', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    showToast(data.message, 'success');
                    document.getElementById('feedbackForm').reset();
                } else {
                    showToast(data.message, 'error');
                }
                btn.disabled = false;
                btn.textContent = 'Submit Feedback';
            })
            .catch(() => {
                showToast('Network error, please try again.', 'error');
                btn.disabled = false;
                btn.textContent = 'Submit Feedback';
            });
        });
    </script>
</body>
</html>
