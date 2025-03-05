<?php
// Start the session
session_start();

// Include database connection file
require '../Database/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve form data
    $first_name = $_POST['first_name'];
    $surname = $_POST['surname'];
    $username = $_POST['username'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = $_POST['role'];

    // Validate input data
    $errors = [];
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }

    if (empty($errors)) {
        try {
            // Hash the password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Check if user exists
            $sql = "SELECT * FROM users WHERE email = :email OR username = :username";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':email' => $email, ':username' => $username]);
            
            if ($stmt->rowCount() > 0) {
                $errors[] = "Username or email already exists.";
            } else {
                // Insert new user
                $sql = "INSERT INTO users (first_name, surname, username, phone, email, password, role) 
                        VALUES (:first_name, :surname, :username, :phone, :email, :password, :role)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':first_name' => $first_name,
                    ':surname' => $surname,
                    ':username' => $username,
                    ':phone' => $phone,
                    ':email' => $email,
                    ':password' => $hashed_password,
                    ':role' => $role
                ]);

                // If role is admin or finance_director, add to employees table
                if ($role === 'admin' || $role === 'finance_director') {
                    $sql = "INSERT INTO employees (first_name, surname, email, phone_number) 
                            VALUES (:first_name, :surname, :email, :phone)";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        ':first_name' => $first_name,
                        ':surname' => $surname,
                        ':email' => $email,
                        ':phone' => $phone
                    ]);
                }

                header("Location: adminDashboard.php");
                exit();
            }
        } catch (PDOException $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }

}
?>
