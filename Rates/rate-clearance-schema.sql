-- Rate Clearance Service Database Schema

-- Rate types table
CREATE TABLE IF NOT EXISTS rate_types (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    base_rate DECIMAL(10,2),
    rate_unit ENUM('hour', 'day', 'month', 'project', 'unit') DEFAULT 'hour',
    category VARCHAR(50),
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Projects table (if not exists)
CREATE TABLE IF NOT EXISTS projects (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    status ENUM('active', 'completed', 'on_hold', 'cancelled') DEFAULT 'active',
    start_date DATE,
    end_date DATE,
    budget DECIMAL(15,2),
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Rate clearances table
CREATE TABLE IF NOT EXISTS rate_clearances (
    id INT PRIMARY KEY AUTO_INCREMENT,
    rate_code VARCHAR(20) UNIQUE NOT NULL,
    rate_type_id INT NOT NULL,
    vendor_id INT NULL,
    project_id INT NULL,
    proposed_rate DECIMAL(10,2) NOT NULL,
    current_rate DECIMAL(10,2) NULL,
    rate_unit ENUM('hour', 'day', 'month', 'project', 'unit') DEFAULT 'hour',
    priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
    status ENUM('pending', 'under_review', 'approved', 'rejected', 'expired') DEFAULT 'pending',
    justification TEXT NOT NULL,
    effective_from DATE,
    effective_date DATE NULL,
    expires_at DATE NULL,
    submitted_by INT NOT NULL,
    approved_by INT NULL,
    rejected_by INT NULL,
    approved_at TIMESTAMP NULL,
    rejected_at TIMESTAMP NULL,
    approval_comments TEXT,
    rejection_reason TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (rate_type_id) REFERENCES rate_types(id),
    FOREIGN KEY (vendor_id) REFERENCES vendors(id),
    FOREIGN KEY (project_id) REFERENCES projects(id),
    FOREIGN KEY (submitted_by) REFERENCES users(id),
    FOREIGN KEY (approved_by) REFERENCES users(id),
    FOREIGN KEY (rejected_by) REFERENCES users(id),
    CHECK (vendor_id IS NOT NULL OR project_id IS NOT NULL)
);

-- Rate documents table (for supporting documents)
CREATE TABLE IF NOT EXISTS rate_documents (
    id INT PRIMARY KEY AUTO_INCREMENT,
    rate_clearance_id INT NOT NULL,
    document_name VARCHAR(255) NOT NULL,
    document_path VARCHAR(500) NOT NULL,
    document_type VARCHAR(50),
    file_size INT,
    uploaded_by INT NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (rate_clearance_id) REFERENCES rate_clearances(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(id)
);

-- Rate approval workflow table
CREATE TABLE IF NOT EXISTS rate_approval_workflow (
    id INT PRIMARY KEY AUTO_INCREMENT,
    rate_clearance_id INT NOT NULL,
    approver_id INT NOT NULL,
    approval_level INT NOT NULL,
    status ENUM('pending', 'approved', 'rejected', 'skipped') DEFAULT 'pending',
    comments TEXT,
    action_date TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (rate_clearance_id) REFERENCES rate_clearances(id) ON DELETE CASCADE,
    FOREIGN KEY (approver_id) REFERENCES users(id),
    UNIQUE KEY unique_approval (rate_clearance_id, approver_id, approval_level)
);

-- Rate history table (for tracking rate changes)
CREATE TABLE IF NOT EXISTS rate_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    rate_clearance_id INT NOT NULL,
    old_rate DECIMAL(10,2),
    new_rate DECIMAL(10,2),
    change_reason TEXT,
    changed_by INT NOT NULL,
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (rate_clearance_id) REFERENCES rate_clearances(id),
    FOREIGN KEY (changed_by) REFERENCES users(id)
);

-- Rate compliance rules table
CREATE TABLE IF NOT EXISTS rate_compliance_rules (
    id INT PRIMARY KEY AUTO_INCREMENT,
    rule_name VARCHAR(100) NOT NULL,
    rule_description TEXT,
    rate_type_id INT,
    min_rate DECIMAL(10,2),
    max_rate DECIMAL(10,2),
    max_increase_percent DECIMAL(5,2),
    approval_required_above DECIMAL(10,2),
    is_active BOOLEAN DEFAULT TRUE,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (rate_type_id) REFERENCES rate_types(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Indexes for better performance
CREATE INDEX idx_rate_clearances_status ON rate_clearances(status);
CREATE INDEX idx_rate_clearances_priority ON rate_clearances(priority);
CREATE INDEX idx_rate_clearances_type ON rate_clearances(rate_type_id);
CREATE INDEX idx_rate_clearances_vendor ON rate_clearances(vendor_id);
CREATE INDEX idx_rate_clearances_project ON rate_clearances(project_id);
CREATE INDEX idx_rate_clearances_submitted_by ON rate_clearances(submitted_by);
CREATE INDEX idx_rate_clearances_expires_at ON rate_clearances(expires_at);
CREATE INDEX idx_rate_clearances_created_at ON rate_clearances(created_at);
CREATE INDEX idx_rate_documents_rate_id ON rate_documents(rate_clearance_id);
CREATE INDEX idx_rate_approval_workflow_rate_id ON rate_approval_workflow(rate_clearance_id);
CREATE INDEX idx_rate_approval_workflow_approver ON rate_approval_workflow(approver_id);
CREATE INDEX idx_rate_history_rate_id ON rate_history(rate_clearance_id);
