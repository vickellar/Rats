<?php
session_start();
// Regenerate session to prevent session fixation
session_regenerate_id(true);


if (isset($_POST['confirm_logout']) && isset($_SESSION['user_id'])) {

    // Unset all session variables
    $_SESSION = []; // Ensure session variables are cleared
    // Destroy the session
    session_destroy(); // Ensure session is destroyed
    // Redirect to the login page or home page
    header("Location: ../index.php");
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
</head>
<body>
    <h2>Are you sure you want to log out?</h2>
    <form method="POST" action="">
        <button type="submit" name="confirm_logout">Yes, log me out</button>
        <button type="button" onclick="window.location.href='../index.php'">Cancel</button>
    </form>
</body>
</html>
