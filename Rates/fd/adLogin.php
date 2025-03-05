<?php
session_start();

// Get cookies if they exist
$username_cookie = isset($_COOKIE['username']) ? $_COOKIE['username'] : '';
$role_cookie = isset($_COOKIE['role']) ? $_COOKIE['role'] : '';

// Database configuration
$servername = "localhost"; // Change as needed
$db_username = "your_db_username"; // Change to your database username
$db_password = "your_db_password"; // Change to your database password
$dbname = "your_db_name"; // Change to your database name

// Create connection
$conn = new mysqli($servername, $db_username, $db_password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Process login if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate and sanitize input
    $user = trim($_POST['username']);
    $role = $_POST['role'];
    $pass = $_POST['password'];
    $remember = isset($_POST['remember']); // Check if remember me is checked

    // Validate username
    if (!preg_match("/^[a-zA-Z0-9_]{3,50}$/", $user)) {
        die("Invalid username. It must be alphanumeric and 3-50 characters long.");
    }

    // Validate password
    if (strlen($pass) < 6) {
        die("Invalid password. It must be at least 6 characters long.");
    }

    // Prepare and bind
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? AND role = ?");
    $stmt->bind_param("ss", $user, $role);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // User found, check password
        $row = $result->fetch_assoc();

        // Assuming passwords are stored as hashed values
        if (password_verify($pass, $row['password'])) {
            // Password is correct, start session
            $_SESSION['username'] = $row['username'];
            $_SESSION['role'] = $row['role'];

            // Set cookie if "Remember Me" is checked
            if ($remember) {
                setcookie('username', $user, time() + (86400 * 30), '/'); // 30 days
                setcookie('role', $role, time() + (86400 * 30), '/'); // 30 days
            } else {
                // Clear cookies if not checked
                if (isset($_COOKIE['username'])) {
                    setcookie('username', '', time() - 3600, '/'); // Clear cookie
                }
                if (isset($_COOKIE['role'])) {
                    setcookie('role', '', time() - 3600, '/'); // Clear cookie
                }
            }

            // Redirect based on role
            if ($row['role'] == 'administrator') {
                header("Location: admin_dashboard.php");
            } elseif ($row['role'] == 'financial_director') {
                header("Location: financial_dashboard.php");
            }
            exit();
        } else {
            // Invalid password
            echo "Invalid password.";
        }
    } else {
        // No user found
        echo "No user found with that username and role.";
    }

    // Close connection
    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-color: #f0f0f0;
            margin: 0;
            font-family: Arial, sans-serif;
        }

        .login-container {
            background-color: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            width: 300px;
            text-align: center;
        }

        .login-container h2 {
            margin-bottom: 20px;
        }

        .login-container input,
        .login-container select {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        .login-container button {
            background-color: red;
            color: white;
            padding: 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
            font-size: 1em;
        }

        .login-container button:hover {
            background-color: darkred;
        }

        .link {
            margin-top: 15px;
            display: block;
            color: blue;
            text-decoration: none;
        }

        .link:hover {
            text-decoration: underline;
        }

        .signup-container button {
            background-color: green;
            color: white;
            padding: 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
            font-size: 1em;
        }

        .signup-container button:hover {
            background-color: darkgreen;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Login</h2>
        <form action="" method="post" onsubmit="return validateForm()">
            <input type="text" name="username" placeholder="Username" value="<?php echo htmlspecialchars($username_cookie); ?>" required minlength="3" maxlength="50" pattern="^[a-zA-Z0-9_]+$" title="Username must be alphanumeric and can include underscores.">
            <select name="role" required>
                <option value="" disabled>Select Role</option>
                <option value="administrator" <?php echo ($role_cookie == 'administrator') ? 'selected' : ''; ?>>Administrator</option>
                <option value="financial_director" <?php echo ($role_cookie == 'financial_director') ? 'selected' : ''; ?>>Financial Director</option>
            </select>
            <input type="password" name="password" placeholder="Password" required minlength="6" title="Password must be at least 6 characters long.">
            <div>
                <input type="checkbox" name="remember" id="remember" <?php echo ($username_cookie) ? 'checked' : ''; ?>>
                <label for="remember">Remember Me</label>
            </div>
            <button type="submit">Login</button>
        </form>

        <script>
        function validateForm() {
            const username = document.querySelector('input[name="username"]');
            const password = document.querySelector('input[name="password"]');

            // Check if username is valid
            if (!username.checkValidity()) {
                alert(username.title);
                username.focus();
                return false;
            }

            // Check if password is valid
            if (!password.checkValidity()) {
                alert(password.title);
                password.focus();
                return false;
            }

            return true; // All checks passed
        }
        </script>
        <a href="recover_password.php" class="link">Recover My Password</a>
        <div class="signup-container">
            <button onclick="window.location.href='registration_form.php'">Sign Up</button>
        </div>
    </div>
</body>
</html>
