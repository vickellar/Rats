
CREATE TABLE `payments` (
  `payment_id` int(11) NOT NULL,
  `property_id` int(11) NOT NULL,
  `account_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `receipt_name` varchar(20) DEFAULT NULL,
  `receipt_fpath` varchar(20) DEFAULT NULL,
  `amount_paid` decimal(15,2) DEFAULT NULL,
  `payment_date` date NOT NULL,
  `payment_method` enum('Bank Transfer','Online','Cash') DEFAULT NULL,
  `transaction_status` enum('Success','Failed','Pending') NOT NULL,
  `invoice_number` int(11) NOT NULL,
  `receipt_number` int(11) NOT NULL,
  `bill_id` int(11) NOT NULL,
  `month_is` int(11) NOT NULL
  FOREIGN KEY (property_id) REFERENCES properties(property_id)ON DELETE CASCADE
    foreign key (account_id) REFERENCES accounts(account_id)ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id)ON DELETE CASCADE
);



CREATE TABLE Clearance_Certificates (
    certificate_id INT PRIMARY KEY AUTO_INCREMENT,
    property_id INT NOT NULL,
    user_id INT NOT NULL,
    application_id INT NOT NULL,
    notes VARCHAR(516) NULL,
    employee_id INT NOT NULL,
    issue_date DATE NOT NULL,
    expiry_date DATE NOT NULL,
    status ENUM('Approved', 'Pending', 'Rejected') NOT NULL,
    FOREIGN KEY (property_id) REFERENCES properties(property_id)ON DELETE CASCADE,
    FOREIGN KEY (application_id) REFERENCES rate_clearance_applications(application_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (employee_id) REFERENCES employees(employee_id)ON DELETE CASCADE
);


