<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MoneyKu - Manage Transactions</title>
    <link rel="stylesheet" href="../css/user_sidebar.css">
    <link rel="stylesheet" href="../css/user_transaction.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="dashboard-body">

    <!-- Global Layout Shared Navigation -->
    <?php require_once '../sidebar.php'; ?>

    <!-- Async Notification Toast Popup -->
    <div id="toast" class="toast"></div>

    <!-- Main Workspace Frame -->
    <main class="main-content">
        <header class="workspace-header">
            <h1>Transaction Manager</h1>
            <p>Log your daily financial cashflows and track your category streams.</p>
        </header>

        <div class="workspace-layout">
            
            <!-- Left Panel: Premium Card Form -->
            <section class="form-card">
                <h2>Add New Transaction</h2>
                <form id="transactionForm">
                    
                    <div class="form-group has-select">
                        <select id="transaction_type" name="transaction_type" required>
                            <option value="" disabled selected>Select Type...</option>
                            <option value="expense">Expense (-)</option>
                            <option value="income">Income (+)</option>
                        </select>
                        <label for="transaction_type">Type</label>
                    </div>

                    <div class="form-group has-select">
                        <select id="category" name="category" required disabled>
                            <option value="" disabled selected>Select category...</option>
                        </select>
                        <label for="category">Category</label>
                    </div>

                    <div class="form-group">
                        <input type="number" id="amount" name="amount" step="0.01" min="0.01" placeholder=" " required>
                        <label for="amount">Amount (RM)</label>
                    </div>

                    <div class="form-group has-select">
                        <input type="text" id="transaction_date" name="transaction_date" value="<?php echo date('Y-m-d'); ?>" readonly style="background: #f1f5f9; color: #94a3b8; cursor: not-allowed;">
                        <label for="transaction_date">Date (Today)</label>
                    </div>

                    <div class="form-group">
                        <input type="text" id="description" name="description" placeholder=" " autocomplete="off" maxlength="255">
                        <label for="description">Optional Description</label>
                    </div>

                    <button type="submit" class="btn-submit" id="submit_button">Save Transaction</button>
                </form>
            </section>

            <!-- Right Panel: Data Table Logs -->
            <section class="table-card">
                <h2>Transaction History</h2>
                <div class="table-wrapper">
                    <table class="transaction-table" id="txTable">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Category</th>
                                <th>Description</th>
                                <th class="text-right">Amount</th>
                                <th class="text-center" style="width: 100px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($user_transactions)): ?>
                                <tr class="empty-row"><td colspan="5" class="text-center">No transactions logged yet.</td></tr>
                            <?php else: ?>
                                <?php foreach ($user_transactions as $tx): 
                                    $current_month = date('Y-m');
                                    $tx_month = date('Y-m', strtotime($tx['transaction_date']));
                                    $can_modify = ($tx_month >= $current_month && $tx['category'] !== 'Savings' && $tx['category'] !== 'Refund');
                                ?>
                                <tr>
                                    <td class="tx-date"><?php echo htmlspecialchars($tx['transaction_date']); ?></td>
                                    <td><span class="badge badge-category"><?php echo htmlspecialchars($tx['category']); ?></span></td>
                                    <td class="tx-desc">
                                        <?php if (mb_strlen($tx['description']) > 10): ?>
                                            <span class="tooltip-trigger" data-tooltip="<?php echo htmlspecialchars($tx['description']); ?>">
                                                <?php echo htmlspecialchars(mb_substr($tx['description'], 0, 15)) . '...'; ?>
                                            </span>
                                        <?php else: ?>
                                            <?php echo htmlspecialchars($tx['description'] ?: '—'); ?>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-middle tx-amount <?php echo $tx['transaction_type'] === 'income' ? 'positive' : 'negative'; ?>">
                                        <?php echo ($tx['transaction_type'] === 'income' ? '+RM' : '-RM') . number_format($tx['amount'], 2); ?>
                                    </td>
                                    <td class="text-center">
                                        <div class="tx-actions">
                                            <?php if ($can_modify): ?>
                                                <button class="btn-action edit-btn" 
                                                        data-id="<?php echo $tx['transaction_id']; ?>" 
                                                        data-type="<?php echo $tx['transaction_type']; ?>" 
                                                        data-category="<?php echo htmlspecialchars($tx['category']); ?>" 
                                                        data-amount="<?php echo $tx['amount']; ?>" 
                                                        data-date="<?php echo $tx['transaction_date']; ?>" 
                                                        data-desc="<?php echo htmlspecialchars($tx['description']); ?>"
                                                        title="Edit">✏️</button>
                                                <button class="btn-action delete-btn" 
                                                        data-id="<?php echo $tx['transaction_id']; ?>"
                                                        title="Delete">🗑️</button>
                                            <?php else: ?>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="user_transaction.php?page=<?php echo $page - 1; ?>" class="page-link">Previous</a>
                        <?php endif; ?>

                        <?php if ($page < $total_pages): ?>
                            <a href="user_transaction.php?page=<?php echo $page + 1; ?>" class="page-link">Next</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </section>

        </div>
    </main>

    <!-- Edit Modal Overlay -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit Transaction</h2>
                <button type="button" class="close-btn" id="closeModalBtn">&times;</button>
            </div>
            <form id="editTransactionForm">
                <input type="hidden" id="edit_transaction_id" name="transaction_id">
                <input type="hidden" name="action" value="edit">

                <div class="form-group has-select">
                    <select id="edit_transaction_type" name="transaction_type" required>
                        <option value="expense">Expense (-)</option>
                        <option value="income">Income (+)</option>
                    </select>
                    <label for="edit_transaction_type">Type</label>
                </div>

                <div class="form-group has-select">
                    <select id="edit_category" name="category" required>
                        <option value="" disabled selected>Select category...</option>
                    </select>
                    <label for="edit_category">Category</label>
                </div>

                <div class="form-group">
                    <input type="number" id="edit_amount" name="amount" step="0.01" min="0.01" placeholder=" " required>
                    <label for="edit_amount">Amount (RM)</label>
                </div>

                <div class="form-group has-select">
                    <input type="text" id="edit_transaction_date" name="transaction_date" readonly style="background: #f1f5f9; color: #94a3b8; cursor: not-allowed;">
                    <label for="edit_transaction_date">Date</label>
                </div>

                <div class="form-group">
                    <input type="text" id="edit_description" name="description" placeholder=" " autocomplete="off" maxlength="255">
                    <label for="edit_description">Optional Description</label>
                </div>

                <button type="submit" class="btn-submit" id="editSubmitBtn">Save Changes</button>
            </form>
        </div>
    </div>

    <!-- AJAX Form Controller AJAX Operations -->
    <script>
        const incomeCategories = ["Salary", "Bonus", "Business", "Investment", "Allowance", "Gift", "Others"];
        const expenseCategories = ["Food", "Transport", "Shopping", "Entertainment", "Bills", "Healthcare", "Education", "Housing", "Insurance", "Travel", "Student Loan", "Others"];

        function showToast(message, type = 'success') {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            toast.className = `toast show ${type}`;
            setTimeout(() => { toast.classList.remove('show'); }, 3000);
        }

        // Populates a category select dropdown dynamically
        function updateCategories(type, selectEl, currentVal = '') {
            selectEl.innerHTML = '';
            
            const placeholder = document.createElement('option');
            placeholder.value = '';
            placeholder.textContent = 'Select category...';
            placeholder.disabled = true;
            placeholder.selected = !currentVal;
            selectEl.appendChild(placeholder);

            let list = [];
            if (type === 'income') {
                list = incomeCategories;
            } else if (type === 'expense') {
                list = expenseCategories;
            }

            if (list.length > 0) {
                selectEl.disabled = false;
                list.forEach(cat => {
                    const opt = document.createElement('option');
                    opt.value = cat;
                    opt.textContent = cat;
                    if (cat === currentVal) {
                        opt.selected = true;
                    }
                    selectEl.appendChild(opt);
                });
            } else {
                selectEl.disabled = true;
            }
        }

        // Add form category trigger
        const typeSelect = document.getElementById('transaction_type');
        const categorySelect = document.getElementById('category');
        typeSelect.addEventListener('change', function () {
            updateCategories(this.value, categorySelect);
        });

        // Add form AJAX Submission
        document.getElementById('transactionForm').addEventListener('submit', function (e) {
            e.preventDefault();
            const btn = document.getElementById('submit_button');
            btn.disabled = true;
            btn.textContent = 'Saving...';

            const formData = new FormData(this);
            formData.append('action', 'add'); // Route to Add action

            fetch('user_transaction.php', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(res => res.json())
            .then(data => {
                if(data.status === 'success') {
                    showToast(data.message, 'success');
                    setTimeout(() => { window.location.reload(); }, 800);
                } else {
                    showToast(data.message, 'error');
                    btn.disabled = false;
                    btn.textContent = 'Save Transaction';
                }
            })
            .catch(() => {
                showToast('Network sync error.', 'error');
                btn.disabled = false;
                btn.textContent = 'Save Transaction';
            });
        });

        // Edit Modal Triggering
        const editModal = document.getElementById('editModal');
        const editForm = document.getElementById('editTransactionForm');
        const editTypeSelect = document.getElementById('edit_transaction_type');
        const editCategorySelect = document.getElementById('edit_category');

        // Close Modal
        document.getElementById('closeModalBtn').addEventListener('click', () => {
            editModal.classList.remove('open');
        });
        window.addEventListener('click', (e) => {
            if (e.target === editModal) {
                editModal.classList.remove('open');
            }
        });

        // Dynamic categories in Edit Modal
        editTypeSelect.addEventListener('change', function() {
            updateCategories(this.value, editCategorySelect);
        });

        // Open modal and pre-fill fields
        document.querySelectorAll('.edit-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const type = this.getAttribute('data-type');
                const category = this.getAttribute('data-category');
                const amount = this.getAttribute('data-amount');
                const date = this.getAttribute('data-date');
                const desc = this.getAttribute('data-desc');

                document.getElementById('edit_transaction_id').value = id;
                editTypeSelect.value = type;
                document.getElementById('edit_amount').value = amount;
                document.getElementById('edit_transaction_date').value = date;
                document.getElementById('edit_description').value = desc;

                // Load options and pre-select current category
                updateCategories(type, editCategorySelect, category);

                editModal.classList.add('open');
            });
        });

        // Edit form AJAX Submission
        editForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = document.getElementById('editSubmitBtn');
            btn.disabled = true;
            btn.textContent = 'Saving Changes...';

            const formData = new FormData(this);

            fetch('user_transaction.php', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    showToast(data.message, 'success');
                    setTimeout(() => { window.location.reload(); }, 800);
                } else {
                    showToast(data.message, 'error');
                    btn.disabled = false;
                    btn.textContent = 'Save Changes';
                }
            })
            .catch(() => {
                showToast('Network sync error.', 'error');
                btn.disabled = false;
                btn.textContent = 'Save Changes';
            });
        });

        // Delete action triggering
        document.querySelectorAll('.delete-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                if (confirm('Are you sure you want to delete this transaction record?')) {
                    const id = this.getAttribute('data-id');
                    const formData = new FormData();
                    formData.append('action', 'delete');
                    formData.append('transaction_id', id);

                    fetch('user_transaction.php', {
                        method: 'POST',
                        body: formData,
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.status === 'success') {
                            showToast(data.message, 'success');
                            setTimeout(() => { window.location.reload(); }, 800);
                        } else {
                            showToast(data.message, 'error');
                        }
                    })
                    .catch(() => {
                        showToast('Network error, failed to delete transaction.', 'error');
                    });
                }
            });
        });
    </script>
</body>
</html>