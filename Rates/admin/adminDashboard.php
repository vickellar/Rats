<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

require_once '../Database/db.php';

$employeeId = $_SESSION['employee_id'] ?? null;

if (!$employeeId) {
    echo "<p style='color:red;'>Employee ID not found in session. Please log in again.</p>";
    exit();
}

$stmt = $pdo->prepare("SELECT * FROM employees WHERE employee_id = ?");
$stmt->execute([$employeeId]);
$employee = $stmt->fetch();

if (!$employee) {
    echo "<p style='color:red;'>Employee not found in database.</p>";
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #333;
        }

        /* Header Styles */
        header {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: white;
            padding: 20px 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            position: relative;
            overflow: hidden;
        }

        header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #ff6b6b, #feca57, #48dbfb, #ff9ff3);
            background-size: 400% 400%;
            animation: gradientShift 3s ease infinite;
        }

        @keyframes gradientShift {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }

        header img {
            width: 100%;
            max-width: 150px;
            height: auto;
            filter: brightness(1.2);
            transition: transform 0.3s ease;
        }

        header img:hover {
            transform: scale(1.05);
        }

        header h1 {
            flex: 1;
            text-align: center;
            font-size: 2rem;
            font-weight: 300;
            letter-spacing: 1px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }

        .notification-icon {
            position: relative;
            cursor: pointer;
            padding: 15px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }

        .notification-icon:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: scale(1.1);
        }

        .notification-icon i {
            font-size: 1.5rem;
            animation: bell-ring 2s infinite;
        }

        @keyframes bell-ring {
            0%, 50%, 100% { transform: rotate(0deg); }
            10%, 30% { transform: rotate(-10deg); }
            20%, 40% { transform: rotate(10deg); }
        }

        .notification-badge {
            position: absolute;
            top: 5px;
            right: 5px;
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a52 100%);
            color: white;
            border-radius: 50%;
            padding: 4px 8px;
            font-size: 0.8rem;
            font-weight: bold;
            min-width: 20px;
            text-align: center;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.2); }
        }

        /* Navigation Styles */
        nav {
            background: linear-gradient(135deg, #1fb5c0 0%, #17a2b8 100%);
            padding: 0;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            position: relative;
        }

        nav::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.5), transparent);
        }

        .nav-container {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        nav a {
            color: white;
            text-decoration: none;
            padding: 15px 20px;
            display: block;
            transition: all 0.3s ease;
            font-weight: 500;
            position: relative;
            overflow: hidden;
        }

        nav a::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        nav a:hover::before {
            left: 100%;
        }

        nav a:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateY(-2px);
        }

        .dropdown {
            position: relative;
            display: inline-block;
        }

        .dropdown-content {
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            min-width: 200px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            z-index: 1000;
            border-radius: 0 0 12px 12px;
            overflow: hidden;
            backdrop-filter: blur(10px);
        }

        .dropdown-content a {
            padding: 12px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .dropdown-content a:last-child {
            border-bottom: none;
        }

        .dropdown-content a:hover {
            background: rgba(255, 255, 255, 0.1);
            padding-left: 30px;
        }

        .dropdown:hover .dropdown-content {
            display: block;
            animation: slideDown 0.3s ease;
        }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Main Content Styles */
        main {
            padding: 30px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 25px;
            margin-top: 20px;
        }

        .dashboard-card {
            background: white;
            border-radius: 16px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .dashboard-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #667eea, #764ba2);
        }

        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }

        .dashboard-card h3 {
            color: #2c3e50;
            font-size: 1.4rem;
            font-weight: 600;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .dashboard-card h3 i {
            color: #667eea;
            font-size: 1.2rem;
        }

        .dashboard-card ul {
            list-style: none;
        }

        .dashboard-card li {
            padding: 12px 0;
            border-bottom: 1px solid #f0f0f0;
            transition: all 0.3s ease;
        }

        .dashboard-card li:last-child {
            border-bottom: none;
        }

        .dashboard-card li:hover {
            padding-left: 10px;
            background: linear-gradient(90deg, rgba(102, 126, 234, 0.05), transparent);
        }

        .dashboard-card a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .dashboard-card a:hover {
            color: #5a6fd8;
            text-decoration: underline;
        }

        /* Notification Panel Styles */
        .notification-panel {
            display: none;
            position: fixed;
            top: 80px;
            right: 20px;
            background: white;
            border-radius: 16px;
            padding: 20px;
            z-index: 1000;
            width: 350px;
            max-height: 500px;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(20px);
            animation: slideInRight 0.3s ease;
        }

        @keyframes slideInRight {
            from { opacity: 0; transform: translateX(100%); }
            to { opacity: 1; transform: translateX(0); }
        }

        .notification-panel h3 {
            color: #2c3e50;
            margin-bottom: 15px;
            font-size: 1.3rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .notification-panel ul {
            list-style: none;
            max-height: 300px;
            overflow-y: auto;
        }

        .notification-panel li {
            padding: 12px;
            border-bottom: 1px solid #f0f0f0;
            border-radius: 8px;
            margin-bottom: 8px;
            background: #f8f9fa;
            transition: all 0.3s ease;
        }

        .notification-panel li:hover {
            background: #e9ecef;
            transform: translateX(5px);
        }

        .notification-panel button {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            margin-top: 15px;
            transition: all 0.3s ease;
        }

        .notification-panel button:hover {
            background: linear-gradient(135deg, #5a6fd8 0%, #6a4190 100%);
            transform: translateY(-2px);
        }

        /* Status Badges */
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-awaiting {
            background: #fff3cd;
            color: #856404;
        }

        .status-approved {
            background: #d4edda;
            color: #155724;
        }

        .status-rejected {
            background: #f8d7da;
            color: #721c24;
        }

        .new-notification {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a52 100%);
            color: white;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 0.7rem;
            font-weight: bold;
            margin-left: 8px;
            animation: glow 2s infinite;
        }

        @keyframes glow {
            0%, 100% { box-shadow: 0 0 5px rgba(255, 107, 107, 0.5); }
            50% { box-shadow: 0 0 20px rgba(255, 107, 107, 0.8); }
        }

        /* Quick Actions Styling */
        .quick-actions li {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 8px;
            margin-bottom: 8px;
            transition: all 0.3s ease;
        }

        .quick-actions li:hover {
            background: linear-gradient(135deg, #e9ecef 0%, #dee2e6 100%);
            transform: scale(1.02);
        }

        .quick-actions a {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 15px;
        }

        .quick-actions a::before {
            content: '→';
            color: #667eea;
            font-weight: bold;
            transition: transform 0.3s ease;
        }

        .quick-actions a:hover::before {
            transform: translateX(5px);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            header {
                padding: 15px 20px;
                flex-direction: column;
                gap: 15px;
            }

            header h1 {
                font-size: 1.5rem;
                text-align: center;
            }

            .nav-container {
                flex-direction: column;
                padding: 0;
            }

            nav a {
                text-align: center;
                border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            }

            .dropdown-content {
                position: static;
                display: block;
                background: rgba(0, 0, 0, 0.1);
                box-shadow: none;
                border-radius: 0;
            }

            main {
                padding: 20px 15px;
            }

            .dashboard-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .notification-panel {
                width: calc(100vw - 40px);
                right: 20px;
                left: 20px;
            }
        }

        /* Loading Animation */
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Scrollbar Styling */
        .notification-panel::-webkit-scrollbar,
        .notification-scroll::-webkit-scrollbar {
            width: 6px;
        }

        .notification-panel::-webkit-scrollbar-track,
        .notification-scroll::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 3px;
        }

        .notification-panel::-webkit-scrollbar-thumb,
        .notification-scroll::-webkit-scrollbar-thumb {
            background: #667eea;
            border-radius: 3px;
        }

        .notification-panel::-webkit-scrollbar-thumb:hover,
        .notification-scroll::-webkit-scrollbar-thumb:hover {
            background: #5a6fd8;
        }
    </style>
</head>
<body>
    <p>Logged in as Employee ID: <?php echo htmlspecialchars($employeeId); ?></p>
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
            echo '<span class="notification-badge">' . $newNotificationCount . '</span>';
        }
        ?>
    </div>
</header>

<nav>
    <div class="nav-container">
        <a href="index.php?page=home"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <div class="dropdown">
            <a href="index.php?page=register"><i class="fas fa-users"></i> User Management</a>
            <div class="dropdown-content">
                <a href="add_user.php"><i class="fas fa-user-plus"></i> Add User</a>
                <a href="view_users.php"><i class="fas fa-eye"></i> View Users</a>
                <a href="delete_user.php"><i class="fas fa-user-minus"></i> Delete User</a>
            </div>
        </div>
        <div class="dropdown">
            <a href="index.php?page=login"><i class="fas fa-file-alt"></i> Application Management</a>
            <div class="dropdown-content">
                <a href="view_history.php"><i class="fas fa-history"></i> View Applications</a>
                <a href="approve_applications.php"><i class="fas fa-check-circle"></i> Approve Applications</a>
                <a href="pending_applications.php"><i class="fas fa-clock"></i> Pending Applications</a>
                <a href="reject_applications.php"><i class="fas fa-times-circle"></i> Reject Applications</a>
            </div>
        </div>
        <a href="../index.php"><i class="fas fa-sign-out-alt"></i> Log Out</a>
    </div>
</nav>

<main>
    <div class="dashboard-grid">
        <div class="dashboard-card">
            <h3><i class="fas fa-file-alt"></i> Recent Applications</h3>
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
                        echo '<li><a href="view_application.php?id=' . $row['id'] . '">' . htmlspecialchars($row['application_ref']) . ' - <span class="status-badge status-' . strtolower($row['status']) . '">' . htmlspecialchars($row['status']) . '</span></a></li>';
                    }
                    echo '</ul>';
                } else {
                    echo '<p style="text-align: center; color: #6c757d; font-style: italic; padding: 20px;"><i class="fas fa-inbox"></i> No recent applications found.</p>';
                }
            } else {
                echo '<p style="color: #dc3545;"><i class="fas fa-exclamation-triangle"></i> User session error. Please login again.</p>';
            }
            ?>
        </div>
        
        <div class="dashboard-card">
            <h3 id="notifications"><i class="fas fa-bell"></i> Notifications</h3>
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
                        LIMIT 5
                    ";
                    $notificationStmt = $pdo->prepare($notificationQuery);
                    $notificationStmt->execute();
                    $notifications = $notificationStmt->fetchAll();

                    if (count($notifications) > 0) {
                        foreach ($notifications as $notification) {
                            echo '<li class="notification-item" data-application-id="' . $notification['application_id'] . '" data-property-id="' . $notification['property_id'] . '">';
                            echo '<div style="font-weight: 600; color: #2c3e50;">' . htmlspecialchars($notification['first_name']) . ' ' . htmlspecialchars($notification['surname']) . '</div>';
                            echo '<div style="font-size: 0.9rem; color: #6c757d; margin: 4px 0;">' . htmlspecialchars($notification['property_address']) . '</div>';
                            echo '<div style="display: flex; justify-content: space-between; align-items: center; margin-top: 8px;">';
                            echo '<span class="status-badge status-' . strtolower($notification['status']) . '">' . htmlspecialchars($notification['status']) . '</span>';
                            if ($notification['status'] === 'awaiting') {
                                echo '<span class="new-notification">New</span>';
                            }
                            echo '</div>';
                            echo '<div style="font-size: 0.8rem; color: #adb5bd; margin-top: 4px;">' . date('M j, Y H:i', strtotime($notification['created_at'])) . '</div>';
                            echo '</li>';
                        }
                    } else {
                        echo '<li style="text-align: center; color: #6c757d; font-style: italic; padding: 20px;"><i class="fas fa-bell-slash"></i> No new notifications</li>';
                    }
                    ?>
                </ul>
            </div>
        </div>
        
        <div class="dashboard-card">
            <h3><i class="fas fa-bolt"></i> Quick Actions</h3>
            <ul class="quick-actions">
                <li><a href="calculate_rate.php"><i class="fas fa-calculator"></i> Calculate Rates</a></li>
                <li><a href="reports.php"><i class="fas fa-chart-bar"></i> Reports</a></li>
                <li><a href="application.php"><i class="fas fa-plus-circle"></i> Apply for Rates</a></li>
                <li><a href="view_history.php"><i class="fas fa-history"></i> View All Applications</a></li>
                <li><a href="download_documents.php"><i class="fas fa-download"></i> Download Documents</a></li>
            </ul>
        </div>
    </div>
</main>

<div id="notificationsPanel" class="notification-panel">
    <h3><i class="fas fa-bell"></i> All Notifications</h3>
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
                echo '<li class="notification-item" data-application-id="' . $application['application_id'] . '" data-property-id="' . $application['property_id'] . '">';
                echo '<div style="font-weight: 600; color: #2c3e50;"><a href="view_application.php?id=' . $application['application_id'] . '">' . htmlspecialchars($application['first_name']) . ' ' . htmlspecialchars($application['surname']) . '</a></div>';
                echo '<div style="font-size: 0.9rem; color: #6c757d; margin: 4px 0;">' . htmlspecialchars($application['property_address']) . '</div>';
                echo '<div style="display: flex; justify-content: space-between; align-items: center; margin-top: 8px;">';
                echo '<span class="status-badge status-' . strtolower($application['status']) . '">' . htmlspecialchars($application['status']) . '</span>';
                if ($application['status'] === 'awaiting') {
                    echo '<span class="new-notification">New</span>';
                }
                echo '</div>';
                echo '<div style="font-size: 0.8rem; color: #adb5bd; margin-top: 4px;">' . date('M j, Y H:i', strtotime($application['created_at'])) . '</div>';
                echo '</li>';
            }
        } else {
            echo '<li style="text-align: center; color: #6c757d; font-style: italic; padding: 20px;"><i class="fas fa-inbox"></i> No applications found</li>';
        }
        ?>
    </ul>
    <button onclick="closeNotifications()"><i class="fas fa-times"></i> Close</button>
</div>

<script>
    function toggleNotifications() {
        var notificationsPanel = document.getElementById('notificationsPanel');
        if (notificationsPanel.style.display === 'none' || notificationsPanel.style.display === '') {
            notificationsPanel.style.display = 'block';
        } else {
            notificationsPanel.style.display = 'none';
        }
    }

    function closeNotifications() {
        document.getElementById('notificationsPanel').style.display = 'none';
    }

    // Close notification panel when clicking outside
    document.addEventListener('click', function(event) {
        var notificationsPanel = document.getElementById('notificationsPanel');
        var notificationIcon = document.querySelector('.notification-icon');
        
        if (!notificationsPanel.contains(event.target) && !notificationIcon.contains(event.target)) {
            notificationsPanel.style.display = 'none';
        }
    });

    document.addEventListener('DOMContentLoaded', function() {
        var notificationItems = document.querySelectorAll('.notification-item');
        notificationItems.forEach(function(item) {
            item.addEventListener('click', function() {
                var applicationId = this.getAttribute('data-application-id');
                var propertyId = this.getAttribute('data-property-id');
                // Mark as read, then redirect with both IDs
                markNotificationAsRead(applicationId, function() {
                    window.location.href = '../includes/fetch_property_details.php?application_id=' + applicationId + '&property_id=' + propertyId;
                });
            });
        });
    });

    function markNotificationAsRead(applicationId, callback) {
        fetch('../includes/mark_as_read.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'application_id=' + applicationId
        }).then(response => response.json())
          .then(data => {
              if (data.success) {
                  var newNotifications = document.querySelectorAll('.new-notification');
                  newNotifications.forEach(function(notification) {
                      notification.style.animation = 'fadeOut 0.5s ease';
                      setTimeout(function() {
                          notification.remove();
                      }, 500);
                  });
                  if (typeof callback === 'function') callback();
              } else {
                  // If marking as read fails, still redirect
                  if (typeof callback === 'function') callback();
              }
          }).catch(error => {
              console.error('Error:', error);
              if (typeof callback === 'function') callback();
          });
    }

    // Add fade out animation
    const style = document.createElement('style');
    style.textContent = `
        @keyframes fadeOut {
            from { opacity: 1; transform: scale(1); }
            to { opacity: 0; transform: scale(0.8); }
        }
    `;
    document.head.appendChild(style);

    // Auto-refresh notifications every 30 seconds
    setInterval(function() {
        // You can implement auto-refresh logic here
        console.log('Auto-refreshing notifications...');
    }, 30000);
</script>
</body>
</html>
