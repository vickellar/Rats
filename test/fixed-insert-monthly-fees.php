<?php
// Include database connection
require_once '../Database/db.php';

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
        error_log("Error fetching application_id: " . $e->getMessage());
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
        error_log("Error fetching accounts: " . $e->getMessage());
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
                            error_log("Database error: " . implode(", ", $errorInfo));
                            $success = false;
                            break 2; // Break out of both loops
                        } else {
                            $insertedRecords++;
                            error_log("Successfully inserted month fee record. Last Insert ID: " . $pdo->lastInsertId());
                        }
                    } catch (PDOException $e) {
                        error_log("PDO Exception in month insertion: " . $e->getMessage());
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

// New: Insert calculated bill and its months
function insertCalculatedBill($pdo, $userId, $propertyId, $accountId, $applicationId, $processingFee, $totalBalance, $overallTotal, $monthsData, $monthBalance) {
    try {
        $pdo->beginTransaction();

        // Insert into calculated_bills
        $stmt = $pdo->prepare("INSERT INTO calculated_bills 
            (user_id, property_id, account_id, application_id, total_balance, processing_fee, overall_total) 
            VALUES (:user_id, :property_id, :account_id, :application_id, :total_balance, :processing_fee, :overall_total)");
        $stmt->execute([
            ':user_id' => $userId,
            ':property_id' => $propertyId,
            ':account_id' => $accountId,
            ':application_id' => $applicationId,
            ':total_balance' => $totalBalance,
            ':processing_fee' => $processingFee,
            ':overall_total' => $overallTotal
        ]);
        $billId = $pdo->lastInsertId();

        // Prepare month names (ensure all 4 are set, or set to NULL)
        $month1 = $monthsData[0] ?? null;
        $month2 = $monthsData[1] ?? null;
        $month3 = $monthsData[2] ?? null;
        $month4 = $monthsData[3] ?? null;

        // Insert into calculated_bill_months
        $stmtMonth = $pdo->prepare("INSERT INTO calculated_bill_months 
            (bill_id, month1_name, month2_name, month3_name, month4_name, month_balance) 
            VALUES (:bill_id, :month1, :month2, :month3, :month4, :month_balance)");
        $stmtMonth->execute([
            ':bill_id' => $billId,
            ':month1' => $month1,
            ':month2' => $month2,
            ':month3' => $month3,
            ':month4' => $month4,
            ':month_balance' => $monthBalance
        ]);

        $pdo->commit();
        return true;
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log("Error inserting calculated bill: " . $e->getMessage());
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
        
        // Example: get user_id from session or form
        $userId = $_SESSION['user_id'] ?? ($_POST['user_id'] ?? null);

        // Get selected account index
        $selectedAccountId = $_POST['accountNumber'];
        $selectedAccountIndex = null;
        foreach ($accountIds as $idx => $aid) {
            if ($aid == $selectedAccountId) {
                $selectedAccountIndex = $idx + 1; // because your form uses 1-based index
                break;
            }
        }

        // Now extract months for the selected account
        $monthsData = [];
        for ($i = 1; $i <= 4; $i++) {
            $monthNameKey = "month{$i}_name_account{$selectedAccountIndex}";
            $monthBalanceKey = "month{$i}_balance_account{$selectedAccountIndex}";
            if (!empty($_POST[$monthNameKey]) && !empty($_POST[$monthBalanceKey])) {
                $monthsData[] = [
                    'name' => $_POST[$monthNameKey],
                    'balance' => floatval(preg_replace('/[^\d.-]/', '', $_POST[$monthBalanceKey]))
                ];
            }
        }

        // Calculate overall total (example: totalBalance + processingFee)
        $overallTotal = $totalBalance + $processingFee;

        // Insert into calculated_bills and calculated_bill_months
        // Extract monthBalance for the selected account (example: sum of month balances)
        $monthBalance = 0;
        foreach ($monthsData as $month) {
            $monthBalance += $month['balance'];
        }
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
        } else {
            $insertMessage = 'Failed to save calculated bill.';
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
<style>
    body {
        font-family: 'Arial', sans-serif;
        margin: 10px;
        background-color: #f4f4f4;
    }
    .header {
        background-color: #007bff;
        padding: 10px;
        color: white;
        text-align: center;
        margin-bottom: 10px;
        border-radius: 8px;
        position: relative;
    }
    .print-button {
        position: absolute;
        top: 10px;
        right: 10px;
        background-color: white;
        color: black;
        border: none;
        padding: 5px 10px;
        border-radius: 4px;
        cursor: pointer;
        transition: background-color 0.3s;
    }
    .container {
        display: flex;
        justify-content: space-between;
        max-width: 800px;
        margin: auto;
        padding: 5px;
        border: 1px solid #ddd;
        border-radius: 8px;
        background-color: #ffffff;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }
    .form-section, .accounts-section {
        width: 45%;
        padding: 5px;
    }
    .input-group {
        display: flex;
        align-items: center;
        margin-bottom: 5px;
    }
    button {
        margin-top: 5px;
        padding: 5px 10px;
        border: none;
        border-radius: 4px;
        background-color: #5cb85c;
        color: white;
        cursor: pointer;
        font-size: 14px;
        transition: background-color 0.3s;
        width: 100%;
        max-width: 200px;
    }
    .footer {
        text-align: center;
        margin-top: 10px;
        font-size: 10px;
        color: #777;
    }
    .status-message {
        padding: 10px;
        margin: 10px 0;
        border-radius: 4px;
        text-align: center;
    }
    .success {
        background-color: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }
    .error {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
    .debug-info {
        background-color: #f8f9fa;
        border: 1px solid #ddd;
        padding: 10px;
        margin: 10px 0;
        border-radius: 4px;
        font-family: monospace;
        white-space: pre-wrap;
        max-height: 200px;
        overflow-y: auto;
        display: none;
    }
</style>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
</head>
<body>

<div class="header">
    <h2>Rates Clearance Calculator</h2>
    <button class="print-button" id="printButton" onclick="printPage()">Print</button>
    <button style="position: absolute; top: 10px; left: 10px; background-color: #f8f9fa; color: #000;" onclick="toggleDebugInfo()">Debug</button>
</div>
<a href="../rates/admin/adminDashboard.php" style="text-decoration: none; color: #007bff; font-size: 14px;">Back to Dashboard</a>

<?php if (isset($insertMessage)): ?>
<div class="status-message <?php echo strpos($insertMessage, 'success') !== false ? 'success' : 'error'; ?>">
    <?php echo htmlspecialchars($insertMessage); ?>
    <?php if (isset($errorDetails)): ?>
        <div style="margin-top: 10px; font-size: 12px;">Error details: <?php echo htmlspecialchars($errorDetails); ?></div>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- Debug information section -->
<div class="debug-info" id="debugInfo">
    <h3>Debug Information</h3>
    <p>Property ID: <?php echo htmlspecialchars($propertyId ?? 'Not set'); ?></p>
    <p>Application ID: <?php echo htmlspecialchars($applicationId ?? 'Not set'); ?></p>
    <p>Account Count: <?php echo htmlspecialchars($accountCount ?? '0'); ?></p>
    <p>Account IDs: <?php echo htmlspecialchars(implode(', ', $accountIds ?? [])); ?></p>
    <p>Account Numbers: <?php echo htmlspecialchars(implode(', ', $accountNumbers ?? [])); ?></p>
    
    <h4>POST Data (if submitted):</h4>
    <pre><?php if ($_SERVER['REQUEST_METHOD'] === 'POST') echo htmlspecialchars(print_r($_POST, true)); ?></pre>
</div>

<div class="container">
    <div class="form-section">
        <label for="period">Select Period</label>
        <select id="period" name="period" onchange="updatePeriodDetails()">
            <option value="">Select...</option>
            <option value="3">3 Months</option>
            <option value="4">4 Months</option>
        </select>

        <input type="text" id="customPeriod" name="customPeriod" placeholder="Enter period in months" style="display:none;" oninput="updateCustomPeriod()">
    </div>

    <div class="accounts-section">
        <label for="accounts">Number of Accounts</label>
        <select id="accounts" name="accounts" onchange="updateAccountDetails()">
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

<script>
    // Pass PHP account numbers array to JS
    const accountNumbers = <?php echo json_encode($accountNumbers ?? []); ?>;
    const accountIds = <?php echo json_encode($accountIds ?? []); ?>;
</script>

<div class="balance-section">
    <h3>Account Summary</h3>

    <div class="input-group">
        <label>Processing Fee (USD):</label>
        <input type="text" class="processing-fee" placeholder="Enter processing fee">
    </div>

    <div class="input-group">
        <label>Total Balance (USD):</label>
        <input type="text" id="OveralTotalBalance" placeholder="Overal Total balance" readonly>
    </div>
    
    <button onclick="calculateTotal()">Calculate</button>
    <button onclick="saveRecords()">Save</button>
    <button onclick="viewSavedRecords()">View Saved</button>
</div>

<form method="POST" action="" id="dataForm" style="display:block; max-width: 800px; margin: auto;" onsubmit="return validateForm()">
    <!-- Hidden inputs for IDs -->
    <input type="hidden" name="property_id" value="<?php echo htmlspecialchars($propertyId ?? ''); ?>">
    <input type="hidden" name="application_id" value="<?php echo htmlspecialchars($applicationId ?? ''); ?>">

    <div id="accountDetails"></div>

    <div class="input-group">
        <label>Account Number:</label>
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

    <!-- Hidden inputs to submit processing fee and total balance -->
    <input type="hidden" name="processingFee" id="hiddenProcessingFee" value="">
    <input type="hidden" name="totalBalance" id="hiddenTotalBalance" value="">

    <button type="submit">Submit</button>

    <div class="footer">
        <p>&copy; 2025 Rates Clearance Calculator. All rights reserved.</p>
    </div>
</form>

<script>
    const records = [];

    // Function to toggle debug information
    function toggleDebugInfo() {
        const debugInfo = document.getElementById('debugInfo');
        debugInfo.style.display = debugInfo.style.display === 'none' ? 'block' : 'none';
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

        document.getElementById('hiddenProcessingFee').value = processingFeeInput ? processingFeeInput.value : '';
        document.getElementById('hiddenTotalBalance').value = overalTotalBalanceInput ? overalTotalBalanceInput.value : '';
        
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
                        <label>Account number:</label>
                        <input type="text" id="accountNumber${i}" placeholder="Enter account number" value="${accountNumberValue}" readonly>
                        <input type="hidden" name="account_id_${i}" value="${accountIdValue}">
                    </div>

                    <div class="input-group">
                        <label>Balance for Account ${i} (USD):</label>
                        <input type="text" placeholder="Enter balance" class="account-balance" onblur="formatCurrency(this)">
                    </div>
            `;
            
            // Add month inputs based on selected period
            for (let j = 1; j <= months; j++) {
                accountHtml += `
                    <div class="input-group">
                        <label>Month ${j} 
                        <select id="month${j}_account${i}" name="month${j}_name_account${i}" onchange="syncMonthSelections(${j}, ${i})" required>
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
                        </label>
                        <input type="text" placeholder="Enter monthly balance" class="monthly-balance" 
                               onblur="formatCurrency(this)" name="month${j}_balance_account${i}" required>
                    </div>
                `;
            }
            
            accountHtml += `
                    <div class="input-group">
                        <label>Account Total Balance (USD):</label>
                        <input type="text" id="totalBalance${i}" placeholder="Total balance" readonly>
                        <button type="button" onclick="calculateAccountTotal(${i})">Calculate</button>
                    </div>
                </div>
            `;
            
            accountDetails.innerHTML += accountHtml;
        }
        
        console.log('Account details generated');
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
        const processingFee = document.querySelector('.processing-fee').value;
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
    });

    <?php
    $monthsData = [];
    for ($i = 1; $i <= 4; $i++) {
        $monthNameKey = "month{$i}_name_account1";
        $monthsData[] = !empty($_POST[$monthNameKey]) ? $_POST[$monthNameKey] : null;
    }
    $monthBalance = floatval(preg_replace('/[^\d.-]/', '', $_POST['month1_balance_account1']));
    ?>
</script>

</body>
</html>