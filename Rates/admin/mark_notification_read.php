<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

require_once '../Database/db.php';
require_once 'notification_manager.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit();
}

try {
    $notificationId = intval($_POST['notification_id'] ?? 0);
    
    if (!$notificationId) {
        echo json_encode(['success' => false, 'error' => 'Invalid notification ID']);
        exit();
    }
    
    $notificationManager = new NotificationManager($pdo);
    $success = $notificationManager->markAsRead($notificationId, $_SESSION['user_id']);
    
    if ($success) {
        echo json_encode(['success' => true, 'message' => 'Notification marked as read']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to mark notification as read']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
