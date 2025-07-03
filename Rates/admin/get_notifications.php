<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in and is an admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

require_once '../Database/db.php';

try {
    // Get notification count
    $countQuery = "
        SELECT COUNT(*) AS new_count
        FROM rate_clearance_applications
        WHERE status = 'awaiting'
    ";
    $countStmt = $pdo->prepare($countQuery);
    $countStmt->execute();
    $notificationCount = $countStmt->fetch()['new_count'];

    // Get all notifications
    $notificationQuery = "
        SELECT 
            a.application_id, 
            a.status, 
            a.created_at, 
            u.first_name, 
            u.surname, 
            p.address AS property_address, 
            p.owner AS property_owner,
            p.property_id
        FROM 
            rate_clearance_applications a
        JOIN 
            users u ON a.user_id = u.user_id
        JOIN 
            properties p ON a.property_id = p.property_id
        ORDER BY 
            a.created_at DESC
        LIMIT 50
    ";
    $notificationStmt = $pdo->prepare($notificationQuery);
    $notificationStmt->execute();
    $notifications = $notificationStmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'count' => $notificationCount,
        'notifications' => $notifications,
        'timestamp' => time()
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
