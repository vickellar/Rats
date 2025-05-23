<?php
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

require_once '../Database/db.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f4f4f4;
        }
        header {
            display: flex; 
            align-items: center; 
            background: rgba(36, 75, 184, 0.7);
            color: #fff;
            padding: 20px; 
            justify-content: space-between; 
        }
        header img {
            width: 100%;         
            max-width: 150px;    
            height: auto;        
            margin-right: 10px;  
        }
        header h1 {
            flex: 1;             
            text-align: center;  
        }
        nav {
            background-color: rgba(31, 181, 192, 0.7);
            color: #fff;
            padding: 10px 0;
            text-align: right;
            position: relative;
        }
        nav a {
            color: #fff;
            text-decoration: none;
            padding: 10px 20px;
            display: inline-block;
        }
        nav a:hover {
            background-color: rgba(189, 193, 199, 0.7);
        }
        nav .dropdown {
            display: inline-block;
            position: relative;
        }
        nav .dropdown-content {
            display: none;
            position: absolute;
            background-color: #007bff;
            min-width: 160px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
            z-index: 1;
        }
        nav .dropdown-content a {
            color: #fff;
            padding: 12px 16px;
            text-decoration: none;
            display: block;
            text-align: left;
        }
        nav .dropdown-content a:hover {
            background-color: #0056b3;
        }
        nav .dropdown:hover .dropdown-content {
            display: block;
        }
        main {
            padding: 20px;
            max-width: 1000px;
            margin: auto;
            background-color: lightgrey;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            padding: 20px;
        }
        .dashboard-card {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #eee;
        }
        .dashboard-card h3 {
            margin-top: 0;
            color: #444;
        }
        .dashboard-card ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .dashboard-card li {
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        .dashboard-card li:last-child {
            border-bottom: none;
        }
        .dashboard-card a {
            color: #007bff;
            text-decoration: none;
        }
        .dashboard-card a:hover {
            text-decoration: underline;
        }
        .notification-panel {
            display: none;
            position: fixed;
            top: 60px;
            right: 20px;
            background: white;
            border: 1px solid #ccc;
            padding: 10px;
            z-index: 1000;
            width: 300px;
            max-height: 400px;
            overflow-y: auto;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .notification-panel h3 {
            margin-top: 0;
            color: #444;
        }
        .notification-panel ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .notification-panel li {
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        .notification-panel li:last-child {
            border-bottom: none;
        }
        .notification-panel a {
            color: #007bff;
            text-decoration: none;
        }
        .notification-panel a:hover {
            text-decoration: underline;
        }
        .notification-panel button {
            display: block;
            margin: 10px 0;
            padding: 10px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .notification-panel button:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
<header>
    <img src="../assets/images/mslogo.png" alt="Logo"> 
    <h1>WELCOME TO ADMIN DASHBOARD</h1>
    <div class="notification-icon" onclick="toggleNotifications()">
        <i class="fas fa-bell"></i> 
        <?php
        // Fetch count of new notifications
        $newNotificationQuery = "
            SELECT COUNT(*) AS new_count
            FROM rate_clearance_applications
            WHERE status = 'awaiting'
        ";
        $newNotificationStmt = $pdo->prepare($newNotificationQuery);
        $newNotificationStmt->execute();
        $newNotificationCount = $newNotificationStmt->fetch()['new_count'];
        if ($newNotificationCount > 0) {
            echo '<span style="background-color: red; color: white; border-radius: 50%; padding: 0 5px; margin-left: 5px;">' . $newNotificationCount . '</span>';
        }
        ?>
    </div>
</header>
<nav>
    <a href="index.php?page=home">Dashboard</a>
    <div class="dropdown">
        <a href="index.php?page=register">User Management</a>
        <div class="dropdown-content">
            <a href="add_user.php">Add User</a>
            <a href="view_users.php">View Users</a>
            <a href="delete_user.php">Delete User</a>
        </div>
    </div>
    <div class="dropdown">
        <a href="index.php?page=login">Application Management</a>
        <div class="dropdown-content">
            <a href="view_history.php">View Applications</a>
            <a href="approve_applications.php">Approve Applications</a>
            <a href="pending_applications.php">Pending Applications</a>
            <a href="reject_applications.php">Reject Applications</a>
        </div>
    </div>
    <a href="index.php?page=logout">Log Out</a>
</nav>

<main>
    <div class="dashboard-grid">
        <div class="dashboard-card">
            <h3>Recent Applications</h3>
            <?php
            if (isset($_SESSION['user_id'])) {
                $userId = $_SESSION['user_id']; // Ensure $userId is defined

                $query = "SELECT * FROM applications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5";
                $stmt = $pdo->prepare($query);
                $stmt->bindValue(1, $userId, PDO::PARAM_INT);
                $stmt->execute();
                $result = $stmt->fetchAll();

                if (count($result) > 0) {
                    echo '<ul>';
                    foreach ($result as $row) {
                        echo '<li><a href="view_application.php?id=' . $row['id'] . '">' . htmlspecialchars($row['application_ref']) . ' - ' . htmlspecialchars($row['status']) . '</a></li>';
                    }
                    echo '</ul>';
                } else {
                    echo '<p>No recent applications found.</p>';
                }
            } else {
                echo '<p>User session error. Please login again.</p>';
            }
            ?>
        </div>
        
        <div class="dashboard-card">
            <h3 id="notifications">Notifications</h3>
            <div class="notification-scroll">
                <ul id="notificationList">
                    <?php
                    // Fetch notifications from the application database 
                    $notificationQuery = "
                        SELECT 
                            a.application_id, 
                            a.status, 
                            a.created_at, 
                            u.first_name, 
                            u.surname, 
                            p.address AS property_address, 
                            p.owner AS property_owner,
                            p.property_id
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
                            echo '<li class="notification-item" data-application-id="' . $notification['application_id'] . '">';
                            echo '<a href="../includes/fetch_property_details.php?property_id=' . $notification['property_id'] . '">' . htmlspecialchars($notification['first_name']) . ' ' . htmlspecialchars($notification['surname']) . ' - </a>';
                            echo '<a href="../includes/fetch_property_details.php?property_id=' . $notification['property_id'] . '">' . htmlspecialchars($notification['property_address']) . ' ' . htmlspecialchars($notification['property_owner']) . ' - </a>' . htmlspecialchars($notification['status']) . ' ' . htmlspecialchars($notification['created_at']);
                            if ($notification['status'] === 'awaiting') {
                                echo ' <span class="new-notification">New</span>';
                            }
                            echo '</li>';
                        }
                    } else {
                        echo '<li>No new notifications</li>';
                    }
                    ?>
                </ul>
            </div>
        </div>
        
        <div class="dashboard-card">
            <h3>Quick Actions</h3>
            <ul>
                <li><a href="calculate_rate.php">Calculate Rates</a></li>
                <li><a href="reports.php">Reports</a></li>
                <li><a href="application.php">Apply for Rates</a></li>
                <li><a href="view_history.php">View All Applications</a></li>
                <li><a href="download_documents.php">Download Documents</a></li>
            </ul>
        </div>
    </div>
</main>

<div id="notificationsPanel" class="notification-panel">
    <h3>Notifications</h3>
    <ul id="notificationsList">
        <?php
        // Fetch all applications from the database 
        $allApplicationsQuery = "
            SELECT 
                a.application_id, 
                a.status, 
                a.created_at, 
                u.first_name, 
                u.surname, 
                p.address AS property_address, 
                p.owner AS property_owner,
                p.property_id
            FROM 
                rate_clearance_applications a
            JOIN 
                users u ON a.user_id = u.user_id
            JOIN 
                properties p ON a.property_id = p.property_id
            ORDER BY 
                a.created_at DESC
        ";
        $allApplicationsStmt = $pdo->prepare($allApplicationsQuery);
        $allApplicationsStmt->execute();
        $allApplications = $allApplicationsStmt->fetchAll();

        if (count($allApplications) > 0) {
            foreach ($allApplications as $application) {
                echo '<li class="notification-item" data-application-id="' . $application['application_id'] . '">';
                echo '<a href="../includes/fetch_property_details.php?property_id=' . $application['property_id'] . '">' . htmlspecialchars($application['first_name']) . ' ' . htmlspecialchars($application['surname']) . '</a>';
                echo ' - <a href="../includes/fetch_property_details.php?property_id=' . $application['property_id'] . '">' . htmlspecialchars($application['property_address']) . ' ' . htmlspecialchars($application['property_owner']) . '</a>';
                echo ' - ' . htmlspecialchars($application['status']) . ' ' . htmlspecialchars($application['created_at']);
                if ($application['status'] === 'awaiting') {
                    echo ' <span class="new-notification">New</span>';
                }
                echo '</li>';
            }
        } else {
            echo '<li>No applications found</li>';
        }
        ?>
    </ul>
    <button onclick="closeNotifications()">Close</button>
</div>

<script>
    function toggleNotifications() {
        var notificationsPanel = document.getElementById('notificationsPanel');
        if (notificationsPanel.style.display === 'none') {
            notificationsPanel.style.display = 'block';
        } else {
            notificationsPanel.style.display = 'none';
        }
    }

    function closeNotifications() {
        document.getElementById('notificationsPanel').style.display = 'none';
    }

    document.addEventListener('DOMContentLoaded', function() {
        var notificationItems = document.querySelectorAll('.notification-item');
        notificationItems.forEach(function(item) {
            item.addEventListener('click', function() {
                var applicationId = this.getAttribute('data-application-id');
                markNotificationAsRead(applicationId);
            });
        });
    });

    function markNotificationAsRead(applicationId) {
        fetch('../includes/mark_as_read.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'application_id=' + applicationId
        }).then(response => response.json())
          .then(data => {
              if (data.success) {
                  var newNotification = document.querySelector('.new-notification');
                  if (newNotification) {
                      newNotification.remove();
                  }
              }
          });
    }
</script>
</body>
</html>