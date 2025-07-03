<?php
session_start(); // Start the session

// Check if user is logged in and is an admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

// Include database connection file
require_once("../Database/db.php");

// Check if property_id is set in the URL
if (isset($_GET['property_id'])) {
    $propertyId = $_GET['property_id'];
    // SQL query to fetch property details and account numbers by property ID
    $sql = "
        SELECT 
            a.application_id,
            a.user_id,
            a.property_id,
            a.applicant_address,
            a.email_address,
            a.relationship_to_owner,
            a.description,
            a.title_deed,
            a.identity_proof,
            a.additional_documents,
            a.status,
            a.created_at,
            a.updated_at,
            p.address,
            p.size,
            p.type,
            p.owner,
            GROUP_CONCAT(ac.account_number SEPARATOR ', ') AS account_numbers,
            p.updated_at 
        FROM 
            rate_clearance_applications a
        LEFT JOIN 
            accounts ac ON a.property_id = ac.property_id
        LEFT JOIN 
            properties p ON a.property_id = p.property_id
        WHERE 
            a.property_id = ?
        GROUP BY 
            a.application_id,
            a.user_id,
            a.property_id,
            a.applicant_address,
            a.email_address,
            a.relationship_to_owner,
            a.description,
            a.title_deed,
            a.identity_proof,
            a.additional_documents,
            a.status,
            a.created_at,
            a.updated_at,
            p.address,
            p.size,
            p.type,
            p.owner,
            p.property_id,
            p.updated_at
    ";

    try {
        // Prepare and execute the query
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(1, $propertyId, PDO::PARAM_INT);
        $stmt->execute();

        // Fetch the property details
        $property = $stmt->fetch(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        $error_message = "Error fetching properties: " . $e->getMessage();
    }
} else {
    $error_message = "No property ID provided.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Property Details - Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
            color: #333;
        }

        .page-container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
            overflow: hidden;
            position: relative;
        }

        .page-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: linear-gradient(90deg, #ff6b6b, #feca57, #48dbfb, #ff9ff3);
            background-size: 400% 400%;
            animation: gradientShift 3s ease infinite;
        }

        @keyframes gradientShift {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }

        /* Header Section */
        .page-header {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: white;
            padding: 30px 40px;
            position: relative;
            overflow: hidden;
        }

        .page-header::after {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 200px;
            height: 200px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 50%;
            transform: translate(50%, -50%);
        }

        .header-content {
            position: relative;
            z-index: 2;
        }

        .page-title {
            font-size: 2.5rem;
            font-weight: 300;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .page-subtitle {
            opacity: 0.9;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .breadcrumb {
            margin-top: 15px;
            opacity: 0.8;
        }

        .breadcrumb a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .breadcrumb a:hover {
            color: white;
        }

        /* Main Content */
        .content-wrapper {
            padding: 40px;
        }

        .property-overview {
            background: linear-gradient(135deg, #e3f2fd 0%, #f3e5f5 100%);
            border-radius: 16px;
            padding: 30px;
            margin-bottom: 30px;
            border-left: 6px solid #2196f3;
            position: relative;
            overflow: hidden;
        }

        .property-overview::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 150px;
            height: 150px;
            background: rgba(33, 150, 243, 0.1);
            border-radius: 50%;
            transform: translate(50%, -50%);
        }

        .overview-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 20px;
        }

        .property-id {
            font-size: 1.8rem;
            font-weight: 600;
            color: #1976d2;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .status-badge {
            padding: 8px 20px;
            border-radius: 25px;
            font-size: 0.9rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .status-pending {
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
            color: #856404;
        }

        .status-approved {
            background: linear-gradient(135deg, #d4edda 0%, #a8e6cf 100%);
            color: #155724;
        }

        .status-rejected {
            background: linear-gradient(135deg, #f8d7da 0%, #ff7675 100%);
            color: #721c24;
        }

        .status-processing {
            background: linear-gradient(135deg, #d1ecf1 0%, #74b9ff 100%);
            color: #0c5460;
        }

        .posted-date {
            color: #6c757d;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Grid Layout */
        .details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 30px;
            margin-bottom: 30px;
        }

        .detail-card {
            background: white;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            border: 1px solid rgba(0, 0, 0, 0.05);
            position: relative;
            overflow: hidden;
        }

        .detail-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #667eea, #764ba2);
        }

        .detail-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.12);
        }

        .card-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f8f9fa;
        }

        .card-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
        }

        .card-icon.property {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .card-icon.applicant {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }

        .card-icon.documents {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }

        .card-title {
            font-size: 1.4rem;
            font-weight: 600;
            color: #2c3e50;
        }

        .detail-item {
            display: flex;
            align-items: flex-start;
            margin-bottom: 18px;
            padding: 12px 0;
            border-bottom: 1px solid #f8f9fa;
        }

        .detail-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }

        .detail-label {
            font-weight: 600;
            color: #495057;
            min-width: 140px;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .detail-value {
            color: #2c3e50;
            font-size: 1rem;
            flex: 1;
            word-break: break-word;
        }

        .detail-value.highlight {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 8px 12px;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            font-weight: 500;
        }

        /* Documents Section */
        .documents-card {
            grid-column: 1 / -1;
        }

        .document-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 20px;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 12px;
            margin-bottom: 15px;
            border: 1px solid #dee2e6;
            transition: all 0.3s ease;
        }

        .document-item:hover {
            background: linear-gradient(135deg, #e9ecef 0%, #dee2e6 100%);
            transform: translateX(5px);
        }

        .document-info {
            display: flex;
            align-items: center;
            gap: 15px;
            flex: 1;
        }

        .document-icon {
            width: 45px;
            height: 45px;
            border-radius: 10px;
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a52 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
        }

        .document-details h4 {
            color: #2c3e50;
            font-size: 1.1rem;
            margin-bottom: 4px;
        }

        .document-details p {
            color: #6c757d;
            font-size: 0.9rem;
        }

        .document-actions {
            display: flex;
            gap: 10px;
        }

        .doc-btn {
            padding: 8px 16px;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .doc-btn.download {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
        }

        .doc-btn.view {
            background: linear-gradient(135deg, #007bff 0%, #6610f2 100%);
            color: white;
        }

        .doc-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        /* Action Buttons */
        .action-section {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 16px;
            padding: 30px;
            text-align: center;
            margin-top: 30px;
            border: 1px solid #dee2e6;
        }

        .action-buttons {
            display: flex;
            justify-content: center;
            gap: 20px;
            flex-wrap: wrap;
        }

        .action-btn {
            padding: 15px 30px;
            border: none;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            min-width: 180px;
            justify-content: center;
        }

        .action-btn.primary {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(0, 123, 255, 0.3);
        }

        .action-btn.secondary {
            background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(108, 117, 125, 0.3);
        }

        .action-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
        }

        /* Error State */
        .error-container {
            text-align: center;
            padding: 60px 40px;
            color: #dc3545;
        }

        .error-container i {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.7;
        }

        .error-container h2 {
            margin-bottom: 15px;
            color: #721c24;
        }

        .error-container p {
            color: #6c757d;
            font-size: 1.1rem;
        }

        /* No Data State */
        .no-data {
            text-align: center;
            padding: 40px;
            color: #6c757d;
            font-style: italic;
        }

        .no-data i {
            font-size: 3rem;
            margin-bottom: 15px;
            opacity: 0.5;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            body {
                padding: 10px;
            }

            .page-header {
                padding: 20px 25px;
            }

            .page-title {
                font-size: 2rem;
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }

            .content-wrapper {
                padding: 25px;
            }

            .details-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .detail-card {
                padding: 20px;
            }

            .overview-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .document-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }

            .document-actions {
                width: 100%;
                justify-content: center;
            }

            .action-buttons {
                flex-direction: column;
                align-items: center;
            }

            .action-btn {
                width: 100%;
                max-width: 300px;
            }
        }

        @media (max-width: 480px) {
            .detail-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }

            .detail-label {
                min-width: auto;
            }

            .doc-btn {
                padding: 6px 12px;
                font-size: 0.8rem;
            }
        }

        /* Loading Animation */
        .loading {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 60px;
        }

        .spinner {
            width: 50px;
            height: 50px;
            border: 5px solid #f3f3f3;
            border-top: 5px solid #007bff;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="page-container">
        <div class="page-header">
            <div class="header-content">
                <h1 class="page-title">
                    <i class="fas fa-building"></i>
                    Property Details
                </h1>
                <p class="page-subtitle">
                    <i class="fas fa-info-circle"></i>
                    Comprehensive property and application information
                </p>
                <div class="breadcrumb">
                    <a href="../admin/adminDashboard.php"><i class="fas fa-home"></i> Dashboard</a>
                    <span> / </span>
                    <span>Property Details</span>
                </div>
            </div>
        </div>

        <div class="content-wrapper">
            <?php if (isset($error_message)): ?>
                <div class="error-container">
                    <i class="fas fa-exclamation-triangle"></i>
                    <h2>Error</h2>
                    <p><?php echo htmlspecialchars($error_message); ?></p>
                </div>
            <?php elseif ($property): ?>
                <!-- Property Overview -->
                <div class="property-overview">
                    <div class="overview-header">
                        <div class="property-id">
                            <i class="fas fa-hashtag"></i>
                            Property ID: <?php echo htmlspecialchars($property['property_id']); ?>
                        </div>
                        <div class="status-badge status-<?php echo strtolower($property['status']); ?>">
                            <i class="fas fa-<?php echo $property['status'] === 'approved' ? 'check-circle' : ($property['status'] === 'rejected' ? 'times-circle' : 'clock'); ?>"></i>
                            <?php echo htmlspecialchars($property['status']); ?>
                        </div>
                    </div>
                    <div class="posted-date">
                        <i class="fas fa-calendar-alt"></i>
                        Application submitted on: <?php echo date('F j, Y \a\t g:i A', strtotime($property['created_at'])); ?>
                    </div>
                </div>

                <!-- Details Grid -->
                <div class="details-grid">
                    <!-- Property Information -->
                    <div class="detail-card">
                        <div class="card-header">
                            <div class="card-icon property">
                                <i class="fas fa-home"></i>
                            </div>
                            <h3 class="card-title">Property Information</h3>
                        </div>
                        
                        <div class="detail-item">
                            <div class="detail-label">
                                <i class="fas fa-hashtag"></i>
                                Property ID
                            </div>
                            <div class="detail-value highlight"><?php echo htmlspecialchars($property['property_id']); ?></div>
                        </div>
                        
                        <div class="detail-item">
                            <div class="detail-label">
                                <i class="fas fa-user"></i>
                                Owner
                            </div>
                            <div class="detail-value"><?php echo htmlspecialchars($property['owner']); ?></div>
                        </div>
                        
                        <div class="detail-item">
                            <div class="detail-label">
                                <i class="fas fa-map-marker-alt"></i>
                                Address
                            </div>
                            <div class="detail-value"><?php echo htmlspecialchars($property['address']); ?></div>
                        </div>
                        
                        <div class="detail-item">
                            <div class="detail-label">
                                <i class="fas fa-ruler-combined"></i>
                                Size
                            </div>
                            <div class="detail-value"><?php echo htmlspecialchars($property['size']); ?> m²</div>
                        </div>
                        
                        <div class="detail-item">
                            <div class="detail-label">
                                <i class="fas fa-tag"></i>
                                Type
                            </div>
                            <div class="detail-value"><?php echo htmlspecialchars($property['type']); ?></div>
                        </div>
                        
                        <div class="detail-item">
                            <div class="detail-label">
                                <i class="fas fa-file-invoice"></i>
                                Account Numbers
                            </div>
                            <div class="detail-value highlight"><?php echo htmlspecialchars($property['account_numbers'] ?: 'No accounts assigned'); ?></div>
                        </div>
                    </div>

                    <!-- Applicant Information -->
                    <div class="detail-card">
                        <div class="card-header">
                            <div class="card-icon applicant">
                                <i class="fas fa-user-tie"></i>
                            </div>
                            <h3 class="card-title">Applicant Information</h3>
                        </div>
                        
                        <div class="detail-item">
                            <div class="detail-label">
                                <i class="fas fa-hashtag"></i>
                                Application ID
                            </div>
                            <div class="detail-value highlight"><?php echo htmlspecialchars($property['application_id']); ?></div>
                        </div>
                        
                        <div class="detail-item">
                            <div class="detail-label">
                                <i class="fas fa-user"></i>
                                User ID
                            </div>
                            <div class="detail-value"><?php echo htmlspecialchars($property['user_id']); ?></div>
                        </div>
                        
                        <div class="detail-item">
                            <div class="detail-label">
                                <i class="fas fa-envelope"></i>
                                Email
                            </div>
                            <div class="detail-value"><?php echo htmlspecialchars($property['email_address']); ?></div>
                        </div>
                        
                        <div class="detail-item">
                            <div class="detail-label">
                                <i class="fas fa-map-marker-alt"></i>
                                Address
                            </div>
                            <div class="detail-value"><?php echo htmlspecialchars($property['applicant_address']); ?></div>
                        </div>
                        
                        <div class="detail-item">
                            <div class="detail-label">
                                <i class="fas fa-users"></i>
                                Relationship
                            </div>
                            <div class="detail-value"><?php echo htmlspecialchars($property['relationship_to_owner']); ?></div>
                        </div>
                        
                        <?php if (!empty($property['description'])): ?>
                        <div class="detail-item">
                            <div class="detail-label">
                                <i class="fas fa-file-text"></i>
                                Description
                            </div>
                            <div class="detail-value"><?php echo nl2br(htmlspecialchars($property['description'])); ?></div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Supporting Documents -->
                <div class="detail-card documents-card">
                    <div class="card-header">
                        <div class="card-icon documents">
                            <i class="fas fa-folder-open"></i>
                        </div>
                        <h3 class="card-title">Supporting Documents</h3>
                    </div>
                    
                    <?php 
                    $hasDocuments = false;
                    $documents = [
                        'identity_proof' => ['title' => 'Identity Proof', 'icon' => 'id-card'],
                        'title_deed' => ['title' => 'Title Deed', 'icon' => 'file-contract'],
                        'additional_documents' => ['title' => 'Additional Documents', 'icon' => 'file-alt']
                    ];
                    
                    foreach ($documents as $key => $doc):
                        if (!empty($property[$key])):
                            $hasDocuments = true;
                            $fileName = basename($property[$key]);
                            $viewUrl = '../uploads/' . rawurlencode($fileName);
                    ?>
                        <div class="document-item">
                            <div class="document-info">
                                <div class="document-icon">
                                    <i class="fas fa-<?php echo $doc['icon']; ?>"></i>
                                </div>
                                <div class="document-details">
                                    <h4><?php echo $doc['title']; ?></h4>
                                    <p><?php echo htmlspecialchars($fileName); ?></p>
                                </div>
                            </div>
                            <div class="document-actions">
                                <a href="download.php?file=<?php echo htmlspecialchars($property[$key]); ?>" class="doc-btn download">
                                    <i class="fas fa-download"></i>
                                    Download
                                </a>
                                <a href="<?php echo htmlspecialchars($viewUrl); ?>" target="_blank" class="doc-btn view">
                                    <i class="fas fa-eye"></i>
                                    View
                                </a>
                            </div>
                        </div>
                    <?php 
                        endif;
                    endforeach;
                    
                    if (!$hasDocuments): 
                    ?>
                        <div class="no-data">
                            <i class="fas fa-file-slash"></i>
                            <p>No documents have been uploaded for this application.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Action Buttons -->
                <div class="action-section">
                    <div class="action-buttons">
                        <a href="../admin/adminDashboard.php" class="action-btn secondary">
                            <i class="fas fa-arrow-left"></i>
                            Back to Dashboard
                        </a>
                        <a href="../admin/fixed-insert-monthly-fees.php?property_id=<?php echo urlencode($propertyId); ?>" class="action-btn primary">
                            <i class="fas fa-calculator"></i>
                            Calculate Rates
                        </a>
                    </div>
                </div>

            <?php else: ?>
                <div class="error-container">
                    <i class="fas fa-search"></i>
                    <h2>Property Not Found</h2>
                    <p>No property found with the provided ID.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Add smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });

        // Add loading states for buttons
        document.querySelectorAll('.action-btn, .doc-btn').forEach(button => {
            button.addEventListener('click', function() {
                if (!this.classList.contains('loading')) {
                    const originalContent = this.innerHTML;
                    this.classList.add('loading');
                    this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
                    
                    // Reset after 3 seconds (in case of navigation issues)
                    setTimeout(() => {
                        this.classList.remove('loading');
                        this.innerHTML = originalContent;
                    }, 3000);
                }
            });
        });

        // Add copy to clipboard functionality for highlighted values
        document.querySelectorAll('.detail-value.highlight').forEach(element => {
            element.style.cursor = 'pointer';
            element.title = 'Click to copy';
            
            element.addEventListener('click', function() {
                const text = this.textContent;
                navigator.clipboard.writeText(text).then(() => {
                    // Show temporary feedback
                    const original = this.innerHTML;
                    this.innerHTML = '<i class="fas fa-check"></i> Copied!';
                    this.style.color = '#28a745';
                    
                    setTimeout(() => {
                        this.innerHTML = original;
                        this.style.color = '';
                    }, 2000);
                }).catch(err => {
                    console.error('Failed to copy text: ', err);
                });
            });
        });

        // Add print functionality
        function printPage() {
            window.print();
        }

        // Add keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl/Cmd + P for print
            if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
                e.preventDefault();
                printPage();
            }
            
            // Escape key to go back
            if (e.key === 'Escape') {
                window.history.back();
            }
        });

        // Add animation on scroll
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);

        // Observe all cards
        document.querySelectorAll('.detail-card, .property-overview').forEach(card => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            observer.observe(card);
        });
    </script>
</body>
</html>
