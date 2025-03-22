<style>
.notification-panel {
    width: 300px; /* Set the width of the notification panel */
    overflow: hidden; /* Hide overflow text */
    white-space: nowrap; /* Prevent text wrapping */
    display: inline-block; /* Allow rotation */
    transform: rotate(0deg); /* Initial rotation */
    transition: transform 0.5s; /* Smooth transition for rotation */
}

.notification-panel:hover {
    transform: rotate(-10deg); /* Rotate on hover */
}
</style>
<?php
require_once '../Database/db.php'; // Include database connection



// Fetch notifications for all users with applicant and property details

$notificationQuery = "SELECT 
a.application_id, 
a.status, 
a.created_at, 
u.first_name, 
u.surname, 
p.address AS property_address, 
p.owner AS property_owner
FROM 
rate_clearance_applications a
JOIN 
users u ON a.user_id = u.user_id
JOIN 
properties p ON a.property_id = p.property_id
ORDER BY 
a.created_at DESC
";

$notificationStmt = $pdo->prepare($notificationQuery);
$notificationStmt->execute();
$notifications = $notificationStmt->fetchAll();

if (count($notifications) > 0) {
    foreach ($notifications as $notification) {
        echo '<li>';


        echo htmlspecialchars($notification['first_name']) . ' ' . htmlspecialchars($notification['surname']) . ' - ';
        echo htmlspecialchars($notification['property_address']) . ' (' . htmlspecialchars($notification['property_owner']) . ') - ';
        echo htmlspecialchars($notification['status']) . ' - ';
        echo htmlspecialchars($notification['created_at']);
        echo '</li>';
    }
} else {
    echo '<li>No new notifications</li>';
}
?>
