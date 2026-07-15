<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once '../../config/db_config.php';
$user_id = $_SESSION['user_id'];

// Pull core loan parameters
$loan_sql = "SELECT loan_id, total_loan, remaining_balance, monthly_payment, initial_override_locked FROM student_loans WHERE user_id = ?";
$stmt = $conn->prepare($loan_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$loan = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$loan) {
    header("Location: ptptn_dashboard.php");
    exit();
}

$loan_id = $loan['loan_id'];

// Check monthly execution metrics state
$month_check_sql = "SELECT repayment_id FROM repayment_records WHERE loan_id = ? AND DATE_FORMAT(payment_date, '%Y-%m') = ?";
$m_stmt = $conn->prepare($month_check_sql);
$this_month = date('Y-m');
$m_stmt->bind_param("is", $loan_id, $this_month);
$m_stmt->execute();
$already_paid_this_month = ($m_stmt->get_result()->num_rows > 0);
$m_stmt->close();

// --- AJAX Repayment Operation Engine ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest') {
    header('Content-Type: application/json');
    
    $action = $_POST['action'] ?? '';
    if ($action === 'no_override') {
        $up_stmt = $conn->prepare("UPDATE student_loans SET initial_override_locked = 1 WHERE loan_id = ?");
        $up_stmt->bind_param("i", $loan_id);
        if ($up_stmt->execute()) {
            echo json_encode(["status" => "success", "message" => "Overrides skipped successfully."]);
        } else {
            echo json_encode(["status" => "error", "message" => "Failed to update overrides state."]);
        }
        $up_stmt->close();
        $conn->close();
        exit;
    }

    $mode = $_POST['mode'] ?? 'standard';
    $payment_amount = filter_var($_POST['payment_amount'] ?? 0, FILTER_VALIDATE_FLOAT) ?: 0.00;

    if ($mode === 'standard') {
        if ($payment_amount <= 0) {
            echo json_encode(["status" => "error", "message" => "Please enter a valid amount."]);
            exit;
        }
        if ($payment_amount < (float)$loan['monthly_payment']) {
            echo json_encode(["status" => "error", "message" => "Payment must be at least RM " . number_format($loan['monthly_payment'], 2)]);
            exit;
        }
    }

    if ($loan['remaining_balance'] <= 0) {
        echo json_encode(["status" => "error", "message" => "This loan profile is already fully settled."]);
        exit;
    }

    $conn->begin_transaction();
    try {
        $payment_date = date('Y-m-d');
        if ($mode === 'initial') {
            if ($loan['initial_override_locked'] >= 1) {
                throw new Exception("One-time custom dynamic adjustments have been exhausted.");
            }
            
            $prior_amount = filter_var($_POST['payment_amount'] ?? 0, FILTER_VALIDATE_FLOAT) ?: 0.00;
            $this_month_paid = isset($_POST['this_month_paid']) ? 1 : 0;
            $this_month_amount = filter_var($_POST['this_month_amount'] ?? 0, FILTER_VALIDATE_FLOAT) ?: 0.00;

            if ($prior_amount <= 0 && $this_month_paid === 0) {
                throw new Exception("Please specify cumulative payments or a current month payment.");
            }

            $current_balance = (float)$loan['remaining_balance'];
            $msg = "Overrides saved successfully.";

            // 1. Process Prior Cumulative Payment
            if ($prior_amount > 0) {
                $current_balance = max(0.00, $current_balance - $prior_amount);
                $prior_date = date('Y-m-d', strtotime('-1 day'));
                
                $ins_sql = "INSERT INTO repayment_records (loan_id, payment_amount, payment_date, remaining_balance) VALUES (?, ?, ?, ?)";
                $ins_stmt = $conn->prepare($ins_sql);
                $ins_stmt->bind_param("idsd", $loan_id, $prior_amount, $prior_date, $current_balance);
                $ins_stmt->execute();
                $ins_stmt->close();
            }

            // 2. Process Current Month's Payment
            if ($this_month_paid === 1) {
                if ($this_month_amount >= (float)$loan['monthly_payment']) {
                    $current_balance = max(0.00, $current_balance - $this_month_amount);
                    $payment_date = date('Y-m-d');

                    $ins_sql = "INSERT INTO repayment_records (loan_id, payment_amount, payment_date, remaining_balance) VALUES (?, ?, ?, ?)";
                    $ins_stmt = $conn->prepare($ins_sql);
                    $ins_stmt->bind_param("idsd", $loan_id, $this_month_amount, $payment_date, $current_balance);
                    $ins_stmt->execute();
                    $repayment_id = $conn->insert_id;
                    $ins_stmt->close();

                    $desc = "PTPTN Repayment ref #" . $repayment_id;
                    $cat = "Student Loan";
                    $type = "expense";
                    $tx_sql = "INSERT INTO user_transactions (user_id, transaction_type, category, amount, description, transaction_date) VALUES (?, ?, ?, ?, ?, ?)";
                    $tx_stmt = $conn->prepare($tx_sql);
                    $tx_stmt->bind_param("issdss", $user_id, $type, $cat, $this_month_amount, $desc, $payment_date);
                    $tx_stmt->execute();
                    $tx_stmt->close();
                } else {
                    $msg .= " This month is marked unpaid because the amount was below RM " . number_format($loan['monthly_payment'], 2) . ".";
                }
            }

            // 3. Update loan
            $up_loan_sql = "UPDATE student_loans SET remaining_balance = ?, initial_override_locked = 1 WHERE loan_id = ?";
            $up_stmt = $conn->prepare($up_loan_sql);
            $up_stmt->bind_param("di", $current_balance, $loan_id);
            $up_stmt->execute();
            $up_stmt->close();
        } else {
            if ($already_paid_this_month) {
                throw new Exception("Repayment for this month is already completed.");
            }

            $new_balance = max(0, $loan['remaining_balance'] - $payment_amount);

            $ins_sql = "INSERT INTO repayment_records (loan_id, payment_amount, payment_date, remaining_balance) VALUES (?, ?, ?, ?)";
            $ins_stmt = $conn->prepare($ins_sql);
            $ins_stmt->bind_param("idsd", $loan_id, $payment_amount, $payment_date, $new_balance);
            $ins_stmt->execute();
            $repayment_id = $conn->insert_id;
            $ins_stmt->close();

            $up_loan_sql = "UPDATE student_loans SET remaining_balance = ? WHERE loan_id = ?";
            $up_stmt = $conn->prepare($up_loan_sql);
            $up_stmt->bind_param("di", $new_balance, $loan_id);
            $up_stmt->execute();
            $up_stmt->close();

            // Log expense transaction to deduct from balance
            $desc = "PTPTN Repayment ref #" . $repayment_id;
            $cat = "Student Loan";
            $type = "expense";
            $tx_sql = "INSERT INTO user_transactions (user_id, transaction_type, category, amount, description, transaction_date) VALUES (?, ?, ?, ?, ?, ?)";
            $tx_stmt = $conn->prepare($tx_sql);
            $tx_stmt->bind_param("issdss", $user_id, $type, $cat, $payment_amount, $desc, $payment_date);
            $tx_stmt->execute();
            $tx_stmt->close();
        }

        $conn->commit();
        echo json_encode(["status" => "success", "message" => "Payment processed and balance updated successfully."]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
    
    $conn->close();
    exit;
}

// Fetch transaction history
$history_sql = "SELECT payment_amount, payment_date, remaining_balance FROM repayment_records WHERE loan_id = ? ORDER BY payment_date DESC, repayment_id DESC";
$h_stmt = $conn->prepare($history_sql);
$h_stmt->bind_param("i", $loan_id);
$h_stmt->execute();
$records = $h_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$h_stmt->close();
$conn->close();

include '../view/ptptn_repayment_view.php';
?>