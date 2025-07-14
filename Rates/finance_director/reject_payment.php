<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'finance_director') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

include '../Database/db.php';

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['payment_id']) || !isset($input['rejection_reason'])) {
    echo json_encode(['success' => false, 'message' => 'Payment ID and rejection reason are required']);
    exit();
}

$payment_id = $input['payment_id'];
$rejection_reason = trim($input['rejection_reason']);

if (empty($rejection_reason)) {
    echo json_encode(['success' => false, 'message' => 'Rejection reason cannot be empty']);
    exit();
}

try {
    // Update payment status to 'rejected' with reason
    $stmt = $pdo->prepare("UPDATE payments SET payment_status = 'rejected', rejection_reason = ?, rejected_by = ?, rejected_at = NOW(), updated_at = NOW() WHERE payment_id = ?");
    $result = $stmt->execute([$rejection_reason, $_SESSION['user_id'] ?? $_SESSION['username'], $payment_id]);
    
    if ($result) {
        // Log the action
        $log_stmt = $pdo->prepare("INSERT INTO payment_actions (payment_id, action_type, performed_by, performed_at, notes) VALUES (?, 'reject', ?, NOW(), ?)");
        $log_stmt->execute([$payment_id, $_SESSION['user_id'] ?? $_SESSION['username'], 'Payment rejected: ' . $rejection_reason]);
        
        echo json_encode(['success' => true, 'message' => 'Payment rejected successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to reject payment']);
    }
} catch (PDOException $e) {
    error_log("Error rejecting payment: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?>
