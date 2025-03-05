<?php
session_start(); // Start the session to access session variables

// Include database connection file
require_once("../Database/db.php");

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    // Redirect to login page if not logged in
    header("Location: ../signin.php");
    exit();
}

// Fetch properties added by the logged-in user using username
$username = $_SESSION['username'];
$sql = "SELECT properties.*, GROUP_CONCAT(accounts.account_number) AS account_numbers 
        FROM properties 
        LEFT JOIN accounts ON properties.id = accounts.property_id 
WHERE properties.user_id = :user_id 

        GROUP BY properties.id";


$stmt = $pdo->prepare($sql);
$stmt->execute([':user_id' => $_SESSION['user_id']]); // Execute query to fetch properties


$properties = $stmt->fetchAll(PDO::FETCH_ASSOC); // Fetch properties from database
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Properties</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .container {
            width: 80%;
            max-width: 800px;
            background-color: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            box-sizing: border-box;
        }

        h2 {
            color: #333;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            border: 1px solid #ccc;
            padding: 10px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Your Properties</h2>
        <table>
            <thead>
                <tr>
                    <th>Owner</th>
                    <th>Address</th>
                    <th>Size (sq meters)</th>
                    <th>Type</th>
                    <th>Accounts</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($properties) > 0): ?>
                    <?php foreach ($properties as $property): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($property['owner']); ?></td>
                            <td><?php echo htmlspecialchars($property['address']); ?></td>
                            <td><?php echo htmlspecialchars($property['size']); ?></td>
                            <td><?php echo htmlspecialchars($property['type']); ?></td>
                            <td><?php echo htmlspecialchars($property['account_numbers']); ?></td>

                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4">No properties found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
