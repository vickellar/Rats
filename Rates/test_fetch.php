<?php
// Start the session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once("./Database/db.php");
include('./test_db_connection.php');

// Check if the form is submitted via POST and the property is selected
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["select_property"])) {
    // Get the selected property ID from the form
    $selectedPropertyId = $_POST['select_property'];
    
    // Ensure the user ID is set in the session
    if (isset($_SESSION['user_id'])) {
        $userId = $_SESSION['user_id'];
    } else {
        // If no user ID is found in the session, show an error message
        echo "<p>User is not logged in.</p>";
        exit;
    }

    // Display the user ID and selected property ID
    echo "<p>User ID: " . htmlspecialchars($userId) . "</p>";
    echo "<p>Selected Property ID: " . htmlspecialchars($selectedPropertyId) . "</p>";
}
?>
