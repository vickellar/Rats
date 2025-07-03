-- Drop tables if they exist (in reverse order of dependencies)
DROP TABLE IF EXISTS clearance_certificates;
DROP TABLE IF EXISTS payments;
DROP TABLE IF EXISTS accounts_fees;
DROP TABLE IF EXISTS rate_clearance_applications;
DROP TABLE IF EXISTS accounts;
DROP TABLE IF EXISTS employees;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS properties;

-- Create base tables first

CREATE TABLE properties (
    property_id INT PRIMARY KEY AUTO_INCREMENT,
    address VARCHAR(255) NOT NULL
);

CREATE TABLE users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL
);

CREATE TABLE employees (
    employee_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    position VARCHAR(100)
);

CREATE TABLE accounts (
    account_id INT PRIMARY KEY AUTO_INCREMENT,
    property_id INT NOT NULL,
    account_number VARCHAR(50) NOT NULL,
    FOREIGN KEY (property_id) REFERENCES properties(property_id) ON DELETE CASCADE
);

CREATE TABLE rate_clearance_applications (
    application_id INT PRIMARY KEY AUTO_INCREMENT,
    property_id INT NOT NULL,
    user_id INT NOT NULL,
    application_date DATE NOT NULL,
    status ENUM('Pending', 'Approved', 'Rejected') NOT NULL,
    FOREIGN KEY (property_id) REFERENCES properties(property_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

CREATE TABLE accounts_fees (
    account_fee_id INT PRIMARY KEY AUTO_INCREMENT,
    account_id INT NOT NULL,
    property_id INT NOT NULL,
    application_id INT NOT NULL,
    processing_fee DECIMAL(15,2),
    total_balance DECIMAL(15,2),
    FOREIGN KEY (account_id) REFERENCES accounts(account_id) ON DELETE CASCADE,
    FOREIGN KEY (property_id) REFERENCES properties(property_id) ON DELETE CASCADE,
    FOREIGN KEY (application_id) REFERENCES rate_clearance_applications(application_id) ON DELETE CASCADE
);

CREATE TABLE payments (
    payment_id INT PRIMARY KEY AUTO_INCREMENT,
    property_id INT NOT NULL,
    account_id INT NOT NULL,
    user_id INT NOT NULL,
    amount_paid DECIMAL(15,2),
    payment_date DATE NOT NULL,
    payment_method ENUM('Bank Transfer', 'Online', 'Cash'),
    transaction_status ENUM('Success', 'Failed', 'Pending') NOT NULL,
    FOREIGN KEY (property_id) REFERENCES properties(property_id) ON DELETE CASCADE,
    FOREIGN KEY (account_id) REFERENCES accounts(account_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

CREATE TABLE clearance_certificates (
    certificate_id INT PRIMARY KEY AUTO_INCREMENT,
    property_id INT NOT NULL,
    user_id INT NOT NULL,
    application_id INT NOT NULL,
    notes VARCHAR(516) NULL,
    employee_id INT NOT NULL,
    issue_date DATE NOT NULL,
    expiry_date DATE NOT NULL,
    status ENUM('Approved', 'Pending', 'Rejected') NOT NULL,
    FOREIGN KEY (property_id) REFERENCES properties(property_id) ON DELETE CASCADE,
    FOREIGN KEY (application_id) REFERENCES rate_clearance_applications(application_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (employee_id) REFERENCES employees(employee_id) ON DELETE CASCADE
);