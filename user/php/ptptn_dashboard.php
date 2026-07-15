<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once '../../config/db_config.php';
$user_id = $_SESSION['user_id'];

// --- AJAX Form Submission Engine ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest') {
    header('Content-Type: application/json');

    $action = $_POST['action'] ?? '';

    if ($action === 'edit_months') {
        $new_months = filter_var($_POST['repayment_period_months'], FILTER_VALIDATE_INT);
        if (!$new_months || $new_months <= 0) {
            echo json_encode(["status" => "error", "message" => "Please enter a valid month count."]);
            exit;
        }

        $stmt = $conn->prepare("SELECT loan_id, total_loan, repayment_period_months, remaining_balance FROM student_loans WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $loan = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$loan) {
            echo json_encode(["status" => "error", "message" => "No loan configuration found."]);
            exit;
        }

        $loan_id = $loan['loan_id'];
        $old_months = (int)$loan['repayment_period_months'];
        $current_total_loan = (float)$loan['total_loan'];

        // Calculate original approved principal loan amount
        $approved_loan = $current_total_loan / (1 + ($old_months / 1200));

        // Recalculate based on new months count
        $new_years = $new_months / 12;
        $new_calculated_total = ($approved_loan * 0.01 * $new_years) + $approved_loan;
        $new_monthly_payment = $new_calculated_total / $new_months;

        // Adjust remaining balance by keeping total amount paid so far intact
        $total_paid = $current_total_loan - (float)$loan['remaining_balance'];
        $new_remaining_balance = max(0.00, $new_calculated_total - $total_paid);

        $up_stmt = $conn->prepare("UPDATE student_loans SET total_loan = ?, repayment_period_months = ?, monthly_payment = ?, remaining_balance = ? WHERE loan_id = ?");
        $up_stmt->bind_param("diddi", $new_calculated_total, $new_months, $new_monthly_payment, $new_remaining_balance, $loan_id);
        if ($up_stmt->execute()) {
            echo json_encode(["status" => "success", "message" => "Repayment period updated successfully."]);
        } else {
            echo json_encode(["status" => "error", "message" => "Database update failed."]);
        }
        $up_stmt->close();
        $conn->close();
        exit;
    }

    if ($action !== 'edit_months') {
        // Security Gate
        $gate_sql = "SELECT loan_id FROM student_loans WHERE user_id = ?";
        $gate_stmt = $conn->prepare($gate_sql);
        $gate_stmt->bind_param("i", $user_id);
        $gate_stmt->execute();
        if ($gate_stmt->get_result()->num_rows > 0) {
            echo json_encode(["status" => "error", "message" => "Loan details are already locked for this account."]);
            $gate_stmt->close();
            exit;
        }
        $gate_stmt->close();
    }

    $total_loan        = filter_var($_POST['total_loan'], FILTER_VALIDATE_FLOAT);
    $start_date        = $_POST['repayment_start_date'] ?? '';
    $monthly_payment   = filter_var($_POST['monthly_payment'], FILTER_VALIDATE_FLOAT);
    $period_months     = filter_var($_POST['repayment_period_months'], FILTER_VALIDATE_INT);
    $is_immediate      = isset($_POST['immediate_repayment_scheme']) ? 1 : 0;

    // Hardcoded Constant Constraints
    $interest_rate     = 1.00;

    if (!$total_loan || !$monthly_payment || !$period_months || empty($start_date)) {
        echo json_encode(["status" => "error", "message" => "Please complete all mandatory parameters correctly."]);
        exit;
    }

    // Mathematical Calculation Formulation Engine
    if ($is_immediate === 1) {
        // Scheme A Formulation Matrix: Interest is calculated for remaining months after the initial year
        $m_12 = $monthly_payment * 12;
        $p_new = $total_loan - $m_12;
        $remaining_months = max(0, $period_months - 12);
        $i_new = $p_new * 0.01 * ($remaining_months / 12);
        $calculated_total = $m_12 + $p_new + $i_new;
    } else {
        // Fallback Base Standard Formula Matrix: (P * 1% * (months / 12)) + P
        $years = $period_months / 12;
        $calculated_total = ($total_loan * 0.01 * $years) + $total_loan;
    }

    $insert_sql = "INSERT INTO student_loans (user_id, total_loan, interest_rate, repayment_start_date, repayment_period_months, monthly_payment, remaining_balance, initial_override_locked, immediate_repayment_scheme) VALUES (?, ?, ?, ?, ?, ?, ?, 0, ?)";
    $stmt = $conn->prepare($insert_sql);
    $stmt->bind_param("iddsiddi", $user_id, $calculated_total, $interest_rate, $start_date, $period_months, $monthly_payment, $calculated_total, $is_immediate);

    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Loan profile finalized and secured."]);
    } else {
        echo json_encode(["status" => "error", "message" => "Execution channel error."]);
    }
    $stmt->close();
    $conn->close();
    exit;
}

// --- Data Assembly Core Pipeline ---
$loan_sql = "SELECT loan_id, total_loan, interest_rate, repayment_start_date, repayment_period_months, monthly_payment, remaining_balance, immediate_repayment_scheme FROM student_loans WHERE user_id = ?";
$stmt = $conn->prepare($loan_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$loan_profile = $stmt->get_result()->fetch_assoc();
$stmt->close();

$has_loan = ($loan_profile !== null);
$paid_amount = 0;
$remaining_balance = 0;
$paid_percentage = 0;
$unpaid_percentage = 100;
$show_due_warning = false;

if ($has_loan) {
    $remaining_balance = (float)$loan_profile['remaining_balance'];
    $total_loan = (float)$loan_profile['total_loan'];
    $paid_amount = max(0, $total_loan - $remaining_balance);
    
    if ($total_loan > 0) {
        $paid_percentage = round(($paid_amount / $total_loan) * 100, 1);
        $unpaid_percentage = round(($remaining_balance / $total_loan) * 100, 1);
    }

    // check paid or not before 27th
    $day_of_month = (int)date('d');
    if ($day_of_month > 27 && $remaining_balance > 0) {
        $month_check_sql = "SELECT repayment_id FROM repayment_records WHERE loan_id = ? AND DATE_FORMAT(payment_date, '%Y-%m') = ?";
        $m_stmt = $conn->prepare($month_check_sql);
        $this_month = date('Y-m');
        $m_stmt->bind_param("is", $loan_profile['loan_id'], $this_month);
        $m_stmt->execute();
        $has_paid_this_month = ($m_stmt->get_result()->num_rows > 0);
        $m_stmt->close();

        if (!$has_paid_this_month) {
            $show_due_warning = true;
        }
    }
}
$conn->close();

include '../view/ptptn_dashboard_view.php';
?>