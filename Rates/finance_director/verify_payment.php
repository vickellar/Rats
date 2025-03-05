<?php
session_start();
require 'db.php'; // Include the database connection file

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // Secure password hashing
    $role = 'lawyer';
    
    // Check if the username already exists
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->rowCount() > 0) {
        echo "Username already exists. Please choose another.";
    } else {
        // Insert new lawyer into the database
        $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
        if ($stmt->execute([$username, $password, $role])) {
            echo "Lawyer registered successfully!";
        } else {
            echo "Error registering lawyer.";
        }
    }
}
?>



