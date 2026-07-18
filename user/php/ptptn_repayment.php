<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once '../../config/db_config.php';
$user_id = $_SESSION['user_id'];

// Pull core loan parameters
$loan_sql = "SELECT loan_id, total_loan, remaining_balance, monthly_payment, initial_override_locked, repayment_start_date FROM student_loans WHERE user_id = ?";
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

// If start date is today or later, they don't need historical overrides!
$start_date = $loan['repayment_start_date'];
$start_month = date('Y-m', strtotime($start_date));
$current_month = date('Y-m');

if ($start_month >= $current_month && $loan['initial_override_locked'] == 0) {
    $up_stmt = $conn->prepare("UPDATE student_loans SET initial_override_locked = 1 WHERE loan_id = ?");
    $up_stmt->bind_param("i", $loan_id);
    $up_stmt->execute();
    $up_stmt->close();
    $loan['initial_override_locked'] = 1;
}

// Calculate unpaid months and past unpaid status for page rendering
$start_ts = strtotime(date('Y-m-01', strtotime($start_date)));
$current_ts = strtotime(date('Y-m-01'));
$unpaid_months_info = [];
$unpaid_months_list = [];
$temp_ts = $start_ts;

while ($temp_ts <= $current_ts) {
    $month_str = date('Y-m', $temp_ts);
    
    $chk_stmt = $conn->prepare("SELECT SUM(payment_amount) AS total_paid FROM repayment_records WHERE loan_id = ? AND (target_month = ? OR (target_month IS NULL AND DATE_FORMAT(payment_date, '%Y-%m') = ?))");
    $chk_stmt->bind_param("iss", $loan_id, $month_str, $month_str);
    $chk_stmt->execute();
    $res = $chk_stmt->get_result()->fetch_assoc();
    $total_paid = $res['total_paid'] ? (float)$res['total_paid'] : 0.0;
    $chk_stmt->close();
    
    $monthly_payment = (float)$loan['monthly_payment'];
    if ($total_paid < $monthly_payment) {
        $outstanding = $monthly_payment - $total_paid;
        $unpaid_months_info[] = [
            'month' => $month_str,
            'outstanding' => $outstanding
        ];
        $unpaid_months_list[] = $month_str;
    }
    
    $temp_ts = strtotime("+1 month", $temp_ts);
}

$has_past_unpaid = false;
foreach ($unpaid_months_list as $m) {
    if ($m < $current_month) {
        $has_past_unpaid = true;
    }
}

// Check monthly execution metrics state
$already_paid_this_month = !in_array($current_month, $unpaid_months_list);

// --- AJAX Repayment Operation Engine ---
if ($_SERVER["REQUEST_METHOD"] == "POST" 
&& isset($_SERVER['HTTP_X_REQUESTED_WITH']) 
&& $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest') 
{
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
                $up_loan_sql = "UPDATE student_loans SET initial_override_locked = 1 WHERE loan_id = ?";
                $up_stmt = $conn->prepare($up_loan_sql);
                $up_stmt->bind_param("i", $loan_id);
                if (!$up_stmt->execute()) {
                    throw new Exception("Failed to save prior repayment settings.");
                }
                $up_stmt->close();
                $conn->commit();
                echo json_encode(["status" => "success", "message" => "Prior repayment settings saved."]);
                $conn->close();
                exit;
            }

            $current_balance = (float)$loan['remaining_balance'];
            $msg = "Overrides saved successfully.";

            // 1. Process Prior Cumulative Payment
            if ($prior_amount > 0) {
                $prior_date = date('Y-m-d', strtotime('-1 day'));
                $monthly_payment = (float)$loan['monthly_payment'];
                
                // Fetch all elapsed prior months (before current calendar month)
                $start_ts = strtotime(date('Y-m-01', strtotime($start_date)));
                $prev_month_ts = strtotime("-1 month", strtotime(date('Y-m-01')));
                
                $prior_months = [];
                $temp_ts = $start_ts;
                while ($temp_ts <= $prev_month_ts) {
                    $prior_months[] = date('Y-m', $temp_ts);
                    $temp_ts = strtotime("+1 month", $temp_ts);
                }
                
                $temp_prior_pool = $prior_amount;
                $current_balance = (float)$loan['remaining_balance'];
                
                foreach ($prior_months as $m_str) {
                    if ($temp_prior_pool >= $monthly_payment) {
                        $temp_prior_pool -= $monthly_payment;
                        $current_balance = max(0.00, $current_balance - $monthly_payment);
                        
                        // Insert repayment record for specific month
                        $ins_sql = "INSERT INTO repayment_records (loan_id, payment_amount, payment_date, remaining_balance, target_month) VALUES (?, ?, ?, ?, ?)";
                        $ins_stmt = $conn->prepare($ins_sql);
                        $ins_stmt->bind_param("idsds", $loan_id, $monthly_payment, $prior_date, $current_balance, $m_str);
                        $ins_stmt->execute();
                        $ins_stmt->close();
                        
                        // Insert transaction entry as non-deducting type 'prior'
                        $desc = "Prior PTPTN Repayment For " . $m_str;
                        $cat = "Student Loan";
                        $type = "prior";
                        $tx_sql = "INSERT INTO user_transactions (user_id, transaction_type, category, amount, description, transaction_date) VALUES (?, ?, ?, ?, ?, ?)";
                        $tx_stmt = $conn->prepare($tx_sql);
                        $tx_stmt->bind_param("issdss", $user_id, $type, $cat, $monthly_payment, $desc, $prior_date);
                        $tx_stmt->execute();
                        $tx_stmt->close();
                    } else {
                        if ($temp_prior_pool > 0) {
                            $current_balance = max(0.00, $current_balance - $temp_prior_pool);
                            
                            $ins_sql = "INSERT INTO repayment_records (loan_id, payment_amount, payment_date, remaining_balance, target_month) VALUES (?, ?, ?, ?, ?)";
                            $ins_stmt = $conn->prepare($ins_sql);
                            $ins_stmt->bind_param("idsds", $loan_id, $temp_prior_pool, $prior_date, $current_balance, $m_str);
                            $ins_stmt->execute();
                            $ins_stmt->close();
                            
                            $desc = "Prior PTPTN Partial Repayment For " . $m_str;
                            $cat = "Student Loan";
                            $type = "prior";
                            $tx_sql = "INSERT INTO user_transactions (user_id, transaction_type, category, amount, description, transaction_date) VALUES (?, ?, ?, ?, ?, ?)";
                            $tx_stmt = $conn->prepare($tx_sql);
                            $tx_stmt->bind_param("issdss", $user_id, $type, $cat, $temp_prior_pool, $desc, $prior_date);
                            $tx_stmt->execute();
                            $tx_stmt->close();
                            
                            $temp_prior_pool = 0.0;
                        }
                        break;
                    }
                }
                
                // If there's still excess in prior amount pool
                if ($temp_prior_pool > 0) {
                    $current_balance = max(0.00, $current_balance - $temp_prior_pool);
                    $excess_str = "prior_excess";
                    
                    $ins_sql = "INSERT INTO repayment_records (loan_id, payment_amount, payment_date, remaining_balance, target_month) VALUES (?, ?, ?, ?, ?)";
                    $ins_stmt = $conn->prepare($ins_sql);
                    $ins_stmt->bind_param("idsds", $loan_id, $temp_prior_pool, $prior_date, $current_balance, $excess_str);
                    $ins_stmt->execute();
                    $ins_stmt->close();
                    
                    $desc = "Prior PTPTN Repayment (Excess)";
                    $cat = "Student Loan";
                    $type = "prior";
                    $tx_sql = "INSERT INTO user_transactions (user_id, transaction_type, category, amount, description, transaction_date) VALUES (?, ?, ?, ?, ?, ?)";
                    $tx_stmt = $conn->prepare($tx_sql);
                    $tx_stmt->bind_param("issdss", $user_id, $type, $cat, $temp_prior_pool, $desc, $prior_date);
                    $tx_stmt->execute();
                    $tx_stmt->close();
                }
            }

            // 2. Process Current Month's Payment
            if ($this_month_paid === 1) {
                if ($this_month_amount >= (float)$loan['monthly_payment']) {
                    $current_balance = max(0.00, $current_balance - $this_month_amount);
                    $payment_date = date('Y-m-d');

                     $ins_sql = "INSERT INTO repayment_records (loan_id, payment_amount, payment_date, remaining_balance, target_month) VALUES (?, ?, ?, ?, ?)";
                     $ins_stmt = $conn->prepare($ins_sql);
                     $this_month_str = date('Y-m');
                     $ins_stmt->bind_param("idsds", $loan_id, $this_month_amount, $payment_date, $current_balance, $this_month_str);
                     $ins_stmt->execute();
                     $repayment_id = $conn->insert_id;
                     $ins_stmt->close();

                    $desc = "PTPTN Repayment Save Goal #" . $repayment_id;
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
            // Find all unpaid months from start date to current month
            $start_date = $loan['repayment_start_date'];
            $start_ts = strtotime(date('Y-m-01', strtotime($start_date)));
            $current_ts = strtotime(date('Y-m-01'));
            
            $unpaid_months_info = [];
            $temp_ts = $start_ts;
            
            while ($temp_ts <= $current_ts) {
                $month_str = date('Y-m', $temp_ts);
                
                $chk_stmt = $conn->prepare("SELECT SUM(payment_amount) AS total_paid FROM repayment_records WHERE loan_id = ? AND (target_month = ? OR (target_month IS NULL AND DATE_FORMAT(payment_date, '%Y-%m') = ?))");
                $chk_stmt->bind_param("iss", $loan_id, $month_str, $month_str);
                $chk_stmt->execute();
                $res = $chk_stmt->get_result()->fetch_assoc();
                $total_paid = $res['total_paid'] ? (float)$res['total_paid'] : 0.0;
                $chk_stmt->close();
                
                $monthly_payment = (float)$loan['monthly_payment'];
                if ($total_paid < $monthly_payment) {
                    $outstanding = $monthly_payment - $total_paid;
                    $unpaid_months_info[] = [
                        'month' => $month_str,
                        'outstanding' => $outstanding
                    ];
                }
                
                $temp_ts = strtotime("+1 month", $temp_ts);
            }

            if (empty($unpaid_months_info)) {
                throw new Exception("Repayment for this month is already completed.");
            }

            $monthly_payment = (float)$loan['monthly_payment'];
            $allocated_amount = $payment_amount;
            $new_balance = (float)$loan['remaining_balance'];
            $inserted_records = 0;
            $months_paid_names = [];

            $total_unpaid = count($unpaid_months_info);
            for ($i = 0; $i < $total_unpaid; $i++) {
                if ($allocated_amount <= 0) {
                    break;
                }
                
                $info = $unpaid_months_info[$i];
                $unpaid_m = $info['month'];
                $outstanding = $info['outstanding'];
                
                // Pay up to the outstanding amount for this month
                $pay_for_this_month = min($allocated_amount, $outstanding);
                
                // If it is the last unpaid month, consume all remaining allocated amount
                if ($i === $total_unpaid - 1 && $allocated_amount > $pay_for_this_month) {
                    $pay_for_this_month = $allocated_amount;
                }
                
                $pay_for_this_month = round($pay_for_this_month, 2);
                if ($pay_for_this_month < 0.01) {
                    continue;
                }
                
                $allocated_amount -= $pay_for_this_month;
                $new_balance = max(0.00, $new_balance - $pay_for_this_month);
                
                $pay_date = date('Y-m-d');
                
                $ins_sql = "INSERT INTO repayment_records (loan_id, payment_amount, payment_date, remaining_balance, target_month) VALUES (?, ?, ?, ?, ?)";
                $ins_stmt = $conn->prepare($ins_sql);
                $ins_stmt->bind_param("idsds", $loan_id, $pay_for_this_month, $pay_date, $new_balance, $unpaid_m);
                $ins_stmt->execute();
                $ins_stmt->close();
                
                $inserted_records++;
                $months_paid_names[] = date('F Y', strtotime($unpaid_m . '-01'));
            }

            // Update remaining loan balance in database
            $up_loan_sql = "UPDATE student_loans SET remaining_balance = ? WHERE loan_id = ?";
            $up_stmt = $conn->prepare($up_loan_sql);
            $up_stmt->bind_param("di", $new_balance, $loan_id);
            $up_stmt->execute();
            $up_stmt->close();

            // Log single combined transaction in user_transactions to deduct wallet balance once
            $desc = "PTPTN Repayment (" . implode(', ', $months_paid_names) . ")";
            $cat = "Student Loan";
            $type = "expense";
            $tx_date = date('Y-m-d');

            $tx_sql = "INSERT INTO user_transactions (user_id, transaction_type, category, amount, description, transaction_date) VALUES (?, ?, ?, ?, ?, ?)";
            $tx_stmt = $conn->prepare($tx_sql);
            $tx_stmt->bind_param("issdss", $user_id, $type, $cat, $payment_amount, $desc, $tx_date);
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
$history_sql = "SELECT payment_amount, payment_date, remaining_balance, target_month FROM repayment_records WHERE loan_id = ? ORDER BY payment_date DESC, repayment_id DESC";
$h_stmt = $conn->prepare($history_sql);
$h_stmt->bind_param("i", $loan_id);
$h_stmt->execute();
$records = $h_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$h_stmt->close();
$conn->close();

include '../view/ptptn_repayment_view.php';
?>