<?php
class NotificationManager {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Create a new notification
     */
    public function createNotification($userId, $applicationId, $type, $title, $message, $priority = 'medium', $metadata = null) {
        try {
            $sql = "INSERT INTO notification_history 
                    (user_id, application_id, notification_type, title, message, priority, metadata) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $userId,
                $applicationId,
                $type,
                $title,
                $message,
                $priority,
                $metadata ? json_encode($metadata) : null
            ]);
            
            return $this->pdo->lastInsertId();
        } catch (Exception $e) {
            error_log("Error creating notification: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Mark notification as read
     */
    public function markAsRead($notificationId, $userId) {
        try {
            $sql = "UPDATE notification_history 
                    SET is_read = TRUE, read_at = NOW() 
                    WHERE id = ? AND user_id = ?";
            
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([$notificationId, $userId]);
        } catch (Exception $e) {
            error_log("Error marking notification as read: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Mark all notifications as read for a user
     */
    public function markAllAsRead($userId) {
        try {
            $sql = "UPDATE notification_history 
                    SET is_read = TRUE, read_at = NOW() 
                    WHERE user_id = ? AND is_read = FALSE";
            
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([$userId]);
        } catch (Exception $e) {
            error_log("Error marking all notifications as read: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get notifications for a user with pagination and filtering
     */
    public function getNotifications($userId, $options = []) {
        $limit = $options['limit'] ?? 20;
        $offset = $options['offset'] ?? 0;
        $type = $options['type'] ?? null;
        $isRead = $options['is_read'] ?? null;
        $priority = $options['priority'] ?? null;
        $dateFrom = $options['date_from'] ?? null;
        $dateTo = $options['date_to'] ?? null;
        
        $whereConditions = ['nh.user_id = ?'];
        $params = [$userId];
        
        if ($type) {
            $whereConditions[] = 'nh.notification_type = ?';
            $params[] = $type;
        }
        
        if ($isRead !== null) {
            $whereConditions[] = 'nh.is_read = ?';
            $params[] = $isRead ? 1 : 0;
        }
        
        if ($priority) {
            $whereConditions[] = 'nh.priority = ?';
            $params[] = $priority;
        }
        
        if ($dateFrom) {
            $whereConditions[] = 'nh.created_at >= ?';
            $params[] = $dateFrom;
        }
        
        if ($dateTo) {
            $whereConditions[] = 'nh.created_at <= ?';
            $params[] = $dateTo;
        }
        
        $whereClause = implode(' AND ', $whereConditions);
        
        try {
            $sql = "SELECT nh.*, 
                           rca.status as application_status,
                           u.first_name, u.surname,
                           p.address as property_address
                    FROM notification_history nh
                    LEFT JOIN rate_clearance_applications rca ON nh.application_id = rca.application_id
                    LEFT JOIN users u ON rca.user_id = u.user_id
                    LEFT JOIN properties p ON rca.property_id = p.property_id
                    WHERE {$whereClause}
                    ORDER BY nh.created_at DESC
                    LIMIT ? OFFSET ?";
            
            $params[] = $limit;
            $params[] = $offset;
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting notifications: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get notification statistics
     */
    public function getNotificationStats($userId) {
        try {
            $sql = "SELECT 
                        COUNT(*) as total,
                        SUM(CASE WHEN is_read = FALSE THEN 1 ELSE 0 END) as unread,
                        SUM(CASE WHEN is_read = TRUE THEN 1 ELSE 0 END) as read,
                        SUM(CASE WHEN priority = 'urgent' AND is_read = FALSE THEN 1 ELSE 0 END) as urgent_unread,
                        SUM(CASE WHEN priority = 'high' AND is_read = FALSE THEN 1 ELSE 0 END) as high_unread,
                        SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) THEN 1 ELSE 0 END) as today
                    FROM notification_history 
                    WHERE user_id = ?";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$userId]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting notification stats: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Delete old notifications (cleanup)
     */
    public function cleanupOldNotifications($daysOld = 90) {
        try {
            $sql = "DELETE FROM notification_history 
                    WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)";
            
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([$daysOld]);
        } catch (Exception $e) {
            error_log("Error cleaning up notifications: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Create notification for new application
     */
    public function notifyNewApplication($applicationId, $applicantName, $propertyAddress) {
        // Get all admin users
        $adminUsers = $this->getAdminUsers();
        
        foreach ($adminUsers as $admin) {
            $this->createNotification(
                $admin['user_id'],
                $applicationId,
                'new_application',
                'New Rate Clearance Application',
                "New application submitted by {$applicantName} for property at {$propertyAddress}",
                'high',
                ['applicant_name' => $applicantName, 'property_address' => $propertyAddress]
            );
        }
    }
    
    /**
     * Create notification for status change
     */
    public function notifyStatusChange($applicationId, $oldStatus, $newStatus, $changedBy) {
        // Get application details
        $application = $this->getApplicationDetails($applicationId);
        if (!$application) return;
        
        // Notify the applicant
        $this->createNotification(
            $application['user_id'],
            $applicationId,
            'status_change',
            'Application Status Updated',
            "Your application status has been changed from {$oldStatus} to {$newStatus}",
            $newStatus === 'approved' ? 'high' : 'medium',
            ['old_status' => $oldStatus, 'new_status' => $newStatus, 'changed_by' => $changedBy]
        );
    }
    
    private function getAdminUsers() {
        $sql = "SELECT user_id, username FROM users WHERE role = 'admin'";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function getApplicationDetails($applicationId) {
        $sql = "SELECT rca.*, u.first_name, u.surname, p.address 
                FROM rate_clearance_applications rca
                JOIN users u ON rca.user_id = u.user_id
                JOIN properties p ON rca.property_id = p.property_id
                WHERE rca.application_id = ?";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$applicationId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>
