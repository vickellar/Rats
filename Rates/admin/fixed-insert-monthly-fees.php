<?php
// Include database connection
require_once '../Database/db.php';

// Start session to handle user data
session_start();

$calculatedBy = $_SESSION['employee_id'];
echo "employee_id = " . $calculatedBy;

// Check if employee_id is set in session
if (!$calculatedBy) {
    error_log("Error: employee_id is not set in session");
    echo "<div style='color:red'>Error: You must be logged in to access this page.</div>";
    exit;
}

// Debug log to track execution
error_log("Script started - " . date('Y-m-d H:i:s'));

// Fetch account number based on application_id or property_id
if (isset($_GET['property_id'])) {
    $propertyId = $_GET['property_id'];
    error_log("Property ID received: " . $propertyId);
    echo "property id " . htmlspecialchars($propertyId);

    // Fetch application_id from rate_clearance_applications table
    try {
        $stmtApp = $pdo->prepare("SELECT application_id FROM rate_clearance_applications WHERE property_id = :propertyId LIMIT 1");
        $stmtApp->execute(['propertyId' => $propertyId]);
        $application = $stmtApp->fetch();
        $applicationId = $application ? $application['application_id'] : null;
        error_log("Application ID fetched: " . ($applicationId ?? 'null'));
    } catch (PDOException $e) {
        error_log("Error fetching application_id: " . $e->getMessage(), 3, __DIR__ . '/../logfile/database_errors.log');
        echo "<div style='color:red'>Error fetching application data: " . $e->getMessage() . "</div>";
        $applicationId = null;
    }

    // Fetch account_id and account_number from accounts table
    try {
        $stmtAcc = $pdo->prepare("SELECT account_id, account_number FROM accounts WHERE property_id = :propertyId");
        $stmtAcc->execute(['propertyId' => $propertyId]);
        $accounts = $stmtAcc->fetchAll();

        if ($accounts) {
            $accountCount = count($accounts);
            $accountNumbers = array_column($accounts, 'account_number');
            $accountIds = array_column($accounts, 'account_id');
            error_log("Accounts found: " . $accountCount);
            error_log("Account IDs: " . implode(", ", $accountIds));
        } else {
            $accountCount = 0;
            $accountNumbers = [];
            $accountIds = [];
            error_log("No accounts found for property ID: " . $propertyId);
            echo "<div style='color:orange'>No account found for the given property ID.</div>";
        }
    } catch (PDOException $e) {
        error_log("Error fetching accounts: " . $e->getMessage(), 3, __DIR__ . '/../logfile/database_errors.log');
        echo "<div style='color:red'>Error fetching account data: " . $e->getMessage() . "</div>";
        $accountCount = 0;
        $accountNumbers = [];
        $accountIds = [];
    }
} else {
    error_log("Error: No property_id received");
    echo "<div style='color:red'>Error: No property ID provided</div>";
    $accountCount = 0;
    $applicationId = null;
    $accountIds = [];
    $propertyId = null;
}

// CORRECTED insertMonthlyFees function with proper table name and validation
function insertMonthlyFees($pdo, $accountIds, $propertyId, $applicationId, $postData) {
    error_log("insertMonthlyFees called with: " . count($accountIds) . " accounts");
    error_log("Property ID: " . $propertyId . ", Application ID: " . $applicationId);
    
    // Validate required parameters
    if (empty($accountIds)) {
        error_log("ERROR: accountIds is empty");
        return false;
    }
    
    if (empty($propertyId)) {
        error_log("ERROR: propertyId is empty");
        return false;
    }
    
    if (empty($applicationId)) {
        error_log("ERROR: applicationId is empty");
        return false;
    }
    
    $success = true;
    $accountCount = min(count($accountIds), 3); // Limit to 3 accounts
    $insertedRecords = 0;
    
    try {
        // Begin transaction for data integrity
        $pdo->beginTransaction();
        error_log("Transaction started for monthly fees insertion");
        
        for ($i = 0; $i < $accountCount; $i++) {
            $accId = $accountIds[$i];
            $accountIndex = $i + 1; // Accounts are numbered from 1 in the form
            
            error_log("Processing account index: " . $accountIndex . ", Account ID: " . $accId);
            
            // Process each month (1-4)
            for ($monthNum = 1; $monthNum <= 4; $monthNum++) {
                $monthNameKey = "month{$monthNum}_name_account{$accountIndex}";
                $monthBalanceKey = "month{$monthNum}_balance_account{$accountIndex}";
                
                error_log("Looking for keys: {$monthNameKey} and {$monthBalanceKey}");
                
                // Check if the month data exists in the POST data
                if (isset($postData[$monthNameKey]) && isset($postData[$monthBalanceKey])) {
                    $monthName = trim($postData[$monthNameKey]);
                    $monthBalance = trim($postData[$monthBalanceKey]);
                    
                    error_log("Found month data: Name='{$monthName}', Balance='{$monthBalance}'");
                    
                    // Skip empty values (month 4 might be optional)
                    if (empty($monthName) || empty($monthBalance)) {
                        error_log("Skipping empty month data for month {$monthNum}");
                        continue;
                    }
                    
                    // Clean the month balance value (remove currency symbols and commas)
                    $cleanedBalance = floatval(preg_replace('/[^\d.-]/', '', $monthBalance));
                    
                    // Validate the cleaned balance
                    if ($cleanedBalance <= 0) {
                        error_log("Invalid balance amount: {$cleanedBalance} for month {$monthNum}");
                        continue;
                    }
                    
                    error_log("Inserting month: {$monthName}, Balance: {$cleanedBalance}, Account ID: {$accId}");
                    
                    // Prepare the SQL statement for months_fees table
                    $sql = "INSERT INTO months_fees 
                            (month_name, month_balance, account_id, property_id, application_id) 
                            VALUES 
                            (:monthName, :monthBalance, :accountId, :propertyId, :applicationId)";
                    
                    try {
                        $stmt = $pdo->prepare($sql);
                        
                        // Execute with parameters
                        $params = [
                            'monthName' => $monthName,
                            'monthBalance' => $cleanedBalance,
                            'accountId' => $accId,
                            'propertyId' => $propertyId,
                            'applicationId' => $applicationId
                        ];
                        
                        error_log("Executing SQL with params: " . print_r($params, true));
                        
                        $result = $stmt->execute($params);
                        
                        if (!$result) {
                            $errorInfo = $stmt->errorInfo();
                            error_log("Database error: " . implode(", ", $errorInfo), 3, __DIR__ . '/../logfile/database_errors.log');
                            $success = false;
                            break 2; // Break out of both loops
                        } else {
                            $insertedRecords++;
                            error_log("Successfully inserted month fee record. Last Insert ID: " . $pdo->lastInsertId());
                        }
                    } catch (PDOException $e) {
                        error_log("PDO Exception in month insertion: " . $e->getMessage(), 3, __DIR__ . '/../logfile/database_errors.log');
                        $success = false;
                        break 2; // Break out of both loops
                    }
                } else {
                    error_log("Month data keys not found for month {$monthNum}, account {$accountIndex}");
                }
            }
        }
        
        // Commit or rollback based on success
        if ($success && $insertedRecords > 0) {
            $pdo->commit();
            error_log("Transaction committed successfully. Inserted {$insertedRecords} month fee records.");
        } else {
            $pdo->rollBack();
            error_log("Transaction rolled back. Success: {$success}, Records: {$insertedRecords}");
            $success = false;
        }
        
    } catch (Exception $e) {
        // Rollback on exception
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Exception in insertMonthlyFees: " . $e->getMessage());
        $success = false;
    }
    
    return $success;
}

// FIXED: Insert calculated bill and its months
function insertCalculatedBill($pdo, $userId, $propertyId, $accountId, $applicationId, $processingFee, $totalBalance, $overallTotal, $monthsData, $monthBalance) {
    error_log("insertCalculatedBill called with parameters:");
    error_log("User ID: " . ($userId ?? 'null'));
    error_log("Property ID: " . $propertyId);
    error_log("Account ID: " . $accountId);
    error_log("Application ID: " . $applicationId);
    error_log("Processing Fee: " . $processingFee);
    error_log("Total Balance: " . $totalBalance);
    error_log("Overall Total: " . $overallTotal);
    error_log("Month Balance: " . $monthBalance);
    error_log("Months Data: " . print_r($monthsData, true));
    
    try {
        $pdo->beginTransaction();
        error_log("Transaction started for calculated bill insertion");

        // Insert into calculated_bills
        $stmt = $pdo->prepare("INSERT INTO calculated_bills 
            (user_id, property_id, account_id, application_id, comments, total_balance, processing_fee, overall_total, invoice_number, calculated_at, due_date, update_on, calculated_by) 
            VALUES 
            (:user_id, :property_id, :account_id, :application_id, :comments, :total_balance, :processing_fee, :overall_total, :invoice_number, NOW(), :due_date, NOW(), :calculated_by)");
        
        $comments = $_POST['comments'] ?? '';
        $dueDate = $_POST['dueDate'] ?? null;
        $invoiceNumber = "INV-$userId$propertyId$applicationId-" . date('Ymd');
        $calculatedBy = $_SESSION['employee_id'] ?? 0;

        $billParams = [
            ':user_id' => $userId,
            ':property_id' => $propertyId,
            ':account_id' => $accountId,
            ':application_id' => $applicationId,
            ':comments' => $comments,
            ':total_balance' => $totalBalance,
            ':processing_fee' => $processingFee,
            ':overall_total' => $overallTotal,
            ':invoice_number' => $invoiceNumber,
            ':due_date' => $dueDate,
            ':calculated_by' => $calculatedBy
        ];
        
        error_log("Executing calculated_bills insert with params: " . print_r($billParams, true));
        
        $result = $stmt->execute($billParams);
        
        if (!$result) {
            $errorInfo = $stmt->errorInfo();
            error_log("Error inserting into calculated_bills: " . implode(", ", $errorInfo), 3, __DIR__ . '/../logfile/database_errors.log');
            throw new Exception("Failed to insert into calculated_bills: " . implode(", ", $errorInfo));
        }
        
        $billId = $pdo->lastInsertId();
        error_log("Calculated bill inserted successfully. Bill ID: " . $billId);

        // Prepare month names (ensure all 4 are set, or set to NULL)
        // Fix: Extract month names correctly from the monthsData array
        $month1 = null;
        $month2 = null;
        $month3 = null;
        $month4 = null;
        
        if (is_array($monthsData)) {
            foreach ($monthsData as $index => $monthData) {
                if (isset($monthData['name']) && !empty($monthData['name'])) {
                    switch ($index) {
                        case 0:
                            $month1 = $monthData['name'];
                            break;
                        case 1:
                            $month2 = $monthData['name'];
                            break;
                        case 2:
                            $month3 = $monthData['name'];
                            break;
                        case 3:
                            $month4 = $monthData['name'];
                            break;
                    }
                }
            }
        }
        
        error_log("Extracted month names: Month1={$month1}, Month2={$month2}, Month3={$month3}, Month4={$month4}");

        // Insert into calculated_bill_months
        $stmtMonth = $pdo->prepare("INSERT INTO calculated_bill_months 
            (bill_id, month1_name, month2_name, month3_name, month4_name, monthly_balance) 
            VALUES (:bill_id, :month1, :month2, :month3, :month4, :monthly_balance)");
        
        $monthParams = [
            ':bill_id' => $billId,
            ':month1' => $month1,
            ':month2' => $month2,
            ':month3' => $month3,
            ':month4' => $month4,
            ':monthly_balance' => $monthBalance
        ];
        
        error_log("Executing calculated_bill_months insert with params: " . print_r($monthParams, true));
        
        $monthResult = $stmtMonth->execute($monthParams);
        
        if (!$monthResult) {
            $errorInfo = $stmtMonth->errorInfo();
            error_log("Error inserting into calculated_bill_months: " . implode(", ", $errorInfo), 3, __DIR__ . '/../logfile/database_errors.log');
            throw new Exception("Failed to insert into calculated_bill_months: " . implode(", ", $errorInfo));
        }
        
        error_log("Calculated bill months inserted successfully");

        $pdo->commit();
        error_log("Transaction committed successfully for calculated bill");
        return true;
        
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
            error_log("Transaction rolled back due to error");
        }
        error_log("Error inserting calculated bill: " . $e->getMessage(), 3, __DIR__ . '/../logfile/database_errors.log');
        return false;
    }
}

$insertMessage = null;
$errorDetails = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Log all POST data for debugging
    error_log("Form submitted. POST data: " . print_r($_POST, true));
    
    // Validate required fields
    if (empty($_POST['accountNumber'])) {
        error_log("Missing accountNumber in POST data");
        $insertMessage = 'Missing account number. Please select an account.';
        $errorDetails = 'Account number is required';
    } else if (empty($_POST['property_id'])) {
        error_log("Missing property_id in POST data");
        $insertMessage = 'Missing property ID. Please reload the page.';
        $errorDetails = 'Property ID is required';
    } else if (empty($_POST['application_id'])) {
        error_log("Missing application_id in POST data");
        $insertMessage = 'Missing application ID. Please reload the page.';
        $errorDetails = 'Application ID is required';
    } else {
        // Retrieve form data
        $accountId = $_POST['accountNumber']; // This is account_id from the select dropdown
        $processingFeeRaw = $_POST['processingFee'] ?? '0';
        $totalBalanceRaw = $_POST['totalBalance'] ?? '0';
        $propertyId = $_POST['property_id'];
        $applicationId = $_POST['application_id'];
        
        error_log("Processing form data - Account ID: {$accountId}, Property ID: {$propertyId}, Application ID: {$applicationId}");
        
        // Clean and convert processing fee and total balance to float
        $processingFee = floatval(preg_replace('/[^\d.-]/', '', $processingFeeRaw));
        $totalBalance = floatval(preg_replace('/[^\d.-]/', '', $totalBalanceRaw));
        
        error_log("Cleaned values - Processing Fee: {$processingFee}, Total Balance: {$totalBalance}");
        
        // FIXED: Get user_id - provide a default if not available
        $userId = $_SESSION['user_id'] ?? ($_POST['user_id'] ?? 1); // Default to user ID 1 if not available
        error_log("User ID: " . $userId);

        // Get selected account index
        $selectedAccountId = $_POST['accountNumber'];
        $selectedAccountIndex = null;
        foreach ($accountIds as $idx => $aid) {
            if ($aid == $selectedAccountId) {
                $selectedAccountIndex = $idx + 1; // because your form uses 1-based index
                break;
            }
        }
        
        error_log("Selected account index: " . ($selectedAccountIndex ?? 'null'));

        // FIXED: Extract months for the selected account
        $monthsData = [];
        $monthBalance = 0;
        
        if ($selectedAccountIndex !== null) {
            $monthBalanceValue = null;
            for ($i = 1; $i <= 4; $i++) {
                $monthNameKey = "month{$i}_name_account{$selectedAccountIndex}";
                $monthBalanceKey = "month{$i}_balance_account{$selectedAccountIndex}";
                
                error_log("Looking for month keys: {$monthNameKey}, {$monthBalanceKey}");
                
                if (!empty($_POST[$monthNameKey]) && !empty($_POST[$monthBalanceKey])) {
                    $monthName = trim($_POST[$monthNameKey]);
                    $currentMonthBalanceValue = floatval(preg_replace('/[^\d.-]/', '', $_POST[$monthBalanceKey]));
                    
                    error_log("Found month data: {$monthName} = {$currentMonthBalanceValue}");
                    
                    $monthsData[] = [
                        'name' => $monthName,
                        'balance' => $currentMonthBalanceValue
                    ];
                    
                    // Set monthBalance only once, assuming all monthly balances are the same
                    if ($monthBalanceValue === null) {
                        $monthBalanceValue = $currentMonthBalanceValue;
                    } else if ($monthBalanceValue !== $currentMonthBalanceValue) {
                        error_log("Warning: Monthly balance values differ for month {$i}. Using the first value: {$monthBalanceValue}");
                    }
                } else {
                    error_log("Month {$i} data not found or empty");
                }
            }
            $monthBalance = $monthBalanceValue ?? 0;
        } else {
            error_log("ERROR: Could not determine selected account index");
            $insertMessage = 'Error: Could not determine selected account.';
            $errorDetails = 'Selected account index not found';
        }
        
        error_log("Final months data: " . print_r($monthsData, true));
        error_log("Total month balance: " . $monthBalance);

        // Calculate overall total (example: totalBalance + processingFee)
        $overallTotal = $totalBalance + $processingFee;
        error_log("Calculated overall total: " . $overallTotal);

        // Only proceed if we have valid data
        if (!empty($monthsData) && $selectedAccountIndex !== null) {
            // Insert into calculated_bills and calculated_bill_months
            $success = insertCalculatedBill(
                $pdo,
                $userId,
                $propertyId,
                $accountId,
                $applicationId,
                $processingFee,
                $totalBalance,
                $overallTotal,
                $monthsData,
                $monthBalance
            );

            if ($success) {
                $insertMessage = 'Calculated bill and months saved successfully!';
                error_log("SUCCESS: Data inserted successfully");
            } else {
                $insertMessage = 'Failed to save calculated bill.';
                $errorDetails = 'Database insertion failed - check error logs';
                error_log("FAILURE: Data insertion failed");
            }
        } else {
            $insertMessage = 'No valid month data found to save.';
            $errorDetails = 'Please ensure you have entered at least one month with valid data';
            error_log("ERROR: No valid month data found");
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rates Clearance Calculator</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #333;
            line-height: 1.6;
        }

        .header {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            padding: 2rem;
            color: white;
            text-align: center;
            margin-bottom: 2rem;
            border-radius: 16px;
            position: relative;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            overflow: hidden;
        }

        .header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #ff6b6b, #feca57, #48dbfb, #ff9ff3);
            background-size: 400% 400%;
            animation: gradientShift 3s ease infinite;
        }

        @keyframes gradientShift {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }

        .header h2 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }

        .header-buttons {
            position: absolute;
            top: 20px;
            display: flex;
            gap: 10px;
        }

        .header-buttons.left {
            left: 20px;
        }

        .header-buttons.right {
            right: 20px;
        }

        .print-button, .debug-button, .export-button {
            background: rgba(255, 255, 255, 0.9);
            color: #333;
            border: none;
            padding: 12px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .print-button:hover, .debug-button:hover, .export-button:hover {
            background: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            max-width: 1200px;
            margin: 0 auto 2rem auto;
            padding: 2rem;
            border: none;
            border-radius: 16px;
            background: rgba(255, 255, 255, 0.95);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            gap: 2rem;
        }

        .form-section, .accounts-section {
            padding: 1.5rem;
            background: white;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        }

        .input-group {
            display: grid;
            grid-template-columns: 1fr 2fr;
            align-items: center;
            margin-bottom: 1.5rem;
            gap: 1rem;
        }

        .input-group.full-width {
            grid-template-columns: 1fr;
            gap: 0.5rem;
        }

        .input-group label {
            font-weight: 600;
            color: #2c3e50;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            justify-self: start;
        }

        .input-group input, .input-group select {
            padding: 12px 16px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: white;
            width: 100%;
        }

        .input-group input:focus, .input-group select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            transform: translateY(-1px);
        }

        button {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            transition: all 0.3s ease;
            width: 100%;
            max-width: 200px;
            margin: 0.5rem 0;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
            background: linear-gradient(135deg, #5a6fd8 0%, #6a4190 100%);
        }

        button:active {
            transform: translateY(0);
        }

        .footer {
            text-align: center;
            margin-top: 2rem;
            padding: 1rem;
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.9rem;
        }

        .status-message {
            padding: 1rem 1.5rem;
            margin: 1rem auto;
            border-radius: 12px;
            text-align: center;
            max-width: 1200px;
            font-weight: 500;
            animation: slideInDown 0.3s ease;
        }

        @keyframes slideInDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .success {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .error {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .debug-info {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border: 1px solid #dee2e6;
            padding: 1.5rem;
            margin: 1rem auto;
            border-radius: 12px;
            font-family: 'Courier New', monospace;
            white-space: pre-wrap;
            max-height: 300px;
            overflow-y: auto;
            display: none;
            max-width: 1200px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .debug-info::-webkit-scrollbar {
            width: 8px;
        }

        .debug-info::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }

        .debug-info::-webkit-scrollbar-thumb {
            background: #667eea;
            border-radius: 4px;
        }

        .account-section {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            border: 1px solid #e9ecef;
            position: relative;
            overflow: hidden;
        }

        .account-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #667eea, #764ba2);
        }

        .account-section h4 {
            color: #2c3e50;
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .account-section h4::before {
            content: '💳';
            font-size: 1.2rem;
        }

        .account-section .input-group {
            grid-template-columns: 1fr 2fr;
            align-items: center;
        }

        .balance-section {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin: 1.5rem auto;
            max-width: 1200px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            border: 1px solid #e9ecef;
        }

        .balance-section h3 {
            color: #2c3e50;
            font-size: 1.4rem;
            font-weight: 600;
            margin-bottom: 1rem;
            text-align: center;
        }

        .balance-section .input-group {
            grid-template-columns: 1fr 2fr;
            align-items: center;
        }

        .button-group {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 1rem;
        }

        .button-group button {
            flex: 1;
            min-width: 150px;
            max-width: 200px;
        }

        .calculate-btn {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        }

        .calculate-btn:hover {
            background: linear-gradient(135deg, #218838 0%, #1ea085 100%);
        }

        .save-btn {
            background: linear-gradient(135deg, #007bff 0%, #6610f2 100%);
        }

        .save-btn:hover {
            background: linear-gradient(135deg, #0056b3 0%, #520dc2 100%);
        }

        .view-btn {
            background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
        }

        .view-btn:hover {
            background: linear-gradient(135deg, #545b62 0%, #3d4142 100%);
        }

        .export-pdf-btn {
            background: linear-gradient(135deg, #fd7e14 0%, #e83e8c 100%);
        }

        .export-pdf-btn:hover {
            background: linear-gradient(135deg, #e8650e 0%, #d91a72 100%);
        }

        .submit-btn {
            background: linear-gradient(135deg, #dc3545 0%, #e83e8c 100%);
            max-width: 300px;
            margin: 2rem auto;
            display: block;
        }

        .submit-btn:hover {
            background: linear-gradient(135deg, #c82333 0%, #d91a72 100%);
        }

        #accountDetails {
            max-width: 1200px;
            margin: 0 auto;
        }

        .form-container {
            max-width: 1200px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
        }

        .form-container h3 {
            color: #2c3e50;
            font-size: 1.8rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            text-align: center;
            position: relative;
        }

        .form-container h3::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 3px;
            background: linear-gradient(90deg, #667eea, #764ba2);
            border-radius: 2px;
        }

        .form-container .input-group {
            grid-template-columns: 1fr 2fr;
            align-items: center;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .container {
                grid-template-columns: 1fr;
                padding: 1rem;
                margin: 0 1rem 2rem 1rem;
            }

            .header {
                margin: 1rem;
                padding: 1.5rem;
            }

            .header h2 {
                font-size: 1.8rem;
            }

            .header-buttons {
                position: static;
                justify-content: center;
                margin-top: 1rem;
            }

            .form-container {
                margin: 0 1rem;
                padding: 1rem;
            }

            .button-group {
                flex-direction: column;
                align-items: center;
            }

            .button-group button {
                max-width: 100%;
            }

            .account-section {
                padding: 1rem;
            }

            .input-group {
                grid-template-columns: 1fr;
                gap: 0.5rem;
            }

            .input-group label {
                justify-self: start;
            }

            .balance-section .input-group,
            .account-section .input-group,
            .form-container .input-group {
                grid-template-columns: 1fr;
                gap: 0.5rem;
            }
        }

        @media (max-width: 480px) {
            .header h2 {
                font-size: 1.5rem;
            }

            .input-group {
                margin-bottom: 1rem;
            }

            .input-group input, .input-group select {
                padding: 10px 12px;
                font-size: 0.9rem;
            }

            button {
                padding: 10px 20px;
                font-size: 0.9rem;
            }
        }

        /* Loading Animation */
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Enhanced Form Styling */
        .monthly-balance, .account-balance, .processing-fee {
            position: relative;
        }

        .monthly-balance:focus, .account-balance:focus, .processing-fee:focus {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);
        }

        /* Custom Select Styling */
        select {
            appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6,9 12,15 18,9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 12px center;
            background-size: 20px;
            padding-right: 40px;
        }

        /* Print Styles */
        @media print {
            body {
                background: white;
                color: black;
            }
            
            .header-buttons, .debug-button, .print-button, .export-button {
                display: none !important;
            }
            
            .header {
                background: #2c3e50 !important;
                color: white !important;
                -webkit-print-color-adjust: exact;
            }
            
            .container, .form-container, .balance-section, .account-section {
                box-shadow: none !important;
                border: 1px solid #ddd !important;
            }
            
            .button-group {
                display: none !important;
            }
        }
    </style>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
</head>
<body>

<div class="header">
    <h2><i class="fas fa-calculator"></i> Rates Clearance Calculator</h2>
    <div class="header-buttons left">
        <button class="debug-button" onclick="toggleDebugInfo()">
            <i class="fas fa-bug"></i> Debug
        </button>
    </div>
    <div class="header-buttons right">
        <button class="export-button" onclick="exportToPDF()">
            <i class="fas fa-file-pdf"></i> Export PDF
        </button>
        <button class="print-button" id="printButton" onclick="printPage()">
            <i class="fas fa-print"></i> Print
        </button>
    </div>
</div>

<?php if (isset($insertMessage)): ?>
<div class="status-message <?php echo strpos($insertMessage, 'success') !== false ? 'success' : 'error'; ?>">
    <i class="fas fa-<?php echo strpos($insertMessage, 'success') !== false ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
    <?php echo htmlspecialchars($insertMessage); ?>
    <?php if (isset($errorDetails)): ?>
        <div style="margin-top: 10px; font-size: 12px;">
            <i class="fas fa-info-circle"></i> Error details: <?php echo htmlspecialchars($errorDetails); ?>
        </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- Debug information section -->
<div class="debug-info" id="debugInfo">
    <h3><i class="fas fa-bug"></i> Debug Information</h3>
    <p><strong>Property ID:</strong> <?php echo htmlspecialchars($propertyId ?? 'Not set'); ?></p>
    <p><strong>Application ID:</strong> <?php echo htmlspecialchars($applicationId ?? 'Not set'); ?></p>
    <p><strong>Account Count:</strong> <?php echo htmlspecialchars($accountCount ?? '0'); ?></p>
    <p><strong>Account IDs:</strong> <?php echo htmlspecialchars(implode(', ', $accountIds ?? [])); ?></p>
    <p><strong>Account Numbers:</strong> <?php echo htmlspecialchars(implode(', ', $accountNumbers ?? [])); ?></p>
    
    <h4><i class="fas fa-database"></i> POST Data (if submitted):</h4>
    <pre><?php if ($_SERVER['REQUEST_METHOD'] === 'POST') echo htmlspecialchars(print_r($_POST, true)); ?></pre>
</div>

<div class="container">
    <div class="form-section">
        <div class="input-group">
            <label for="period"><i class="fas fa-calendar-alt"></i> Select Period</label>
            <select id="period" name="period" onchange="updatePeriodDetails()">
                <option value="">Select...</option>
                <option value="3">3 Months</option>
                <option value="4">4 Months</option>
            </select>
        </div>

        <input type="text" id="customPeriod" name="customPeriod" placeholder="Enter period in months" style="display:none;" oninput="updateCustomPeriod()">
    </div>

    <div class="accounts-section">
        <div class="input-group">
            <label for="accounts"><i class="fas fa-users"></i> Number of Accounts</label>
            <select id="accounts" name="accounts" onchange="updatePeriodDetails()">
                <option value="">Select Account</option>
                <?php
                    for ($i = 1; $i <= $accountCount; $i++) {
                        $selected = ($i == $accountCount) ? 'selected' : '';
                        echo "<option value=\"$i\" $selected>$i Account" . ($i > 1 ? 's' : '') . "</option>";
                    }
                ?>
            </select>
        </div>
    </div>
</div>

<script>
    // Pass PHP account numbers array to JS
    const accountNumbers = <?php echo json_encode($accountNumbers ?? []); ?>;
    const accountIds = <?php echo json_encode($accountIds ?? []); ?>;
</script>

<div class="form-container">
    <h3><i class="fas fa-file-invoice-dollar"></i> Account Summary</h3>
    
    <form method="POST" action="" id="dataForm" style="display:block;" onsubmit="return validateForm()">
        <!-- Hidden inputs for IDs -->
        <input type="hidden" name="property_id" value="<?php echo htmlspecialchars($propertyId ?? ''); ?>">
        <input type="hidden" name="application_id" value="<?php echo htmlspecialchars($applicationId ?? ''); ?>">

        <div id="accountDetails"></div>

        <!-- Balance section -->
        <div class="balance-section">
            <h3><i class="fas fa-calculator"></i> Financial Summary</h3>
            <div class="input-group">
                <label><i class="fas fa-dollar-sign"></i> Processing Fee (USD):</label>
                <input type="text" class="processing-fee" placeholder="Enter processing fee" onblur="formatCurrency(this)">
            </div>
            <div class="input-group">
                <label><i class="fas fa-chart-line"></i> Total Balance (USD):</label>
                <input type="text" id="OveralTotalBalance" placeholder="Overall Total balance" readonly>
            </div>
            
            <div class="button-group">
                <button type="button" class="calculate-btn" onclick="calculateTotal()">
                    <i class="fas fa-calculator"></i> Calculate
                </button>
                <button type="button" class="save-btn" onclick="saveRecords()">
                    <i class="fas fa-save"></i> Save
                </button>
                <button type="button" class="view-btn" onclick="viewSavedRecords()">
                    <i class="fas fa-eye"></i> View Saved
                </button>
                <button type="button" class="export-pdf-btn" onclick="exportToPDF()">
                    <i class="fas fa-file-pdf"></i> Export PDF
                </button>
            </div>
        </div>

        <div class="input-group">
            <label><i class="fas fa-calendar-alt"></i> Due Date:</label>
            <input type="date" class="due-date" name="dueDate" placeholder="Select due date" required>
        </div>
        <div class="input-group">
            <label><i class="fas fa-info-circle"></i> Comments:</label>
            <input type="text" name="comments" rows="1" placeholder="Enter any additional notes here..." style="radius: 10px;">
        </div>

        <!-- Hidden inputs to submit processing fee and total balance -->
        <input type="hidden" name="processingFee" id="hiddenProcessingFee" value="">
        <input type="hidden" name="totalBalance" id="hiddenTotalBalance" value="">

        <button type="submit" class="submit-btn">
            <i class="fas fa-paper-plane"></i> Submit Application
        </button>
    </form>

    <div class="footer">
        <p><i class="fas fa-copyright"></i> 2025 Rates Clearance Calculator. All rights reserved.</p>
    </div>
</div>

<script>
    const records = [];

    // Function to toggle debug information
    function toggleDebugInfo() {
        const debugInfo = document.getElementById('debugInfo');
        debugInfo.style.display = debugInfo.style.display === 'none' ? 'block' : 'none';
    }

    // Function to export to PDF using FPDF-like approach
    function exportToPDF() {
        // Collect form data
        const formData = collectFormData();
        
        if (!formData.isValid) {
            alert('Please fill in all required fields before exporting to PDF.');
            return;
        }

        // Create a form to submit to PDF generation script
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'generate_pdf.php'; // You'll need to create this file
        form.target = '_blank';

        // Add form data as hidden inputs
        Object.keys(formData.data).forEach(key => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = key;
            input.value = formData.data[key];
            form.appendChild(input);
        });

        // Add property and application IDs
        const propertyInput = document.createElement('input');
        propertyInput.type = 'hidden';
        propertyInput.name = 'property_id';
        propertyInput.value = '<?php echo htmlspecialchars($propertyId ?? ''); ?>';
        form.appendChild(propertyInput);

        const applicationInput = document.createElement('input');
        applicationInput.type = 'hidden';
        applicationInput.name = 'application_id';
        applicationInput.value = '<?php echo htmlspecialchars($applicationId ?? ''); ?>';
        form.appendChild(applicationInput);

        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);
    }

    // Function to collect form data for PDF export
    function collectFormData() {
        const data = {};
        let isValid = true;

        // Get period and accounts
        const period = document.getElementById('period').value;
        const accounts = document.getElementById('accounts').value;
        
        if (!period || !accounts) {
            isValid = false;
        }

        data.period = period;
        data.accounts = accounts;

        // Get processing fee and total balance
        const processingFee = document.querySelector('.processing-fee')?.value || '';
        const totalBalance = document.getElementById('OveralTotalBalance')?.value || '';
        
        data.processing_fee = processingFee;
        data.total_balance = totalBalance;

        // Get account details
        const accountSections = document.querySelectorAll('.account-section');
        accountSections.forEach((section, index) => {
            const accountIndex = index + 1;
            const accountNumber = section.querySelector(`#accountNumber${accountIndex}`)?.value || '';
            const accountBalance = section.querySelector('.account-balance')?.value || '';
            
            data[`account_${accountIndex}_number`] = accountNumber;
            data[`account_${accountIndex}_balance`] = accountBalance;

            // Get monthly data
            for (let month = 1; month <= 4; month++) {
                const monthSelect = section.querySelector(`#month${month}_account${accountIndex}`);
                const monthBalance = section.querySelector(`input[name="month${month}_balance_account${accountIndex}"]`);
                
                if (monthSelect && monthBalance) {
                    data[`month${month}_name_account${accountIndex}`] = monthSelect.value;
                    data[`month${month}_balance_account${accountIndex}`] = monthBalance.value;
                }
            }

            // Get account total
            const accountTotal = section.querySelector(`#totalBalance${accountIndex}`)?.value || '';
            data[`account_${accountIndex}_total`] = accountTotal;
        });

        return { data, isValid };
    }

    // Function to synchronize month selections across accounts
    function syncMonthSelections(monthNumber, accountIndex) {
        console.log(`Syncing month ${monthNumber} from account ${accountIndex}`);
        // Only sync if this is Account 1
        if (accountIndex === 1) {
            const sourceSelect = document.getElementById(`month${monthNumber}_account${accountIndex}`);
            if (!sourceSelect) {
                console.error(`Source select for month${monthNumber}_account${accountIndex} not found`);
                return;
            }
            
            const selectedMonth = sourceSelect.value;
            console.log(`Selected month: ${selectedMonth}`);
            
            // Get the number of accounts
            const accountCount = parseInt(document.getElementById('accounts').value) || 0;
            console.log(`Account count: ${accountCount}`);
            
            // Update the same month dropdown for all other accounts
            for (let i = 2; i <= accountCount; i++) {
                const targetSelect = document.getElementById(`month${monthNumber}_account${i}`);
                if (targetSelect) {
                    console.log(`Setting month${monthNumber}_account${i} to ${selectedMonth}`);
                    targetSelect.value = selectedMonth;
                    // Trigger change event to ensure any listeners are notified
                    const event = new Event('change', { bubbles: true });
                    targetSelect.dispatchEvent(event);
                } else {
                    console.error(`Target select for month${monthNumber}_account${i} not found`);
                }
            }
        }
    }

    // Function to synchronize all months at once
    function syncAllMonths() {
        console.log('Syncing all months');
        // Get the number of accounts
        const accountCount = parseInt(document.getElementById('accounts').value) || 0;
        console.log(`Account count: ${accountCount}`);
        
        // Only proceed if we have more than 1 account
        if (accountCount > 1) {
            // For each month (1-4)
            for (let monthNumber = 1; monthNumber <= 4; monthNumber++) {
                // Get the source month selection from account 1
                const sourceSelect = document.getElementById(`month${monthNumber}_account1`);
                if (sourceSelect) {
                    const selectedMonth = sourceSelect.value;
                    console.log(`Month ${monthNumber} selected value: ${selectedMonth}`);
                    
                    // Apply to all other accounts
                    for (let i = 2; i <= accountCount; i++) {
                        const targetSelect = document.getElementById(`month${monthNumber}_account${i}`);
                        if (targetSelect) {
                            console.log(`Setting month${monthNumber}_account${i} to ${selectedMonth}`);
                            targetSelect.value = selectedMonth;
                            // Trigger change event
                            const event = new Event('change', { bubbles: true });
                            targetSelect.dispatchEvent(event);
                        } else {
                            console.error(`Target select for month${monthNumber}_account${i} not found`);
                        }
                    }
                } else {
                    console.error(`Source select for month${monthNumber}_account1 not found`);
                }
            }
        }
    }

    // Function to validate form before submission
    function validateForm() {
        console.log('Validating form');
        
        // Update hidden inputs with visible input values
        const processingFeeInput = document.querySelector('.processing-fee');
        const overalTotalBalanceInput = document.getElementById('OveralTotalBalance');

        // Clean the values before setting them
        const processingFeeValue = processingFeeInput ? processingFeeInput.value.replace(/[^0-9.-]+/g, "") : '0';
        const totalBalanceValue = overalTotalBalanceInput ? overalTotalBalanceInput.value.replace(/[^0-9.-]+/g, "") : '0';

        document.getElementById('hiddenProcessingFee').value = processingFeeValue;
        document.getElementById('hiddenTotalBalance').value = totalBalanceValue;
        
        console.log('Hidden processing fee set to:', processingFeeValue);
        console.log('Hidden total balance set to:', totalBalanceValue);
        
        const period = document.getElementById('period').value;
        if (!period) {
            alert('Please select a period');
            return false;
        }
        
        const accounts = document.getElementById('accounts').value;
        if (!accounts) {
            alert('Please select number of accounts');
            return false;
        }
        
        // Check that at least one month has data
        let hasMonthData = false;
        const monthInputs = document.querySelectorAll('input[name*="_balance_account"]');
        for (let input of monthInputs) {
            if (input.value && input.value.trim() !== '') {
                hasMonthData = true;
                break;
            }
        }
        
        if (!hasMonthData) {
            alert('Please enter at least one month balance');
            return false;
        }
        
        console.log('Form validation passed');
        return true;
    }

    // Function to handle period selection
    function updatePeriodDetails() {
        const period = document.getElementById('period').value;
        const accountCount = document.getElementById('accounts').value;
        const customInput = document.getElementById('customPeriod');
        const accountDetails = document.getElementById('accountDetails');

        if (period === 'custom') {
            customInput.style.display = 'block';
            accountDetails.innerHTML = '';
            return;
        } else {
            customInput.style.display = 'none';
            if (period && accountCount) {
                generateAccountDetails(period, accountCount);
                // Add a slight delay to ensure DOM is updated before syncing
                setTimeout(syncAllMonths, 100);
            } else {
                accountDetails.innerHTML = '';
            }
        }
    }

    function updateCustomPeriod() {
        console.log('Updating custom period');
        const customPeriod = document.getElementById('customPeriod').value;
        const accountCount = document.getElementById('accounts').value;
        if (customPeriod && accountCount) {
            generateAccountDetails(customPeriod, accountCount);
            // After generating account details, sync all months
            setTimeout(syncAllMonths, 100);
        }
    }

    // Function to handle account selection
    function updateAccountDetails() {
        console.log('Updating account details');
        const accountCount = document.getElementById('accounts').value;
        const period = document.getElementById('period').value;
        const customInput = document.getElementById('customPeriod');

        if (period === 'custom') {
            if (customInput.value && accountCount) {
                generateAccountDetails(customInput.value, accountCount);
                setTimeout(syncAllMonths, 100);
            }
        } else if (period && accountCount) {
            generateAccountDetails(period, accountCount);
            setTimeout(syncAllMonths, 100);
        }
    }

    // Function to generate account details with proper name attributes and onchange events
    function generateAccountDetails(months, accountCount) {
        console.log(`Generating account details for ${months} months and ${accountCount} accounts`);
        const accountDetails = document.getElementById('accountDetails');
        accountDetails.innerHTML = ''; // Clear previous details

        // Loop through each account
        for (let i = 1; i <= accountCount; i++) {
            const accountNumberValue = accountNumbers[i - 1] || '';
            const accountIdValue = accountIds[i - 1] || '';
            
            let accountHtml = `
                <div class="account-section" data-account="${i}">
                    <h4>Account ${i}</h4>
                    <div class="input-group">
                        <label><i class="fas fa-hashtag"></i> Account number:</label>
                        <input type="text" id="accountNumber${i}" placeholder="Enter account number" value="${accountNumberValue}" readonly>
                        <input type="hidden" name="account_id_${i}" value="${accountIdValue}">
                    </div>

                    <div class="input-group">
                        <label><i class="fas fa-credit-card"></i> Account Number:</label>
                        <select name="accountNumber" required>
                            <?php
                                if (!empty($accountIds)) {
                                    foreach ($accountIds as $index => $accId) {
                                        $accNum = $accountNumbers[$index] ?? '';
                                        echo "<option value=\"" . htmlspecialchars($accId) . "\">" . htmlspecialchars($accNum) . "</option>";
                                    }
                                } else {
                                    echo "<option value=\"\">No accounts available</option>";
                                }
                            ?>
                        </select>
                    </div>

                    <div class="input-group">
                        <label><i class="fas fa-wallet"></i> Balance for Account ${i} (USD):</label>
                        <input type="text" placeholder="Enter balance" class="account-balance" onblur="formatCurrency(this)">
                    </div>
            `;
            
            // Add month inputs based on selected period
            for (let j = 1; j <= months; j++) {
                accountHtml += `
                    <div class="input-group">
                        <label><i class="fas fa-calendar"></i> Month ${j}:</label>
                        <div style="display: flex; gap: 0.5rem;">
                            <select id="month${j}_account${i}" name="month${j}_name_account${i}" onchange="syncMonthSelections(${j}, ${i})" required style="flex: 1;">
                                <option value="">Select Month</option>
                                <option value="January">Jan</option>
                                <option value="February">Feb</option>
                                <option value="March">Mar</option>
                                <option value="April">Apr</option>
                                <option value="May">May</option>
                                <option value="June">Jun</option>
                                <option value="July">Jul</option>
                                <option value="August">Aug</option>
                                <option value="September">Sep</option>
                                <option value="October">Oct</option>
                                <option value="November">Nov</option>
                                <option value="December">Dec</option>
                            </select>
                            <input type="text" placeholder="Enter monthly balance" class="monthly-balance" 
                                   onblur="formatCurrency(this)" name="month${j}_balance_account${i}" required style="flex: 1;">
                        </div>
                    </div>
                `;
            }
            
            accountHtml += `
                    <div class="input-group">
                        <label><i class="fas fa-chart-bar"></i> Account Total Balance (USD):</label>
                        <div style="display: flex; gap: 0.5rem; align-items: center;">
                            <input type="text" id="totalBalance${i}" placeholder="Total balance" readonly style="flex: 1;">
                            <button type="button" onclick="calculateAccountTotal(${i})" class="calculate-btn" style="max-width: 120px; margin: 0;">
                                <i class="fas fa-calculator"></i> Calculate
                            </button>
                        </div>
                    </div>
                </div>
            `;
            
            accountDetails.innerHTML += accountHtml;
        }
        
        console.log('Account details generated');
        
        // Add direct event listeners to all month selects after they're created
        for (let i = 1; i <= accountCount; i++) {
            for (let j = 1; j <= months; j++) {
                const selectId = `month${j}_account${i}`;
                const selectElement = document.getElementById(selectId);
                if (selectElement) {
                    // Remove any existing event listeners first
                    const newSelect = selectElement.cloneNode(true);
                    selectElement.parentNode.replaceChild(newSelect, selectElement);
                    
                    // Add the event listener
                    newSelect.addEventListener('change', function() {
                        console.log(`${selectId} changed to ${this.value}`);
                        if (i === 1) {
                            syncMonthSelections(j, i);
                        }
                    });
                }
            }
        }
    }

    function formatCurrency(input) {
        let value = parseFloat(input.value.replace(/[^0-9.-]+/g, "")); 
        if (!isNaN(value)) {
            input.value = value.toLocaleString('en-US', { style: 'currency', currency: 'USD' });
        } else {
            input.value = '';
        }
    }

    // New function to calculate total for a specific account
    function calculateAccountTotal(accountIndex) {
        console.log(`Calculating total for account ${accountIndex}`);
        const accountSection = document.querySelector(`.account-section[data-account="${accountIndex}"]`);
        if (!accountSection) {
            console.error(`Account section ${accountIndex} not found`);
            return;
        }
        
        const accountBalance = accountSection.querySelector('.account-balance');
        const monthlyBalances = accountSection.querySelectorAll('.monthly-balance');
        const totalBalanceInput = document.getElementById(`totalBalance${accountIndex}`);
        
        let total = 0;
        
        // Add account balance
        if (accountBalance) {
            const value = parseFloat(accountBalance.value.replace(/[^0-9.-]+/g, "")) || 0;
            total += value;
        }
        
        // Add monthly balances
        monthlyBalances.forEach(input => {
            const value = parseFloat(input.value.replace(/[^0-9.-]+/g, "")) || 0;
            total += value;
        });
        
        // Update total balance
        if (totalBalanceInput) {
            totalBalanceInput.value = total.toLocaleString('en-US', { style: 'currency', currency: 'USD' });
        }
        
        console.log(`Account ${accountIndex} total: ${total}`);
    }

    function calculateTotal() {
        console.log('Calculating overall total');
        // Calculate total for each account first
        console.log('Calculating overall total');
        // Calculate total for each account first
        const accountCount = parseInt(document.getElementById('accounts').value) || 0;
        for (let i = 1; i <= accountCount; i++) {
            calculateAccountTotal(i);
        }
        
        // Now calculate the overall total
        const accountTotals = document.querySelectorAll('input[id^="totalBalance"]');
        let total = 0;
        
        accountTotals.forEach(input => {
            const value = parseFloat(input.value.replace(/[^0-9.-]+/g, "")) || 0;
            total += value;
        });
        
        // Add processing fee
        const processingFeeInput = document.querySelector('.processing-fee');
        const processingFee = processingFeeInput ? processingFeeInput.value : '0';
        total += parseFloat(processingFee.replace(/[^0-9.-]+/g, "")) || 0;
        
        // Display total balance
        document.getElementById('OveralTotalBalance').value = total.toLocaleString('en-US', { style: 'currency', currency: 'USD' });
        console.log(`Overall total: ${total}`);
    }

    function saveRecords() {
        // Implementation for saving records
        alert('Save functionality not implemented yet');
    }

    function viewSavedRecords() {
        // Implementation for viewing saved records
        alert('View saved records functionality not implemented yet');
    }

    function printPage() {
        window.print();
    }

    // Initialize the form if we have accounts and period
    document.addEventListener('DOMContentLoaded', function() {
        const period = document.getElementById('period');
        const accounts = document.getElementById('accounts');
        
        if (period.value && accounts.value) {
            updatePeriodDetails();
        }
        
        // Add debug info for month selects
        console.log('DOM fully loaded');
        setTimeout(function() {
            const monthSelects = document.querySelectorAll('select[id^="month"]');
            console.log(`Found ${monthSelects.length} month select elements`);
            monthSelects.forEach(select => {
                console.log(`Select ID: ${select.id}, Value: ${select.value}`);
            });
        }, 500);
    });
</script>

</body>
</html>