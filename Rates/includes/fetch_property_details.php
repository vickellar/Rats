<?php
session_start(); // Start the session

// Include database connection file
require_once("../Database/db.php");

// Ensure user_id is available from the session
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
} else {
    echo "User not logged in.";
    exit();
}

if (isset($_GET['property_id'])) {
    $propertyId = $_GET['property_id'];
    // SQL query to fetch property details by ID
    $sql = "SELECT property_id, address, size, type, owner, created_at, updated_at FROM properties WHERE property_id = ?";

    try {
        // Prepare and execute the query
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(1, $propertyId, PDO::PARAM_INT);
        $stmt->execute();

        // Fetch the property details
        if ($property = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Display property details
            echo "<h2>Property Details</h2>";
            echo "<p>ID: " . htmlspecialchars($property['property_id']) . "</p>";
            echo "<p>Address: " . htmlspecialchars($property['address']) . "</p>";
            echo "<p>Size: " . htmlspecialchars($property['size']) . "</p>";
            echo "<p>Type: " . htmlspecialchars($property['type']) . "</p>";
            echo "<p>Owner: " . htmlspecialchars($property['owner']) . "</p>";
            echo "<p>Created At: " . htmlspecialchars($property['created_at']) . "</p>";
            echo "<p>Updated At: " . htmlspecialchars($property['updated_at']) . "</p>";
        } else {
            echo "No property found with the given ID.";
        }
    } catch (PDOException $e) {
        echo "Error fetching properties: " . $e->getMessage();
    }
} else {
    echo "No property ID provided.";
}
?>
