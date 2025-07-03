CREATE TABLE IF NOT EXISTS replies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    message TEXT NOT NULL,
    user_id INT NOT NULL,
    parent_id INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Example foreign key constraints (uncomment and adjust as needed)
    -- FOREIGN KEY (user_id) REFERENCES users(id),
    -- FOREIGN KEY (parent_id) REFERENCES posts(id)
);
