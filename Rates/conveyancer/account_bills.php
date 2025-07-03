<?php
// filepath: c:\xampp\htdocs\rats\rates\conveyancer\account_bills.php
session_start();
require_once '../Database/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get property_id and account_id from GET or POST
$property_id = isset($_GET['property_id']) ? intval($_GET['property_id']) : 0;
$account_id = isset($_GET['account_id']) ? intval($_GET['account_id']) : 0;

if (!$property_id || !$account_id) {
    echo "<p style='color: #dc2626;'>Property or Account not specified.</p>";
    exit();
}

// Fetch calculated bills for this user, property, and account
$sql = "SELECT cb.*, p.address 
        FROM calculated_bills cb
        JOIN properties p ON cb.property_id = p.property_id
        WHERE cb.user_id = :user_id AND cb.property_id = :property_id AND cb.account_id = :account_id
        ORDER BY cb.calculated_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([
    ':user_id' => $user_id,
    ':property_id' => $property_id,
    ':account_id' => $account_id
]);
$bills = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Account Bills</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f8fafc; margin: 0; padding: 0; }
        .container { max-width: 800px; margin: 30px auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 0 10px #ccc; }
        h2 { color: #2c3e50; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        th, td { border: 1px solid #bbb; padding: 8px 12px; text-align: left; }
        th { background: #e3eafc; }
        .no-data { color: #888; }
        ul { margin: 0; padding-left: 20px; }
        a { color: #3b82f6; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
<div class="container">
    <h2>Calculated Bills for Account #<?= htmlspecialchars($account_id) ?></h2>
    <?php if (empty($bills)): ?>
        <p class="no-data">No calculated bills found for this account.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Property Address</th>
                    <th>Total Balance</th>
                    <th>Processing Fee</th>
                    <th>Overall Total</th>
                    <th>Calculated At</th>
                    <th>Monthly Breakdown</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($bills as $bill): ?>
                    <tr>
                        <td><?= htmlspecialchars($bill['address']) ?></td>
                        <td>$<?= number_format($bill['total_balance'], 2) ?></td>
                        <td>$<?= number_format($bill['processing_fee'], 2) ?></td>
                        <td>$<?= number_format($bill['overall_total'], 2) ?></td>
                        <td><?= htmlspecialchars($bill['calculated_at']) ?></td>
                        <td>
                            <?php
                            // Fetch monthly breakdown for this bill
                            $sqlMonths = "SELECT * FROM calculated_bill_months WHERE bill_id = :bill_id";
                            $stmtMonths = $pdo->prepare($sqlMonths);
                            $stmtMonths->execute([':bill_id' => $bill['bill_id']]);
                            $months = $stmtMonths->fetchAll(PDO::FETCH_ASSOC);

                            if ($months) {
                                echo "<ul>";
                                foreach ($months as $month) {
                                    $monthNames = [];
                                    if (!empty($month['month1_name'])) $monthNames[] = $month['month1_name'];
                                    if (!empty($month['month2_name'])) $monthNames[] = $month['month2_name'];
                                    if (!empty($month['month3_name'])) $monthNames[] = $month['month3_name'];
                                    if (!empty($month['month4_name'])) $monthNames[] = $month['month4_name'];
                                    foreach ($monthNames as $name) {
                                        echo "<li>{$name}: $" . number_format($month['month_balance'], 2) . "</li>";
                                    }
                                }
                                echo "</ul>";
                            } else {
                                echo "<span class='no-data'>No monthly breakdown found.</span>";
                            }
                            ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
    <a href="cdashboard.php">&larr; Back to Dashboard</a>
</div>
</body>
</html>