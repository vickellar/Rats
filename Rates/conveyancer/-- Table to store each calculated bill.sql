-- Table to store each calculated bill (one per calculation per account)
CREATE TABLE calculated_bills (
    bill_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    property_id INT NOT NULL,
    account_id INT NOT NULL,
    total_balance DECIMAL(15,2) NOT NULL,
    processing_fee DECIMAL(15,2) NOT NULL,
    overall_total DECIMAL(15,2) NOT NULL,
    calculated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (property_id) REFERENCES properties(property_id) ON DELETE CASCADE,
    FOREIGN KEY (account_id) REFERENCES accounts(account_id) ON DELETE CASCADE
);

-- Table to store monthly balances for each bill
CREATE TABLE calculated_bill_months (
    month_id INT PRIMARY KEY AUTO_INCREMENT,
    bill_id INT NOT NULL,
    month1_name VARCHAR(20) NOT NULL,
    month2_name VARCHAR(20) NOT NULL,
    month3_name VARCHAR(20) NOT NULL,
    month4_name VARCHAR(20),
    month_balance DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (bill_id) REFERENCES calculated_bills(bill_id)
);