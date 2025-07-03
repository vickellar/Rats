<?php
session_start(); // Start the session

// Check if user is logged in and has required session data
if (empty($_SESSION['role']) || $_SESSION['role'] !== 'conveyancer' || 
    empty($_SESSION['user_id']) || empty($_SESSION['username'])) {
    header("Location: ../index.php");
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
            try {
                foreach ($accountNumbers as $accountNumber) {
                    // Validate account number is not empty
                    if (!empty($accountNumber)) {
                        $sqlAccount = "INSERT INTO accounts (property_id, account_number) VALUES (:property_id, :account_number)";
                        $stmtAccount = $pdo->prepare($sqlAccount);
                        $stmtAccount->execute([
                            ':property_id' => $propertyId,
                            ':account_number' => $accountNumber
                        ]);
                    }
                }
                $success = true;
                $message = "Property and accounts added successfully!";
            } catch (PDOException $e) {
                file_put_contents('../logfile/database_errors.log', date('Y-m-d H:i:s') . " - Error adding account: " . $e->getMessage() . PHP_EOL, FILE_APPEND);
                $success = false;
                $message = "Error adding account(s). Please try again.";
            }
        } else {
            $success = false;
            $message = "Error adding property. Please try again.";
        }
    } catch (PDOException $e) {
        file_put_contents('../logfile/database_errors.log', date('Y-m-d H:i:s') . " - Error adding property: " . $e->getMessage() . PHP_EOL, FILE_APPEND);
        $success = false;
        $message = "Error adding property. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Property - Rate Clearance System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4361ee;
            --primary-hover: #3a56d4;
            --secondary-color: #3f37c9;
            --text-color: #333;
            --light-text: #666;
            --border-color: #e0e0e0;
            --success-color: #4caf50;
            --error-color: #f44336;
            --bg-color: #f5f7fa;
            --card-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
            --input-shadow: 0 2px 5px rgba(0, 0, 0, 0.08);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg-color);
            color: var(--text-color);
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 1.5rem 0;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .header h1 {
            font-size: 1.8rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .nav {
            display: flex;
            justify-content: center;
            gap: 1.5rem;
            margin-top: 1rem;
        }

        .nav-link {
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            transition: background-color 0.3s;
        }

        .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.2);
        }

        .container {
            max-width: 800px;
            width: 100%;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .card {
            background: white;
            border-radius: 12px;
            box-shadow: var(--card-shadow);
            overflow: hidden;
        }

        .card-header {
            background: linear-gradient(to right, #f8f9fa, #e9ecef);
            padding: 1.5rem;
            border-bottom: 1px solid var(--border-color);
        }

        .card-header h2 {
            font-size: 1.5rem;
            color: var(--primary-color);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .card-body {
            padding: 2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--text-color);
        }

        .form-control {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 2.5rem;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s;
            box-shadow: var(--input-shadow);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2);
        }

        .input-icon-wrapper {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--primary-color);
            font-size: 1rem;
        }

        .btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.3s, transform 0.2s;
            text-align: center;
            text-decoration: none;
        }

        .btn:hover {
            background-color: var(--primary-hover);
            transform: translateY(-2px);
        }

        .btn-secondary {
            background-color: #6c757d;
        }

        .btn-secondary:hover {
            background-color: #5a6268;
        }

        .btn-group {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }

        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-weight: 500;
        }

        .alert-success {
            background-color: rgba(76, 175, 80, 0.1);
            border: 1px solid rgba(76, 175, 80, 0.3);
            color: var(--success-color);
        }

        .alert-error {
            background-color: rgba(244, 67, 54, 0.1);
            border: 1px solid rgba(244, 67, 54, 0.3);
            color: var(--error-color);
        }

        .property-details {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 1.5rem;
            margin-top: 2rem;
        }

        .property-details h3 {
            color: var(--primary-color);
            margin-bottom: 1rem;
            font-size: 1.25rem;
        }

        .property-details p {
            margin-bottom: 0.5rem;
            display: flex;
            justify-content: space-between;
            border-bottom: 1px dashed #e0e0e0;
            padding-bottom: 0.5rem;
        }

        .property-details p:last-child {
            border-bottom: none;
        }

        .property-details strong {
            color: var(--text-color);
        }

        @media (max-width: 768px) {
            .container {
                padding: 0 1rem;
            }
            
            .card-body {
                padding: 1.5rem;
            }
            
            .btn-group {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
            }
        }

        /* Animation for success message */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .animate-fade-in {
            animation: fadeIn 0.5s ease-out forwards;
        }
    </style>
</head>
<body>
    <header class="header">
        <h1>Rate Clearance System</h1>
        <nav class="nav">
            <a href="#property-management" class="nav-link">
                <i class="fas fa-home"></i> Property Management
            </a>
            <a href="#rate-calculation" class="nav-link">
                <i class="fas fa-calculator"></i> Rate Calculation
            </a>
            <a href="cdashboard.php" class="nav-link">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
        </nav>
    </header>

    <div class="container">
        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-plus-circle"></i> Add New Property</h2>
            </div>
            <div class="card-body">
                <?php if (isset($success)): ?>
                    <div class="alert <?php echo $success ? 'alert-success' : 'alert-error'; ?> animate-fade-in">
                        <?php echo $message; ?>
                    </div>
                    
                    <?php if ($success): ?>
                    <div class="property-details">
                        <h3>Property Details</h3>
                        <p><strong>Address:</strong> <span><?php echo htmlspecialchars($address); ?></span></p>
                        <p><strong>Size:</strong> <span><?php echo htmlspecialchars($size); ?> sq meters</span></p>
                        <p><strong>Type:</strong> <span><?php echo htmlspecialchars($type); ?></span></p>
                        <p><strong>Owner:</strong> <span><?php echo htmlspecialchars($owner); ?></span></p>
                        <p><strong>Number of Accounts:</strong> <span><?php echo htmlspecialchars($numAccounts); ?></span></p>
                        <p><strong>Account Numbers:</strong> <span><?php echo implode(", ", array_map('htmlspecialchars', $accountNumbers)); ?></span></p>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="form-group">
                        <label for="address" class="form-label">Property Address</label>
                        <div class="input-icon-wrapper">
                            <i class="fas fa-map-marker-alt input-icon"></i>
                            <input type="text" id="address" name="address" class="form-control" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="size" class="form-label">Size (sq meters)</label>
                        <div class="input-icon-wrapper">
                            <i class="fas fa-ruler-combined input-icon"></i>
                            <input type="number" id="size" name="size" class="form-control" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="type" class="form-label">Property Type</label>
                        <div class="input-icon-wrapper">
                            <i class="fas fa-building input-icon"></i>
                            <select id="type" name="type" class="form-control" required>
                                <option value="">Select property type</option>
                                <option value="residential">Residential</option>
                                <option value="commercial">Commercial</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="owner" class="form-label">Property Owner</label>
                        <div class="input-icon-wrapper">
                            <i class="fas fa-user input-icon"></i>
                            <input type="text" id="owner" name="owner" class="form-control" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="numAccounts" class="form-label">Number of Accounts</label>
                        <div class="input-icon-wrapper">
                            <i class="fas fa-list-ol input-icon"></i>
                            <select id="numAccounts" name="numAccounts" class="form-control" onchange="addAccountInputs()" required>
                                <option value="">Select number of accounts</option>
                                <option value="1">1</option>
                                <option value="2">2</option>
                                <option value="3">3</option>
                                <option value="4">4</option>
                            </select>
                        </div>
                    </div>

                    <div id="account-container"></div>

                    <div class="btn-group">
                        <a href="cdashboard.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Dashboard
                        </a>
                        <button type="submit" name="add_property" class="btn">
                            <i class="fas fa-save"></i> Add Property
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function addAccountInputs() {
            const accountContainer = document.getElementById("account-container");
            const numOfAccounts = parseInt(document.getElementById("numAccounts").value);
            accountContainer.innerHTML = ""; // Clear previous account inputs

            for (let i = 1; i <= numOfAccounts; i++) {
                const formGroup = document.createElement("div");
                formGroup.classList.add("form-group");
                
                const label = document.createElement("label");
                label.textContent = "Account Number " + i;
                label.classList.add("form-label");
                
                const inputWrapper = document.createElement("div");
                inputWrapper.classList.add("input-icon-wrapper");
                
                const icon = document.createElement("i");
                icon.classList.add("fas", "fa-hashtag", "input-icon");
                
                const input = document.createElement("input");
                input.type = "text";
                input.name = "account_number_" + i;
                input.classList.add("form-control");
                input.pattern = "[0-9]*"; // Only allow numbers
                input.required = true;
                input.placeholder = "Enter account number";
                
                inputWrapper.appendChild(icon);
                inputWrapper.appendChild(input);
                formGroup.appendChild(label);
                formGroup.appendChild(inputWrapper);
                accountContainer.appendChild(formGroup);
            }
        }
    </script>
</body>
</html>