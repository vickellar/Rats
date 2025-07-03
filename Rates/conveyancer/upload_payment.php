<?php
session_start();

// Error logging setup
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logfile/php_error.log');
error_reporting(E_ALL);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once('../Database/db.php');

// Get user ID from session
$user_id = $_SESSION['user_id'];

// Get property_id from URL if present
$selected_property_id = isset($_GET['property_id']) ? intval($_GET['property_id']) : null;
$selected_account_id = null;
$selected_account_number = null;
$selected_property_address = null;

// Fetch properties that have calculated bills for this user
try {
    // Modified query to only get properties that have calculated bills
    $property_query = "
        SELECT DISTINCT p.property_id, p.address, cb.account_id
        FROM properties p
        INNER JOIN calculated_bills cb ON cb.property_id = p.property_id
        WHERE p.user_id = :user_id
        ORDER BY p.address
    ";
    
    $stmt_properties = $pdo->prepare($property_query);
    $stmt_properties->execute([':user_id' => $user_id]);
    $result_properties = $stmt_properties->fetchAll(PDO::FETCH_ASSOC);

    // If property_id is provided in URL, validate it and get all account info
    if ($selected_property_id) {
        // Check if the property_id belongs to the user and has calculated bills
        $validate_property_query = "
            SELECT DISTINCT p.property_id, p.address, cb.account_id
            FROM properties p
            INNER JOIN calculated_bills cb ON cb.property_id = p.property_id
            WHERE p.property_id = :property_id AND p.user_id = :user_id
        ";
        
        $stmt_validate = $pdo->prepare($validate_property_query);
        $stmt_validate->execute([
            ':property_id' => $selected_property_id,
            ':user_id' => $user_id
        ]);
        
        $property_accounts = $stmt_validate->fetchAll(PDO::FETCH_ASSOC);
        
        if ($property_accounts) {
            $selected_property_address = $property_accounts[0]['address'];
            
            // Get all account numbers for this property
            $selected_property_accounts = [];
            foreach ($property_accounts as $account_data) {
                // Fetch account_number from accounts table
                $stmt_account = $pdo->prepare("SELECT account_number FROM accounts WHERE account_id = :account_id LIMIT 1");
                $stmt_account->execute([':account_id' => $account_data['account_id']]);
                $account_row = $stmt_account->fetch(PDO::FETCH_ASSOC);
                
                if ($account_row) {
                    $selected_property_accounts[] = [
                        'account_id' => $account_data['account_id'],
                        'account_number' => $account_row['account_number']
                    ];
                }
            }
            
            // If only one account, auto-select it
            if (count($selected_property_accounts) == 1) {
                $selected_account_id = $selected_property_accounts[0]['account_id'];
                $selected_account_number = $selected_property_accounts[0]['account_number'];
            }
        } else {
            // Property not found or doesn't have calculated bills
            $error_message = "Invalid property or property doesn't have calculated bills.";
        }
    }

} catch (PDOException $e) {
    error_log("DB fetch error: " . $e->getMessage());
    $error_message = "An internal error occurred. Please try again later.";
}

// Prepare accounts grouped by property_id for JavaScript
$accounts_by_property = [];
foreach ($result_properties as $row) {
    if (!isset($accounts_by_property[$row['property_id']])) {
        $accounts_by_property[$row['property_id']] = [];
    }
    
    // Fetch account number for this account_id
    try {
        $stmt_acc = $pdo->prepare("SELECT account_number FROM accounts WHERE account_id = :account_id");
        $stmt_acc->execute([':account_id' => $row['account_id']]);
        $acc_row = $stmt_acc->fetch(PDO::FETCH_ASSOC);
        
        $accounts_by_property[$row['property_id']][] = [
            'account_id' => $row['account_id'],
            'account_number' => $acc_row ? $acc_row['account_number'] : 'Account #' . $row['account_id']
        ];
    } catch (PDOException $e) {
        error_log("Error fetching account number: " . $e->getMessage());
    }
}

// Function to get minimum payment amount for a property
function getMinimumPaymentAmount($pdo, $property_id, $month_is) {
    try {
        // Sum up total_balance and processing_fee from calculated_bills for the property and month
        $query = "SELECT 
                    COALESCE(SUM(total_balance), 0) as total_balance_sum,
                    COALESCE(SUM(processing_fee), 0) as processing_fee_sum,
                    COALESCE(SUM(overall_total), 0) as overall_total_sum
                  FROM calculated_bills 
                  WHERE property_id = :property_id";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([':property_id' => $property_id]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            return [
                'total_balance' => $result['total_balance_sum'],
                'processing_fee' => $result['processing_fee_sum'],
                'overall_total' => $result['overall_total_sum'],
                'minimum_amount' => $result['overall_total_sum']
            ];
        }
        
        return [
            'total_balance' => 0,
            'processing_fee' => 0,
            'overall_total' => 0,
            'minimum_amount' => 0
        ];
        
    } catch (PDOException $e) {
        error_log("Error getting minimum payment amount: " . $e->getMessage());
        return [
            'total_balance' => 0,
            'processing_fee' => 0,
            'overall_total' => 0,
            'minimum_amount' => 0
        ];
    }
}

// Initialize variables
$success_message = "";
$error_message = "";
$amount_paid = "";
$payment_date = date("Y-m-d");
$payment_method = "";
$invoice_number = "";
$receipt_number = "";
$property_address = $selected_property_address ?: "";
$property_id = $selected_property_id ?: "";
$account_id = $selected_account_id ?: "";
$month_is = date("n");

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate and sanitize inputs
    $property_address = filter_input(INPUT_POST, 'property_address', FILTER_SANITIZE_STRING);
    $account_id = filter_input(INPUT_POST, 'account_id', FILTER_SANITIZE_NUMBER_INT);
    $amount_paid = filter_input(INPUT_POST, 'amount_paid', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $payment_date = filter_input(INPUT_POST, 'payment_date', FILTER_SANITIZE_STRING);
    $payment_method = filter_input(INPUT_POST, 'payment_method', FILTER_SANITIZE_STRING);
    $invoice_number = filter_input(INPUT_POST, 'invoice_number', FILTER_SANITIZE_NUMBER_INT);
    $receipt_number = filter_input(INPUT_POST, 'receipt_number', FILTER_SANITIZE_NUMBER_INT);
    $notes = filter_input(INPUT_POST, 'notes', FILTER_SANITIZE_STRING);
    
    // Validate required fields
    if (empty($property_address) || empty($account_id) || empty($amount_paid) || 
        empty($payment_date) || empty($payment_method) || 
        empty($invoice_number) || empty($receipt_number)) {
        $error_message = "All fields are required";
    } else {
        // Find property_id based on property_address and ensure it has calculated bills
        $property_id = null;
        foreach ($result_properties as $row) {
            if ($row['address'] === $property_address) {
                $property_id = $row['property_id'];
                break;
            }
        }
        
        if ($property_id === null) {
            $error_message = "Invalid property address or property doesn't have calculated bills";
        } else {
            // Validate that the account_id belongs to this property in calculated_bills
            try {
                $validate_account_query = "
                    SELECT COUNT(*) as count 
                    FROM calculated_bills 
                    WHERE property_id = :property_id AND account_id = :account_id
                ";
                $stmt_validate_account = $pdo->prepare($validate_account_query);
                $stmt_validate_account->execute([
                    ':property_id' => $property_id,
                    ':account_id' => $account_id
                ]);
                $account_validation = $stmt_validate_account->fetch(PDO::FETCH_ASSOC);
                
                if ($account_validation['count'] == 0) {
                    $error_message = "Invalid account selection for this property";
                } else {
                    // Validate payment amount against calculated bills
                    $payment_info = getMinimumPaymentAmount($pdo, $property_id, $month_is);
                    $minimum_required = $payment_info['minimum_amount'];
                    
                    if ($amount_paid < $minimum_required) {
                        $error_message = "Payment amount ($" . number_format($amount_paid, 2) . ") must be greater than or equal to the required amount ($" . number_format($minimum_required, 2) . ") for this property.";
                    } else {
                        // Handle file upload
                        $receipt_name = "";
                        $receipt_fpath = "";
                        $upload_dir = __DIR__ . '/../uploads/receipts/'; // Use absolute path for reliability
                        if (!file_exists($upload_dir)) {
                            mkdir($upload_dir, 0777, true);
                        }

                        if (isset($_FILES['receipt']) && $_FILES['receipt']['error'] == 0) {
                            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];
                            $file_type = $_FILES['receipt']['type'];
                            if (in_array($file_type, $allowed_types)) {
                                $receipt_name = time() . '_' . basename($_FILES['receipt']['name']);
                                $receipt_fpath = $upload_dir . $receipt_name;
                                if (move_uploaded_file($_FILES['receipt']['tmp_name'], $receipt_fpath)) {
                                    // File uploaded successfully
                                    // Store relative path for DB if needed:
                                    $receipt_fpath = 'uploads/receipts/' . $receipt_name;
                                } else {
                                    $error_message = "Failed to upload file";
                                }
                            } else {
                                $error_message = "Invalid file type. Only JPG, PNG, GIF, and PDF are allowed.";
                            }
                        } else {
                            $error_message = "Receipt file is required";
                        }

                        // If no errors, insert into database
                        if (empty($error_message)) {
                            try {
                                // Get bill_id from calculated_bills
                                $bill_query = "SELECT bill_id FROM calculated_bills WHERE property_id = :property_id AND account_id = :account_id LIMIT 1";
                                $stmt_bill = $pdo->prepare($bill_query);
                                $stmt_bill->execute([
                                    ':property_id' => $property_id,
                                    ':account_id' => $account_id
                                ]);
                                $bill_row = $stmt_bill->fetch(PDO::FETCH_ASSOC);
                                
                                if ($bill_row) {
                                    $bill_id = $bill_row['bill_id'];
                                    
                                    // Insert payment record
                                    $insert_query = "INSERT INTO payments 
                                        (property_id, account_id, user_id, receipt_name, receipt_fpath, amount_paid, payment_date, payment_method, transaction_status, invoice_number, receipt_number, bill_id, notes) 
                                        VALUES 
                                        (:property_id, :account_id, :user_id, :receipt_name, :receipt_fpath, :amount_paid, :payment_date, :payment_method, 'Pending', :invoice_number, :receipt_number, :bill_id, :notes)";
                                    
                                    $stmt_insert = $pdo->prepare($insert_query);
                                    $stmt_insert->execute([
                                        ':property_id' => $property_id,
                                        ':account_id' => $account_id,
                                        ':user_id' => $user_id,
                                        ':receipt_name' => $receipt_name,
                                        ':receipt_fpath' => $receipt_fpath,
                                        ':amount_paid' => $amount_paid,
                                        ':payment_date' => $payment_date,
                                        ':payment_method' => $payment_method,
                                        ':invoice_number' => $invoice_number,
                                        ':receipt_number' => $receipt_number,
                                        ':bill_id' => $bill_id,
                                        ':notes' => $notes
                                    ]);

                                    $success_message = "Payment of $" . number_format($amount_paid, 2) . " uploaded successfully!";
                                    
                                    // Reset form fields
                                    $amount_paid = "";
                                    $payment_date = date("Y-m-d");
                                    $payment_method = "";
                                    $invoice_number = "";
                                    $receipt_number = "";
                                    if (!$selected_property_id) {
                                        $property_address = "";
                                        $account_id = "";
                                    }
                                } else {
                                    $error_message = "No calculated bill found for the selected property and account";
                                }
                            } catch (PDOException $e) {
                                error_log("Insert payment error: " . $e->getMessage());
                                $error_message = "An internal error occurred. Please try again later.";
                            }
                        }
                    }
                }
            } catch (PDOException $e) {
                error_log("Account validation error: " . $e->getMessage());
                $error_message = "An internal error occurred during validation.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Payment</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .payment-form {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            box-sizing: border-box;
        }
        .btn-primary {
            background-color: #4CAF50;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        .btn-primary:hover {
            background-color: #45a049;
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .alert-info {
            background-color: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        .form-row {
            display: flex;
            flex-wrap: wrap;
            margin-right: -10px;
            margin-left: -10px;
        }
        .form-col {
            flex: 0 0 50%;
            max-width: 50%;
            padding-right: 10px;
            padding-left: 10px;
            box-sizing: border-box;
        }
        @media (max-width: 768px) {
            .form-col {
                flex: 0 0 100%;
                max-width: 100%;
            }
        }
        .file-upload {
            position: relative;
            display: inline-block;
            width: 100%;
        }
        .file-upload-label {
            display: block;
            padding: 10px;
            background-color: #f8f9fa;
            border: 1px dashed #ddd;
            border-radius: 4px;
            text-align: center;
            cursor: pointer;
        }
        .file-upload input[type="file"] {
            position: absolute;
            left: 0;
            top: 0;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }
        .file-name {
            margin-top: 5px;
            font-size: 14px;
            color: #666;
        }
        .loading {
            opacity: 0.6;
            pointer-events: none;
        }
        .payment-info {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 15px;
            margin-bottom: 20px;
            display: none;
        }
        .payment-info.show {
            display: block;
        }
        .payment-info h4 {
            margin-top: 0;
            color: #495057;
        }
        .payment-breakdown {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-top: 10px;
        }
        .payment-breakdown div {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
            border-bottom: 1px solid #dee2e6;
        }
        .payment-breakdown div:last-child {
            border-bottom: 2px solid #007bff;
            font-weight: bold;
            color: #007bff;
        }
        .property-locked {
            background-color: #e9ecef;
            color: #6c757d;
        }
        .property-locked:disabled {
            cursor: not-allowed;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1><i class="fas fa-money-bill-wave"></i> Upload Payment</h1>
        
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
            </div>
        <?php endif; ?>
        
        <?php if (empty($result_properties)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> No properties with calculated bills found. Please ensure your properties have calculated bills before making payments.
            </div>
        <?php else: ?>
        
        <div class="payment-form">
            <!-- Payment Information Display -->
            <div id="payment-info" class="payment-info <?php echo $selected_property_id ? 'show' : ''; ?>">
                <h4><i class="fas fa-info-circle"></i> Payment Requirements</h4>
                <div class="payment-breakdown">
                    <div>
                        <span>Total Balance:</span>
                        <span id="total-balance">$0.00</span>
                    </div>
                    <div>
                        <span>Processing Fee:</span>
                        <span id="processing-fee">$0.00</span>
                    </div>
                    <div>
                        <span>Minimum Required:</span>
                        <span id="minimum-amount">$0.00</span>
                    </div>
                </div>
            </div>
            
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . ($selected_property_id ? '?property_id=' . $selected_property_id : ''); ?>" method="post" enctype="multipart/form-data">
                <div class="form-row">
                    <div class="form-col">
                        <div class="form-group">
                            <label for="property_address">Property Address:</label>
                            <select name="property_address" id="property_address" class="form-control <?php echo $selected_property_id ? 'property-locked' : ''; ?>" <?php echo $selected_property_id ? 'disabled' : ''; ?> required>
                                <?php if ($selected_property_id): ?>
                                    <option value="<?php echo htmlspecialchars($selected_property_address); ?>" selected>
                                        <?php echo htmlspecialchars($selected_property_address); ?> (Selected from URL)
                                    </option>
                                    <input type="hidden" name="property_address" value="<?php echo htmlspecialchars($selected_property_address); ?>">
                                <?php else: ?>
                                    <option value="">Select property address</option>
                                    <?php foreach ($result_properties as $row): ?>
                                        <option value="<?php echo htmlspecialchars($row['address']); ?>" <?php echo ($property_address == $row['address']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($row['address']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                            <?php if ($selected_property_id): ?>
                                <small class="text-muted">Property selected from URL link</small>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="form-col">
                        <div class="form-group">
                            <label for="account_id">Account:</label>
                            <select name="account_id" id="account_id" class="form-control" required>
                                <?php if ($selected_property_id && !empty($selected_property_accounts)): ?>
                                    <option value="">-- Select Account --</option>
                                    <?php foreach ($selected_property_accounts as $account): ?>
                                        <option value="<?php echo $account['account_id']; ?>" 
                                                <?php echo ($account_id == $account['account_id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($account['account_number']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php elseif ($selected_account_id && $selected_account_number): ?>
                                    <option value="<?php echo $selected_account_id; ?>" selected>
                                        <?php echo htmlspecialchars($selected_account_number); ?>
                                    </option>
                                <?php else: ?>
                                    <option value="">-- First Select Property --</option>
                                <?php endif; ?>
                            </select>
                            <?php if ($selected_property_id): ?>
                                <small class="text-muted">
                                    <?php if (count($selected_property_accounts) > 1): ?>
                                        Multiple accounts available - please select one
                                    <?php else: ?>
                                        Account for selected property
                                    <?php endif; ?>
                                </small>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-col">
                        <div class="form-group">
                            <label for="amount_paid">Amount Paid:</label>
                            <input type="number" step="0.01" name="amount_paid" id="amount_paid" class="form-control" value="<?php echo htmlspecialchars($amount_paid); ?>" required>
                            <small class="text-muted">Enter amount to pay (must meet minimum requirement)</small>
                        </div>
                    </div>
                    <div class="form-col">
                        <div class="form-group">
                            <label for="payment_method">Payment Method:</label>
                            <select name="payment_method" id="payment_method" class="form-control" required>
                                <option value="">-- Select Method --</option>
                                <option value="Bank Transfer" <?php echo ($payment_method == 'Bank Transfer') ? 'selected' : ''; ?>>Bank Transfer</option>
                                <option value="Online" <?php echo ($payment_method == 'Online') ? 'selected' : ''; ?>>Online</option>
                                <option value="Cash" <?php echo ($payment_method == 'Cash') ? 'selected' : ''; ?>>Cash</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-col">
                        <div class="form-group">
                            <label for="payment_date">Payment Date:</label>
                            <input type="date" name="payment_date" id="payment_date" class="form-control" value="<?php echo htmlspecialchars($payment_date); ?>" required>
                        </div>
                    </div>
                    <div class="form-col">
                        <div class="form-group">
                            <label for="receipt">Upload Receipt:</label>
                            <div class="file-upload">
                                <label for="receipt" class="file-upload-label">
                                    <i class="fas fa-cloud-upload-alt"></i> Choose File (JPG, PNG, PDF)
                                </label>
                                <input type="file" name="receipt" id="receipt" accept=".jpg,.jpeg,.png,.gif,.pdf" required>
                                <div class="file-name" id="file-name">No file chosen</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-col">
                        <div class="form-group">
                            <label for="invoice_number">Invoice Number:</label>
                            <input type="number" name="invoice_number" id="invoice_number" class="form-control" value="<?php echo htmlspecialchars($invoice_number); ?>" required>
                        </div>
                    </div>
                    <div class="form-col">
                        <div class="form-group">
                            <label for="receipt_number">Receipt Number:</label>
                            <input type="number" name="receipt_number" id="receipt_number" class="form-control" value="<?php echo htmlspecialchars($receipt_number); ?>" required>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label for="notes">Notes</label>
                    <input type="text" name="notes" id="notes" class="form-control" placeholder="Additional Notes (optional)">
                </div>
                <div class="form-group">
                    <p class="text-muted">
                        <i class="fas fa-info-circle"></i> 
                        Please ensure all details are correct before submitting. Payments will be processed within 3-5 business days.
                </div>
                <div class="form-group">
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-save"></i> Submit Payment
                    </button>
                </div>
                
            </form>
        </div>
        
        <?php endif; ?>
    </div>
    
<script>
    // Pass PHP data to JavaScript
    var accountsByProperty = <?php echo json_encode($accounts_by_property); ?>;
    var addressToPropertyId = {};
    var selectedPropertyAccounts = <?php echo isset($selected_property_accounts) ? json_encode($selected_property_accounts) : 'null'; ?>;

    // Build address to property ID mapping
    <?php foreach ($result_properties as $row): ?>
        addressToPropertyId[<?php echo json_encode($row['address']); ?>] = <?php echo json_encode($row['property_id']); ?>;
    <?php endforeach; ?>

    var currentMinimumAmount = 0;
    var selectedPropertyId = <?php echo $selected_property_id ? $selected_property_id : 'null'; ?>;

    console.log('Accounts by property:', accountsByProperty);
    console.log('Selected property ID:', selectedPropertyId);
    console.log('Selected property accounts:', selectedPropertyAccounts);

    // Display selected filename
    document.getElementById('receipt').addEventListener('change', function() {
        var fileName = this.files.length > 0 ? this.files[0].name : 'No file chosen';
        document.getElementById('file-name').textContent = fileName;
    });

    // Function to fetch payment requirements
    function fetchPaymentRequirements(propertyId) {
        if (propertyId) {
            // Make AJAX call to get payment requirements
            fetch('get_payment_requirements.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'property_id=' + propertyId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('total-balance').textContent = '$' + parseFloat(data.total_balance).toFixed(2);
                    document.getElementById('processing-fee').textContent = '$' + parseFloat(data.processing_fee).toFixed(2);
                    document.getElementById('minimum-amount').textContent = '$' + parseFloat(data.minimum_amount).toFixed(2);
                    document.getElementById('payment-info').classList.add('show');
                    currentMinimumAmount = parseFloat(data.minimum_amount);
                    
                    // Update amount input placeholder
                    document.getElementById('amount_paid').placeholder = 'Minimum: $' + parseFloat(data.minimum_amount).toFixed(2);
                } else {
                    document.getElementById('payment-info').classList.remove('show');
                    currentMinimumAmount = 0;
                }
            })
            .catch(error => {
                console.error('Error fetching payment requirements:', error);
                document.getElementById('payment-info').classList.remove('show');
            });
        } else {
            document.getElementById('payment-info').classList.remove('show');
        }
    }

    // Populate accounts based on selected property address (only if not locked)
    if (!selectedPropertyId) {
        document.getElementById('property_address').addEventListener('change', function() {
            var selectedAddress = this.value;
            var accountSelect = document.getElementById('account_id');
            accountSelect.innerHTML = '<option value="">-- Select Account --</option>';
            
            var propertyId = addressToPropertyId[selectedAddress];
            
            if (propertyId && accountsByProperty[propertyId]) {
                accountsByProperty[propertyId].forEach(function(account) {
                    var option = document.createElement('option');
                    option.value = account.account_id;
                    option.textContent = account.account_number;
                    accountSelect.appendChild(option);
                });
            
                // Auto-select first account if only one exists
                if (accountsByProperty[propertyId].length === 1) {
                    accountSelect.value = accountsByProperty[propertyId][0].account_id;
                }
            
                // Fetch payment requirements for this property
                fetchPaymentRequirements(propertyId);
            } else if (propertyId) {
                var option = document.createElement('option');
                option.value = '';
                option.textContent = '-- No accounts found for this property --';
                option.disabled = true;
                accountSelect.appendChild(option);
            }
        });
    }

    // Add event listener for account selection to update payment info if needed
    document.getElementById('account_id').addEventListener('change', function() {
        var propertyId = selectedPropertyId;
        if (!propertyId) {
            var selectedAddress = document.getElementById('property_address').value;
            propertyId = addressToPropertyId[selectedAddress];
        }
    
        if (propertyId && this.value) {
            // Refresh payment requirements when account changes
            fetchPaymentRequirements(propertyId);
        }
    });

    // Initialize on page load
    window.addEventListener('DOMContentLoaded', function() {
        if (selectedPropertyId) {
            // If property is selected from URL, fetch payment requirements
            fetchPaymentRequirements(selectedPropertyId);
        
            // If multiple accounts available, show selection message
            if (selectedPropertyAccounts && selectedPropertyAccounts.length > 1) {
                console.log('Multiple accounts available for this property');
            }
        } else {
            // Trigger change event if property is already selected
            var propertySelect = document.getElementById('property_address');
            if (propertySelect.value) {
                var event = new Event('change');
                propertySelect.dispatchEvent(event);
            }
        }
    });

    // Validate payment amount before submission
    document.querySelector('form').addEventListener('submit', function(e) {
        var amountPaid = parseFloat(document.getElementById('amount_paid').value);
        if (currentMinimumAmount > 0 && amountPaid < currentMinimumAmount) {
            e.preventDefault();
            alert('Payment amount must be at least $' + currentMinimumAmount.toFixed(2));
            return false;
        }
    });
</script>

</body>
</html>
