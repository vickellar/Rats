<?php
session_start();

// Check if user is logged in and has required session data
if (empty($_SESSION['role']) || $_SESSION['role'] !== 'conveyancer' || 
    empty($_SESSION['user_id']) || empty($_SESSION['username'])) {
    header("Location: ../index.php");
    exit();
}

require_once '../Database/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate input
    $application_id = $_POST['application_id'] ?? null;
    $message = trim($_POST['message'] ?? '');

    if (!$application_id || empty($message)) {
        $_SESSION['error'] = 'Please fill in all required fields';
        header("Location: view_application.php?id=$application_id");
        exit();
    }

    // Verify application belongs to user
    $check_query = "SELECT id FROM applications WHERE id = ? AND user_id = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("ii", $application_id, $_SESSION['user_id']);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows === 0) {
        $_SESSION['error'] = 'Invalid application';
        header("Location: cdashboard.php");
        exit();
    }

    // Insert reply
    $insert_query = "INSERT INTO application_replies (application_id, user_id, message) VALUES (?, ?, ?)";
    $insert_stmt = $conn->prepare($insert_query);
    $insert_stmt->bind_param("iis", $application_id, $_SESSION['user_id'], $message);

    if ($insert_stmt->execute()) {
        $_SESSION['success'] = 'Reply added successfully';
    } else {
        $_SESSION['error'] = 'Failed to add reply';
    }

    header("Location: view_application.php?id=$application_id");
    exit();
}

header("Location: cdashboard.php");
exit();
?>
