<?php

require_once '../Database/db.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$payment_id = isset($data['payment_id']) ? intval($data['payment_id']) : 0;

if ($payment_id) {
    try {
        $stmt = $pdo->prepare("UPDATE payments SET payment_status = 'verification' WHERE payment_id = ?");
        $stmt->execute([$payment_id]);
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        error_log("Error updating payment status: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid payment ID']);
}   

?> 