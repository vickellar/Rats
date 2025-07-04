<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'finance_director') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

include '../Database/db.php';

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['payment_id']) || !isset($input['action'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit();
}

$payment_id = (int)$input['payment_id'];
$action = $input['action'];

// Validate action
if (!in_array($action, ['approve', 'reject'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    exit();
}

try {
    // Update payment status
    $status = ($action === 'approve') ? 'approved' : 'rejected';
    
    $stmt = $pdo->prepare("UPDATE payments SET transaction_status = ? WHERE payment_id = ?");
    $result = $stmt->execute([$status, $payment_id]);
    
    if ($result) {
        // Log the action for audit trail
        $log_stmt = $pdo->prepare("
            INSERT INTO audit_log (user_id, action, table_name, record_id, timestamp) 
            VALUES (?, ?, 'payments', ?, NOW())
        ");
        $log_stmt->execute([$_SESSION['user_id'] ?? 0, "Payment {$status}", $payment_id]);
        
        echo json_encode(['success' => true, 'message' => "Payment {$status} successfully"]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update payment status']);
    }
    
} catch (PDOException $e) {
    error_log("Error updating payment status: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?>
