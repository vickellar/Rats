<?php
require_once("./Database/db.php"); // Adjusted path to the database connection file

try {
    // Test the database connection
    $stmt = $pdo->query("SELECT 1");
    echo "Database connection is successful.";
} catch (PDOException $e) {
    echo "Database connection failed: " . $e->getMessage();
}
?>
