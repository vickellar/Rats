<?php
// Database configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "rate_clearance_system";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST["email"];
    $token = bin2hex(random_bytes(32)); // Generate a unique token

    // Check if email exists
    $sql = "SELECT * FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        // Store token in the database
        $sql = "UPDATE users SET reset_token = ?, reset_token_expiry = DATE_ADD(NOW(), INTERVAL 1 HOUR) WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $token, $email);
        $stmt->execute();

        // Send reset link via email (using mail() for simplicity)
        $reset_link = "http://yourdomain.com/reset_password.php?token=" . $token;
        $subject = "Password Reset Request";
        $message = "Click the following link to reset your password: " . $reset_link;
        $headers = "From: no-reply@yourdomain.com";

        if (mail($email, $subject, $message, $headers)) {
            echo "Reset link sent to your email.";
        } else {
            echo "Failed to send email.";
        }
    } else {
        echo "No account found with that email.";
    }
}

$conn->close();
?>
