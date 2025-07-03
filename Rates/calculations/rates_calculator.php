<?php
// Include database connection
require_once '../Database/db.php';

// Fetch account number based on application_id or property_id
if (isset($_GET['property_id'])) {
    $propertyId = $_GET['property_id'];
    error_log("../logfile/php_error.log.php: " . $propertyId);
    echo "property id " . htmlspecialchars($propertyId);

    // Fetch application_id from rate_clearance_applications table
    $stmtApp = $pdo->prepare("SELECT application_id FROM rate_clearance_applications WHERE property_id = :propertyId LIMIT 1");
    $stmtApp->execute(['propertyId' => $propertyId]);
    $application = $stmtApp->fetch();
    $applicationId = $application ? $application['application_id'] : null;

    // Fetch account_id and account_number from accounts table
    $stmtAcc = $pdo->prepare("SELECT account_id, account_number FROM accounts WHERE property_id = :propertyId");
    $stmtAcc->execute(['propertyId' => $propertyId]);
    $accounts = $stmtAcc->fetchAll();

        if ($accounts) {
            $accountCount = count($accounts);
            $accountNumbers = array_column($accounts, 'account_number');
            $accountIds = array_column($accounts, 'account_id');
        } else {
            $accountCount = 0;
            $accountNumbers = [];
            $accountIds = [];
            echo "No account found for the given property ID.";
        }
    } else {
        error_log("Error: No property_id received in calculate_rate.php");
        echo "Error: No property ID provided";
        $accountCount = 0;
        $applicationId = null;
        $accountIds = [];
    }

$insertMessage = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve form data
    $accountId = $_POST['accountNumber']; // This is account_id from the select dropdown
    $processingFeeRaw = $_POST['processingFee'];
    $totalBalanceRaw = $_POST['totalBalance'];
    $propertyId = $_POST['property_id']; // Assuming property_id is passed via POST
    $applicationId = $_POST['application_id']; // Assuming application_id is passed via POST

    // Debug log for accountId
    error_log("Debug: accountId received: " . var_export($accountId, true));

    // Clean and convert processing fee and total balance to float
    $processingFee = floatval(preg_replace('/[^\d.-]/', '', $processingFeeRaw));
    $totalBalance = floatval(preg_replace('/[^\d.-]/', '', $totalBalanceRaw));

    // Insert into accounts_fees table
    $stmt = $pdo->prepare("INSERT INTO accounts_fees (processing_fee, total_balance, account_id, property_id, application_id) VALUES (:processingFee, :totalBalance, :accountId, :propertyId, :applicationId)");
    $success = $stmt->execute([
        'processingFee' => $processingFee,
        'totalBalance' => $totalBalance,
        'accountId' => $accountId,
        'propertyId' => $propertyId,
        'applicationId' => $applicationId
    ]);

    if (!$success) {
        $errorInfo = $stmt->errorInfo();
        error_log("Error inserting into accounts_fees: " . implode(", ", $errorInfo));
    }

    // Retrieve the last inserted account fee ID
    $accountFeeId = $pdo->lastInsertId();

    if ($success && $accountFeeId) {
        $insertMessage = 'Data inserted successfully!';
    } else {
        $insertMessage = 'Failed to insert data.';
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
    .input-group label {
        width: 150px;
    }
    .input-group input, .input-group select {
        flex: 1;
        padding: 5px;
        border-radius: 4px;
        border: 1px solid #ccc;
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
</style>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
</head>
<body>

<div class="header">
    <h2>Rates Clearance Calculator</h2>
    <button class="print-button" id="printButton" onclick="printPage()">Print</button>
</div>

<div class="container">
    <div class="form-section">
        <div class="input-group">
            <label for="firstMonth">First Month</label>
            <select id="firstMonth" name="firstMonth">
                <option value="">Select First Month...</option>
                <option value="January">January</option>
                <option value="February">February</option>
                <option value="March">March</option>
                <option value="April">April</option>
                <option value="May">May</option>
                <option value="June">June</option>
                <option value="July">July</option>
                <option value="August">August</option>
                <option value="September">September</option>
                <option value="October">October</option>
                <option value="November">November</option>
                <option value="December">December</option>
            </select>
        </div>

        <div class="input-group">
            <label for="numMonths">Number of Months</label>
        <input type="number" id="numMonths" name="numMonths" min="3" max="4" value="3" />
        </div>

        <div class="input-group">
            <label for="monthlyFee">Monthly Fee (USD)</label>
            <input type="text" id="monthlyFee" name="monthlyFee" placeholder="Enter monthly fee" onblur="formatCurrency(this)" />
        </div>
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
    const accountNumbers = <?php echo json_encode($accountNumbers); ?>;

    function formatCurrency(input) {
        let value = parseFloat(input.value.replace(/[^0-9.-]+/g, ""));
        if (!isNaN(value)) {
            input.value = value.toLocaleString('en-US', { style: 'currency', currency: 'USD' });
        } else {
            input.value = '';
        }
    }

    function updateAccountDetails() {
        const accountsCount = document.getElementById('accounts').value;
        const firstMonth = document.getElementById('firstMonth').value;
        let numMonths = parseInt(document.getElementById('numMonths').value) || 3;
        const monthlyFee = document.getElementById('monthlyFee').value;

        // Enforce numMonths between 3 and 4
        if (numMonths < 3) {
            numMonths = 3;
            document.getElementById('numMonths').value = 3;
        } else if (numMonths > 4) {
            numMonths = 4;
            document.getElementById('numMonths').value = 4;
        }

        const accountDetails = document.getElementById('accountDetails');
        accountDetails.innerHTML = '';

        if (!accountsCount || !firstMonth || !numMonths || !monthlyFee) {
            return;
        }

        const months = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
        let startIndex = months.indexOf(firstMonth);
        if (startIndex === -1) startIndex = 0;

        for (let i = 1; i <= accountsCount; i++) {
            accountDetails.innerHTML += `
                <p>Account ${i}</p>
                <div class="input-group">
                    <label>Account number:</label>
                    <input type="text" id="accountNumber${i}" placeholder="Enter account number" value="${accountNumbers[i - 1] || ''}">
                </div>
            `;

            for (let m = 0; m < numMonths; m++) {
                const monthName = months[(startIndex + m) % 12];
                accountDetails.innerHTML += `
                    <div class="input-group">
                        <label>${monthName} (USD):</label>
                        <input type="text" class="monthly-fee" value="${monthlyFee}" readonly>
                    </div>
                `;
            }
        }
    }
</script>

<form method="POST" action="" id="dataForm" style="display:block; max-width: 800px; margin: auto;">
    <input type="hidden" name="property_id" value="<?php echo htmlspecialchars($propertyId); ?>">
    <input type="hidden" name="application_id" value="<?php echo htmlspecialchars($applicationId); ?>">

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
    <input type="hidden" name="processingFee" id="hiddenProcessingFee" value="" required>
    <input type="hidden" name="totalBalance" id="hiddenTotalBalance" value="" required>

    <button type="submit">Submit</button>
</form>

<div class="footer">
    <p>&copy; 2025 Rates Clearance Calculator. All rights reserved.</p>
</div>

<script>
    // Before form submission, update hidden inputs with visible input values
    document.getElementById('dataForm').addEventListener('submit', function(event) {
        var processingFeeInput = document.querySelector('.processing-fee');
        var overalTotalBalanceInput = document.getElementById('OveralTotalBalance');

        document.getElementById('hiddenProcessingFee').value = processingFeeInput ? processingFeeInput.value : '';
        document.getElementById('hiddenTotalBalance').value = overalTotalBalanceInput ? overalTotalBalanceInput.value : '';
    });
</script>

</body>
</html>
