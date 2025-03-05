CREATE TABLE rate_clearance_applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    property_id INT NOT NULL,
    applicant_name VARCHAR(255) NOT NULL,
    contact_number VARCHAR(15) NOT NULL,
    email_address VARCHAR(100) NOT NULL,
    relationship_to_owner VARCHAR(100),
    description TEXT,
    title_deed VARCHAR(255),
    previous_certificate VARCHAR(255),
    identity_proof VARCHAR(255),
    additional_documents VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (property_id) REFERENCES properties(property_id)
);
