<?php
// Database configuration 
$servername = "localhost";
$username = "root";
$password = "";
$database = "rate_clearance";

// Create connection
$conn = mysqli_connect($servername, $username, $password, $database);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Initialize counts
$total_requests = 0;
$pending_requests = 0;
$new_applications = 0;

// Fetch total number of requests
$total_query = "SELECT COUNT(*) AS total FROM clearance_requests";
$total_result = mysqli_query($conn, $total_query);
if ($total_result) {
    $row = mysqli_fetch_assoc($total_result);
    $total_requests = $row['total'];
}

// Fetch total number of pending requests
$pending_query = "SELECT COUNT(*) AS pending FROM clearance_requests WHERE status = 'Pending'";
$pending_result = mysqli_query($conn, $pending_query);
if ($pending_result) {
    $row = mysqli_fetch_assoc($pending_result);
    $pending_requests = $row['pending'];
}

// Fetch total number of new applications (submitted today)
$new_query = "SELECT COUNT(*) AS new FROM clearance_requests WHERE DATE(request_date) = CURDATE()";
$new_result = mysqli_query($conn, $new_query);
if ($new_result) {
    $row = mysqli_fetch_assoc($new_result);
    $new_applications = $row['new'];
}

// Close connection
mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clearance Requests Dashboard</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .dashboard {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }
        .card {
            background-color: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            text-align: center;
            width: 200px;
        }
        .card h3 {
            margin: 0;
            font-size: 24px;
            color: #333;
        }
        .card p {
            margin: 10px 0 0;
            font-size: 18px;
            color: #777;
        }
    </style>
</head>
<body>
    <h2>Clearance Requests Dashboard</h2>
    <div class="dashboard">
        <div class="card">
            <h3><?php echo $total_requests; ?></h3>
            <p>Total Requests</p>
        </div>
        <div class="card">
            <h3><?php echo $pending_requests; ?></h3>
            <p>Pending Requests</p>
        </div>
        <div class="card">
            <h3><?php echo $new_applications; ?></h3>
            <p>New Applications</p>
        </div>
    </div>
</body>
</html>