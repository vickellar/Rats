<?php
session_start(); // Start the session

// Include database connection file
require_once("../Database/db.php");

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page if not logged in
    header("Location: ../signin.php");
    exit();
}

// Fetch properties added by the logged-in user
$userId = $_SESSION['user_id'];
$sql = "SELECT properties.*, GROUP_CONCAT(accounts.account_number) AS account_numbers 
        FROM properties 
        LEFT JOIN accounts ON properties.id = accounts.property_id 
        WHERE properties.user_id = :user_id 
        GROUP BY properties.id";

$stmt = $pdo->prepare($sql);
$stmt->execute([':user_id' => $userId]);

$properties = $stmt->fetchAll(PDO::FETCH_ASSOC);

$applicant_name = ''; // Initialize variables for applicant details
$contact_number = '';
$email_address = '';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rate Clearance Application Form</title>
    <style>
        @page {
            size: A4;
        }
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .container {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            width: 90%;
            max-width: 600px;
        }
        h2, h3 {
            color: #333;
        }
        label {
            display: block;
            margin-top: 10px;
            color: #555;
        }
        input, select, textarea {
            width: 100%;
            padding: 8px;
            margin-top: 5px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        button {
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        button:hover {
            background-color: #45a049;
        }
        .file-input {
            padding: 8px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Rate Clearance Application Form</h2>
        <form action="rate_clearance_form.php" method="post" enctype="multipart/form-data">
            <label for="select-property">Select Property:</label>
            <select id="select-property" name="select_property" required onchange="fetchPropertyDetails(this.value)">
                <option value="">Select a property</option>
                <?php foreach ($properties as $property): ?>
                    <option value="<?= htmlspecialchars($property['id']); ?>"><?= htmlspecialchars($property['address']); ?></option>
                <?php endforeach; ?>
            </select>

            <h3>Property Details</h3>
            <div id="property-details">
                <!-- Property details will be displayed here -->
            </div>

            <h3>Upload Required Documents</h3>
            <label for="title-deed">Title Deed/Ownership Proof:</label>
            <input type="file" id="title-deed" name="title_deed" class="file-input" required>

            <label for="previous_certificate">Previous Rate Clearance Certificate (if any):</label>
            <input type="file" id="previous_certificate" name="previous_certificate" class="file-input">

            <label for="identity_proof">Proof of Identity:</label>
            <input type="file" id="identity_proof" name="identity_proof" required>

            <label for="additional_documents">Additional Supporting Documents (optional):</label>
            <input type="file" id="additional_documents" name="additional_documents">

            <h3>Applicant Details</h3>
            <label for="applicant-name">Applicant Name:</label>
            <input type="text" id="applicant-name" name="applicant_name" value="<?= htmlspecialchars($applicant_name); ?>" readonly required>

            <label for="contact-number">Contact Number:</label>
            <input type="tel" id="contact-number" name="contact_number" value="<?= htmlspecialchars($contact_number); ?>" readonly required>

            <label for="email-address">Email Address:</label>
            <input type="email" id="email-address" name="email_address" value="<?= htmlspecialchars($email_address); ?>" readonly required>

            <label for="relationship">Relationship to Owner:</label>
            <input type="text" id="relationship" name="relationship">

            <h3>Additional Information</h3>
            <label for="description">Description:</label>
            <textarea id="description" name="description" rows="4"></textarea>

            <!-- CSRF Protection Token -->
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']); ?>">

            <button type="submit">Submit Application</button>
            <a href="cdashboard.php" style="display: inline-block; margin-top: 10px; padding: 10px; background-color: blue; color: white; text-decoration: none; border-radius: 4px;">Back to Dashboard</a>

        </form>
    </div>
    <script>
    function fetchPropertyDetails(propertyId) {
        // Use AJAX to fetch property details based on selected property
        var xhr = new XMLHttpRequest();
        xhr.open("POST", "./fetch_property_details.php", true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        xhr.onreadystatechange = function () {
            if (xhr.readyState === 4 && xhr.status === 200) {
                document.getElementById("property-details").innerHTML = xhr.responseText;
            }
        };
        xhr.send("property_id=" + propertyId);
    }
    </script>
</body>
</html>
