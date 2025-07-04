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

// --- NEW: Check if property_id exists in calculated_bills ---
if ($selected_property_id) {
    $stmt_check = $pdo->prepare("SELECT COUNT(*) as count FROM calculated_bills WHERE property_id = :property_id");
    $stmt_check->execute([':property_id' => $selected_property_id]);
    $row = $stmt_check->fetch(PDO::FETCH_ASSOC);
    
    if (empty($row['count']) || $row['count'] == 0) {
        // Show message and back button, then exit
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>No Calculated Bill</title>
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
            <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
            <style>
                * {
                    margin: 0;
                    padding: 0;
                    box-sizing: border-box;
                }
                
                body {
                    font-family: 'Inter', sans-serif;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    min-height: 100vh;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }
                
                .error-container {
                    max-width: 500px;
                    background: white;
                    border-radius: 20px;
                    box-shadow: 0 20px 40px rgba(0,0,0,0.1);
                    padding: 40px;
                    text-align: center;
                    animation: slideUp 0.6s ease-out;
                }
                
                @keyframes slideUp {
                    from {
                        opacity: 0;
                        transform: translateY(30px);
                    }
                    to {
                        opacity: 1;
                        transform: translateY(0);
                    }
                }
                
                .error-icon {
                    font-size: 4rem;
                    color: #e74c3c;
                    margin-bottom: 20px;
                    animation: pulse 2s infinite;
                }
                
                @keyframes pulse {
                    0%, 100% { transform: scale(1); }
                    50% { transform: scale(1.05); }
                }
                
                .error-title {
                    color: #2c3e50;
                    font-size: 1.8rem;
                    font-weight: 600;
                    margin-bottom: 15px;
                }
                
                .error-message {
                    color: #7f8c8d;
                    font-size: 1.1rem;
                    line-height: 1.6;
                    margin-bottom: 30px;
                }
                
                .btn-back {
                    background: linear-gradient(135deg, #3498db, #2980b9);
                    color: white;
                    border: none;
                    padding: 15px 30px;
                    border-radius: 50px;
                    font-size: 1rem;
                    font-weight: 500;
                    cursor: pointer;
                    transition: all 0.3s ease;
                    display: inline-flex;
                    align-items: center;
                    gap: 10px;
                }
                
                .btn-back:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 10px 20px rgba(52, 152, 219, 0.3);
                }
            </style>
        </head>
        <body>
            <div class="error-container">
                <i class="fas fa-exclamation-triangle error-icon"></i>
                <h2 class="error-title">No Calculated Bill Found</h2>
                <p class="error-message">This property does not have a calculated bill. You cannot upload a payment for it.</p>
                <button class="btn-back" onclick="window.history.back();">
                    <i class="fas fa-arrow-left"></i> 
                    Go Back
                </button>
            </div>
        </body>
        </html>
        <?php
        exit();
    }
}

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

// Prefill invoice number from calculated_bills using only property_id
if ($selected_property_id) {
    $stmt_invoice = $pdo->prepare("SELECT invoice_number FROM calculated_bills WHERE property_id = :property_id ORDER BY calculated_at DESC LIMIT 1");
    $stmt_invoice->execute([':property_id' => $selected_property_id]);
    $invoice_row = $stmt_invoice->fetch(PDO::FETCH_ASSOC);
    if ($invoice_row) {
        $invoice_number = $invoice_row['invoice_number'];
    }
}

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate and sanitize inputs
    $property_address = filter_input(INPUT_POST, 'property_address', FILTER_UNSAFE_RAW);
    $account_id = filter_input(INPUT_POST, 'account_id', FILTER_SANITIZE_NUMBER_INT);
    $amount_paid = filter_input(INPUT_POST, 'amount_paid', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $payment_date = filter_input(INPUT_POST, 'payment_date', FILTER_UNSAFE_RAW);
    $payment_method = filter_input(INPUT_POST, 'payment_method', FILTER_UNSAFE_RAW);
    $invoice_number = filter_input(INPUT_POST, 'invoice_number', FILTER_SANITIZE_NUMBER_INT);
    $receipt_number = filter_input(INPUT_POST, 'receipt_number', FILTER_SANITIZE_NUMBER_INT);
    $notes = filter_input(INPUT_POST, 'notes', FILTER_UNSAFE_RAW);
    
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
            $error_message = "Invalid property address or property doesn't have calculated bills.";
        } else {
            // Check that the property has a calculated bill
            $stmt_check_bill = $pdo->prepare("SELECT COUNT(*) as count FROM calculated_bills WHERE property_id = :property_id");
            $stmt_check_bill->execute([':property_id' => $property_id]);
            $bill_check = $stmt_check_bill->fetch(PDO::FETCH_ASSOC);
            
            if ($bill_check['count'] == 0) {
                $error_message = "This property does not have a calculated bill. Payment cannot be uploaded.";
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
                                            (property_id, account_id, user_id, receipt_name, receipt_fpath, amount_paid, payment_date, payment_method, payment_status, invoice_number, receipt_number, bill_id, notes)
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
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Payment - FinanceHub</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #333;
        }
        
        .container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .page-header {
            text-align: center;
            margin-bottom: 40px;
            color: white;
            animation: fadeInDown 0.8s ease-out;
        }
        
        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .page-header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 10px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }
        
        .page-header p {
            font-size: 1.1rem;
            opacity: 0.9;
        }
        
        .payment-form {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            padding: 40px;
            animation: fadeInUp 0.8s ease-out;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .form-section {
            margin-bottom: 30px;
        }
        
        .section-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .section-title i {
            color: #667eea;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .form-group {
            position: relative;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #34495e;
            font-size: 0.95rem;
        }
        
        .form-control {
            width: 100%;
            padding: 15px 20px;
            border: 2px solid #e1e8ed;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            transform: translateY(-1px);
        }
        
        .form-control:disabled {
            background: #e9ecef;
            color: #6c757d;
            cursor: not-allowed;
        }
        
        .property-locked {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-color: #dee2e6;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 18px 40px;
            border-radius: 50px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }
        
        .btn-primary:active {
            transform: translateY(0);
        }
        
        .alert {
            padding: 20px 25px;
            border-radius: 15px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 15px;
            font-weight: 500;
            animation: slideIn 0.5s ease-out;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        .alert-success {
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-danger {
            background: linear-gradient(135deg, #f8d7da, #f5c6cb);
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .alert-info {
            background: linear-gradient(135deg, #d1ecf1, #bee5eb);
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        
        .alert i {
            font-size: 1.2rem;
        }
        
        .file-upload {
            position: relative;
            display: block;
            width: 100%;
        }
        
        .file-upload-label {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 20px;
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border: 2px dashed #dee2e6;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 500;
            color: #6c757d;
        }
        
        .file-upload-label:hover {
            border-color: #667eea;
            background: linear-gradient(135deg, #f0f2ff, #e6e9ff);
            color: #667eea;
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
            margin-top: 10px;
            font-size: 0.9rem;
            color: #6c757d;
            text-align: center;
            font-style: italic;
        }
        
        .payment-info {
            background: linear-gradient(135deg, #e3f2fd, #bbdefb);
            border: 1px solid #90caf9;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            display: none;
            animation: expandIn 0.5s ease-out;
        }
        
        @keyframes expandIn {
            from {
                opacity: 0;
                max-height: 0;
                padding: 0 25px;
            }
            to {
                opacity: 1;
                max-height: 200px;
                padding: 25px;
            }
        }
        
        .payment-info.show {
            display: block;
        }
        
        .payment-info h4 {
            margin: 0 0 20px 0;
            color: #1565c0;
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .payment-breakdown {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .payment-item {
            background: white;
            padding: 15px;
            border-radius: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .payment-item:last-child {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            font-weight: 600;
        }
        
        .text-muted {
            color: #6c757d;
            font-size: 0.85rem;
            margin-top: 5px;
        }
        
        .form-footer {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 15px;
            margin-top: 30px;
            text-align: center;
        }
        
        .form-footer p {
            color: #6c757d;
            margin-bottom: 20px;
            line-height: 1.6;
        }
        
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }
        
        .loading-spinner {
            background: white;
            padding: 30px;
            border-radius: 15px;
            text-align: center;
        }
        
        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 15px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }
            
            .payment-form {
                padding: 25px;
            }
            
            .page-header h1 {
                font-size: 2rem;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner">
            <div class="spinner"></div>
            <p>Processing payment...</p>
        </div>
    </div>
    
    <div class="container">
        <div class="page-header">
            <h1><i class="fas fa-credit-card"></i> Upload Payment</h1>
            <p>Submit your payment details securely and efficiently</p>
        </div>
        
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <span><?php echo $success_message; ?></span>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo $error_message; ?></span>
            </div>
        <?php endif; ?>
        
        <?php if (empty($result_properties)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                <span>No properties with calculated bills found. Please ensure your properties have calculated bills before making payments.</span>
            </div>
        <?php else: ?>
            
            <div class="payment-form">
                <!-- Payment Information Display -->
                <div id="payment-info" class="payment-info <?php echo $selected_property_id ? 'show' : ''; ?>">
                    <h4><i class="fas fa-calculator"></i> Payment Requirements</h4>
                    <div class="payment-breakdown">
                        <div class="payment-item">
                            <span>Total Balance:</span>
                            <span id="total-balance">$0.00</span>
                        </div>
                        <div class="payment-item">
                            <span>Processing Fee:</span>
                            <span id="processing-fee">$0.00</span>
                        </div>
                        <div class="payment-item">
                            <span>Minimum Required:</span>
                            <span id="minimum-amount">$0.00</span>
                        </div>
                    </div>
                </div>
                
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . ($selected_property_id ? '?property_id=' . $selected_property_id : ''); ?>" method="post" enctype="multipart/form-data" id="paymentForm">
                    
                    <!-- Property & Account Section -->
                    <div class="form-section">
                        <h3 class="section-title">
                            <i class="fas fa-building"></i>
                            Property & Account Details
                        </h3>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="property_address">Property Address</label>
                                <select name="property_address" id="property_address" class="form-control <?php echo $selected_property_id ? 'property-locked' : ''; ?>" <?php echo $selected_property_id ? 'disabled' : ''; ?> required>
                                    <?php if ($selected_property_id): ?>
                                        <option value="<?php echo htmlspecialchars($selected_property_address); ?>" selected>
                                            <?php echo htmlspecialchars($selected_property_address); ?>
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
                                    <div class="text-muted">Property selected from URL link</div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="form-group">
                                <label for="account_id">Account Number</label>
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
                                    <div class="text-muted">
                                        <?php if (count($selected_property_accounts) > 1): ?>
                                            Multiple accounts available - please select one
                                        <?php else: ?>
                                            Account for selected property
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Payment Details Section -->
                    <div class="form-section">
                        <h3 class="section-title">
                            <i class="fas fa-money-bill-wave"></i>
                            Payment Details
                        </h3>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="amount_paid">Amount Paid</label>
                                <input type="number" step="0.01" name="amount_paid" id="amount_paid" class="form-control" value="<?php echo htmlspecialchars($amount_paid); ?>" required>
                                <div class="text-muted">Enter amount to pay (must meet minimum requirement)</div>
                            </div>
                            
                            <div class="form-group">
                                <label for="payment_method">Payment Method</label>
                                <select name="payment_method" id="payment_method" class="form-control" required>
                                    <option value="">-- Select Method --</option>
                                    <option value="Bank Transfer" <?php echo ($payment_method == 'Bank Transfer') ? 'selected' : ''; ?>>Bank Transfer</option>
                                    <option value="Online" <?php echo ($payment_method == 'Online') ? 'selected' : ''; ?>>Online Payment</option>
                                    <option value="Cash" <?php echo ($payment_method == 'Cash') ? 'selected' : ''; ?>>Cash</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="payment_date">Payment Date</label>
                                <input type="date" name="payment_date" id="payment_date" class="form-control" value="<?php echo htmlspecialchars($payment_date); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="receipt">Upload Receipt</label>
                                <div class="file-upload">
                                    <label for="receipt" class="file-upload-label">
                                        <i class="fas fa-cloud-upload-alt"></i>
                                        Choose Receipt File
                                    </label>
                                    <input type="file" name="receipt" id="receipt" accept=".jpg,.jpeg,.png,.gif,.pdf" required>
                                    <div class="file-name" id="file-name">No file chosen</div>
                                </div>
                                <div class="text-muted">Accepted formats: JPG, PNG, GIF, PDF (Max 10MB)</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Invoice Details Section -->
                    <div class="form-section">
                        <h3 class="section-title">
                            <i class="fas fa-file-invoice"></i>
                            Invoice Details
                        </h3>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="invoice_number">Invoice Number</label>
                                <?php if ($selected_property_id): ?>
                                    <input type="text" name="invoice_number_display" id="invoice_number" class="form-control property-locked" value="<?php echo htmlspecialchars($invoice_number); ?>" readonly>
                                    <input type="hidden" name="invoice_number" value="<?php echo htmlspecialchars($invoice_number); ?>">
                                    <div class="text-muted">Invoice number auto-filled for selected property</div>
                                <?php else: ?>
                                    <input type="text" name="invoice_number" id="invoice_number" class="form-control" value="<?php echo htmlspecialchars($invoice_number); ?>" required>
                                <?php endif; ?>
                            </div>
                            
                            <div class="form-group">
                                <label for="receipt_number">Receipt Number</label>
                                <input type="number" name="receipt_number" id="receipt_number" class="form-control" value="<?php echo htmlspecialchars($receipt_number); ?>" required>
                                <div class="text-muted">Enter your receipt reference number</div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="notes">Additional Notes</label>
                            <textarea name="notes" id="notes" class="form-control" rows="3" placeholder="Any additional information or comments (optional)"></textarea>
                        </div>
                    </div>
                    
                    <div class="form-footer">
                        <p>
                            <i class="fas fa-shield-alt"></i>
                            Your payment information is secure and will be processed within 3-5 business days.
                            Please ensure all details are correct before submitting.
                        </p>
                        
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-paper-plane"></i>
                            Submit Payment
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
        
        // Display selected filename with animation
        document.getElementById('receipt').addEventListener('change', function() {
            var fileName = this.files.length > 0 ? this.files[0].name : 'No file chosen';
            var fileNameElement = document.getElementById('file-name');
            fileNameElement.style.opacity = '0';
            setTimeout(() => {
                fileNameElement.textContent = fileName;
                fileNameElement.style.opacity = '1';
            }, 150);
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
            } else {
                // Trigger change event if property is already selected
                var propertySelect = document.getElementById('property_address');
                if (propertySelect.value) {
                    var event = new Event('change');
                    propertySelect.dispatchEvent(event);
                }
            }
        });
        
        // Enhanced form submission with loading overlay
        document.getElementById('paymentForm').addEventListener('submit', function(e) {
            var amountPaid = parseFloat(document.getElementById('amount_paid').value);
            
            if (currentMinimumAmount > 0 && amountPaid < currentMinimumAmount) {
                e.preventDefault();
                alert('Payment amount must be at least $' + currentMinimumAmount.toFixed(2));
                return false;
            }
            
            // Show loading overlay
            document.getElementById('loadingOverlay').style.display = 'flex';
        });
        
        // Add smooth transitions to form elements
        document.querySelectorAll('.form-control').forEach(function(element) {
            element.addEventListener('focus', function() {
                this.style.transform = 'translateY(-1px)';
            });
            
            element.addEventListener('blur', function() {
                this.style.transform = 'translateY(0)';
            });
        });
    </script>
</body>
</html>
