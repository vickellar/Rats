
<?php

session_start(); // Start the session

// Check if user is logged in
if (empty($_SESSION['user_id'])) {
header("Location: index.php");
exit();
}

// Include database connection file
require_once("../Database/db.php");
if (!$pdo) {
die("Database connection failed.");
}

// Fetch properties added by the logged-in user
$userId = $_SESSION['user_id'];
try {
$sql = "SELECT properties.*, GROUP_CONCAT(accounts.account_number) AS account_numbers 
        FROM properties 
        LEFT JOIN accounts ON properties.property_id = accounts.property_id 
        WHERE properties.user_id = :user_id 
        GROUP BY properties.property_id";
$stmt = $pdo->prepare($sql);
$stmt->execute([':user_id' => $userId]);
$properties = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
echo "Database error: " . $e->getMessage();
exit();
}

// Initialize variables for applicant details
$applicant_address = '';
$contact_number = '';
$email_address = '';
$relationship_to_owner = '';
$description = '';

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
// Sanitize and assign form data
$selectedPropertyId = filter_var($_POST['select_property'], FILTER_SANITIZE_NUMBER_INT);
$applicant_address = filter_var($_POST['applicant_address'], FILTER_SANITIZE_STRING);
$contact_number = filter_var($_POST['contact_number'], FILTER_SANITIZE_STRING);
$email_address = filter_var($_POST['email_address'], FILTER_SANITIZE_EMAIL);
$relationship_to_owner = filter_var($_POST['relationship_to_owner'], FILTER_SANITIZE_STRING);
$description = filter_var($_POST['description'], FILTER_SANITIZE_STRING);

// Define the upload directory
$uploadDir = '../uploads/';
$folder_path = $uploadDir . $userId . '/';
if (!is_dir($folder_path)) {
    mkdir($folder_path, 0777, true);
}

// Function to handle file uploads
function handleFileUpload($file, $folder_path) {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return "Error: " . $file['error'];
    }
    $targetFile = $folder_path . basename($file['name']);
    $fileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
    $allowedTypes = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'];
    if (!in_array($fileType, $allowedTypes) || $file["size"] > 5000000) {
        return "Error: Only PDF, JPG, JPEG, PNG, DOC, and DOCX files are allowed, and file size must be less than 5MB.";
    }
    if (!move_uploaded_file($file['tmp_name'], $targetFile)) {
        return "Error: There was an error uploading your file.";
    }
    return basename($targetFile);
}

// Handle file uploads
$title_deed = handleFileUpload($_FILES['title_deed'], $folder_path);
$identity_proof = handleFileUpload($_FILES['identity_proof'], $folder_path);
$additional_documents = handleFileUpload($_FILES['additional_documents'], $folder_path);

// Insert application into the database
$insertSql = "INSERT INTO rate_clearance_applications (user_id, property_id, applicant_address, email_address, relationship_to_owner, description, title_deed, identity_proof, additional_documents, folder_path) 
              VALUES (:user_id, :property_id, :applicant_address, :email_address, :relationship_to_owner, :description, :title_deed, :identity_proof, :additional_documents, :folder_path)";
$insertStmt = $pdo->prepare($insertSql);
try {
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
    header("Location: cdashboard.php");
    exit();
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage();
}
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
