<?php
session_start();

require_once("./Database/db.php");

if (!isset($_SESSION['last_activity'])) {
    $_SESSION['last_activity'] = time(); // Store last activity time
}

if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
    $_SESSION['last_attempt_time'] = time();
}

// Security constants
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 1800); // 30 minutes in seconds

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['signin'])) {

    $_SESSION['last_activity'] = time(); // Update last activity time

    $username = $_POST["username"];
    $password = $_POST["password"];
    $role = $_POST["role"];
    $max_attempts = MAX_LOGIN_ATTEMPTS;
    $lockout_time = LOGIN_LOCKOUT_TIME;

    // Check for too many login attempts
    if ($_SESSION['login_attempts'] >= $max_attempts) {
        if (time() - $_SESSION['last_attempt_time'] < $lockout_time) {
            die("Too many login attempts. Please try again later.");
        } else {
            $_SESSION['login_attempts'] = 0;
        }
    }

    try {
        if ($role === 'admin') {
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
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Sign-In</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }
        main {
            padding: 20px;
            max-width: 105mm;
            height: 148mm;
            background-color: #fff;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
        }
        .logo {
            margin-bottom: 20px;
            width: 200px;
            height: auto;
            max-width: 100%;
        }
        h2 {
            color: #333;
            margin-bottom: 20px;
        }
        form {
            width: 100%;
        }
        label {
            display: block;
            margin-bottom: 5px;
            color: #333;
        }
        input,
        button,
        select {
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        .button-container {
            display: flex;
            justify-content: space-between;
        }
        button {
            background-color: #007BFF;
            color: #fff;
            border: none;
            cursor: pointer;
            width: 48%;
        }
        button:hover {
            background-color: #0056b3;
        }
        .forgot-password,
        .sign-up {
            margin-top: 10px;
        }
        .forgot-password a,
        .sign-up a {
            color: #007BFF;
            text-decoration: none;
        }
        .forgot-password a:hover,
        .sign-up a:hover {
            text-decoration: underline;
        }
        .input-container {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            padding: 10px;
        }
        .input-container i {
            margin-right: 10px;
            color: #007BFF;
        }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const loginAttempts = <?php echo $_SESSION['login_attempts']; ?>;
            const forgotPasswordLink = document.getElementById('forgot-password-link');
            if (loginAttempts >= 3) {
                forgotPasswordLink.style.display = 'block';
            }
        });
    </script>
</head>
<body>
    <main>
        <!-- Logo at the top -->
        <img src="./assets/images/mslogo.png" alt="Logo" class="logo">
        <h2>Welcome to Sign-In</h2>

        <!-- Sign-in form -->
        <form method="POST" action="">
            <div class="input-container">
                <i class="fas fa-user"></i>
                <input type="text" id="username" name="username" placeholder="Username" required>
            </div>
            <div class="input-container">
                <i class="fas fa-lock"></i>
                <input type="password" id="password" name="password" placeholder="Password" required>
            </div>
            <div class="input-container">
                <i class="fas fa-user-tag"></i>
                <select id="role" name="role" required>
                    <option value="" disabled selected>Select Role</option>
                    <option value="admin">Admin</option>
                    <option value="finance_director">Finance Director</option>
                    <option value="conveyancer">Conveyancer</option>
                </select>
            </div>
            <div class="button-container">
                <button type="submit" name="signin">Sign In</button>
                <button type="button" onclick="window.location.href='./signup.php'">Sign Up</button>
            </div>
        </form>
    </main>
</body>
</html>
