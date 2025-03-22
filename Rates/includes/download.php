<?php
session_start(); // Start the session

// Include database connection file
require_once("../Database/db.php");

if (isset($_GET['file'])) {
    $file = basename($_GET['file']); // Get the filename
    $filePath = "../uploads/" . $file; // Construct the full file path

    // Check if the file exists
    if (file_exists($filePath)) {
        // Set headers to force download
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $file . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filePath));
        flush(); // Flush system output buffer
        readfile($filePath); // Read the file and send it to the output buffer
        exit;
    } else {
        echo "File not found.";
    }
} else {
    echo "No file specified.";
}
?>
