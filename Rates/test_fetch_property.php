<?php
session_start();
require_once("./Database/db.php"); // Adjusted path to the database connection file

$userId = 6; // Replace with a valid user ID for testing
$selectedPropertyId = 12; // Replace with a valid property ID for testing

try {
    $sql = "SELECT properties.*, GROUP_CONCAT(accounts.account_number) AS account_numbers 
            FROM properties 
            LEFT JOIN accounts ON properties.id = accounts.property_id 
            WHERE properties.id = :property_id AND properties.user_id = :user_id 
            GROUP BY properties.id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':property_id' => $selectedPropertyId, ':user_id' => $userId]);
    $propertyDetails = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($propertyDetails) {
        echo "<p>Address: " . htmlspecialchars($propertyDetails['address']) . "</p>";
        echo "<p>Size: " . htmlspecialchars($propertyDetails['size']) . " sq meters</p>";
        echo "<p>Type: " . htmlspecialchars($propertyDetails['type']) . "</p>";
        echo "<p>Owner: " . htmlspecialchars($propertyDetails['owner']) . "</p>";
        echo "<p>Account Numbers: " . htmlspecialchars($propertyDetails['account_numbers']) . "</p>";
    } else {
        echo "<p>No property details found. Please check the property ID and try again.</p>";
    }
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage();
}
?>