
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Sign-In</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="./assets/css/styles.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            padding: 20px;
            background-color: #f4f4f4;
        }

        main {
            
            padding: 20px;
            max-width: 500mm;
            height: 90mm;
            background-color: rgba(255, 255, 255, 0.5);
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            grid: 10px;
            gap: 20px;
        }

        .logo {
            margin-bottom: 20px;
            width: 100px;
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
    document.addEventListener('DOMContentLoaded', function() {
        const loginAttempts = <?php echo $_SESSION['login_attempts']; ?>;
        const forgotPasswordLink = document.getElementById('forgot-password-link');
        if (loginAttempts >= 3) {
            forgotPasswordLink.style.display = 'block';
        }
    });
    </script>
</head>

<body>
    <!-- Logo at the top -->
    <div class="page-container">
    <header class="header">
        <div class="container">
            <div class="header-content">
                <div class="logo-section">
                    <img src="./assets/images/mslogo.png" alt="Logo" class="logo">
                    <h1 class="site-title">Rate Clearance System</h1>
                </div>
            </div>
        </div>
    </header>

    <nav class="navigation">
        <div class="container">
            <ul class="nav-list">
                <li><a href="home.php" class="nav-link">Home</a></li>
                <li><a href="signin.php" class="nav-link active">Login</a></li>
                <li><a href="signup.php" class="nav-link">Register</a></li>
                <li><a href="services.html" class="nav-link">Services</a></li>
                <li><a href="contacts.html" class="nav-link">Contacts</a></li>
                <li><a href="about.html" class="nav-link">About</a></li>
            </ul>
        </div>
    </nav>
    <main class="main-contant">
        <div class="container">
            <div class="form-container">

                <!-- Sign-in form -->
                <form class="form-contant" method="POST" action="./includes/signin.php">
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
                    <div class="form-footer">
                         Don't have an account? 
                         <a href="register.html" class="form-link">Register</a>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <p>&copy; 2024 Rate Clearance System. All rights reserved.</p>
                <div class="footer-links">
                    <a href="#" class="footer-link">Privacy Policy</a>
                    <a href="#" class="footer-link">Terms of Service</a>
                    <a href="#" class="footer-link">Contact Us</a>
                </div>
            </div>
        </div>
    </footer>
</body>

</html>