-- Sample data for Rate Clearance Service

-- Insert sample rate types
INSERT INTO rate_types (name, description, base_rate, rate_unit, category, created_by) VALUES
('Consulting Services', 'Professional consulting and advisory services', 150.00, 'hour', 'Professional Services', 1),
('Software Development', 'Custom software development and programming', 120.00, 'hour', 'Technology', 1),
('Project Management', 'Project management and coordination services', 100.00, 'hour', 'Management', 1),
('Technical Support', 'Technical support and maintenance services', 80.00, 'hour', 'Support', 1),
('Training Services', 'Training and knowledge transfer services', 90.00, 'hour', 'Education', 1),
('Design Services', 'Graphic design and creative services', 75.00, 'hour', 'Creative', 1),
('Legal Services', 'Legal consultation and advisory services', 300.00, 'hour', 'Legal', 1),
('Accounting Services', 'Accounting and financial services', 85.00, 'hour', 'Finance', 1);

-- Insert sample projects
INSERT INTO projects (name, description, status, start_date, end_date, budget, created_by) VALUES
('ERP Implementation', 'Enterprise Resource Planning system implementation', 'active', '2024-01-01', '2024-12-31', 500000.00, 1),
('Website Redesign', 'Company website redesign and development', 'active', '2024-02-01', '2024-06-30', 75000.00, 1),
('Data Migration', 'Legacy system data migration project', 'active', '2024-03-01', '2024-08-31', 150000.00, 1),
('Security Audit', 'Comprehensive security assessment and audit', 'active', '2024-01-15', '2024-04-15', 50000.00, 1),
('Mobile App Development', 'Customer mobile application development', 'active', '2024-04-01', '2024-10-31', 200000.00, 1);

-- Insert sample rate clearances
INSERT INTO rate_clearances (rate_code, rate_type_id, vendor_id, project_id, proposed_rate, current_rate, rate_unit, priority, status, justification, effective_from, expires_at, submitted_by) VALUES
('RC-2024-001', 1, 1, NULL, 175.00, 150.00, 'hour', 'high', 'pending', 'Rate increase requested due to market conditions and increased expertise requirements for specialized consulting services.', '2024-02-01', '2024-12-31', 2),
('RC-2024-002', 2, 2, NULL, 135.00, 120.00, 'hour', 'medium', 'pending', 'Adjustment needed to match current market rates for senior software developers with specialized skills.', '2024-02-15', '2024-12-31', 2),
('RC-2024-003', 3, NULL, 1, 110.00, 100.00, 'hour', 'medium', 'under_review', 'Project complexity requires senior project management expertise, justifying rate increase.', '2024-03-01', '2024-12-31', 3),
('RC-2024-004', 4, 3, NULL, 85.00, 80.00, 'hour', 'low', 'approved', 'Minor adjustment to align with industry standards for technical support services.', '2024-01-01', '2024-12-31', 2),
('RC-2024-005', 5, 4, NULL, 95.00, 90.00, 'hour', 'medium', 'approved', 'Enhanced training curriculum requires additional preparation time and expertise.', '2024-01-15', '2024-12-31', 2),
('RC-2024-006', 6, NULL, 2, 80.00, 75.00, 'hour', 'low', 'rejected', 'Current market analysis does not support the requested rate increase for design services.', '2024-02-01', '2024-12-31', 3),
('RC-2024-007', 1, 5, NULL, 320.00, 300.00, 'hour', 'high', 'pending', 'Specialized legal expertise required for complex regulatory compliance matters.', '2024-03-01', '2024-12-31', 1),
('RC-2024-008', 8, NULL, 3, 90.00, 85.00, 'hour', 'medium', 'pending', 'Additional certification and compliance requirements for financial reporting services.', '2024-02-15', '2024-12-31', 2);

-- Update some rates with approval/rejection details
UPDATE rate_clearances SET 
    status = 'approved', 
    approved_by = 1, 
    approved_at = '2024-01-20 10:30:00',
    approval_comments = 'Approved based on market analysis and service quality.',
    effective_date = '2024-02-01'
WHERE rate_code = 'RC-2024-004';

UPDATE rate_clearances SET 
    status = 'approved', 
    approved_by = 1, 
    approved_at = '2024-01-25 14:15:00',
    approval_comments = 'Justified by enhanced service offering and market conditions.',
    effective_date = '2024-02-01'
WHERE rate_code = 'RC-2024-005';

UPDATE rate_clearances SET 
    status = 'rejected', 
    rejected_by = 1, 
    rejected_at = '2024-02-05 09:45:00',
    rejection_reason = 'Market analysis does not support rate increase. Please provide additional justification.'
WHERE rate_code = 'RC-2024-006';

-- Insert sample rate documents
INSERT INTO rate_documents (rate_clearance_id, document_name, document_path, document_type, file_size, uploaded_by) VALUES
(1, 'Market_Analysis_Report.pdf', '/uploads/rates/RC-2024-001/Market_Analysis_Report.pdf', 'application/pdf', 2048576, 2),
(1, 'Competitor_Rate_Comparison.xlsx', '/uploads/rates/RC-2024-001/Competitor_Rate_Comparison.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 1024000, 2),
(2, 'Developer_Salary_Survey.pdf', '/uploads/rates/RC-2024-002/Developer_Salary_Survey.pdf', 'application/pdf', 1536000, 2),
(3, 'Project_Complexity_Analysis.docx', '/uploads/rates/RC-2024-003/Project_Complexity_Analysis.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 512000, 3),
(7, 'Legal_Market_Rates_2024.pdf', '/uploads/rates/RC-2024-007/Legal_Market_Rates_2024.pdf', 'application/pdf', 3072000, 1);

-- Insert sample rate compliance rules
INSERT INTO rate_compliance_rules (rule_name, rule_description, rate_type_id, min_rate, max_rate, max_increase_percent, approval_required_above, created_by) VALUES
('Consulting Rate Limits', 'Maximum and minimum rates for consulting services', 1, 100.00, 500.00, 25.00, 200.00, 1),
('Development Rate Limits', 'Rate constraints for software development services', 2, 80.00, 300.00, 20.00, 150.00, 1),
('Support Rate Limits', 'Rate boundaries for technical support services', 4, 50.00, 150.00, 15.00, 100.00, 1),
('Training Rate Limits', 'Rate guidelines for training and education services', 5, 60.00, 200.00, 20.00, 120.00, 1),
('Legal Rate Limits', 'Rate constraints for legal services', 7, 200.00, 1000.00, 30.00, 400.00, 1);

-- Insert sample rate history records
INSERT INTO rate_history (rate_clearance_id, old_rate, new_rate, change_reason, changed_by) VALUES
(4, 80.00, 85.00, 'Approved rate increase for technical support services', 1),
(5, 90.00, 95.00, 'Approved rate increase for enhanced training services', 1);

-- Insert sample audit log entries for rate clearance
INSERT INTO audit_log (user_id, action, description, related_id, related_type) VALUES
(2, 'rate_submitted', 'Rate clearance RC-2024-001 submitted for consulting services', 1, 'rate_clearance'),
(2, 'rate_submitted', 'Rate clearance RC-2024-002 submitted for software development', 2, 'rate_clearance'),
(3, 'rate_submitted', 'Rate clearance RC-2024-003 submitted for project management', 3, 'rate_clearance'),
(1, 'rate_approved', 'Rate clearance RC-2024-004 approved for technical support', 4, 'rate_clearance'),
(1, 'rate_approved', 'Rate clearance RC-2024-005 approved for training services', 5, 'rate_clearance'),
(1, 'rate_rejected', 'Rate clearance RC-2024-006 rejected for design services', 6, 'rate_clearance'),
(1, 'rate_submitted', 'Rate clearance RC-2024-007 submitted for legal services', 7, 'rate_clearance'),
(2, 'rate_submitted', 'Rate clearance RC-2024-008 submitted for accounting services', 8, 'rate_clearance');

-- Insert sample notifications for rate clearance
INSERT INTO notifications (user_id, title, message, type, related_id, related_type) VALUES
(1, 'New Rate Clearance Request', 'A new high priority rate clearance request requires your review.', 'info', 1, 'rate_clearance'),
(1, 'New Rate Clearance Request', 'A new medium priority rate clearance request requires your review.', 'info', 2, 'rate_clearance'),
(1, 'New Rate Clearance Request', 'A new high priority rate clearance request requires your review.', 'info', 7, 'rate_clearance'),
(2, 'Rate Approved', 'Your rate clearance request RC-2024-004 has been approved.', 'success', 4, 'rate_clearance'),
(2, 'Rate Approved', 'Your rate clearance request RC-2024-005 has been approved.', 'success', 5, 'rate_clearance'),
(3, 'Rate Rejected', 'Your rate clearance request RC-2024-006 has been rejected. Please review the feedback.', 'error', 6, 'rate_clearance');
