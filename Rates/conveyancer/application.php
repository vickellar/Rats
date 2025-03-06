<?php
require_once __DIR__ . '/../includes/error_handler.php';
require_once __DIR__ . '/../Database/config.php';

ErrorHandler::initialize();

try {
    $config = include __DIR__ . '/../Database/config.php';
    $dsn = "mysql:host={$config['host']};dbname={$config['db']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['user'], $config['pass'], $config['options']);
} catch (PDOException $e) {
    throw new Exception("Database connection failed: " . $e->getMessage());
}


session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

$user_id = $_SESSION['user_id'];


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // CSRF Protection
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        throw new Exception("Invalid CSRF token!");
    }


    // Process the form data
    $select_property = filter_input(INPUT_POST, 'select_property', FILTER_SANITIZE_NUMBER_INT);
    $property_address = filter_input(INPUT_POST, 'property_address', FILTER_SANITIZE_STRING);
    $property_type = filter_input(INPUT_POST, 'property_type', FILTER_SANITIZE_STRING);
    $lot_number = filter_input(INPUT_POST, 'lot_number', FILTER_SANITIZE_STRING);


    // Fetch applicant details
    $applicant_name = filter_input(INPUT_POST, 'applicant_name', FILTER_SANITIZE_STRING);
    $contact_number = filter_input(INPUT_POST, 'contact_number', FILTER_SANITIZE_STRING);
    $email_address = filter_input(INPUT_POST, 'email_address', FILTER_SANITIZE_EMAIL);
    $mailing_address = filter_input(INPUT_POST, 'mailing_address', FILTER_SANITIZE_STRING);
    $relationship = filter_input(INPUT_POST, 'relationship', FILTER_SANITIZE_STRING);


    // Fetch additional information
    $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);


    // Handle file uploads
    $upload_dir = __DIR__ . '/../uploads/';
    if (!is_dir($upload_dir)) {
        if (!mkdir($upload_dir, 0755, true)) {
            throw new Exception("Failed to create upload directory");
        }
    }


    $allowed_types = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'application/pdf' => 'pdf'
    ];
    $max_size = 2 * 1024 * 1024; // 2 MB


    $files = [
        'title_deed' => $_FILES['title_deed'],
        'previous_certificate' => $_FILES['previous_certificate'],
        'identity_proof' => $_FILES['identity_proof'],
        'additional_documents' => $_FILES['additional_documents'],
    ];

    foreach ($files as $key => $file) {
        if ($file['size'] > 0) {
            $file_name = basename($file['name']);
            $file_name = preg_replace("/[^a-zA-Z0-9\._-]/", "", $file_name);
            $file_type = mime_content_type($file['tmp_name']);
            
            if (!array_key_exists($file_type, $allowed_types)) {
                throw new Exception("Invalid file type for $key!");
            }
            
            if ($file['size'] > $max_size) {
                throw new Exception("File size exceeds the maximum limit for $key!");
            }
            
            $extension = $allowed_types[$file_type];
            $random_name = bin2hex(random_bytes(16)) . '.' . $extension;
            $file_path = $upload_dir . $random_name;
            
            if (!move_uploaded_file($file['tmp_name'], $file_path)) {
                throw new Exception("Failed to move uploaded file for $key");
            }
            
            chmod($file_path, 0640);
            $$key = $file_path;
        }
    }


    // Save form data to database
    try {
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("INSERT INTO applications 
            (user_id, property_id, property_address, property_type, lot_number, 
            applicant_name, contact_number, email_address, mailing_address, 
            relationship, description, title_deed, previous_certificate, 
            identity_proof, additional_documents) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
        $stmt->execute([
            $user_id, $select_property, $property_address, $property_type, $lot_number,
            $applicant_name, $contact_number, $email_address, $mailing_address,
            $relationship, $description, $title_deed ?? null, $previous_certificate ?? null,
            $identity_proof ?? null, $additional_documents ?? null
        ]);
        
        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        throw new Exception("Failed to save application: " . $e->getMessage());
    }


    header('Location: submit_application.php?success=1');
    exit;

}

// Fetch properties from the database
$properties = [];
try {
    $stmt = $pdo->prepare("SELECT id, property_name FROM property");
    $stmt->execute();
    $properties = $stmt->fetchAll();
} catch (Exception $e) {
    throw new Exception("Failed to fetch properties: " . $e->getMessage());
}


// Fetch applicant details from the database
try {
    $stmt = $pdo->prepare("SELECT name, contact_number, email, mailing_address, relationship FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if (!$user) {
        throw new Exception("User not found!");
    }
    
    $applicant_name = $user['name'];
    $contact_number = $user['contact_number'];
    $email_address = $user['email'];
    $mailing_address = $user['mailing_address'];
    $relationship = $user['relationship'];
} catch (Exception $e) {
    throw new Exception("Failed to fetch user details: " . $e->getMessage());
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rate Clearance Application Form</title>
    <style>
        @page{
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
            <select id="select-property" name="select_property">
                <?php foreach ($properties as $property): ?>
                    <option value="<?= $property['id']; ?>"><?= $property['property_name']; ?></option>
                <?php endforeach; ?>
            </select>

            <h3>Upload Required Documents</h3>
            <label for="title-deed">Title Deed/Ownership Proof:</label>
            <input type="file" id="title-deed" name="title_deed" class="file-input" required>

            <label for="previous-certificate">Previous Rate Clearance Certificate (if any):</label>
            <input type="file" id="previous_certificate" name="previous_certificate" class="file-input">

            <label for="identity-proof">Proof of Identity:</label>
            <input type="file" id="identity_proof" name="identity_proof" required>

            <label for="additional-documents">Additional Supporting Documents (optional):</label>
            <input type="file" id="additional_documents" name="additional_documents">

            <h3>Property Details</h3>
            <label for="property-address">Property Address:</label>
            <input type="text" id="property-address" name="property_address" required>

            <label for="property-type">Property Type:</label>
            <select id="property-type" name="property_type" required>
                <option value="residential">Residential</option>
                <option value="commercial">Commercial</option>
                <option value="industrial">Industrial</option>
                <option value="agricultural">Agricultural</option>
            </select>

            <label for="lot-number">Lot/Parcel Number:</label>
            <input type="text" id="lot-number" name="lot_number" required>

            <h3>Applicant Details</h3>
            <label for="applicant-name">Applicant Name:</label>
            <input type="text" id="applicant-name" name="applicant_name" value="<?= $applicant_name; ?>" readonly required>

            <label for="contact-number">Contact Number:</label>
            <input type="tel" id="contact-number" name="contact_number" value="<?= $contact_number; ?>" readonly required>

            <label for="email-address">Email Address:</label>
            <input type="email" id="email-address" name="email_address"

            <label for="mailing-address">Mailing Address:</label>
            <input type="text" id="mailing-address" name="mailing_address" required>

            <label for="relationship">Relationship to Owner:</label>
            <input type="text" id="relationship" name="relationship" required>

            <h3>Additional Information</h3>
            <label for="description">Description:</label>
            <textarea id="description" name="description" rows="4"></textarea>

            <!-- CSRF Protection Token -->
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

            <button type="submit">Submit Application</button>
        </form>
    </div>
</body>
</html>
