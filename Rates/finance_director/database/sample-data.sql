-- Sample data for Finance Director Dashboard

-- Insert sample users
INSERT INTO users (username, email, password_hash, role, first_name, last_name, phone) VALUES
('finance_director', 'director@company.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'finance_director', 'John', 'Smith', '+1-555-0101'),
('accountant1', 'accountant1@company.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'accountant', 'Sarah', 'Johnson', '+1-555-0102'),
('manager1', 'manager1@company.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'manager', 'Mike', 'Davis', '+1-555-0103');

-- Insert sample vendors
INSERT INTO vendors (name, email, phone, address, tax_id, payment_terms) VALUES
('Acme Corporation', 'billing@acmecorp.com', '+1-555-1001', '123 Business St, City, State 12345', 'TAX123456', 30),
('Tech Solutions Ltd', 'accounts@techsolutions.com', '+1-555-1002', '456 Tech Ave, City, State 12346', 'TAX789012', 15),
('Office Supplies Inc', 'billing@officesupplies.com', '+1-555-1003', '789 Supply Rd, City, State 12347', 'TAX345678', 30),
('Global Services Inc', 'finance@globalservices.com', '+1-555-1004', '321 Service Blvd, City, State 12348', 'TAX901234', 45),
('Professional Consulting', 'billing@proconsult.com', '+1-555-1005', '654 Consult Way, City, State 12349', 'TAX567890', 30);

-- Insert sample budget categories
INSERT INTO budget_categories (name, description, annual_budget, monthly_budget, department) VALUES
('Operations', 'General operational expenses', 1800000.00, 150000.00, 'Operations'),
('Marketing', 'Marketing and advertising expenses', 1200000.00, 100000.00, 'Marketing'),
('Technology', 'IT and technology expenses', 2400000.00, 200000.00, 'IT'),
('Human Resources', 'HR and employee-related expenses', 600000.00, 50000.00, 'HR'),
('Facilities', 'Office and facility expenses', 960000.00, 80000.00, 'Facilities');

-- Insert sample invoices
INSERT INTO invoices (id, vendor_id, amount, invoice_date, due_date, description, status, priority, purchase_order_number, department, budget_category, submitted_by) VALUES
('INV-2024-001', 1, 15000.00, '2024-01-15', '2024-02-14', 'Q1 Office Supplies and Equipment', 'urgent', 'high', 'PO-2024-001', 'Operations', 'Operations', 2),
('INV-2024-002', 2, 8500.00, '2024-01-14', '2024-01-29', 'Software Licenses Annual Renewal', 'pending', 'medium', 'PO-2024-002', 'IT', 'Technology', 2),
('INV-2024-003', 3, 2300.00, '2024-01-13', '2024-02-12', 'Monthly Office Supplies', 'pending', 'low', 'PO-2024-003', 'Operations', 'Operations', 2),
('INV-2024-004', 4, 25000.00, '2024-01-12', '2024-02-26', 'Q1 Consulting Services', 'pending', 'high', 'PO-2024-004', 'Operations', 'Operations', 3),
('INV-2024-005', 5, 12000.00, '2024-01-11', '2024-02-10', 'HR Training and Development', 'approved', 'medium', 'PO-2024-005', 'HR', 'Human Resources', 2),
('INV-2024-006', 1, 5500.00, '2024-01-10', '2024-02-09', 'Facility Maintenance Services', 'approved', 'medium', 'PO-2024-006', 'Facilities', 'Facilities', 2);

-- Insert sample payments
INSERT INTO payments (id, invoice_id, vendor_id, amount, payment_method, description, due_date, status, priority, requested_by) VALUES
('PAY-2024-045', 'INV-2024-004', 4, 25000.00, 'bank_transfer', 'Q1 Consulting Services Payment', '2024-01-20', 'pending', 'high', 3),
('PAY-2024-046', NULL, 2, 1200.00, 'bank_transfer', 'Employee Travel Reimbursement', '2024-01-18', 'pending', 'medium', 2),
('PAY-2024-047', 'INV-2024-005', 5, 12000.00, 'bank_transfer', 'HR Training Payment', '2024-01-25', 'approved', 'medium', 2),
('PAY-2024-048', 'INV-2024-006', 1, 5500.00, 'check', 'Facility Maintenance Payment', '2024-01-22', 'approved', 'medium', 2);

-- Insert sample budget allocations for current year and month
INSERT INTO budget_allocations (category_id, year, month, allocated_amount, spent_amount, remaining_amount) VALUES
(1, 2024, 1, 150000.00, 90000.00, 60000.00),
(2, 2024, 1, 100000.00, 45000.00, 55000.00),
(3, 2024, 1, 200000.00, 150000.00, 50000.00),
(4, 2024, 1, 50000.00, 30000.00, 20000.00),
(5, 2024, 1, 80000.00, 48000.00, 32000.00);

-- Insert sample audit log entries
INSERT INTO audit_log (user_id, action, description, related_id, related_type) VALUES
(1, 'invoice_approved', 'Invoice INV-2024-005 approved for $12,000', 'INV-2024-005', 'invoice'),
(1, 'invoice_approved', 'Invoice INV-2024-006 approved for $5,500', 'INV-2024-006', 'invoice'),
(1, 'payment_approved', 'Payment PAY-2024-047 approved for $12,000', 'PAY-2024-047', 'payment'),
(1, 'payment_approved', 'Payment PAY-2024-048 approved for $5,500', 'PAY-2024-048', 'payment'),
(1, 'login', 'User logged into Finance Director Dashboard', NULL, NULL);

-- Insert sample notifications
INSERT INTO notifications (user_id, title, message, type, related_id, related_type) VALUES
(1, 'Urgent Invoice Review', 'Invoice INV-2024-001 requires immediate attention - High priority', 'warning', 'INV-2024-001', 'invoice'),
(1, 'Payment Approval Needed', 'Payment PAY-2024-045 is pending your approval', 'info', 'PAY-2024-045', 'payment'),
(1, 'Budget Alert', 'Technology budget is 75% utilized for this month', 'warning', '3', 'budget');

-- Insert sample settings
INSERT INTO settings (setting_key, setting_value, description, updated_by) VALUES
('monthly_budget_limit', '500000', 'Monthly budget limit in USD', 1),
('auto_approve_threshold', '1000', 'Auto-approve payments under this amount', 1),
('notification_email', 'director@company.com', 'Email for system notifications', 1),
('invoice_retention_days', '2555', 'Days to retain invoice records', 1),
('payment_approval_timeout', '72', 'Hours before payment approval times out', 1);
