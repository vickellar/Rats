-- Create notification history table
CREATE TABLE IF NOT EXISTS notification_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    application_id INT NOT NULL,
    notification_type ENUM('new_application', 'status_change', 'reply_added', 'forwarded', 'reminder') NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    read_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    metadata JSON NULL,
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (application_id) REFERENCES rate_clearance_applications(application_id) ON DELETE CASCADE,
    INDEX idx_user_read (user_id, is_read),
    INDEX idx_created_at (created_at),
    INDEX idx_application (application_id)
);

-- Create notification preferences table
CREATE TABLE IF NOT EXISTS notification_preferences (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    notification_type VARCHAR(50) NOT NULL,
    enabled BOOLEAN DEFAULT TRUE,
    email_enabled BOOLEAN DEFAULT TRUE,
    push_enabled BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_type (user_id, notification_type)
);

-- Insert default notification preferences for existing users
INSERT IGNORE INTO notification_preferences (user_id, notification_type, enabled, email_enabled, push_enabled)
SELECT u.user_id, 'new_application', TRUE, TRUE, TRUE FROM users u WHERE u.role = 'admin'
UNION ALL
SELECT u.user_id, 'status_change', TRUE, TRUE, TRUE FROM users u WHERE u.role = 'admin'
UNION ALL
SELECT u.user_id, 'reply_added', TRUE, TRUE, FALSE FROM users u WHERE u.role = 'admin'
UNION ALL
SELECT u.user_id, 'forwarded', TRUE, FALSE, TRUE FROM users u WHERE u.role = 'admin'
UNION ALL
SELECT u.user_id, 'reminder', TRUE, TRUE, TRUE FROM users u WHERE u.role = 'admin';
