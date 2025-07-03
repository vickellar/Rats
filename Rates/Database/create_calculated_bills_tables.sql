-- Create table to store calculated bills
CREATE TABLE calculated_bills (
    bill_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    property_id INT NOT NULL,
    account_id INT NOT NULL,
    application_id int UNSIGNED NOT NULL,
    total_balance DECIMAL(10, 2) NOT NULL,
    processing_fee DECIMAL(10, 2) NOT NULL,
    overall_total DECIMAL(10, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (property_id) REFERENCES properties(property_id),
    FOREIGN KEY (account_id) REFERENCES accounts(account_id)
    FOREIGN KEY (application_id) REFERENCES rate_clearance_applications(application_id)
);

-- Create table to store monthly breakdown for calculated bills
CREATE TABLE calculated_bill_months (
    month_id INT PRIMARY KEY AUTO_INCREMENT,
    bill_id INT NOT NULL,
    month1_name VARCHAR(20) NOT NULL,
    month4_name VARCHAR(20) NOT NULL,
    month3_name VARCHAR(20) NOT NULL,
    month4_name VARCHAR(20),
    month_balance DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (bill_id) REFERENCES calculated_bills(bill_id)
);
