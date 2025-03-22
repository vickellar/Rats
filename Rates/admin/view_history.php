<?php
// view_history.php
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

// Include database connection
require_once '../Database/db.php';

$query = "SELECT * FROM rate_clearance_applications WHERE application_id = :application_id";
$stmt = $pdo->prepare($query);
$stmt->execute(['application_id' => $_GET['application_id']]);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($results) > 0) {
    echo '<table border="1"><tr><th>Application ID</th><th>First Name</th><th>Last Name</th><th>Status</th></tr>';
    foreach ($results as $row) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($row['application_id']) . '</td>';
        echo '<td>' . htmlspecialchars($row['first_name']) . '</td>';
        echo '<td>' . htmlspecialchars($row['last_name']) . '</td>';
        echo '<td>' . htmlspecialchars($row['status']) . '</td>';
        echo '</tr>';
    }
    echo '</table>';
} else {
    echo '<p>No application history found.</p>';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View History</title>
</head>
<body>
    <h1>Application History</h1>
    <p>History of applications will be displayed here.</p>
    <a href="adminDashboard.php">Back to Dashboard</a>
</body>
</html>
