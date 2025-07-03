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
require_once 'notification_manager.php';

try {
    $notificationManager = new NotificationManager($pdo);
    
    // Get parameters
    $page = max(1, intval($_GET['page'] ?? 1));
    $filter = $_GET['filter'] ?? 'all';
    $limit = max(1, min(50, intval($_GET['limit'] ?? 10)));
    $offset = ($page - 1) * $limit;
    
    // Build filter options
    $options = [
        'limit' => $limit,
        'offset' => $offset
    ];
    
    switch ($filter) {
        case 'unread':
            $options['is_read'] = false;
            break;
        case 'urgent':
            $options['priority'] = 'urgent';
            break;
        case 'today':
            $options['date_from'] = date('Y-m-d 00:00:00');
            break;
    }
    
    // Get notifications
    $notifications = $notificationManager->getNotifications($_SESSION['user_id'], $options);
    
    // Get total count for pagination
    $totalOptions = $options;
    unset($totalOptions['limit'], $totalOptions['offset']);
    $totalNotifications = $notificationManager->getNotifications($_SESSION['user_id'], $totalOptions);
    $totalCount = count($totalNotifications);
    
    $totalPages = ceil($totalCount / $limit);
    
    echo json_encode([
        'success' => true,
        'notifications' => $notifications,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_items' => $totalCount,
            'items_per_page' => $limit
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
