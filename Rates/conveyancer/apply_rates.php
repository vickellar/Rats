<?php
session_start(); // Start the session

// Generate CSRF token if not set
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Check if the session has expired
if (!isset($_SESSION['last_activity'])) {
    $_SESSION['last_activity'] = time(); // Store last activity time
} else {
    if (time() - $_SESSION['last_activity'] > 1800) { // 30 minutes
        session_unset(); // Unset session variables
        session_destroy(); // Destroy the session
        header("Location: ../signin.php"); // Redirect to login page
        exit();
    }
}
$_SESSION['last_activity'] = time(); // Update last activity time

// Include database connection file
require_once("../Database/db.php");

if (!isset($_SESSION['user_id'])) {
    // Redirect to login page if not logged in
    header("Location: ../signin.php");
    exit();
}

// Fetch properties added by the logged-in user
$userId = $_SESSION['user_id'];
try {
    $sql = "SELECT properties.*, GROUP_CONCAT(accounts.account_number) AS account_numbers 
            FROM properties 
            LEFT JOIN accounts ON properties.id = accounts.property_id 
            WHERE properties.user_id = :user_id 
            GROUP BY properties.property_id";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':user_id' => $userId]);
} catch (PDOException $e) {
    // Handle database error
   file_put_contents('../logfile/database_errors.log', date('Y-m-d H:i:s') . " - Database connection failed: " . $e->getMessage() . PHP_EOL, FILE_APPEND);
    exit();
}

$properties = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Initialize variables for applicant details
$applicant_name = '';
$contact_number = '';
$email_address = '';
$relationship_to_owner = '';
$description = '';

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        // CSRF token validation failed
        echo "Invalid CSRF token.";
        exit();
    }

    // Validate and assign form data
    $selectedPropertyId = $_POST['select_property'] ?? null;
    $applicant_name = $_POST['applicant_name'] ?? '';
    $contact_number = $_POST['contact_number'] ?? '';
    $email_address = $_POST['email_address'] ?? '';
    $relationship_to_owner = $_POST['relationship_to_owner'] ?? '';
    $description = $_POST['description'] ?? '';

    // Handle file uploads
    $title_deed = $_FILES['title_deed']['name'] ?? '';
    $previous_certificate = $_FILES['previous_certificate']['name'] ?? '';
    $identity_proof = $_FILES['identity_proof']['name'] ?? '';
    $additional_documents = $_FILES['additional_documents']['name'] ?? '';

    // Define the upload directory
    $uploadDir = '../uploads/';
    $errors = [];

    // Function to handle file uploads
    function handleFileUpload($file, $uploadDir) {
        $targetFile = $uploadDir . basename($file['name']);
        $fileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
        // Check file size (limit to 2MB)
        if ($file['size'] > 2000000) {
            return "Error: File is too large.";
        }
        // Allow only specific file formats
        if (!in_array($fileType, ['pdf', 'jpg', 'jpeg', 'png'])) {
            return "Error: Only PDF, JPG, JPEG, and PNG files are allowed.";
        }
        // Move the file to the upload directory
        if (!move_uploaded_file($file['tmp_name'], $targetFile)) {
            return "Error: There was an error uploading your file.";
        }
        return $file['name'];
    }

    // Handle each file upload
    $title_deed = handleFileUpload($_FILES['title_deed'], $uploadDir);
    if (is_string($title_deed)) $errors[] = $title_deed; // Store error if any

    $previous_certificate = handleFileUpload($_FILES['previous_certificate'], $uploadDir);
    if (is_string($previous_certificate)) $errors[] = $previous_certificate;

    $identity_proof = handleFileUpload($_FILES['identity_proof'], $uploadDir);
    if (is_string($identity_proof)) $errors[] = $identity_proof;

    $additional_documents = handleFileUpload($_FILES['additional_documents'], $uploadDir);
    if (is_string($additional_documents)) $errors[] = $additional_documents;

    // If there are errors, display them
    if (!empty($errors)) {
        foreach ($errors as $error) {
            echo "<p>$error</p>";
        }
        exit();
    }

    // Insert application into the database
    $insertSql = "INSERT INTO rate_clearance_applications (user_id, property_id, applicant_name, contact_number, email_address, relationship_to_owner, description, title_deed, previous_certificate, identity_proof, additional_documents) 
                  VALUES (:user_id, :property_id, :applicant_name, :contact_number, :email_address, :relationship_to_owner, :description, :title_deed, :previous_certificate, :identity_proof, :additional_documents)";

    $insertStmt = $pdo->prepare($insertSql);
    $insertStmt->execute([
        ':user_id' => $userId,
        ':property_id' => $selectedPropertyId,
        ':applicant_name' => $applicant_name,
        ':contact_number' => $contact_number,
        ':email_address' => $email_address,
        ':relationship_to_owner' => $relationship_to_owner,
        ':description' => $description,
        ':title_deed' => $title_deed,
        ':previous_certificate' => $previous_certificate,
        ':identity_proof' => $identity_proof,
        ':additional_documents' => $additional_documents
    ]);

    // Redirect or display success message
    header("Location: success.php");
    exit();
}
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
            <select id="select-property" name="select_property" required>
                <option value="">Select a property</option>
                <?php foreach ($properties as $property): ?>
                <option value="<?= htmlspecialchars($property['id']); ?>" <?php if (isset($_POST['select_property']) && $_POST['select_property'] == $property['id']) echo 'selected'; ?>>
                    <?= htmlspecialchars($property['address']); ?> (Accounts: <?= htmlspecialchars($property['account_numbers']); ?>)
                </option>
                <?php endforeach; ?>
            </select>

            <h3>Property Details</h3>
            <div id="property-details">
                <?php
                if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['select_property'])) {
                    $selectedPropertyId = $_POST['select_property'];
                    $propertySql = "SELECT * FROM properties WHERE id = :property_id AND user_id = :user_id";
                    $propertyStmt = $pdo->prepare($propertySql);
                    $propertyStmt->execute([':property_id' => $selectedPropertyId, ':user_id' => $userId]);
                    $propertyDetails = $propertyStmt->fetch(PDO::FETCH_ASSOC);
                    if ($propertyDetails) {
                        include_once("../includes/fetch_property_details.php");
                    } else {
                        echo "Error: The selected property does not belong to you.";
                    }
                }
                ?>
            </div>

            <h3>Upload Required Documents</h3>
            
            <label for="title-deed">Title Deed/Ownership Proof:</label>
            <input type="file" id="title-deed" name="title_deed" class="file-input" accept=".pdf,.jpg,.jpeg,.png">

            <label for="previous_certificate">Previous Rate Clearance Certificate (if any):</label>
            <input type="file" id="previous_certificate" name="previous_certificate" class="file-input">

            <label for="identity_proof">Proof of Identity:</label>
            <input type="file" id="identity_proof" name="identity_proof" accept=".pdf,.jpg,.jpeg,.png">

            <label for="additional_documents">Additional Supporting Documents (optional):</label>
            <input type="file" id="additional_documents" name="additional_documents" accept=".pdf,.jpg,.jpeg,.png">

            <h3>Applicant Details</h3>
            <label for="applicant-name">Applicant Name:</label>
            <input type="text" id="applicant-name" name="applicant_name" value="<?= htmlspecialchars($applicant_name); ?>" required>
            
            <label for="contact-number">Contact Number:</label>
            <input type="tel" id="contact-number" name="contact_number" value="<?= htmlspecialchars($contact_number); ?>" required>
            
            <label for="email-address">Email Address:</label>
            <input type="email" id="email-address" name="email_address" value="<?= htmlspecialchars($email_address); ?>" required>
            
            <label for="relationship">Relationship to Owner:</label>
            <input type="text" id="relationship" name="relationship_to_owner">

            <h3>Additional Information</h3>
            <label for="description">Description:</label>
            <textarea id="description" name="description" rows="4"><?= htmlspecialchars($description); ?></textarea>

            <!-- CSRF Protection Token -->
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']); ?>">

            <button type="submit">Submit Application</button>
            <a href="cdashboard.php" style="display: inline-block; margin-top: 10px; padding: 10px; background-color: blue; color: white; text-decoration: none; border-radius: 10px;">Back to Dashboard</a>
        </form>
    </div>

    <script>
        document.getElementById('select-property').addEventListener('change', function() {
            var propertyId = this.value;
            var xhr = new XMLHttpRequest();
            xhr.open("POST", "fetch_property_details.php", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    document.getElementById("property-details").innerHTML = xhr.responseText;
                } else if (xhr.readyState === 4) {
                    // Handle AJAX error
                    document.getElementById("property-details").innerHTML = 'Error fetching property details. Please try again.';
                }
            };
            xhr.send("property_id=" + propertyId);
        });
    </script>
</body>
</html>