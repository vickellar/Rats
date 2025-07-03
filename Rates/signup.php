<?php
// session start
if(session_status() === PHP_SESSION_NONE){
    session_start();
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
// Include database connection
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
    $contact_number = filter_input(INPUT_POST, 'contact_number', FILTER_SANITIZE_SPECIAL_CHARS);

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
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        try {
            // Check for existing username
            $sql = "SELECT * FROM users WHERE username = :username";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':username' => $username]);

            if ($stmt->rowCount() > 0) {
                $error_message = "Username already exists. Please use a different username.";
            } else {
                // Insert new user
                $sql = "INSERT INTO users (first_name, surname, username, password, role, contact_number) 
                        VALUES (:first_name, :surname, :username, :password, :role, :contact_number)";
                $stmt = $pdo->prepare($sql);

                $result = $stmt->execute([
                    ':first_name' => $first_name,
                    ':surname' => $surname,
                    ':username' => $username,
                    ':password' => $password_hash,
                    ':role' => $role,
                    ':contact_number' => $contact_number
                ]);

                if ($result) {
                    $user_id = $pdo->lastInsertId();
                    $_SESSION['role'] = $role;
                    $_SESSION['user_id'] = $user_id;
                    $_SESSION['username'] = $username;

                    $success_message = "Registration successful! Welcome " . htmlspecialchars($first_name) . "!";
                    if ($role === 'conveyancer') {
                        $redirect_url = "./conveyancer/cdashboard.php";
                    } else {
                        $redirect_url = "/login.php";
                    }
                } else {
                    $error_message = "Failed to create user account. Please try again.";
                }
            }
        } catch (PDOException $e) {
            $error_message = "Database error: " . $e->getMessage();
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
    <link rel="stylesheet" href="./assets/css/styles.css">
    <link rel="stylesheet" href="./assets/css/signup.css">
   
 <script>
        function toggleEmployeeIdField() {
            const role = document.getElementById("role").value;
            const employeeIdContainer = document.getElementById("employee-id-container");
            if (role === "admin" || role === "finance_director") {
                employeeIdContainer.style.display = "block";
                employeeIdContainer.classList.add("show");
            } else {
                employeeIdContainer.style.display = "none";
                employeeIdContainer.classList.remove("show");
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            const errorMessage = "<?php echo addslashes($error_message); ?>";
            const successMessage = "<?php echo isset($success_message) ? addslashes($success_message) : ''; ?>";
            const redirectUrl = "<?php echo isset($redirect_url) ? addslashes($redirect_url) : ''; ?>";

            if (successMessage && redirectUrl) {
                // Show success animation
                showSuccessAnimation(redirectUrl);
            } else if (errorMessage) {
                console.log('Registration error:', errorMessage);
            }

            // Add form validation
            const form = document.querySelector('.registration-form');
            const submitBtn = document.querySelector('.submit-btn');
            let isSubmitting = false;

            form.addEventListener('submit', function(e) {
                // Prevent multiple submissions
                if (isSubmitting) {
                    e.preventDefault();
                    return;
                }

                const password = document.getElementById('password').value;
                const confirmPassword = document.getElementById('confirmPassword').value;

                // Validate passwords
                if (password !== confirmPassword) {
                    e.preventDefault();
                    alert('Passwords do not match!');
                    return;
                }

                if (password.length < 8) {
                    e.preventDefault();
                    alert('Password must be at least 8 characters long!');
                    return;
                }

                // If validation passes, set loading state but allow form to submit
                isSubmitting = true;
                
                // Add loading state with a small delay to allow form submission
                setTimeout(() => {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating Account...';
                }, 100);

                // Don't prevent default - let the form submit naturally
            });

            // Add real-time password validation
            const passwordInput = document.getElementById('password');
            const confirmPasswordInput = document.getElementById('confirmPassword');

            function validatePasswords() {
                if (confirmPasswordInput.value && passwordInput.value !== confirmPasswordInput.value) {
                    confirmPasswordInput.setCustomValidity('Passwords do not match');
                } else {
                    confirmPasswordInput.setCustomValidity('');
                }
            }

            passwordInput.addEventListener('input', validatePasswords);
            confirmPasswordInput.addEventListener('input', validatePasswords);
        });

        function showSuccessAnimation(redirectUrl) {
            const overlay = document.getElementById('successOverlay');
            const countdownElement = document.getElementById('countdown');
            
            // Create confetti
            createConfetti();
            
            // Show overlay
            setTimeout(() => {
                overlay.classList.add('show');
            }, 100);
            
            // Countdown and redirect
            let countdown = 3;
            const countdownInterval = setInterval(() => {
                countdown--;
                countdownElement.textContent = countdown;
                
                if (countdown <= 0) {
                    clearInterval(countdownInterval);
                    window.location.href = redirectUrl;
                }
            }, 1000);
        }

        function createConfetti() {
            const overlay = document.getElementById('successOverlay');
            const confettiCount = 50;
            
            for (let i = 0; i < confettiCount; i++) {
                const confetti = document.createElement('div');
                confetti.className = 'confetti';
                confetti.style.left = Math.random() * 100 + '%';
                confetti.style.animationDelay = Math.random() * 3 + 's';
                confetti.style.animationDuration = (Math.random() * 3 + 2) + 's';
                overlay.appendChild(confetti);
            }
        }
</script>


</head>
<body>
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
                    <li><a href="signin.php" class="nav-link">Login</a></li>
                    <li><a href="signup.php" class="nav-link active">Register</a></li>
                    <li><a href="services.html" class="nav-link">Services</a></li>
                    <li><a href="contacts.html" class="nav-link">About</a></li>
                </ul>
            </div>
        </nav>

        <main class="main-content">
            <div class="registration-container">
                <h2 class="form-title">Create Account</h2>
                       
                <form method="POST" action="" class="registration-form">
                    <div class="form-row">
                        <div class="input-group">
                            <div class="input-container">
                                <i class="fas fa-user"></i>
                                <input type="text" id="first_name" name="first_name" placeholder="First Name" required>
                            </div>
                        </div>
                        <div class="input-group">
                            <div class="input-container">
                                <i class="fas fa-user"></i>
                                <input type="text" id="surname" name="surname" placeholder="Surname" required>
                            </div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="input-group">
                            <div class="input-container">
                                <i class="fas fa-user-circle"></i>
                                <input type="text" id="username" name="username" placeholder="Username" required>
                            </div>
                        </div>
                        <div class="input-group">
                            <div class="input-container">
                                <i class="fas fa-phone"></i>
                                <input type="text" id="contact_number" name="contact_number" placeholder="Contact Number" required>
                            </div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="input-group">
                            <div class="input-container">
                                <i class="fas fa-lock"></i>
                                <input type="password" id="password" name="password" placeholder="Password" required>
                            </div>
                        </div>
                        <div class="input-group">
                            <div class="input-container">
                                <i class="fas fa-lock"></i>
                                <input type="password" id="confirmPassword" name="confirmPassword" placeholder="Confirm Password" required>
                            </div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="input-group">
                            <div class="input-container">
                                <i class="fas fa-user-tag"></i>
                                <select id="role" name="role" required onchange="toggleEmployeeIdField()">
                                    <option value="" disabled selected>Select Role</option>
                                    <!--<option value="admin">Admin</option>
                                    <option value="finance_director">Finance Director</option>-->
                                    <option value="conveyancer">Conveyancer</option>
                                </select>
                            </div>
                        </div>
                        <div id="employee-id-container" class="input-group employee-id-container">
                            <div class="input-container">
                                <i class="fas fa-id-badge"></i>
                                <input type="text" id="employee_id" name="employee_id" placeholder="Employee ID">
                            </div>
                        </div>
                    </div>

                    <button type="submit" name="register" class="submit-btn">
                        <i class="fas fa-user-plus"></i>
                        Create Account
                    </button>
                    <?php if (!empty($error_message)): ?>
                        <div class="error-message">
                            <i class="fas fa-exclamation-triangle"></i>
                            <?php echo htmlspecialchars($error_message); ?>
                        </div>
                    <?php endif; ?>
                </form>
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
    </div>

    <?php if (isset($success_message) && isset($redirect_url)): ?>
    <div id="successOverlay" class="success-overlay">
        <div class="success-animation">
            <div class="success-icon">
                <i class="fas fa-check"></i>
            </div>
            <h3 class="success-title">Welcome Aboard!</h3>
            <p class="success-message"><?php echo htmlspecialchars($success_message); ?></p>
            <div class="success-progress">
                <div class="success-progress-bar"></div>
            </div>
            <p class="redirect-text">Redirecting in <span id="countdown">3</span> seconds...</p>
        </div>
    </div>
    <?php endif; ?>

</body>
</html>
