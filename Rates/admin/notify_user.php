<?php
require_once '../Database/db.php';
session_start();

if (!isset($_GET['application_id'])) {
    echo "Error: application_id is required.";
    exit;
}

$applicationId = intval($_GET['application_id']);

try {
    // Fetch the latest calculated bill for the application
    $stmtBill = $pdo->prepare("
        SELECT cb.*, p.property_name, u.username, u.email
        FROM calculated_bills cb
        JOIN rate_clearance_applications rca ON cb.application_id = rca.application_id
        JOIN properties p ON rca.property_id = p.property_id
        JOIN users u ON cb.user_id = u.user_id
        WHERE cb.application_id = :applicationId
        ORDER BY cb.bill_id DESC
        LIMIT 1
    ");
    $stmtBill->execute(['applicationId' => $applicationId]);
    $bill = $stmtBill->fetch();

    if (!$bill) {
        echo "No calculated bill found for the given application.";
        exit;
    }

    // Prepare notification message
    $message = "Dear " . htmlspecialchars($bill['username']) . ",\n\n";
    $message .= "Your rates clearance bill for the property '" . htmlspecialchars($bill['property_name']) . "' has been calculated and stored successfully.\n";
    $message .= "Total Balance: $" . number_format($bill['total_balance'], 2) . "\n";
    $message .= "Processing Fee: $" . number_format($bill['processing_fee'], 2) . "\n";
    $message .= "Overall Total: $" . number_format($bill['overall_total'], 2) . "\n\n";
    $message .= "Thank you for using the Rates Clearance System.";

} catch (PDOException $e) {
    error_log("Error fetching calculated bill for notification: " . $e->getMessage(), 3, __DIR__ . '/../logfile/database_errors.log');
    echo "Error fetching calculated bill data.";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Rates Clearance Notification</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .notification { border: 1px solid #007bff; padding: 20px; border-radius: 8px; background-color: #e9f5ff; }
        h2 { color: #007bff; }
        pre { white-space: pre-wrap; font-size: 16px; }
    </style>
</head>
<body>
    <div class="notification">
        <h2>Notification</h2>
        <pre><?php echo nl2br(htmlspecialchars($message)); ?></pre>
    </div>
</body>
</html>
