<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration 
$servername = "localhost";
$username = "root";
$password = "";
$database = "rate_clearance"; 

// Create connection
$conn = mysqli_connect($servername, $username, $password, $database);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $first_name = $_POST['first_name'];
    $surname = $_POST['surname'];
    $username = $_POST['username'];
    $employee_id = $_POST['employee_id'];
    $email = $_POST['email'];
    $role = $_POST['role'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Check for duplicate username or email
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        echo "<p style='color:red;'>Error: Username or email already exists.</p>";
    } else {
        // Check if password and confirm password match
        if ($_POST['password'] !== $_POST['confirm_password']) {
            echo "<p style='color:red;'>Error: Password and Confirm Password do not match.</p>";
            exit();
        }

        // Prepare and bind
        $stmt = $conn->prepare("INSERT INTO users (first_name, surname, username, employee_id, email, role, password) VALUES (?, ?, ?, ?, ?, ?, ?)");
        if (!$stmt) {
            die("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param("sssssss", $first_name, $surname, $username, $employee_id, $email, $role, $password);

        // Execute the statement
        if ($stmt->execute()) {
            // Ensure no output before header
            ob_clean();
            header("Location: fdashboard.php");

            exit();
        } else {
            echo "<p style='color:red;'>Error: " . $stmt->error . "</p>";
        }

    }

    // Close statement
    $stmt->close();
}

// Close connection
mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Form</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            padding: 20px;
            background-color: #f4f4f4;
        }
        h2 {
            color: #333;
        }
        form {
            background-color: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        label {
            display: block;
            margin: 10px 0 5px;
        }
        input[type="text"],
        input[type="tel"],
        input[type="email"],
        input[type="password"],
        select {
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        input[type="submit"] {
            background-color: #5cb85c;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        input[type="submit"]:hover {
            background-color: #4cae4c;
        }
    </style>
    <script>
        function checkRole() {
            var role = document.getElementById("role").value;
            var employeeIDField = document.getElementById("employee_ID");
            
            if (role === "Administration" || role === "Finance Director") {
                employeeIDField.required = true;
                employeeIDField.parentElement.style.display = "block";
            } else {
                employeeIDField.required = false;
                employeeIDField.parentElement.style.display = "none";
            }
        }
    </script>
</head>
<body> 
    <h2>Rate Clearance System Registration</h2>
    <form action="registration_form.php" method="POST">
        <label for="first_name">First Name:</label>
        <input type="text" id="first_name" name="first_name" required>

        <label for="surname">Surname:</label>
        <input type="text" id="surname" name="surname" required>

        <label for="username">Username:</label>
        <input type="text" id="username" name="username" required>

        <div style="display: none;">
            <label for="employee_ID">Employee ID:</label>
            <input type="tel" id="employee_ID" name="employee_ID">
        </div>

        <label for="email">Email:</label>
        <input type="email" id="email" name="email" required>

        <label for="role">Role:</label>
        <select id="role" name="role" required onchange="checkRole()">
            <option value="Conveyancer">Conveyancer</option>
            <option value="Administration">Administration</option>
            <option value="Finance Director">Finance Director</option>
        </select>

        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required>

        <label for="confirm_password">Confirm Password:</label>
        <input type="password" id="confirm_password" name="confirm_password" required>

        <input type="submit" value="Register">
    </form>
</body>
</html>
