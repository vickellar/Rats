<?php
// Start session
session_start();

// Include database connection
require_once '../Database/db.php';

if ($_SESSION['role'] !== 'conveyancer') {
    header("Location: ../index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Conveyancer Dashboard</title>
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
        background: linear-gradient(170deg, blueviolet, blue);
        color: #fff;
        padding: 10px 20px;
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
        text-align: center;
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
        background-color: rgba(255, 255, 255, 0.9);
        border-radius: 5px;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    }

    .dashboard-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 20px;
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

    .btn {
        display: inline-block;
        padding: 10px 20px;
        margin: 10px 0;
        background-color: #007bff;
        color: white;
        text-decoration: none;
        border-radius: 4px;
    }

    .btn:hover {
        background-color: #0056b3;
    }

    .notification-item, .application-item {
        margin-bottom: 10px;
        border: 1px solid #ddd;
        border-radius: 5px;
        padding: 10px;
        background-color: #fff;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .notification-summary, .application-summary {
        cursor: pointer;
        padding: 10px;
        background-color: #f9f9f9;
        border-bottom: 1px solid #ddd;
    }

    .notification-details, .application-details {
        padding: 10px;
        display: none;
    }
    </style>
</head>

<body>
    <header>
        <img src="../assets/images/mslogo.png" alt="Logo"> <!-- Add your logo -->
        <h1>Welcome, <?php echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Guest'; ?> to
            rate clearance dashboard</h1>
    </header>
    <nav>
        <a href="../index.php?page=home"><i class="fas fa-tachometer-alt"></i> Dashboard</a>

        <div class="dropdown">
            <a href="../index.php?page=register"><i class="fas fa-home"></i> Property Management</a>

            <div class="dropdown-content">
                <a href="add_property.php">Add Property</a>
                <a href="update_property.php">Update Property</a>
                <a href="view_properties.php">View Property</a>
                <a href="delete_property.php">Delete property</a>
            </div>
        </div>
        <div class="dropdown">
            <a href="../index.php?page=login"><i class="fas fa-file-alt"></i> Application Management</a>

            <div class="dropdown-content">
                <a href="view_applications.php">View Applications</a>
                <a href="approve_applications.php">Application Status</a>
                <a href="reject_applications.php">Application History</a>
            </div>
        </div>
        <a href="../includes/logout.php">Log Out</a>
        <a href="../index.php?page=services">Services</a>
    </nav>
    <div class="dashboard-container">
        <div class="dashboard-grid">
            <div class="dashboard-card">
                <h3><i class="fas fa-clock"></i> Recent Applications</h3>

                <?php
                if (isset($_SESSION['user_id'])) {
                    $userId = $_SESSION['user_id'];
                    try {
                        $query = "SELECT a.*, p.address AS property_address, p.owner AS property_owner FROM rate_clearance_applications a JOIN properties p ON a.property_id = p.property_id WHERE a.user_id = ? ORDER BY a.created_at DESC LIMIT 5";
                        $stmt = $pdo->prepare($query);
                        $stmt->bindValue(1, $userId, PDO::PARAM_INT);
                        $stmt->execute();
                        $recentApplications = $stmt->fetchAll(PDO::FETCH_ASSOC);

                        if (count($recentApplications) > 0) {
                            foreach ($recentApplications as $application) {
                                $applicationId = htmlspecialchars($application['application_id']);
                                $propertyAddress = htmlspecialchars($application['property_address']);
                                $propertyOwner = htmlspecialchars($application['property_owner']);
                                $status = htmlspecialchars($application['status']);
                                ?>
                                <div class="application-item">
                                    <div class="application-summary" onclick="toggleDetails(this)">
                                        <p>Application <strong><?php echo $applicationId; ?></strong> - <?php echo $status; ?></p>
                                    </div>
                                    <div class="application-details" style="display: none;">
                                        <p><strong>Property Address:</strong> <?php echo $propertyAddress; ?></p>
                                        <p><strong>Owner:</strong> <?php echo $propertyOwner; ?></p>
                                        <p><strong>Status:</strong> <?php echo $status; ?></p>
                                    </div>
                                </div>
                                <?php
                            }
                        } else {
                            echo '<p>No recent applications found.</p>';
                        }
                    } catch (PDOException $e) {
                        echo '<p>An error occurred while retrieving your applications. Please try again later.</p>';
                        error_log($e->getMessage());
                    }
                } else {
                    echo '<p>User session error. Please login again.</p>';
                }
                ?>
            </div>

            <div class="dashboard-card">
                <h3><i class="fas fa-bell"></i> Notifications</h3>
                <?php
                if (isset($_SESSION['user_id'])) {
                    $userId = $_SESSION['user_id'];
                    try {
                        $query = "SELECT a.application_id, a.status, p.address AS property_address, p.owner AS property_owner FROM rate_clearance_applications a JOIN properties p ON a.property_id = p.property_id WHERE a.user_id = ? ORDER BY a.created_at DESC LIMIT 5";
                        $stmt = $pdo->prepare($query);
                        $stmt->bindValue(1, $userId, PDO::PARAM_INT);
                        $stmt->execute();
                        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

                        if (count($notifications) > 0) {
                            foreach ($notifications as $notification) {
                                $applicationId = htmlspecialchars($notification['application_id']);
                                $propertyAddress = htmlspecialchars($notification['property_address']);
                                $propertyOwner = htmlspecialchars($notification['property_owner']);
                                $status = htmlspecialchars($notification['status']);
                                ?>
                                <div class="notification-item">
                                    <div class="notification-summary" onclick="toggleDetails(this)">
                                        <p>Application <strong><?php echo $applicationId; ?></strong> - <?php echo $status; ?></p>
                                    </div>
                                    <div class="notification-details" style="display: none;">
                                        <p><strong>Property Address:</strong> <?php echo $propertyAddress; ?></p>
                                        <p><strong>Owner:</strong> <?php echo $propertyOwner; ?></p>
                                        <p><strong>Status:</strong> <?php echo $status; ?></p>
                                    </div>
                                </div>
                                <?php
                            }
                        } else {
                            echo '<p>No new notifications</p>';
                        }
                    } catch (PDOException $e) {
                        echo '<p>An error occurred while retrieving notifications. Please try again later.</p>';
                        error_log($e->getMessage());
                    }
                } else {
                    echo '<p>User session error. Please login again.</p>';
                }
                ?>
            </div>

            <div class="dashboard-card">
                <h3><i class="fas fa-cogs"></i> Quick Actions</h3>
                <ul>
                    <li><a href="add_property.php">Add New Property</a></li>
                    <li><a href="view_properties.php">View Property</a></li>
                    <li><a href="apply_rates.php">Apply for Rates</a></li>
                    <li><a href="view_applications.php">View All Applications</a></li>
                </ul>
            </div>
        </div>
    </div>

    <script>
    function toggleDetails(summaryElement) {
        var detailsElement = summaryElement.nextElementSibling;
        if (detailsElement.style.display === 'none') {
            detailsElement.style.display = 'block';
        } else {
            detailsElement.style.display = 'none';
        }
    }
    </script>
</body>

</html>