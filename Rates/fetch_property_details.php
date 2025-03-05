<?php
require_once("../Database/db.php");

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['property_id'])) {
    $propertyId = $_POST['property_id'];
    $userId = $_SESSION['user_id'];

    try {
        $sql = "SELECT * FROM properties WHERE id = :property_id AND user_id = :user_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':property_id' => $propertyId, ':user_id' => $userId]);
        $propertyDetails = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($propertyDetails) {
            // Display property details
            echo "<p><strong>Address:</strong> " . htmlspecialchars($propertyDetails['address']) . "</p>";
            echo "<p><strong>Account Numbers:</strong> " . htmlspecialchars($propertyDetails['account_numbers']) . "</p>";
            // Add more details as needed
        } else {
            echo "Error: The selected property does not belong to you.";
        }
    } catch (PDOException $e) {
        echo "Database error: " . $e->getMessage();
    }
} else {
    echo "Invalid request.";
}
?>