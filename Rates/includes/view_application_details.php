<?php

session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

require_once '../Database/db.php';

// Check if application ID is provided
// if (!isset($_GET['id'])) {
//     header("Location: ../error.php?error=Application ID not provided");

//     exit();
// }

//$application_id = $_GET['application_id'];

// Fetch application details
$applicationQuery = "
    SELECT 
        a.application_id, 
        a.status, 
        a.created_at, 
        u.first_name, 
        u.surname, 
        u.address AS applicant_address, 
        u.contact_number, 
        p.address AS property_address, 
        p.owner AS property_owner, 
        p.size, 
        p.type, 
        a.title_deed, 
        a.identity_proof, 
        a.additional_documents
    FROM 
        rate_clearance_applications a
    JOIN 
        users u ON a.user_id = u.user_id
    JOIN 
        properties p ON a.property_id = p.property_id
    WHERE 
        a.application_id = ?
";
$applicationStmt = $pdo->prepare($applicationQuery);
$applicationStmt->execute([$application_id]);
$application = $applicationStmt->fetch();

// Debug: Verify property_id exists
error_log("Debug - Property ID: " . ($application['property_id'] ?? 'NOT FOUND'));

if (!$application) {
    header("Location: ../error.php?error=Application not found");

    exit();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application Details</title>
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
        main {
            padding: 20px;
            max-width: 1000px;
            margin: auto;
            background-color: rgba(255, 255, 255, 0.9);
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .application-details {
            display: flex;
            justify-content: space-between;
        }
        .applicant-details, .property-details {
            flex: 1;
            margin: 10px;
            padding: 20px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        .documents {
            margin-top: 20px;
        }
        .documents a {
            display: block;
            margin: 10px 0;
        }
    </style>
</head>
<body>
<?php include './header.html'; ?>

<main>
    <div class="application-details">
        <div class="applicant-details">
            <h3>Applicant Details</h3>
            <p><strong>Name:</strong> <?php echo htmlspecialchars($application['first_name']) . ' ' . htmlspecialchars($application['surname']); ?></p>
            <p><strong>Address:</strong> <?php echo htmlspecialchars($application['applicant_address']); ?></p>
            <p><strong>Contact Number:</strong> <?php echo htmlspecialchars($application['contact_number']); ?></p>
        </div>
        <div class="property-details">
            <h3>Property Details</h3>
            <p><strong>Owner:</strong> <?php echo htmlspecialchars($application['property_owner']); ?></p>
            <p><strong>Address:</strong> <?php echo htmlspecialchars($application['property_address']); ?></p>
            <p><strong>Size:</strong> <?php echo htmlspecialchars($application['size']); ?> sq meters</p>
            <p><strong>Type:</strong> <?php echo htmlspecialchars($application['type']); ?></p>
        </div>
    </div>
    <div class="documents">
        <h3>Documents</h3>
        <?php if (file_exists($application['title_deed'])): ?>
            <a href="<?php echo htmlspecialchars($application['title_deed']); ?>" target="_blank">Title Deed</a>
        <?php endif; ?>
        <?php if (file_exists($application['identity_proof'])): ?>
            <a href="<?php echo htmlspecialchars($application['identity_proof']); ?>" target="_blank">Proof of Identity</a>
        <?php endif; ?>
        <?php if (file_exists($application['additional_documents'])): ?>
            <a href="<?php echo htmlspecialchars($application['additional_documents']); ?>" target="_blank">Additional Documents</a>
        <?php endif; ?>

    </div>
    <div class="actions">
        <a href="../admin/calculate_rate.php?property_id=<?php echo $application['property_id']; ?>" class="btn">Calculate Rates</a>
    </div>
    <div class="notifications">
        <h3>Notifications</h3>
        <ul>
            <?php
            if (count($notifications) > 0) {
                foreach ($notifications as $notification) {
                    echo '<li>';
                    echo '<a href="../includes/fetch_property_details.php?property_id=' . $notification['property_id'] . '">' . htmlspecialchars($notification['first_name']) . ' ' . htmlspecialchars($notification['surname']) . '</a>';
                    echo ' - <a href="../includes/fetch_property_details.php?property_id=' . $notification['property_id'] . '">' . htmlspecialchars($notification['property_address']) . ' ' . htmlspecialchars($notification['property_owner']) . '</a>';
                    echo ' - ' . htmlspecialchars($notification['status']) . ' ' . htmlspecialchars($notification['created_at']);
                    if ($notification['status'] === 'awaiting') {
                        echo ' <span class="new-notification">New</span>';
                    }
                    echo '</li>';
                }
            } else {
                echo '<li>No notifications found</li>';
            }
            ?>
        </ul>
    </div>

</body>
</html>
