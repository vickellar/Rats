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

// Logic for fetching and displaying history goes here
// For example, fetching application history from the database

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
