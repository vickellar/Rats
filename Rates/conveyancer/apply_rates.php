<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', '../logfile/php_error.log');

session_start(); // Start the session

// Check if user is logged in and has required session data
if (empty($_SESSION['role']) || $_SESSION['role'] !== 'conveyancer' || 
    empty($_SESSION['user_id']) || empty($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

// Generate CSRF token if not set
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Check if the session has expired
if (!isset($_SESSION['last_activity'])) {
    $_SESSION['last_activity'] = time(); // Store last activity time
} else {
    if (time() - $_SESSION['last_activity'] > 1800) { // 30 minutes
        session_unset();
        session_destroy();
        header("Location: ./signin.php");
        exit();
    }
}
$_SESSION['last_activity'] = time(); // Update last activity time

// Include database connection file
require_once("../Database/db.php");
if (!$pdo) {
    die("Database connection failed.");
}

if (!isset($_SESSION['user_id'])) {
    header("Location: ../signin.php");
    echo "User ID not set.";
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
    file_put_contents('../logfile/database_errors.log', "\n\n" . date('Y-m-d H:i:s') . " - Database connection failed: " . $e->getMessage()  . PHP_EOL, FILE_APPEND);
    include('../includes/catch_error.php');
    exit();
}

$properties = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Initialize variables for applicant details
$applicant_address = '';
$contact_number = '';
$email_address = '';
$relationship_to_owner = '';
$description = '';

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        echo "Invalid CSRF token.";
        exit();
    }

    // Validate and assign form data
    $selectedPropertyId = $_POST['select_property'] ?? null;
    $applicant_address = $_POST['applicant_address'] ?? '';
    $contact_number = $_POST['contact_number'] ?? '';
    $email_address = $_POST['email_address'] ?? '';
    $relationship_to_owner = $_POST['relationship_to_owner'] ?? '';
    $description = $_POST['description'] ?? '';

    // Handle file uploads
    $title_deed = $_FILES['title_deed']['name'] ?? '';
    $identity_proof = $_FILES['identity_proof']['name'] ?? '';
    $additional_documents = $_FILES['additional_documents']['name'] ?? '';

    // Define the upload directory
    $uploadDir = '../uploads/';
    $errors = [];
    $folder_path = $folder_path;

    // Function to handle file uploads
    function handleFileUpload($file, $folder_path) {
        $targetFile = $folder_path . basename($file["pdfile"]['name']);
        $fileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));

        // Allow only specific file formats
        if (!in_array($fileType, ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx']) || $_FILES["pdffile"]["size"] > 5000000 ) {
            return "Error: Only PDF, JPG, JPEG, and PNG files are allowed.";
        }
        // Move the file to the upload directory
        if (!move_uploaded_file($file["pdffile"]['tmp_name'], $targetFile)) {
            return "Error: There was an error uploading your file.";
        }
        return $file['name'];
    }
    
    

    // Handle each file upload
    $title_deed = handleFileUpload($_FILES['title_deed'], $folder_path);
    if (is_string($title_deed)) $errors[] = $title_deed; // Store error if any

    $identity_proof = handleFileUpload($_FILES['identity_proof'], $folder_path);
    if (is_string($identity_proof)) $errors[] = $identity_proof;

    $additional_documents = handleFileUpload($_FILES['additional_documents'], $folder_path);
    if (is_string($additional_documents)) $errors[] = $additional_documents;

    // If there are errors, display them
    if (!empty($errors)) {
        foreach ($errors as $error) {
            echo "<p>$error</p>";
        }
        exit();
    }

    // Insert application into the database
    $insertSql = "INSERT INTO rate_clearance_applications (user_id, property_id, applicant_address, email_address, relationship_to_owner, description, title_deed, identity_proof, additional_documents, folder_path) 
                  VALUES (:user_id, :property_id, :applicant_address, :email_address, :relationship_to_owner, :description, :title_deed, :identity_proof, :additional_documents, :folder_path)";

    $insertStmt = $pdo->prepare($insertSql);
    $insertStmt->execute([
        ':user_id' => $userId,
        ':property_id' => $selectedPropertyId,
        ':applicant_address' => $applicant_address,
        ':email_address' => $email_address,
        ':relationship_to_owner' => $relationship_to_owner,
        ':description' => $description,
        ':title_deed' => $title_deed,
        ':identity_proof' => $identity_proof,
        ':additional_documents' => $additional_documents,
        ':folder_path' => $folder_path
    ]);

    echo "Application submitted successfully!";
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rate Clearance Application Form</title>
    <style>
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
    </style>
</head>
<body>
    <div class="container">
        <h2>Rate Clearance Application Form</h2>
        <form action="" method="post" enctype="multipart/form-data">
            <label for="select-property">Select Property:</label>
            <select id="select-property" name="select_property" required>
                <option value="">Select a property</option>
                <?php foreach ($properties as $property): ?>
                <option value="<?= htmlspecialchars($property['property_id']); ?>">
                    <?= htmlspecialchars($property['address']); ?> (Accounts: <?= htmlspecialchars($property['account_numbers']); ?>)
                </option>
                <?php endforeach; ?>
            </select>

            <h3>Upload Required Documents</h3>
            <label for="title-deed">Title Deed/Ownership Proof:</label>
            <input type="file" id="title-deed" name="title_deed" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" required>

            <label for="identity_proof">Proof of Identity:</label>
            <input type="file" id="identity_proof" name="identity_proof" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" required>

            <label for="additional_documents">Additional Supporting Documents (optional):</label>
            <input type="file" id="additional_documents" name="additional_documents" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">

            <h3>Applicant Details</h3>
            <label for="applicant-address">Applicant Address:</label>
            <input type="text" id="applicant-address" name="applicant_address" value="<?= htmlspecialchars($applicant_address); ?>" required>

            <label for="email-address">Email Address:</label>
            <input type="email" id="email-address" name="email_address" value="<?= htmlspecialchars($email_address); ?>" required>

            <label for="relationship">Relationship to Owner:</label>
            <input type="text" id="relationship" name="relationship_to_owner" value="<?= htmlspecialchars($relationship_to_owner); ?>">

            <h3>Additional Information</h3>
            <label for="description">Description:</label>
            <textarea id="description" name="description" rows="4"><?= htmlspecialchars($description); ?></textarea>

            <!-- CSRF Protection Token -->
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']); ?>">

            <button type="submit">Submit Application</button>
            <a href="cdashboard.php" style="display: inline-block; margin-top: 10px; padding: 10px; background-color: blue; color: white; text-decoration: none; border-radius: 10px;">Back to Dashboard</a>
        </form>
    </div>
</body>
</html>