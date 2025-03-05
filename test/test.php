

<?php
//signin.php
// Configuration
$db_host = 'localhost';
$db_username = 'root';
$db_password = '';
$db_name = 'rate_clearance';

// Connect to database
$conn = new mysqli($db_host, $db_username, $db_password, $db_name);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get form data
$username = $_POST['username'];
$password = $_POST['password'];

// Query database
$query = "SELECT * FROM users WHERE username = '$username' AND password = '$password'";
$result = $conn->query($query);

// Check result
if ($result->num_rows > 0) {
    // Login successful
    echo "Welcome, $username!";
} else {
    // Login failed
    echo "Invalid username or password.";
}

// Close connection
$conn->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In/Up</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

      <style>
                
        body {
            font-family: Arial, sans-serif;
            background-color: #f0f0f0;
        }

        .container {
            width: 300px;
            margin: 50px auto;
            padding: 20px;
            background-color: #fff;
            border: 1px solid #ddd;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        form {
            margin-top: 20px;
        }

        label {
            display: block;
            margin-bottom: 10px;
        }

        input[type="text"], input[type="email"], input[type="password"] {
            width: 100%;
            height: 40px;
            margin-bottom: 20px;
            padding: 10px;
            border: 1px solid #ccc;
        }

        input[type="submit"] {
            width: 100%;
            height: 40px;
            background-color: #4CAF50;
            color: #fff;
            padding: 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        input[type="submit"]:hover {
            background-color: #3e8e41;
        }

        p {
            margin-top: 20px;
        }

        a {
            text-decoration: none;
            color: #4CAF50;
        }

        a:hover {
            color: #3e8e41;
        }

      </style>  

    <div class="container">
        <form action="signin.php" method="post" id="signin-form">
            <h2>Sign In</h2>
            <label for="username">Username:</label>
            <input type="text" id="username" name="username"><br><br>
            <label for="password">Password:</label>
            <input type="password" id="password" name="password"><br><br>
            <input type="submit" value="Sign In">
            <p>Don't have an account? <a href="#" onclick="showRegisterForm()">Register here</a></p>
        </form>
        <form action="register.php" method="post" id="register-form" style="display: none;">
            <h2>Register</h2>
            <label for="username">Username:</label>
            <input type="text" id="username" name="username"><br><br>
            <label for="email">Email:</label>
            <input type="email" id="email" name="email"><br><br>
            <label for="password">Password:</label>
            <input type="password" id="password" name="password"><br><br>
            <label for="confirm-password">Confirm Password:</label>
            <input type="password" id="confirm-password" name="confirm-password"><br><br>
            <input type="submit" value="Register">
            <p>Already have an account? <a href="#" onclick="showSignInForm()">Sign in here</a></p>
        </form>
    </div>

    <script>
        

        function showRegisterForm() {
            document.getElementById("signin-form").style.display = "none";
            document.getElementById("register-form").style.display = "block";
        }

        function showSignInForm() {
            document.getElementById("signin-form").style.display = "block";
            document.getElementById("register-form").style.display = "none";
        }
    </script>
</body>
</html>
