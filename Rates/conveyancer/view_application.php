<?php
session_start();

// Check if user is logged in and has required session data
if (empty($_SESSION['role']) || $_SESSION['role'] !== 'conveyancer' || 
    empty($_SESSION['user_id']) || empty($_SESSION['username'])) {
    header("Location: ../index.php");
    exit();
}

require_once '../Database/db.php';

// Get application ID from URL
$application_id = $_GET['application_id'] ?? null;

if (!$application_id) {
    header("Location: cdashboard.php");
    exit();
}

// Fetch application details
$query = "SELECT rca.application_id, rca.user_id, rca.property_id, rca.applicant_address, rca.email_address, rca.relationship_to_owner, rca.description, rca.title_deed, rca.identity_proof, rca.additional_documents, rca.status, rca.created_at, p.address AS property_address, p.type AS property_type
FROM rate_clearance_applications rca
JOIN properties p ON rca.property_id = p.property_id
WHERE rca.application_id = :application_id AND rca.user_id = :user_id";

$stmt = $pdo->prepare($query);
$stmt->bindValue(':application_id', $application_id, PDO::PARAM_INT);
$stmt->bindValue(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
$stmt->execute();
$application = $stmt->fetch();

if (!$application) {
    header("Location: cdashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Application - <?php echo htmlspecialchars($application['application_id']); ?></title>
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
        }

        .dashboard-container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .dashboard-nav {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .dashboard-nav a {
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 8px;
            transition: all 0.3s ease;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .dashboard-nav a:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateY(-1px);
        }

        .content {
            padding: 30px;
        }

        h1 {
            color: #2c3e50;
            font-size: 2.5rem;
            font-weight: 300;
            margin-bottom: 30px;
            text-align: center;
        }

        .application-header {
            background: linear-gradient(135deg, #e3f2fd 0%, #f3e5f5 100%);
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 30px;
            border-left: 4px solid #2196f3;
        }

        .application-header h2 {
            color: #1976d2;
            font-size: 1.8rem;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .status-badge {
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-approved {
            background: #d4edda;
            color: #155724;
        }

        .status-rejected {
            background: #f8d7da;
            color: #721c24;
        }

        .status-processing {
            background: #d1ecf1;
            color: #0c5460;
        }

        .application-details {
            display: grid;
            gap: 25px;
        }

        .details-section {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            border: 1px solid #e9ecef;
        }

        .section-title {
            color: #2c3e50;
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e9ecef;
        }

        .detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }

        .detail-row {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .label {
            font-weight: 600;
            color: #495057;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .value {
            color: #2c3e50;
            font-size: 1rem;
            padding: 8px 0;
        }

        .property-info {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 8px;
            padding: 15px;
            margin-top: 10px;
        }

        .replies-section {
            margin-top: 30px;
        }

        .replies-list {
            max-height: 400px;
            overflow-y: auto;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 15px;
            background: #f8f9fa;
        }

        .reply {
            background: white;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            border-left: 4px solid #007bff;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .reply:last-child {
            margin-bottom: 0;
        }

        .reply p {
            color: #2c3e50;
            line-height: 1.6;
            margin-bottom: 8px;
        }

        .reply small {
            color: #6c757d;
            font-size: 0.85rem;
        }

        .no-replies {
            text-align: center;
            color: #6c757d;
            font-style: italic;
            padding: 30px;
        }

        .form-section {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 25px;
            margin-top: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            font-weight: 600;
            color: #495057;
            margin-bottom: 8px;
            font-size: 0.9rem;
        }

        input[type="text"],
        input[type="email"],
        select,
        textarea {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: white;
        }

        input[type="text"]:focus,
        input[type="email"]:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
        }

        textarea {
            resize: vertical;
            min-height: 100px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 500;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .btn-primary {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: white;
        }

        .btn-success {
            background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%);
            color: white;
        }

        .btn-warning {
            background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);
            color: #212529;
        }

        .btn-secondary {
            background: linear-gradient(135deg, #6c757d 0%, #545b62 100%);
            color: white;
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            margin-top: 20px;
        }

        .documents-section {
            margin-top: 20px;
        }

        .document-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px;
            background: #e3f2fd;
            border-radius: 6px;
            margin-bottom: 10px;
        }

        .document-item a {
            color: #1976d2;
            text-decoration: none;
            font-weight: 500;
        }

        .document-item a:hover {
            text-decoration: underline;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid;
        }

        .alert-info {
            background: #d1ecf1;
            color: #0c5460;
            border-color: #bee5eb;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border-color: #c3e6cb;
        }

        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border-color: #ffeaa7;
        }

        @media (max-width: 768px) {
            body {
                padding: 10px;
            }

            .content {
                padding: 20px;
            }

            h1 {
                font-size: 2rem;
            }

            .dashboard-nav {
                padding: 15px 20px;
                flex-direction: column;
                align-items: stretch;
            }

            .dashboard-nav a {
                justify-content: center;
            }

            .detail-grid {
                grid-template-columns: 1fr;
            }

            .action-buttons {
                flex-direction: column;
            }

            .btn {
                justify-content: center;
            }
        }

        @media (max-width: 480px) {
            .application-header h2 {
                font-size: 1.4rem;
            }

            .section-title {
                font-size: 1.1rem;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="dashboard-nav">
            <a href="cdashboard.php">
                <i class="fas fa-arrow-left"></i>
                Back to Dashboard
            </a>
            <a href="logout.php">
                <i class="fas fa-sign-out-alt"></i>
                Logout
            </a>
        </div>
        
        <div class="content">
            <h1><i class="fas fa-file-alt"></i> Application Details</h1>
            
            <div class="application-header">
                <h2>
                    <i class="fas fa-hashtag"></i>
                    Application #<?php echo htmlspecialchars($application['application_id']); ?>
                </h2>
                <div class="status-badge status-<?php echo strtolower($application['status']); ?>">
                    <?php echo htmlspecialchars($application['status']); ?>
                </div>
            </div>
            
            <div class="application-details">
                <!-- Basic Information Section -->
                <div class="details-section">
                    <h3 class="section-title">
                        <i class="fas fa-info-circle"></i>
                        Basic Information
                    </h3>
                    
                    <div class="detail-grid">
                        <div class="detail-row">
                            <span class="label">Application ID</span>
                            <span class="value">#<?php echo htmlspecialchars($application['application_id']); ?></span>
                        </div>
                        
                        <div class="detail-row">
                            <span class="label">Status</span>
                            <span class="value">
                                <span class="status-badge status-<?php echo strtolower($application['status']); ?>">
                                    <?php echo htmlspecialchars($application['status']); ?>
                                </span>
                            </span>
                        </div>
                        
                        <div class="detail-row">
                            <span class="label">Submitted On</span>
                            <span class="value"><?php echo date('M j, Y H:i', strtotime($application['created_at'])); ?></span>
                        </div>
                        
                        <div class="detail-row">
                            <span class="label">Applicant Email</span>
                            <span class="value"><?php echo htmlspecialchars($application['email_address']); ?></span>
                        </div>
                        
                        <div class="detail-row">
                            <span class="label">Applicant Address</span>
                            <span class="value"><?php echo htmlspecialchars($application['applicant_address']); ?></span>
                        </div>
                        
                        <div class="detail-row">
                            <span class="label">Relationship to Owner</span>
                            <span class="value"><?php echo htmlspecialchars($application['relationship_to_owner']); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Property Information Section -->
                <div class="details-section">
                    <h3 class="section-title">
                        <i class="fas fa-home"></i>
                        Property Information
                    </h3>
                    
                    <div class="property-info">
                        <div class="detail-row">
                            <span class="label">Property Address</span>
                            <span class="value"><?php echo htmlspecialchars($application['property_address']); ?></span>
                        </div>
                        
                        <div class="detail-row">
                            <span class="label">Property Type</span>
                            <span class="value"><?php echo htmlspecialchars($application['property_type']); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Description Section -->
                <?php if (!empty($application['description'])): ?>
                <div class="details-section">
                    <h3 class="section-title">
                        <i class="fas fa-file-text"></i>
                        Description
                    </h3>
                    <div class="value"><?php echo nl2br(htmlspecialchars($application['description'])); ?></div>
                </div>
                <?php endif; ?>

                <!-- Documents Section -->
                <div class="details-section">
                    <h3 class="section-title">
                        <i class="fas fa-paperclip"></i>
                        Attached Documents
                    </h3>
                    
                    <div class="documents-section">
                        <?php if (!empty($application['title_deed'])): ?>
                        <div class="document-item">
                            <i class="fas fa-file-pdf"></i>
                            <a href="<?php echo htmlspecialchars($application['title_deed']); ?>" target="_blank">Title Deed</a>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($application['identity_proof'])): ?>
                        <div class="document-item">
                            <i class="fas fa-id-card"></i>
                            <a href="<?php echo htmlspecialchars($application['identity_proof']); ?>" target="_blank">Identity Proof</a>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($application['additional_documents'])): ?>
                        <div class="document-item">
                            <i class="fas fa-file"></i>
                            <a href="<?php echo htmlspecialchars($application['additional_documents']); ?>" target="_blank">Additional Documents</a>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (empty($application['title_deed']) && empty($application['identity_proof']) && empty($application['additional_documents'])): ?>
                        <div class="no-replies">No documents attached</div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Replies Section -->
                <div class="details-section replies-section">
                    <h3 class="section-title">
                        <i class="fas fa-comments"></i>
                        Replies & Communication
                    </h3>
                    
                    <?php
                    $reply_query = "SELECT * FROM application_replies WHERE application_id = :application_id ORDER BY created_at ASC";
                    $reply_stmt = $pdo->prepare($reply_query);
                    $reply_stmt->bindValue(':application_id', $application['application_id'], PDO::PARAM_INT);
                    $reply_stmt->execute();
                    $replies = $reply_stmt->fetchAll();
                    
                    if (count($replies) > 0): ?>
                        <div class="replies-list">
                            <?php foreach ($replies as $reply): ?>
                                <div class="reply">
                                    <p><?php echo nl2br(htmlspecialchars($reply['message'])); ?></p>
                                    <small>
                                        <i class="fas fa-clock"></i>
                                        Posted on <?php echo date('M j, Y H:i', strtotime($reply['created_at'])); ?>
                                    </small>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="no-replies">
                            <i class="fas fa-comment-slash"></i>
                            No replies yet.
                        </div>
                    <?php endif; ?>

                    <!-- Add Reply Form -->
                    <div class="form-section">
                        <h4><i class="fas fa-reply"></i> Add Reply</h4>
                        <form method="POST" action="add_reply.php">
                            <input type="hidden" name="application_id" value="<?php echo $application['application_id']; ?>">
                            <div class="form-group">
                                <label for="message">Your Reply</label>
                                <textarea name="message" id="message" required placeholder="Enter your reply here..."></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane"></i>
                                Submit Reply
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Forward Application Section -->
                <div class="details-section">
                    <h3 class="section-title">
                        <i class="fas fa-share"></i>
                        Forward Application
                    </h3>
                    
                    <div class="form-section">
                        <form method="POST" action="forward_application.php">
                            <input type="hidden" name="application_id" value="<?php echo $application['application_id']; ?>">
                            
                            <div class="form-group">
                                <label for="to_user_id">Forward To</label>
                                <select name="to_user_id" id="to_user_id" required>
                                    <option value="">Select a user...</option>
                                    <?php
                                    $users_query = "SELECT user_id, username FROM users WHERE user_id != :user_id";
                                    $users_stmt = $pdo->prepare($users_query);
                                    $users_stmt->bindValue(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
                                    $users_stmt->execute();
                                    $users = $users_stmt->fetchAll();
                                    
                                    foreach ($users as $user): ?>
                                        <option value="<?php echo $user['user_id']; ?>"><?php echo htmlspecialchars($user['username']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="forward_message">Message (optional)</label>
                                <textarea name="message" id="forward_message" placeholder="Add a message with the forwarded application..."></textarea>
                            </div>
                            
                            <button type="submit" class="btn btn-warning">
                                <i class="fas fa-share"></i>
                                Forward Application
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="action-buttons">
                    <a href="cdashboard.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i>
                        Back to Dashboard
                    </a>
                    <a href="edit_application.php?application_id=<?php echo $application['application_id']; ?>" class="btn btn-primary">
                        <i class="fas fa-edit"></i>
                        Edit Application
                    </a>
                    <button onclick="window.print()" class="btn btn-success">
                        <i class="fas fa-print"></i>
                        Print Details
                    </button>
                </div>
            </div>
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

        // Auto-resize textareas
        document.querySelectorAll('textarea').forEach(textarea => {
            textarea.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = this.scrollHeight + 'px';
            });
        });

        // Form validation
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function(e) {
                const requiredFields = this.querySelectorAll('[required]');
                let isValid = true;
                
                requiredFields.forEach(field => {
                    if (!field.value.trim()) {
                        isValid = false;
                        field.style.borderColor = '#dc3545';
                    } else {
                        field.style.borderColor = '#e9ecef';
                    }
                });
                
                if (!isValid) {
                    e.preventDefault();
                    alert('Please fill in all required fields.');
                }
            });
        });
    </script>
</body>
</html>
