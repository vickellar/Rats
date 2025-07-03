<?php
session_start(); // Start the session

// Check if user is logged in
if (empty($_SESSION['user_id'])) {
    header("Location: ../index.php");
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
$success = false;
$error = '';

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and assign form data
    $selectedPropertyId = filter_var($_POST['select_property'], FILTER_SANITIZE_NUMBER_INT);
    $applicant_address = filter_var($_POST['applicant_address'], FILTER_SANITIZE_STRING);
    $email_address = filter_var($_POST['email_address'], FILTER_SANITIZE_EMAIL);
    $description = filter_var($_POST['description'], FILTER_SANITIZE_STRING);

    // Define the upload directory
    $uploadDir = '../uploads/';

    // Remove creation of user-specific subfolder to avoid creating any other folder inside uploads
    $folder_path = $uploadDir;
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

    $additional_documents = '';
    if (isset($_FILES['additional_documents']) && $_FILES['additional_documents']['error'] !== UPLOAD_ERR_NO_FILE) {
        $additional_documents = handleFileUpload($_FILES['additional_documents'], $folder_path);
    }

    // Check for upload errors
    if (strpos($title_deed, 'Error:') === 0 || (is_string($additional_documents) && strpos($additional_documents, 'Error:') === 0)) {
        $error = "File upload failed. Please check your files and try again.";
    } else {
        // Insert application into the database
        $insertSql = "INSERT INTO rate_clearance_applications (
            user_id, property_id, applicant_address, email_address, relationship_to_owner, description, title_deed, additional_documents, folder_path
        ) VALUES (
            :user_id, :property_id, :applicant_address, :email_address, :relationship_to_owner, :description, :title_deed, :additional_documents, :folder_path
        )";
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
                ':additional_documents' => $additional_documents,
                ':folder_path' => $folder_path
            ]);
            $success = true;
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rate Clearance Application Form</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4361ee;
            --primary-hover: #3a56d4;
            --secondary-color: #3f37c9;
            --success-color: #10b981;
            --error-color: #ef4444;
            --warning-color: #f59e0b;
            --text-color: #1f2937;
            --text-light: #6b7280;
            --border-color: #e5e7eb;
            --bg-color: #f9fafb;
            --card-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            --input-shadow: 0 2px 4px rgba(0, 0, 0, 0.06);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 2rem 1rem;
            color: var(--text-color);
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 16px;
            box-shadow: var(--card-shadow);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 2rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="white" opacity="0.1"/><circle cx="75" cy="75" r="1" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            opacity: 0.3;
        }

        .header h2 {
            font-size: 1.75rem;
            font-weight: 600;
            margin: 0;
            position: relative;
            z-index: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
        }

        .form-container {
            padding: 2rem;
        }

        .alert {
            padding: 1rem 1.25rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .alert-success {
            background-color: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: var(--success-color);
        }

        .alert-error {
            background-color: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: var(--error-color);
        }

        .form-section {
            margin-bottom: 2rem;
        }

        .section-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #f3f4f6;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--text-color);
        }

        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
            box-shadow: var(--input-shadow);
            background: white;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
        }

        .file-input-wrapper {
            position: relative;
            display: inline-block;
            width: 100%;
        }

        .file-input {
            position: absolute;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }

        .file-input-label {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            padding: 1rem;
            border: 2px dashed var(--border-color);
            border-radius: 8px;
            background: #f9fafb;
            cursor: pointer;
            transition: all 0.3s ease;
            color: var(--text-light);
            font-weight: 500;
        }

        .file-input-label:hover {
            border-color: var(--primary-color);
            background: rgba(67, 97, 238, 0.05);
            color: var(--primary-color);
        }

        .file-input-label i {
            font-size: 1.25rem;
        }

        .file-requirements {
            font-size: 0.875rem;
            color: var(--text-light);
            margin-top: 0.5rem;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            text-align: center;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background-color: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(67, 97, 238, 0.3);
        }

        .btn-secondary {
            background-color: #6b7280;
            color: white;
        }

        .btn-secondary:hover {
            background-color: #4b5563;
            transform: translateY(-2px);
        }

        .btn-group {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
            flex-wrap: wrap;
        }

        .property-info {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 1rem;
            margin-top: 0.5rem;
            font-size: 0.875rem;
            color: var(--text-light);
        }

        .loading {
            display: none;
            width: 20px;
            height: 20px;
            border: 2px solid #f3f3f3;
            border-top: 2px solid var(--primary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .animate-fade-in {
            animation: fadeIn 0.5s ease-out forwards;
        }

        @media (max-width: 768px) {
            body {
                padding: 1rem;
            }
            
            .form-container {
                padding: 1.5rem;
            }
            
            .header {
                padding: 1.5rem;
            }
            
            .header h2 {
                font-size: 1.5rem;
            }
            
            .btn-group {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h2>
                <i class="fas fa-file-alt"></i>
                Rate Clearance Application Form
            </h2>
        </div>

        <div class="form-container">
            <?php if ($success): ?>
                <div class="alert alert-success animate-fade-in">
                    <i class="fas fa-check-circle"></i>
                    Application submitted successfully! Redirecting to dashboard...
                </div>
                <script>
                    setTimeout(function() {
                        window.location.href = 'cdashboard.php';
                    }, 2000);
                </script>
            <?php elseif ($error): ?>
                <div class="alert alert-error animate-fade-in">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form action="" method="post" enctype="multipart/form-data" id="applicationForm">
                <!-- Property Selection -->
                <div class="form-section">
                    <h3 class="section-title">
                        <i class="fas fa-home"></i>
                        Property Selection
                    </h3>
                    
                    <div class="form-group">
                        <label for="select-property" class="form-label">Select Property *</label>
                        <select id="select-property" name="select_property" class="form-control" required>
                            <option value="">Choose a property from your portfolio</option>
                            <?php foreach ($properties as $property): ?>
                            <option value="<?= htmlspecialchars($property['property_id']); ?>">
                                <?= htmlspecialchars($property['address']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="property-info">
                            <i class="fas fa-info-circle"></i>
                            Select the property for which you want to apply for rate clearance
                        </div>
                    </div>
                </div>

                <!-- Document Upload -->
                <div class="form-section">
                    <h3 class="section-title">
                        <i class="fas fa-upload"></i>
                        Required Documents
                    </h3>
                    
                    <div class="form-group">
                        <label for="title-deed" class="form-label">Application Letter *</label>
                        <div class="file-input-wrapper">
                            <input type="file" id="title-deed" name="title_deed" class="file-input" 
                                   accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" required>
                            <label for="title-deed" class="file-input-label">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <span>Click to upload application letter</span>
                            </label>
                        </div>
                        <div class="file-requirements">
                            Accepted formats: PDF, JPG, JPEG, PNG, DOC, DOCX (Max: 5MB)
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="additional_documents" class="form-label">Additional Supporting Documents</label>
                        <div class="file-input-wrapper">
                            <input type="file" id="additional_documents" name="additional_documents" class="file-input" 
                                   accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                            <label for="additional_documents" class="file-input-label">
                                <i class="fas fa-paperclip"></i>
                                <span>Click to upload supporting documents (optional)</span>
                            </label>
                        </div>
                        <div class="file-requirements">
                            Any additional documents that support your application
                        </div>
                    </div>
                </div>

                <!-- Applicant Details -->
                <div class="form-section">
                    <h3 class="section-title">
                        <i class="fas fa-user"></i>
                        Applicant Details
                    </h3>
                    
                    <div class="form-group">
                        <label for="applicant-address" class="form-label">Applicant Address *</label>
                        <input type="text" id="applicant-address" name="applicant_address" class="form-control"
                               value="<?= htmlspecialchars($applicant_address); ?>" 
                               placeholder="e.g., 294 Muchecheni Street, Harare" required>
                    </div>

                    <div class="form-group">
                        <label for="email-address" class="form-label">Email Address *</label>
                        <input type="email" id="email-address" name="email_address" class="form-control"
                               value="<?= htmlspecialchars($email_address); ?>" 
                               placeholder="your.email@example.com" required>
                    </div>
                </div>

                <!-- Additional Information -->
                <div class="form-section">
                    <h3 class="section-title">
                        <i class="fas fa-info-circle"></i>
                        Additional Information
                    </h3>
                    
                    <div class="form-group">
                        <label for="description" class="form-label">Description</label>
                        <textarea id="description" name="description" class="form-control" rows="4" 
                                  placeholder="Provide any additional details about your application..."><?= htmlspecialchars($description); ?></textarea>
                    </div>
                </div>

                <!-- CSRF Protection Token -->
                <?php if (isset($_SESSION['csrf_token'])): ?>
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']); ?>">
                <?php endif; ?>

                <div class="btn-group">
                    <a href="cdashboard.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i>
                        Back to Dashboard
                    </a>
                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        <i class="fas fa-paper-plane"></i>
                        <span>Submit Application</span>
                        <div class="loading" id="loading"></div>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // File input handling
        document.querySelectorAll('.file-input').forEach(input => {
            input.addEventListener('change', function() {
                const label = this.nextElementSibling;
                const span = label.querySelector('span');
                if (this.files.length > 0) {
                    span.textContent = this.files[0].name;
                    label.style.borderColor = 'var(--success-color)';
                    label.style.backgroundColor = 'rgba(16, 185, 129, 0.05)';
                    label.style.color = 'var(--success-color)';
                } else {
                    span.textContent = span.getAttribute('data-original') || 'Click to upload file';
                    label.style.borderColor = 'var(--border-color)';
                    label.style.backgroundColor = '#f9fafb';
                    label.style.color = 'var(--text-light)';
                }
            });
        });

        // Form submission handling
        document.getElementById('applicationForm').addEventListener('submit', function() {
            const submitBtn = document.getElementById('submitBtn');
            const loading = document.getElementById('loading');
            const span = submitBtn.querySelector('span');
            
            submitBtn.disabled = true;
            loading.style.display = 'block';
            span.textContent = 'Submitting...';
        });

        // Property selection info
        document.getElementById('select-property').addEventListener('change', function() {
            const propertyInfo = document.querySelector('.property-info');
            if (this.value) {
                const selectedOption = this.options[this.selectedIndex];
                propertyInfo.innerHTML = `
                    <i class="fas fa-check-circle" style="color: var(--success-color);"></i>
                    Selected: ${selectedOption.text}
                `;
            } else {
                propertyInfo.innerHTML = `
                    <i class="fas fa-info-circle"></i>
                    Select the property for which you want to apply for rate clearance
                `;
            }
        });
    </script>
</body>

</html>