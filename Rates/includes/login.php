<?php

if(session_status() === PHP_SESSION_NONE){
    session_start();
}

require_once("./Database/db.php");

if (!isset($_SESSION['last_activity']) || time() - $_SESSION['last_activity'] > 1800) {

    $_SESSION['last_activity'] = time(); // Store last activity time
}

if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
    $_SESSION['last_attempt_time'] = time();
}

// Security constants
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 1800); // 30 minutes in seconds

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['signin']) && $_SESSION['login_attempts'] < MAX_LOGIN_ATTEMPTS) {


    $_SESSION['last_activity'] = time(); // Update last activity time

    $username = $_POST["username"];
    $password = $_POST["password"];
    $role = $_POST["role"];
    $max_attempts = MAX_LOGIN_ATTEMPTS;
    $lockout_time = LOGIN_LOCKOUT_TIME;

    // Check for too many login attempts
    if ($_SESSION['login_attempts'] >= $max_attempts) {
        if (time() - $_SESSION['last_attempt_time'] < $lockout_time) {
            die("Too many login attempts. Please try again later. You can try again after 30 minutes.");

        } else {
            $_SESSION['login_attempts'] = 0;
        }
    }

    try {
        if ($role === 'admin' || $role === 'finance_director') {
            // Check against employee table for admin
            $sql = "SELECT * FROM employees WHERE username = :username";
            error_log("Executing SQL: " . $sql . " with parameters: " . json_encode([':username' => $username]));

            $stmt = $pdo->prepare($sql);
            $stmt->execute([':username' => $username]);
            $user = $stmt->fetch();


            if ($user && password_verify($password, $user['password'])) {
                // Clear existing session data for a new user
                session_unset(); // Clear all session variables
                session_destroy(); // Destroy the current session

                session_start(); // Start a new session
                $_SESSION['user_id'] = $user['id']; // Assuming 'id' is the primary key
                $_SESSION['username'] = $username;
                $_SESSION['role'] = $role;
                $_SESSION['login_attempts'] = 0;


                switch($role){
                    case 'admin':
                        // Redirect to admin dashboard
                        header("Location: ./admin/adminDashboard.php");
                        exit();
                    case 'finance_director':
                        // Redirect to finance director dashboard
                        header("Location: ./finance_director/fdashboard.php");
                        exit();
                    default:    "";
                }

                // Redirect to admin dashboard
                header("Location: ./admin/adminDashboard.php");
                exit();
            } else {
                $_SESSION['login_attempts']++;
                $_SESSION['last_attempt_time'] = time();
                echo "Invalid login credentials.";
            }
        } else {
            // Existing logic for other roles
            $sql = "SELECT * FROM users WHERE username = :username AND role = :role";
            error_log("Executing SQL: " . $sql . " with parameters: " . json_encode([':username' => $username, ':role' => $role]));

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':username' => $username,
                ':role' => $role
            ]);
            
            $user = $stmt->fetch();
            
            if ($user) {
                if (password_verify($password, $user['password'])) {
                    // Clear existing session data for a new user
                    session_unset(); // Clear all session variables
                    session_destroy(); // Destroy the current session

                    session_start(); // Start a new session
                    $_SESSION['user_id'] = $user['id'] ?? $user['user_id'];
                    $_SESSION['username'] = $username;
                    $_SESSION['role'] = $role;
                    $_SESSION['login_attempts'] = 0;

                    $dashboards = [
                        'admin' => './admin/adminDashboard.php',
                        'finance_director' => './finance_director/fdashboard.php',
                        'conveyancer' => './conveyancer/cdashboard.php'
                    ];
                    header("Location: " . $dashboards[$role]);
                    exit();
                } else {
                    $_SESSION['login_attempts']++;
                    $_SESSION['last_attempt_time'] = time();
                    echo "Invalid login credentials.";
                }
            } else {
                $_SESSION['login_attempts']++;
                $_SESSION['last_attempt_time'] = time();
                echo "Invalid login credentials.";
            }
        }
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine(), 3, "logfile/database_errors.log");
        die("An error occurred. Please try again later.");
    }
}
?>