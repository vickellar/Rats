
-- Insert Account Data
INSERT INTO Accounts (account_number, balance) VALUES ('123456', 45.00);

-- Insert Month Data for Account 1
INSERT INTO Months (month_name, month_balance, account_id) VALUES ('January', 12.00, 1);
INSERT INTO Months (month_name, month_balance, account_id) VALUES ('February', 12.00, 1);
INSERT INTO Months (month_name, month_balance, account_id) VALUES ('March', 12.00, 1);

-- Insert Fee Data for Account 1
INSERT INTO Fees (processing_fee, total_balance, account_id) VALUES (56.00, 105.00, 1);

-- Insert Account Data for Account 2
INSERT INTO Accounts (account_number, balance) VALUES ('123456', 45.00);

-- Insert Month Data for Account 2
INSERT INTO Months (month_name, month_balance, account_id) VALUES ('January', 12.00, 2);
INSERT INTO Months (month_name, month_balance, account_id) VALUES ('February', 12.00, 2);
INSERT INTO Months (month_name, month_balance, account_id) VALUES ('March', 12.00, 2);

-- Insert Fee Data for Account 2
INSERT INTO Fees (processing_fee, total_balance, account_id) VALUES (56.00, 105.00, 2);





--query to get all the months and their balances for a specific account

--for account 1
SELECT 
    a.account_number,
    a.balance AS 'Balance for Account 1 (USD)',
    m.month_name AS 'Month',
    m.month_balance AS 'Month Balance (USD)',
    f.processing_fee AS 'Processing Fee',
    f.total_balance AS 'Total Balance'
FROM 
    Accounts a
JOIN 
    Months m ON a.account_id = m.account_id
JOIN 
    Fees f ON a.account_id = f.account_id
WHERE 
    a.account_id = 1;


--for account 2
SELECT 
    a.account_number,
    a.balance AS 'Balance for Account 1 (USD)',
    m.month_name AS 'Month',
    m.month_balance AS 'Month Balance (USD)',
    f.processing_fee AS 'Processing Fee',
    f.total_balance AS 'Total Balance'
FROM 
    Accounts a
JOIN 
    Months m ON a.account_id = m.account_id
JOIN 
    Fees f ON a.account_id = f.account_id
WHERE 
    a.account_id IN (1, 2);


--for account 3
SELECT 
    a.account_number,
    a.balance AS 'Balance for Account 1 (USD)',
    m.month_name AS 'Month',
    m.month_balance AS 'Month Balance (USD)',
    f.processing_fee AS 'Processing Fee',
    f.total_balance AS 'Total Balance'
FROM 
    Accounts a
JOIN 
    Months m ON a.account_id = m.account_id
JOIN 
    Fees f ON a.account_id = f.account_id
WHERE 
    a.account_id IN (1, 2, 3);