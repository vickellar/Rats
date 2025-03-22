<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Regenerate session to prevent session fixation
//session_regenerate_id(true);


// Ensure $requestedPage is defined
$requestedPage = isset($_GET['page']) ? $_GET['page'] : 'home';

// Route the request to the appropriate controller based on the URL
switch ($requestedPage) {
    case 'home':
        include './home.php';
        break;
    case 'register':
        include './signup.php';
        break;
    case 'login':
        include './signin.php';
        break;
    default:
        include './404.php'; // Page not found
        break;
}

if (isset($_POST['login'])) {
    // Validate user credentials against the database
    $username = $_POST['username'];
    $password = $_POST['password'];

    $sql = "SELECT * FROM users WHERE username = :username";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':username' => $username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        // Set session variables
        $_SESSION['role'] = $user['role'];
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['username'] = $user['username'];

        // Redirect based on user role
        switch ($_SESSION['role']) {
            case 'conveyancer':
                header("Location: /Rates/conveyancer/cdashboard.php");
                exit();
            case 'admin':
                header("Location: /Rates/admin/adminDashboard.php");
                exit();
            case 'Finance Director':
                header("Location: /Rates/finance_director/fdashboard.php");
                exit();
            default:
                header("Location: ../index.php"); // Redirect to home for unknown roles
                exit();
        }
    }
}
/*
if (empty($_SESSION['role'])) {
    header("Location: index.php");
    exit();
}
    */

?>
