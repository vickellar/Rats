<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'finance_director') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

include '../Database/db.php';

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['payment_id']) || !isset($input['certificate_type'])) {
    echo json_encode(['success' => false, 'message' => 'Payment ID and certificate type are required']);
    exit();
}

$payment_id = $input['payment_id'];
$certificate_type = $input['certificate_type'];

// Validate certificate type
if (!in_array($certificate_type, ['rates', 'tax'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid certificate type']);
    exit();
}

try {
    // Get payment details
    $payment_stmt = $pdo->prepare("
        SELECT p.*, prop.address, prop.owner, cb.invoice_number 
        FROM payments p 
        LEFT JOIN properties prop ON p.property_id = prop.property_id 
        LEFT JOIN calculated_bills cb ON p.bill_id = cb.bill_id 
        WHERE p.payment_id = ?
    ");
    $payment_stmt->execute([$payment_id]);
    $payment = $payment_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$payment) {
        echo json_encode(['success' => false, 'message' => 'Payment not found']);
        exit();
    }
    
    // Generate certificate number
    $certificate_number = strtoupper($certificate_type) . '-' . date('Y') . '-' . str_pad($payment_id, 6, '0', STR_PAD_LEFT);
    
    // Insert clearance certificate record
    $cert_stmt = $pdo->prepare("
        INSERT INTO clearance_certificates 
        (payment_id, certificate_type, certificate_number, property_address, property_owner, amount, issued_by, issued_at, status) 
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), 'active')
    ");
    
    $result = $cert_stmt->execute([
        $payment_id,
        $certificate_type,
        $certificate_number,
        $payment['address'],
        $payment['owner'],
        $payment['amount_paid'],
        $_SESSION['user_id'] ?? $_SESSION['username']
    ]);
    
    if ($result) {
        // Log the action
        $log_stmt = $pdo->prepare("INSERT INTO payment_actions (payment_id, action_type, performed_by, performed_at, notes) VALUES (?, 'certificate_issued', ?, NOW(), ?)");
        $log_stmt->execute([
            $payment_id, 
            $_SESSION['user_id'] ?? $_SESSION['username'], 
            ucfirst($certificate_type) . ' clearance certificate issued: ' . $certificate_number
        ]);
        
        // Generate certificate URL (you can implement actual PDF generation here)
        $certificate_url = 'generate_certificate.php?cert_id=' . $pdo->lastInsertId() . '&type=' . $certificate_type;
        
        echo json_encode([
            'success' => true, 
            'message' => ucfirst($certificate_type) . ' clearance certificate issued successfully',
            'certificate_number' => $certificate_number,
            'certificate_url' => $certificate_url
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to issue certificate']);
    }
} catch (PDOException $e) {
    error_log("Error issuing certificate: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?>
