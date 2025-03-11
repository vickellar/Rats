
    <?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start(); // Ensure session is started to use $_SESSION

include("../Database/db.php");
// Check connection
if (!$pdo) {
    die("Database connection failed.");
}

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['register'])) {
    // Prevent double submission
    // if (isset($_SESSION['submitted']) && $_SESSION['submitted'] === true) {
    //     die("Error: Form already submitted.");
    // }
    $_SESSION['submitted'] = true;

    $employee_id = $_POST["employee_id"];
    $first_name = $_POST["first_name"];
    $surname = $_POST["surname"];
    $username = $_POST["username"];
    $role = $_POST["role"];
    $password = $_POST["password"];

    // Validate form data
    if (empty($employee_id) || empty($first_name) || empty($surname) || empty($username) || empty($role) || empty($password)) {
        die("All fields are required.");
    }

    // Hash the password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Check if employee_id already exists
    $checkQuery = "SELECT COUNT(*) FROM employees WHERE employee_id = ?";
    $checkStmt = $pdo->prepare($checkQuery);
    $checkStmt->execute([$employee_id]);
    $exists = $checkStmt->fetchColumn();

    if ($exists) {
        die("Error: Employee ID already exists.");
    }

    // Check if username already exists
    $checkUserQuery = "SELECT COUNT(*) FROM employees WHERE username = ?";
    $checkUserStmt = $pdo->prepare($checkUserQuery);
    $checkUserStmt->execute([$username]);
    $userExists = $checkUserStmt->fetchColumn();

    if ($userExists) {
        die("Error: Username already exists.");
    }

    // Prepare and execute SQL query
    $sql = "INSERT INTO employees (employee_id, first_name, surname, username, role, password) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$employee_id, $first_name, $surname, $username, $role, $hashed_password]);

    if ($stmt) {
        echo "User registered successfully!";
        // Redirect to login page or another page as needed
        
        header("Location: adminDashboard.php");
        exit();
    } else {
        echo "Error: " . implode(", ", $pdo->errorInfo());
        // Log the error to a file if needed
        // include("../logfile/database_errors.log");
    }
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>addemployee</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 10px;
            background-color: rgb(39, 225, 231);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            overflow: hidden;
        }

        h2 {
            color: #333;
        }
        .form-container {
            width: 100%;
            max-width: 105mm; /* A6 width */
            box-sizing: border-box; /* Include padding in width */
        }
        form {
            background-color: white;
            padding: 15px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .form-group {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        label {
            width: 100px; /* Adjust the width as needed */
            margin-right: 10px;
            font-weight: bold;
        }
        .input-group {
            flex: 1;
            display: flex;
            align-items: center;
        }
        .input-group i {
            margin-right: 10px;
            color: #007bff;
        }
        input[type="text"],
        input[type="role"],
        input[type="password"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        button {
            background-color: #007bff;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        button:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    
    <div class="form-container" alig nt="center">
        <form action="" method="POST">
            <h2>Add User Employee</h2>
            <div class="form-group">
                <label for="employee_id">Emplo_ID:</label>
                <div class="input-group">
                    <i class="fas fa-id-badge"></i>
                    <input type="text" id="employee_id" name="employee_id" required>
                </div>
            </div>

            <div class="form-group">
                <label for="first_name">First Name:</label>
                <div class="input-group">
                    <i class="fas fa-user"></i>
                    <input type="text" id="first_name" name="first_name" required>
                </div>
            </div>

            <div class="form-group">
                <label for="surname">Surname:</label>
                <div class="input-group">
                    <i class="fas fa-user"></i>
                    <input type="text" id="surname" name="surname"  required>
                </div>
            </div>

            <div class="form-group">
                <label for="username">Username:</label>
                <div class="input-group">
                    <i class="fas fa-user-circle"></i>
                    <input type="text" id="username" name="username" placeholder="Username here" required>
                </div>
            </div>

            <div class="input-group">
                <label for="role">Role:</label>
                <i class="fas fa-user-tag"></i>
                <select id="role" name="role" required>
                    <option value="" disabled selected>Select Role</option>
                    <option value="admin">Admin</option>
                    <option value="finance_director">Finance Director</option>
                </select>
            </div>

            <div class="form-group">
                <label for="password">Password:</label>
                <div class="input-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="password" name="password" placeholder="password" required>
                </div>
            </div>

            <button type="submit" name="register">Submit</button>
        </form>
    </div>
</body>
</html>