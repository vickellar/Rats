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
    $address = $_POST["address"];
    $size = $_POST["size"];
    $type = $_POST["type"];
    $owner = $_POST["owner"];
    $property_id = $_POST["property_id"]; // Ensure property_id is included in the form

    // Update property details in the database
    $sql = "UPDATE properties SET address = :address, size = :size, type = :type, owner = :owner WHERE id = :id";

    $stmt = $pdo->prepare($sql);
    try {
        $success = $stmt->execute([

        ':address' => $address,
        ':size' => $size,
        ':type' => $type,
        ':owner' => $owner,
        ':id' => $property_id
    ]);

    } catch (PDOException $e) {
        $error_message = "Database error: " . $e->getMessage();
    }

    if ($success) {

        // Redirect or display success message
        header("Location: view_properties.php");
        exit();
    } else {
        $error_message = "Failed to update property.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rate Clearance System</title>
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
            width: 150mm; /* Wider than A6 */
            height: auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            box-sizing: border-box;
        }

        header {
            background-color: #333;
            color: #fff;
            padding: 10px 0;
            text-align: center;
        }

        nav ul {
            list-style: none;
            padding: 0;
            text-align: center;
        }

        nav ul li {
            display: inline;
            margin: 0 10px;
        }

        nav ul li a {
            color: #fff;
            text-decoration: none;
        }

        main {
            padding: 20px;
        }

        h2 {
            color: #333;
        }

        form {
            padding: 20px;
        }

        label {
            display: block; /* Ensure labels are block elements */
            margin-bottom: 5px;
        }

        input, select, button {
            width: 100%;
            padding: 8px;
            margin-bottom: 10px;
            box-sizing: border-box;
        }

        button {
            background-color: blue;
            color: #fff;
            border: none;
            cursor: pointer;
        }

        button:hover {
            background-color: #555;
        }

        .input-icon {
            display: flex;
            align-items: center;
        }

        .input-icon input, .input-icon select {
            flex-grow: 1;
            padding-left: 35px;
        }

        .input-icon i {
            position: absolute;
            padding-left: 10px;
            color: #333;
        }
    </style>

    <script>
        function addAccountInputs() {
            const accountContainer = document.getElementById("account-container");
            const numOfAccounts = parseInt(document.getElementById("numAccounts").value);
            accountContainer.innerHTML = ""; // Clear previous account inputs

            for (let i = 1; i <= numOfAccounts; i++) {
                const label = document.createElement("label");
                label.textContent = "Account Number " + i + ":";

                const inputDiv = document.createElement("div");
                inputDiv.classList.add("input-icon");

                const input = document.createElement("input");
                input.pattern = "[0-9]*"; // Only allow numbers
                input.type = "text";
                input.name = "account_number_" + i;
                input.required = true;

                const icon = document.createElement("i");
                icon.classList.add("fas", "fa-hashtag");

                inputDiv.appendChild(icon);
                inputDiv.appendChild(input);
                accountContainer.appendChild(label);
                accountContainer.appendChild(inputDiv);
            }
        }
    </script>
</head>
<body>
    <div class="container">
        <header>
            <h1>Update Property</h1>
            <?php if (!empty($error_message)): ?>
                <p><?php echo $error_message; ?></p>
            <?php endif; ?>
            <nav>
                <ul>
                    <li><a href="../dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                    <li><a href="#rate-calculation"><i class="fas fa-calculator"></i> Rate Calculation</a></li>
                </ul>
            </nav>
        </header>
        <main>
            <section id="property-management">
                <h2>Property Management</h2>
                <form method="POST" action="">
                    <input type="hidden" name="property_id" value="<?php echo isset($property['id']) ? $property['id'] : ''; ?>">

                    <label for="address">Address:</label>
                    <div class="input-icon">
                        <i class="fas fa-map-marker-alt" style="color: blue;"></i>
                        <input type="text" id="address" name="address" required value="<?php echo isset($property['address']) ? $property['address'] : ''; ?>">
                    </div>

                    <label for="size">Size (sq meters):</label>
                    <div class="input-icon">
                        <i class="fas fa-ruler-combined" style="color: blue;"></i>
                        <input type="number" id="size" name="size" required value="<?php echo isset($property['size']) ? $property['size'] : ''; ?>">
                    </div>

                    <label for="type">Type:</label>
                    <div class="input-icon">
                        <i class="fas fa-building" style="color: blue;"></i>
                        <select id="type" name="type" required>
                            <option value="residential" <?php echo isset($property['type']) && $property['type'] == 'residential' ? 'selected' : ''; ?>>Residential</option>
                            <option value="commercial" <?php echo isset($property['type']) && $property['type'] == 'commercial' ? 'selected' : ''; ?>>Commercial</option>
                        </select>
                    </div>

                    <label for="owner">Owner:</label>
                    <input type="text" id="owner" name="owner" required value="<?php echo isset($property['owner']) ? $property['owner'] : ''; ?>">
                    
                    <label for="numAccounts">Number of Accounts:</label>
                    <select id="numAccounts" name="numAccounts" oninput="addAccountInputs()" required>
                        <option value="">Select</option>
                        <option value="1">1</option>
                        <option value="2">2</option>
                        <option value="3">3</option>
                        <option value="4">4</option>
                    </select>

                    <div id="account-container"></div>
                    
                    <button type="submit" name="update">Update Property</button>
                </form>
            </section>
        </main>
    </div>
</body>
