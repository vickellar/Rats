<?php
session_start();
// Regenerate session to prevent session fixation
session_regenerate_id(true);

if (isset($_POST['confirm_logout']) && isset($_SESSION['user_id'])) {
    // Unset all session variables
    $_SESSION = [];
    // Destroy the session
    session_destroy();
    // Redirect to the login page or home page
    header("Location: ../index.php?logout=success");
    exit();
}

// If the user has not confirmed, show a confirmation message
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirm Logout</title>
    <link rel="icon" type="image/x-icon" href="assets/images/favicon.ico">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/logout.css" type="text/css">
</head>
<body>
    <div class="logout-container">
        <div class="logout-icon">
            <i class="fas fa-sign-out-alt"></i>
        </div>
        
        <h2>Confirm Logout</h2>
        
        <p class="logout-message">
            Are you sure you want to log out of your account? You will need to sign in again to access your dashboard.
        </p>

        <?php if (isset($_SESSION['username'])): ?>
        <div class="user-info">
            <p><strong>Current User:</strong> <?php echo htmlspecialchars($_SESSION['username']); ?></p>
            <?php if (isset($_SESSION['role'])): ?>
            <p><strong>Role:</strong> <?php echo htmlspecialchars(ucfirst($_SESSION['role'])); ?></p>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <form method="POST" action="../index.php" id="logoutForm">
            <div class="button-container">
                <button type="submit" name="confirm_logout" class="btn btn-danger" id="logoutBtn">
                    <i class="fas fa-sign-out-alt"></i>
                    Yes, Log Me Out
                </button>
                <button type="button" onclick="goBack()" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i>
                    Cancel
                </button>
            </div>
        </form>

        <div class="security-note">
            <h4>
                <i class="fas fa-shield-alt"></i>
                Security Note
            </h4>
            <p>
                For your security, always log out when using shared or public computers. 
                Your session will be completely terminated and all data will be cleared.
            </p>
        </div>
    </div>

    <script>
        document.getElementById('logoutBtn').addEventListener('click', function() {
            this.classList.add('loading');
            this.innerHTML = '<i class="fas fa-spinner"></i> Logging Out...';
            this.disabled = true; // Disable to prevent multiple submissions
            document.getElementById('logoutForm').submit(); // Explicitly submit the form
        });

        function goBack() {
            if (document.referrer && document.referrer !== window.location.href) {
                window.history.back();
            } else {
                window.location.href = '../index.php';
            }
        }

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && e.target.tagName !== 'BUTTON') {
                e.preventDefault();
                document.getElementById('logoutBtn').click();
            }
            if (e.key === 'Escape') {
                e.preventDefault();
                goBack();
            }
        });

        window.addEventListener('load', function() {
            document.getElementById('logoutBtn').focus();
        });

        let formSubmitted = false;
        document.getElementById('logoutForm').addEventListener('submit', function() {
            formSubmitted = true;
        });

        window.addEventListener('beforeunload', function(e) {
            if (!formSubmitted) {
                e.preventDefault();
                e.returnValue = '';
            }
        });
    </script>
</body>
</html>