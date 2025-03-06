<?php

// download_documents.php
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

// Logic for downloading documents goes here
// For example, listing documents available for download

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Download Documents</title>
</head>
<body>
    <h1>Download Documents</h1>
    <p>Documents available for download will be listed here.</p>
    <a href="adminDashboard.php">Back to Dashboard</a>
</body>
</html>
