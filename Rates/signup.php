<?php
// session start
if(session_status() === PHP_SESSION_NONE){
    session_start();
}

// Check if user is logged in and has required session data
if (empty($_SESSION['role']) || $_SESSION['role'] !== 'conveyancer' || 
    empty($_SESSION['user_id']) || empty($_SESSION['username'])) {
    header("Location: ../index.php");
    exit();
}

ob_start();

ini_set('display_errors', 1);

require_once("./Database/db.php");

// Initialize error message
$error_message = "";

// Handle registration form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['register'])) {
    $first_name = $_POST["first_name"];
    $surname = $_POST["surname"];
    $username = $_POST["username"];
    $password = $_POST["password"];
    $confirmPassword = $_POST["confirmPassword"];
    $role = $_POST["role"];
    $employee_id = isset($_POST["employee_id"]) ? filter_input(INPUT_POST, 'employee_id', FILTER_SANITIZE_NUMBER_INT) : null;
    $contact_number = filter_input(INPUT_POST, 'contact_number', FILTER_SANITIZE_STRING); // New field for contact number

    // Validation
    if (!preg_match("/^[a-zA-Z-' ]*$/", $first_name)) {
        $error_message = "First name should only contain letters and spaces";
    } elseif (!preg_match("/^[a-zA-Z-' ]*$/", $surname)) {
        $error_message = "Surname should only contain letters and spaces";
    } elseif ($password !== $confirmPassword) {
        $error_message = "Passwords do not match";
    } elseif (strlen($password) < 8) {
        $error_message = "Password must be at least 8 characters long";
    }

    if (empty($error_message)) {
        $password = password_hash($password, PASSWORD_DEFAULT);
        try {
            // For admin and finance_director roles, check employee database
            if ($role === 'admin' || $role === 'finance_director') {
                // First check if employee exists in employees table
                $sql = "SELECT * FROM employees WHERE role = :role";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([':role' => $role]);
                $employee = $stmt->fetch();
                
                if (!$employee) {
                    $error_message = "Employee record not found. Please register as employee first.";
                    throw new Exception($error_message);
                }
                
                // Verify employee_id exists
                if (!isset($employee['employee_id'])) {
                    $error_message = "Invalid employee record. Missing employee_id.";
                    throw new Exception($error_message);
                }
                
                $employee_id = $employee['employee_id'];
            } else {
                // Set employee_id to null for non-admin/finance_director roles
                $employee_id = null;
            }

            // Verify employee_id is valid for admin/finance_director roles
            if (($role === 'admin' || $role === 'finance_director') && empty($employee_id)) {
                $error_message = "Employee ID is required for admin/finance_director roles.";
                throw new Exception($error_message);
            }

            // Check for existing username
            $sql = "SELECT * FROM users WHERE username = :username";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':username' => $username]);
            
            if ($stmt->rowCount() > 0) {
                $error_message = "Username already exists. Please use a different username.";
            } else {
                // Insert new user
                $sql = "INSERT INTO users (first_name, surname, username, password, role, employee_id, contact_number) 
                        VALUES (:first_name, :surname, :username, :password, :role, :employee_id, :contact_number)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':first_name' => $first_name,
                    ':surname' => $surname,
                    ':username' => $username,
                    ':password' => $password,
                    ':role' => $role,
                    ':employee_id' => $employee_id,
                    ':contact_number' => $contact_number // Include contact number in the insertion
                ]);

                $_SESSION['role'] = $role;
                $_SESSION['username'] = $username; // Store username in session

                error_log("Session data after registration: " . print_r($_SESSION, true)); // Log the entire session data for debugging
                
                // Clear output buffer before redirecting
                ob_end_clean();
                
                session_start();
                error_log("Session data after registration: " . print_r($_SESSION, true)); // Log the entire session data for debugging
                if (!isset($_SESSION['role'])) {
                    // If the user role is not set in the session, redirect to login or another appropriate page
                    header("Location: /login.php");
                    exit();
                }

                // Display the appropriate dashboard based on the user role
                $role = $_SESSION['role'];

                if ($role === 'admin') {
                    header("Location: ./admin/adminDashboard.php");
                } elseif ($role === 'finance_director') {
                    header("Location: ./finance_director/fdashboard.php");
                } elseif ($role === 'conveyancer') {
                    header("Location: ./conveyancer/cdashboard.php");
                } else {
                    // Redirect to login or another appropriate page if the role is invalid
                    header("Location: /login.php");
                    exit();
                }

                exit();
            }
        } catch (PDOException $e) {
            $error_message = "Database error: " . $e->getMessage();
            error_log($e->getMessage()); // Log the error message
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Registration</title>
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

        main {
            padding: 20px;
            max-width: 500px;
            background-color: #fff;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        h2 {
            color: #333;
            margin-bottom: 20px;
            text-align: center;
        }

        form {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .input-container-group {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }

        label {
            display: block;
            margin-bottom: 5px;
        }

        input, select, button {
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        button {
            background-color: #007BFF;
            color: #fff;
            border: none;
            cursor: pointer;
            grid-column: span 2;
        }

        button:hover {
            background-color: #0056b3;
        }

        .input-container {
            display: flex;
            align-items: center;
            background-color: #f4f4f4;
            border: 1px solid #ccc;
            border-radius: 5px;
            padding: 10px;
        }

        .input-container i {
            margin-right: 10px;
            color: #007BFF;
        }

        .employee-id-container {
            display: none;
            grid-column: span 2;
        }

        .error {
            color: red;
            text-align: center;
            margin-bottom: 20px;
        }
    </style>
    <script>
        function toggleEmployeeIdField() {
            const role = document.getElementById("role").value;
            const employeeIdContainer = document.getElementById("employee-id-container");
            if (role === "admin" || role === "finance_director") {
                employeeIdContainer.style.display = "block";
            } else {
                employeeIdContainer.style.display = "none";
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            const errorMessage = "<?php echo $error_message; ?>";
            if (errorMessage) {
                alert(errorMessage);
            }
        });
    </script>
</head>
<body>
    <main>
        <h2>User Registration</h2>
        <form method="POST" action="">
            <div class="input-container-group">
                <div class="input-container">
                    <i class="fas fa-user"></i>
                    <input type="text" id="first_name" name="first_name" placeholder="First Name" required>
                </div>
                <div class="input-container">
                    <i class="fas fa-user"></i>
                    <input type="text" id="surname" name="surname" placeholder="Surname" required>
                </div>
            </div>

            <div class="input-container-group">
                <div class="input-container">
                    <i class="fas fa-user"></i>
                    <input type="text" id="username" name="username" placeholder="Username" required>
                </div>
                <div class="input-container">
                    <i class="fas fa-phone"></i>
                    <input type="text" id="contact_number" name="contact_number" placeholder="Contact Number" required>
                </div>
            </div>

            <div class="input-container-group">
                <div class="input-container">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="password" name="password" placeholder="Password" required>
                </div>
                <div class="input-container">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="confirmPassword" name="confirmPassword" placeholder="Confirm Password" required>
                </div>
            </div>

            <div class="input-container-group">
                <div class="input-container">
                    <i class="fas fa-user-tag"></i>
                    <select id="role" name="role" required onchange="toggleEmployeeIdField()">
                        <option value="" disabled selected>Select Role</option>
                        <option value="admin">Admin</option>
                        <option value="finance_director">Finance Director</option>
                        <option value="conveyancer">Conveyancer</option>
                    </select>
                </div>
                <div id="employee-id-container" class="input-container employee-id-container">
                    <i class="fas fa-id-badge"></i>
                    <input type="text" id="employee_id" name="employee_id" placeholder="Employee ID">
                </div>
            </div>

            <button type="submit" name="register">Register</button>
            <?php if (!empty($error_message)): ?>
                <p class="error"><?php echo $error_message; ?></p>
            <?php endif; ?>
        </form>
    </main>
</body>
</html>
