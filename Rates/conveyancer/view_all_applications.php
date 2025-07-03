<?php
session_start();

// Check if user is logged in and has required session data
if (empty($_SESSION['role']) || $_SESSION['role'] !== 'conveyancer' || 
    empty($_SESSION['user_id']) || empty($_SESSION['username'])) {
    header("Location: ../index.php");
    exit();
}

require_once '../Database/db.php';

$user_id = $_SESSION['user_id'];

// Fetch all applications for the logged-in user
$query = "SELECT application_id, property_id, status, created_at FROM rate_clearance_applications WHERE user_id = :user_id ORDER BY created_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute([':user_id' => $user_id]);
$applications = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Applications - Rate Clearance System</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color: #f4f4f4;
        }
        h1 {
            color: #333;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background-color: #fff;
        }
        th, td {
            padding: 12px;
            border: 1px solid #ccc;
            text-align: left;
        }
        th {
            background-color: #333;
            color: #fff;
        }
        tr:nth-child(even) {
            background-color: #eee;
        }
        a {
            color: blue;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
        .status-approved {
            color: green;
            font-weight: bold;
        }
        .status-pending {
            color: orange;
            font-weight: bold;
        }
        .status-rejected {
            color: red;
            font-weight: bold;
        }
        .nav-links {
            margin-bottom: 20px;
        }
        .nav-links a {
            margin-right: 15px;
        }
    </style>
</head>
<body>
    <div class="nav-links">
        <a href="cdashboard.php">Dashboard</a>
        <a href="logout.php">Logout</a>
    </div>
    <h1>My Applications</h1>
    <?php if (count($applications) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Application ID</th>
                    <th>Property ID</th>
                    <th>Status</th>
                    <th>Submitted On</th>
                    <th>Details</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($applications as $app): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($app['application_id']); ?></td>
                        <td><?php echo htmlspecialchars($app['property_id']); ?></td>
                        <td>
                            <?php 
                                $status = strtolower($app['status']);
                                $class = '';
                                if ($status === 'approved') {
                                    $class = 'status-approved';
                                } elseif ($status === 'pending') {
                                    $class = 'status-pending';
                                } elseif ($status === 'rejected') {
                                    $class = 'status-rejected';
                                }
                            ?>
                            <span class="<?php echo $class; ?>"><?php echo htmlspecialchars($app['status']); ?></span>
                        </td>
                        <td><?php echo date('M j, Y', strtotime($app['created_at'])); ?></td>
                        <td><a href="view_application.php?application_id=<?php echo urlencode($app['application_id']); ?>">View</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No applications found.</p>
    <?php endif; ?>
</body>
</html>
