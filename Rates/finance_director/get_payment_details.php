<?php
session_start();

// Check if user is logged in and has proper role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'finance_director') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

include '../Database/db.php';

if (!isset($_GET['payment_id']) || !is_numeric($_GET['payment_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid payment ID']);
    exit();
}

$payment_id = intval($_GET['payment_id']);

try {
    // Fetch payment details with related information
    $payment_query = "
        SELECT 
            p.payment_id,
            p.property_id,
            p.account_id,
            p.user_id,
            p.receipt_name,
            p.receipt_fpath,
            p.amount_paid,
            p.payment_date,
            p.payment_method,
            p.payment_status,
            p.invoice_number,
            p.receipt_number,
            p.bill_id,
            p.notes,
            prop.address as property_address,
            prop.owner as property_owner,
            u.first_name,
            u.surname,
            CONCAT(u.first_name, ' ', u.surname) as full_name
        FROM payments p
        LEFT JOIN properties prop ON p.property_id = prop.property_id
        LEFT JOIN users u ON p.user_id = u.user_id
        WHERE p.payment_id = :payment_id
    ";
    
    $stmt = $pdo->prepare($payment_query);
    $stmt->execute([':payment_id' => $payment_id]);
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$payment) {
        echo json_encode(['success' => false, 'message' => 'Payment not found']);
        exit();
    }
    
    // Fetch invoice details from calculated_bills
    $invoice_query = "
        SELECT 
            cb.bill_id,
            cb.user_id,
            cb.property_id,
            cb.account_id,
            cb.application_id,
            cb.comments,
            cb.total_balance,
            cb.processing_fee,
            cb.overall_total,
            cb.invoice_number,
            cb.calculated_at,
            cb.currents,
            cb.due_date,
            cb.update_on,
            cb.calculated_by
        FROM calculated_bills cb
        WHERE cb.bill_id = :bill_id
    ";
    
    $stmt = $pdo->prepare($invoice_query);
    $stmt->execute([':bill_id' => $payment['bill_id']]);
    $invoice = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Fetch account details
    $account_query = "
        SELECT 
            account_id,
            property_id,
            account_number,
            account_balance,
            created_at,
            updated_at
        FROM accounts
        WHERE account_id = :account_id
    ";
    
    $stmt = $pdo->prepare($account_query);
    $stmt->execute([':account_id' => $payment['account_id']]);
    $account = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Fetch monthly breakdown if invoice exists
    $monthly_breakdown = [];
    if ($invoice) {
        $monthly_query = "
            SELECT 
                month_id,
                bill_id,
                month1_name,
                month2_name,
                month3_name,
                month4_name,
                monthly_balance
            FROM calculated_bill_months
            WHERE bill_id = :bill_id
            ORDER BY month_id
        ";
        
        $stmt = $pdo->prepare($monthly_query);
        $stmt->execute([':bill_id' => $invoice['bill_id']]);
        $monthly_breakdown = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Fetch property details
    $property_query = "
        SELECT 
            property_id,
            address,
            owner
        FROM properties
        WHERE property_id = :property_id
    ";
    
    $stmt = $pdo->prepare($property_query);
    $stmt->execute([':property_id' => $payment['property_id']]);
    $property = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Prepare response
    $response = [
        'success' => true,
        'payment' => $payment,
        'invoice' => $invoice,
        'account' => $account,
        'property' => $property,
        'monthly_breakdown' => $monthly_breakdown
    ];
    
    // Add calculated fields
    if ($payment && $invoice) {
        $response['payment_vs_invoice'] = [
            'payment_amount' => floatval($payment['amount_paid']),
            'invoice_total' => floatval($invoice['overall_total']),
            'difference' => floatval($invoice['overall_total']) - floatval($payment['amount_paid']),
            'is_full_payment' => (floatval($payment['amount_paid']) >= floatval($invoice['overall_total']))
        ];
    }
    
    echo json_encode($response);
    
} catch (PDOException $e) {
    error_log("Error fetching payment details: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Database error occurred while fetching payment details'
    ]);
} catch (Exception $e) {
    error_log("General error in get_payment_details.php: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'An unexpected error occurred'
    ]);
}
?>
