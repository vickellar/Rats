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
    $token = $_POST["token"];
    $new_password = password_hash($_POST["new_password"], PASSWORD_DEFAULT);

    // Verify the token
    $sql = "SELECT * FROM users WHERE reset_token = ? AND reset_token_expiry > NOW()";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        // Update the password
        $sql = "UPDATE users SET password = ?, reset_token = NULL, reset_token_expiry = NULL WHERE reset_token = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $new_password, $token);
        if ($stmt->execute()) {
            echo "Password updated successfully.";
        } else {
            echo "Failed to update password.";
        }
    } else {
        echo "Invalid or expired token.";
    }
}

$conn->close();
?>
