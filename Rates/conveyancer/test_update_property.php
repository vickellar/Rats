<?php
// Start session
session_start();

// Simulate a logged-in user with the correct role
$_SESSION['role'] = 'conveyancer';

// Include database connection
require_once '../Database/db.php';

// Simulate form data
$_POST['update'] = true;
$_POST['property_id'] = 1; // Assuming property ID 1 exists
$_POST['address'] = '123 New Address';
$_POST['size'] = 150;
$_POST['type'] = 'residential';
$_POST['owner'] = 'John Doe';

// Include the update property script
require_once 'update_property.php';
?>
