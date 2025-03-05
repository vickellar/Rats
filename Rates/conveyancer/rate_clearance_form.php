<?php

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../signin.php");
    exit();
}

// Generate CSRF token if it doesn't exist
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Include database connection file
require_once("../Database/db.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // CSRF token validation
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        echo "Invalid CSRF token.";
        exit();
    }

    // Property validation
    $userId = $_SESSION['user_id'];
    $selectedPropertyId = $_POST['select_property'];
    $propertySql = "SELECT * FROM properties WHERE id = :property_id AND user_id = :user_id";
    $propertyStmt = $pdo->prepare($propertySql);
    $propertyStmt->execute([':property_id' => $selectedPropertyId, ':user_id' => $userId]);
    $propertyDetails = $propertyStmt->fetch(PDO::FETCH_ASSOC);
    if (!$propertyDetails) {
        echo "Error: The selected property does not belong to you.";
        exit();
    }

    // File handling
    $titleDeedPath = ''; // Save file paths
    $previousCertificatePath = '';
    $identityProofPath = '';
    $additionalDocumentsPath = '';

    // Save uploaded files
    if (isset($_FILES['title_deed']) && $_FILES['title_deed']['error'] == UPLOAD_ERR_OK) {
        $titleDeedPath = 'uploads/' . basename($_FILES['title_deed']['name']);
        move_uploaded_file($_FILES['title_deed']['tmp_name'], $titleDeedPath);
    }

    if (isset($_FILES['previous_certificate']) && $_FILES['previous_certificate']['error'] == UPLOAD_ERR_OK) {
        $previousCertificatePath = 'uploads/' . basename($_FILES['previous_certificate']['name']);
        move_uploaded_file($_FILES['previous_certificate']['tmp_name'], $previousCertificatePath);
    }

    if (isset($_FILES['identity_proof']) && $_FILES['identity_proof']['error'] == UPLOAD_ERR_OK) {
        $identityProofPath = 'uploads/' . basename($_FILES['identity_proof']['name']);
        move_uploaded_file($_FILES['identity_proof']['tmp_name'], $identityProofPath);
    }

    if (isset($_FILES['additional_documents']) && $_FILES['additional_documents']['error'] == UPLOAD_ERR_OK) {
        $additionalDocumentsPath = 'uploads/' . basename($_FILES['additional_documents']['name']);
        move_uploaded_file($_FILES['additional_documents']['tmp_name'], $additionalDocumentsPath);
    }

    // Insert application details into database
    $sql = "INSERT INTO rate_clearance_applications (user_id, property_id, applicant_name, contact_number, email_address, relationship_to_owner, description, title_deed, previous_certificate, identity_proof, additional_documents) 
            VALUES (:user_id, :property_id, :applicant_name, :contact_number, :email_address, :relationship_to_owner, :description, :title_deed, :previous_certificate, :identity_proof, :additional_documents)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':user_id' => $userId,
        ':property_id' => $selectedPropertyId,
        ':applicant_name' => $_POST['applicant_name'],
        ':contact_number' => $_POST['contact_number'],
        ':email_address' => $_POST['email_address'],
        ':relationship_to_owner' => $_POST['relationship_to_owner'],
        ':description' => $_POST['description'],
        ':title_deed' => $titleDeedPath,
        ':previous_certificate' => $previousCertificatePath,
        ':identity_proof' => $identityProofPath,
        ':additional_documents' => $additionalDocumentsPath
    ]);

    // Display confirmation message
    echo "Application submitted successfully!";
}
?>