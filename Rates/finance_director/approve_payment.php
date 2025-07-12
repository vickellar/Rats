<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'finance_director') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

include '../Database/db.php';

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['payment_id']) || !isset($input['action'])) {
    echo json_encode(['success' => false, 'message' => 'Payment ID and action are required']);
    exit();
}

$payment_id = $input['payment_id'];
$action = $input['action'];

if ($action !== 'approve') {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    exit();
}

try {
    // Get employee_id from session (assuming you have it stored)
    $employee_id = $_SESSION['employee_id'] ?? null;
    
    if (!$employee_id) {
        echo json_encode(['success' => false, 'message' => 'Employee ID not found in session']);
        exit();
    }
    
    // Update payment status to 'Approved' and set actioned_by
    $stmt = $pdo->prepare("UPDATE payments SET payment_status = 'Approved', actioned_by = ? WHERE payment_id = ?");
    $result = $stmt->execute([$employee_id, $payment_id]);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Payment approved successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to approve payment']);
    }
} catch (PDOException $e) {
    error_log("Error approving payment: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?>
