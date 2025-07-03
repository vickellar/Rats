-- Finance Director Dashboard Database Schema

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('finance_director', 'accountant', 'manager', 'employee') NOT NULL,
    first_name VARCHAR(50),
    last_name VARCHAR(50),
    phone VARCHAR(20),
    profile_picture VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE
);



-- Invoices table
CREATE TABLE invoices (
    invoice_id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    invoice_number INT(11) NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    property_id INT NOT NULL,
    application_id INT UNSIGNED NOT NULL,
    user_id  INT NOT NULL,
    account_id INT NOT NULL,
    currency VARCHAR(3) DEFAULT 'USD',
    invoice_date DATE NOT NULL,
    due_date DATE,
    description TEXT,
    status ENUM('pending', 'approved', 'rejected', 'paid') DEFAULT 'pending',
    submitted_by INT,
    comments TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (submitted_by) REFERENCES employees(employee_id),
    FOREIGN KEY (account_id) REFERENCES accounts(account_id),
    FOREIGN KEY (property_id) REFERENCES properties(property_id),
    FOREIGN KEY (application_id) REFERENCES rate_clearance_applications(application_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

-- Payments table
CREATE TABLE IF NOT EXISTS payments (
    id VARCHAR(20) PRIMARY KEY,
    invoice_id VARCHAR(20),
    amount DECIMAL(15,2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'USD',
    payment_method ENUM('bank_transfer', 'check', 'credit_card', 'cash') DEFAULT 'bank_transfer',
    reference_number VARCHAR(50),
    description TEXT,
    due_date DATE,
    status ENUM('pending', 'approved', 'rejected', 'processed', 'completed') DEFAULT 'pending',
    priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
    requested_by INT,
    approved_by INT,
    rejected_by INT,
    processed_by INT,
    approved_at TIMESTAMP NULL,
    rejected_at TIMESTAMP NULL,
    processed_at TIMESTAMP NULL,
    rejection_reason TEXT,
    comments TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (invoice_id) REFERENCES invoices(id),
    FOREIGN KEY (vendor_id) REFERENCES vendors(id),
    FOREIGN KEY (requested_by) REFERENCES users(id),
    FOREIGN KEY (approved_by) REFERENCES users(id),
    FOREIGN KEY (rejected_by) REFERENCES users(id),
    FOREIGN KEY (processed_by) REFERENCES users(id)
);


-- Audit log table
CREATE TABLE IF NOT EXISTS audit_log (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    action VARCHAR(50) NOT NULL,
    description TEXT,
    related_id VARCHAR(50),
    related_type ENUM('invoice', 'payment', 'budget', 'user', 'vendor'),
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Financial reports table
CREATE TABLE IF NOT EXISTS financial_reports (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    type ENUM('monthly_summary', 'invoice_analysis', 'budget_variance', 'payment_summary') NOT NULL,
    parameters JSON,
    file_path VARCHAR(255),
    generated_by INT,
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (generated_by) REFERENCES employees(employee_id)
);

-- Notifications table
CREATE TABLE IF NOT EXISTS notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    payment_id INT NOT NULL,
    title VARCHAR(100) NOT NULL,
    message TEXT,
    type ENUM('info', 'warning', 'error', 'success') DEFAULT 'info',
    related_id VARCHAR(50),
    related_type ENUM('invoice', 'payment', 'budget', 'report'),
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (payment_id) REFERENCES payments(payment_id)
);

-- Settings table
CREATE TABLE IF NOT EXISTS settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(50) UNIQUE NOT NULL,
    setting_value TEXT,
    description TEXT,
    updated_by INT NOT null,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (updated_by) REFERENCES employees(employee_id)
);

-- Indexes for better performance
CREATE INDEX idx_invoices_status ON invoices(status);
CREATE INDEX idx_invoices_vendor ON invoices(vendor_id);
CREATE INDEX idx_invoices_date ON invoices(invoice_date);
CREATE INDEX idx_payments_status ON payments(status);
CREATE INDEX idx_payments_vendor ON payments(vendor_id);
CREATE INDEX idx_payments_due_date ON payments(due_date);
CREATE INDEX idx_audit_log_user ON audit_log(user_id);
CREATE INDEX idx_audit_log_date ON audit_log(created_at);
CREATE INDEX idx_notifications_user ON notifications(user_id);
CREATE INDEX idx_notifications_read ON notifications(is_read);
