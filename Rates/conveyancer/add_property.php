<?php
session_start(); // Start the session

echo "user_is" . $_SESSION['user_id'];

// Check if the user is logged in;

// Check if user is logged in and has required session data
if (empty($_SESSION['role']) || $_SESSION['role'] !== 'conveyancer' || 
    empty($_SESSION['user_id']) || empty($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}



if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_property'])) {
    $address = $_POST["address"];
    $size = $_POST["size"];
    $type = $_POST["type"];
    $owner = $_POST["owner"];
    $user_id = $_SESSION['user_id']; // Retrieve the logged-in user's ID

    $numAccounts = $_POST["numAccounts"];
    $accountNumbers = [];

    // Collect account numbers from the form input
    for ($i = 1; $i <= $numAccounts; $i++) {
        $accountNumbers[] = $_POST["account_number_$i"];
    }

    // Include database connection file
    require_once("../Database/db.php");

    // Prepare SQL query to insert property details into the database
    $sql = "INSERT INTO properties (address, size, type, owner, user_id) VALUES (:address, :size, :type, :owner, :user_id)";

    $stmt = $pdo->prepare($sql);
    try {
        $stmt->execute([
            ':address' => $address,
            ':size' => $size,
            ':type' => $type,
            ':owner' => $owner,
            ':user_id' => $user_id // Include user_id in the insert
        ]);

        // Check if the property was added successfully
        if ($stmt->rowCount() > 0) {
            // Get the last inserted property ID
            $propertyId = $pdo->lastInsertId();

            // Insert account numbers into the accounts table
            foreach ($accountNumbers as $accountNumber) {
                $sqlAccount = "INSERT INTO accounts (property_id, account_number) VALUES (:property_id, :account_number)";
                $stmtAccount = $pdo->prepare($sqlAccount);
                $stmtAccount->execute([
                    ':property_id' => $propertyId,
                    ':account_number' => $accountNumber
                ]);
            }

            echo "<h3>Property and accounts added successfully!</h3>";
        } else {
            echo "<h3>Error adding property. Please try again.</h3>";
        }
    } catch (PDOException $e) {
        file_put_contents('../logfile/database_errors.log', date('Y-m-d H:i:s') . " - Error adding property: " . $e->getMessage() . PHP_EOL, FILE_APPEND);
        echo "<h3>Error adding property. Please try again.</h3>";
    }

    // Display the collected property details
    echo "<h3>Property Details</h3>";
    echo "Address: " . htmlspecialchars($address) . "<br>";
    echo "Size: " . htmlspecialchars($size) . " sq meters<br>";
    echo "Type: " . htmlspecialchars($type) . "<br>";
    echo "Owner: " . htmlspecialchars($owner) . "<br>";
    echo "Number of Accounts: " . htmlspecialchars($numAccounts) . "<br>";
    echo "Account Numbers: " . implode(", ", array_map('htmlspecialchars', $accountNumbers)) . "<br>";
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
            <h1>Rate Clearance System</h1>
            <nav>
                <ul>
                    <li><a href="#property-management"><i class="fas fa-home"></i> Property Management</a></li>
                    <li><a href="#rate-calculation"><i class="fas fa-calculator"></i> Rate Calculation</a></li>
                </ul>
            </nav>
        </header>
        <main>
            <section id="property-management">
                <h2>Property Management</h2>
                <form method="POST" action="">
                    <label for="address">Address:</label>
                    <div class="input-icon">
                        <i class="fas fa-map-marker-alt" style="color: blue;"></i>
                        <input type="text" id="address" name="address" required>
                    </div>

                    <label for="size">Size (sq meters):</label>
                    <div class="input-icon">
                        <i class="fas fa-ruler-combined" style="color: blue;"></i>
                        <input type="number" id="size" name="size" required>
                    </div>

                    <label for="type">Type:</label>
                    <div class="input-icon">
                        <i class="fas fa-building" style="color: blue;"></i>
                        <select id="type" name="type" required>
                            <option value="residential">Residential</option>
                            <option value="commercial">Commercial</option>
                        </select>
                    </div>
                    <label for="owner">Owner:</label>
                    <input type="text" id="owner" name="owner" required>
                    
                    <label for="numAccounts">Number of Accounts:</label>
                    <select id="numAccounts" name="numAccounts" oninput="addAccountInputs()" required>
                        <option value="">Select</option>
                        <option value="1">1</option>
                        <option value="2">2</option>
                        <option value="3">3</option>
                        <option value="4">4</option>
                    </select>

                    <div id="account-container"></div>
                    
                    <button type="submit" name="add_property">Add Property</button>
                </form>
            </section>
        </main>
    </div>
</body>
</html>
