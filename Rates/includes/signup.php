<?php
// session start
if(session_status() === PHP_SESSION_NONE){
    session_start();

    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['username'] = $username;
    $_SESSION['role'] = $role;
    $_SESSION['employee_id'] = $user['employee_id'];
}
/*
// Check if user is logged in and has required session data
if (empty($_SESSION['role']) || $_SESSION['role'] !== 'conveyancer' || 
    empty($_SESSION['user_id']) || empty($_SESSION['username'])) {
    header("Location: ./index.php");
    exit();
}
*/

ob_start();

ini_set('display_errors', 1);

require_once("../Database/db.php");

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
    $contact_number = filter_input(INPUT_POST, 'contact_number', FILTER_SANITIZE_SPECIAL_CHARS); // New field for contact number

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
                    header("Location: ../login.php");
                    exit();
                }

                // Display the appropriate dashboard based on the user role
                $role = $_SESSION['role'];

                if ($role === 'admin') {
                    header("Location: ../admin/adminDashboard.php");
                } elseif ($role === 'finance_director') {
                    header("Location: ../finance_director/fdashboard.php");
                } elseif ($role === 'conveyancer') {
                    header("Location: ../conveyancer/cdashboard.php");
                } else {
                    // Redirect to login or another appropriate page if the role is invalid
                    header("Location: ../login.php");
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