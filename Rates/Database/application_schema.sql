CREATE TABLE rate_clearance_applications (
    application_id SERIAL PRIMARY KEY,  -- Serial to auto-increment
    user_id INT NOT NULL,
    property_id INT NOT NULL,
    applicant_address VARCHAR(255) NOT NULL,
    email_address VARCHAR(255) NOT NULL,
    relationship_to_owner VARCHAR(100) NOT NULL,
    description TEXT,
    title_deed VARCHAR(255),
    identity_proof VARCHAR(255),
    additional_documents VARCHAR(255),
    file_path VARCHAR(255),
    status VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);