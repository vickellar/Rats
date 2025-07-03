-- Migration script to add account_fee_id column to months_fees table
ALTER TABLE months_fees
ADD account_fee_id INT;

ALTER TABLE months_fees
ADD CONSTRAINT fk_account_fee
FOREIGN KEY (account_fee_id) REFERENCES accounts_fees(fee_id);
