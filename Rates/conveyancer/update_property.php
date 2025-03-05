<?php
// Start session
session_start();

// Include database connection
require_once '../Database/db.php';

// Check if user is logged in and has the correct role
if ($_SESSION['role'] !== 'conveyancer') {
    header("Location: ../index.php");
    exit();
}

// Initialize error message
$error_message = "";

// Check if property ID is provided
if (isset($_GET['id'])) {
    $property_id = $_GET['id'];

    // Fetch existing property details
    $sql = "SELECT * FROM properties WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $property_id]);
    $property = $stmt->fetch();

    if (!$property) {
        $error_message = "Property not found.";
    }
} else {
    $error_message = "No property ID provided.";
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update'])) {
    $name = $_POST["name"];
    $location = $_POST["location"];
    $price = $_POST["price"];
    // Add other fields as necessary

    // Update property details in the database
    $sql = "UPDATE properties SET name = :name, location = :location, price = :price WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':name' => $name,
        ':location' => $location,
        ':price' => $price,
        ':id' => $property_id
    ]);

    // Redirect or display success message
    header("Location: view_properties.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Property</title>
</head>
<body>
    <h1>Update Property</h1>
    <?php if (!empty($error_message)): ?>
        <p><?php echo $error_message; ?></p>
    <?php endif; ?>
    <form method="POST" action="">
        <label for="name">Property Name:</label>
        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($property['name']); ?>" required>

        <label for="location">Location:</label>
        <input type="text" id="location" name="location" value="<?php echo htmlspecialchars($property['location']); ?>" required>

        <label for="price">Price:</label>
        <input type="number" id="price" name="price" value="<?php echo htmlspecialchars($property['price']); ?>" required>

        <!-- Add other fields as necessary -->

        <button type="submit" name="update">Update Property</button>
    </form>
</body>
</html>
