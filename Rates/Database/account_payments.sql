
CREATE TABLE months_fees (
    month_id INT PRIMARY KEY AUTO_INCREMENT,
    month_name VARCHAR(20),
    month_balance DECIMAL(10, 2),
    account_id INT,
    property_id INT,
    application_id INT,
    FOREIGN KEY (account_id) REFERENCES accounts(account_id),
    FOREIGN KEY (property_id) REFERENCES properties(property_id),
    FOREIGN KEY (application_id) REFERENCES rate_clearance_applications(application_id)
);

CREATE TABLE accounts_Fees (
    fee_id INT PRIMARY KEY AUTO_INCREMENT,
    processing_fee DECIMAL(10, 2),
    total_balance DECIMAL(10, 2),
    account_id INT,
    property_id INT,
    application_id INT,
    FOREIGN KEY (account_id) REFERENCES accounts(account_id),
    FOREIGN KEY (property_id) REFERENCES properties(property_id),
    FOREIGN KEY (application_id) REFERENCES rate_clearance_applications(application_id)
);

