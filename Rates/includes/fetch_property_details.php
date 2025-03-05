<?php
session_start(); // Start the session

// Include database connection file
require_once("../Database/db.php");

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page if not logged in
    header("Location: ../signin.php");
    exit();
}

// Check if the property_id is provided in the POST request
if (isset($_POST['property_id'])) {
    $propertyId = $_POST['property_id'];
    $userId = $_SESSION['user_id'];
    
    // Fetch property details from the database
    $sql = "SELECT properties.*, GROUP_CONCAT(accounts.account_number) AS account_numbers 
            FROM properties 
            LEFT JOIN accounts ON properties.id = accounts.property_id 
            WHERE properties.id = :property_id AND properties.user_id = :user_id 
            GROUP BY properties.id";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':property_id' => $propertyId, ':user_id' => $userId]);

    $property = $stmt->fetch(PDO::FETCH_ASSOC);

    // Display property details
    if ($property) {
        echo "<p>Address: " . htmlspecialchars($property['address']) . "</p>";
        echo "<p>Size: " . htmlspecialchars($property['size']) . " sq meters</p>";
        echo "<p>Type: " . htmlspecialchars($property['type']) . "</p>";
        echo "<p>Owner: " . htmlspecialchars($property['owner']) . "</p>";
        echo "<p>Account Numbers: " . htmlspecialchars($property['account_numbers']) . "</p>";
    } else {
        echo "<p>No property details found.</p>";
    }
} else {
    echo "<p>Invalid request.</p>";
}
?>
