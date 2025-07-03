<?php
// Start session
session_start();

// Include database connection
require_once '../Database/db.php';

// Check if user is logged in and is a conveyancer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'conveyancer') {
    header("Location: ../index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$bill_id = $_GET['bill_id'] ?? null;
$application_id = $_GET['application_id'] ?? null;

// Initialize variables
$bill_details = null;
$monthly_breakdown = [];
$application_details = null;
$property_details = null;
$error_message = null;

try {
    // If we have a bill_id, fetch by bill_id
    if ($bill_id) {
        $query = "
        SELECT cb.*, 
               rca.applicant_name, rca.applicant_address, rca.email_address, 
               rca.relationship_to_owner, rca.description, rca.status as application_status,
               p.address as property_address, p.owner as property_owner, p.property_type,
               p.erf_number, p.township
        FROM calculated_bills cb
        LEFT JOIN rate_clearance_applications rca ON cb.application_id = rca.application_id
        LEFT JOIN properties p ON rca.property_id = p.property_id
        WHERE cb.bill_id = :bill_id AND cb.user_id = :user_id";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([':bill_id' => $bill_id, ':user_id' => $user_id]);
        $bill_details = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    // If we have application_id but no bill_id, fetch by application_id
    elseif ($application_id) {
        $query = "
        SELECT cb.*, 
               rca.applicant_name, rca.applicant_address, rca.email_address, 
               rca.relationship_to_owner, rca.description, rca.status as application_status,
               p.address as property_address, p.owner as property_owner, p.property_type,
               p.erf_number, p.township
        FROM calculated_bills cb
        LEFT JOIN rate_clearance_applications rca ON cb.application_id = rca.application_id
        LEFT JOIN properties p ON rca.property_id = p.property_id
        WHERE cb.application_id = :application_id AND cb.user_id = :user_id
        ORDER BY cb.created_at DESC
        LIMIT 1";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([':application_id' => $application_id, ':user_id' => $user_id]);
        $bill_details = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    if ($bill_details) {
        // Get monthly breakdown
        $monthly_query = "
        SELECT * FROM calculated_bill_months 
        WHERE bill_id = :bill_id 
        ORDER BY id";
        
        $monthly_stmt = $pdo->prepare($monthly_query);
        $monthly_stmt->execute([':bill_id' => $bill_details['bill_id']]);
        $monthly_breakdown = $monthly_stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $error_message = "Bill not found or you don't have permission to view this bill.";
    }

} catch (PDOException $e) {
    $error_message = "Database error: " . $e->getMessage();
    error_log("[" . date('Y-m-d H:i:s') . "] Bill Details Error: " . $e->getMessage() . " | User ID: " . $user_id . "\n", 3, __DIR__ . '/../logfile/php_error.log');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bill Details - Conveyancer Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            min-height: 100vh;
            color: #334155;
            line-height: 1.6;
        }

        /* Header Styles */
        header {
            background: linear-gradient(135deg, #8b5cf6 0%, #3b82f6 50%, #1d4ed8 100%);
            color: white;
            padding: 1.5rem 0;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .header-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .back-btn {
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }

        .back-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-1px);
        }

        .header-content h1 {
            font-size: 1.5rem;
            font-weight: 600;
        }

        /* Main Content */
        main {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .bill-container {
            display: grid;
            gap: 2rem;
        }

        .bill-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }

        .card-header {
            padding: 1.5rem;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        }

        .card-header h2 {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 1.25rem;
            font-weight: 600;
            color: #1e293b;
            margin: 0;
        }

        .card-content {
            padding: 1.5rem;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
        }

        .info-item {
            margin-bottom: 1rem;
        }

        .info-label {
            font-weight: 600;
            color: #64748b;
            font-size: 0.875rem;
            margin-bottom: 0.25rem;
        }

        .info-value {
            color: #1e293b;
            font-size: 1rem;
        }

        .amount-highlight {
            font-size: 1.25rem;
            font-weight: 700;
            color: #059669;
        }

        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .status-pending {
            background: #fef3c7;
            color: #92400e;
            border: 1px solid #fbbf24;
        }

        .status-approved {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #10b981;
        }

        .status-rejected {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #ef4444;
        }

        /* Monthly Breakdown Table */
        .monthly-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        .monthly-table th,
        .monthly-table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }

        .monthly-table th {
            background: #f8fafc;
            font-weight: 600;
            color: #374151;
        }

        .monthly-table tr:hover {
            background: #f8fafc;
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
            flex-wrap: wrap;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }

        .btn-primary {
            background: #3b82f6;
            color: white;
        }

        .btn-primary:hover {
            background: #2563eb;
            transform: translateY(-1px);
        }

        .btn-secondary {
            background: #6b7280;
            color: white;
        }

        .btn-secondary:hover {
            background: #4b5563;
            transform: translateY(-1px);
        }

        .btn-success {
            background: #10b981;
            color: white;
        }

        .btn-success:hover {
            background: #059669;
            transform: translateY(-1px);
        }

        /* Error Message */
        .error-message {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 2rem;
        }

        /* Print Styles */
        @media print {
            body {
                background: white;
            }
            
            header {
                background: #333 !important;
                -webkit-print-color-adjust: exact;
            }
            
            .back-btn,
            .action-buttons {
                display: none;
            }
            
            .bill-card {
                box-shadow: none;
                border: 1px solid #ccc;
            }
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .header-container {
                padding: 0 1rem;
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }

            main {
                padding: 1rem;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }

            .action-buttons {
                flex-direction: column;
            }

            .monthly-table {
                font-size: 0.875rem;
            }
        }
    </style>
</head>

<body>
    <header>
        <div class="header-container">
            <a href="dashboard.php" class="back-btn">
                <i class="fas fa-arrow-left"></i>
                Back to Dashboard
            </a>
            <div class="header-content">
                <h1>Bill Details</h1>
            </div>
        </div>
    </header>

    <main>
        <?php if ($error_message): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-triangle"></i>
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php elseif ($bill_details): ?>
            <div class="bill-container">
                <!-- Bill Summary -->
                <div class="bill-card">
                    <div class="card-header">
                        <h2>
                            <i class="fas fa-file-invoice-dollar" style="color: #059669;"></i>
                            Bill Summary
                        </h2>
                    </div>
                    <div class="card-content">
                        <div class="info-grid">
                            <div class="info-item">
                                <div class="info-label">Bill ID</div>
                                <div class="info-value"><?php echo htmlspecialchars($bill_details['bill_id']); ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Account ID</div>
                                <div class="info-value"><?php echo htmlspecialchars($bill_details['account_id']); ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Application ID</div>
                                <div class="info-value"><?php echo htmlspecialchars($bill_details['application_id']); ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Total Balance</div>
                                <div class="info-value amount-highlight">$<?php echo number_format($bill_details['total_balance'], 2); ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Processing Fee</div>
                                <div class="info-value">$<?php echo number_format($bill_details['processing_fee'], 2); ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Overall Total</div>
                                <div class="info-value amount-highlight">$<?php echo number_format($bill_details['overall_total'], 2); ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Created Date</div>
                                <div class="info-value"><?php echo date('F j, Y g:i A', strtotime($bill_details['created_at'])); ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Calculated Date</div>
                                <div class="info-value"><?php echo date('F j, Y g:i A', strtotime($bill_details['calculated_at'])); ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Property Information -->
                <?php if ($bill_details['property_address']): ?>
                <div class="bill-card">
                    <div class="card-header">
                        <h2>
                            <i class="fas fa-home" style="color: #3b82f6;"></i>
                            Property Information
                        </h2>
                    </div>
                    <div class="card-content">
                        <div class="info-grid">
                            <div class="info-item">
                                <div class="info-label">Property Address</div>
                                <div class="info-value"><?php echo htmlspecialchars($bill_details['property_address']); ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Property Owner</div>
                                <div class="info-value"><?php echo htmlspecialchars($bill_details['property_owner']); ?></div>
                            </div>
                            <?php if ($bill_details['property_type']): ?>
                            <div class="info-item">
                                <div class="info-label">Property Type</div>
                                <div class="info-value"><?php echo htmlspecialchars($bill_details['property_type']); ?></div>
                            </div>
                            <?php endif; ?>
                            <?php if ($bill_details['erf_number']): ?>
                            <div class="info-item">
                                <div class="info-label">ERF Number</div>
                                <div class="info-value"><?php echo htmlspecialchars($bill_details['erf_number']); ?></div>
                            </div>
                            <?php endif; ?>
                            <?php if ($bill_details['township']): ?>
                            <div class="info-item">
                                <div class="info-label">Township</div>
                                <div class="info-value"><?php echo htmlspecialchars($bill_details['township']); ?></div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Application Information -->
                <?php if ($bill_details['applicant_name']): ?>
                <div class="bill-card">
                    <div class="card-header">
                        <h2>
                            <i class="fas fa-user" style="color: #8b5cf6;"></i>
                            Application Information
                        </h2>
                    </div>
                    <div class="card-content">
                        <div class="info-grid">
                            <div class="info-item">
                                <div class="info-label">Applicant Name</div>
                                <div class="info-value"><?php echo htmlspecialchars($bill_details['applicant_name']); ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Applicant Address</div>
                                <div class="info-value"><?php echo htmlspecialchars($bill_details['applicant_address']); ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Email Address</div>
                                <div class="info-value"><?php echo htmlspecialchars($bill_details['email_address']); ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Relationship to Owner</div>
                                <div class="info-value"><?php echo htmlspecialchars($bill_details['relationship_to_owner']); ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Application Status</div>
                                <div class="info-value">
                                    <span class="status-badge status-<?php echo strtolower($bill_details['application_status']); ?>">
                                        <?php echo htmlspecialchars($bill_details['application_status']); ?>
                                    </span>
                                </div>
                            </div>
                            <?php if ($bill_details['description']): ?>
                            <div class="info-item" style="grid-column: 1 / -1;">
                                <div class="info-label">Description</div>
                                <div class="info-value"><?php echo htmlspecialchars($bill_details['description']); ?></div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Monthly Breakdown -->
                <?php if (!empty($monthly_breakdown)): ?>
                <div class="bill-card">
                    <div class="card-header">
                        <h2>
                            <i class="fas fa-calendar-alt" style="color: #f59e0b;"></i>
                            Monthly Breakdown
                        </h2>
                    </div>
                    <div class="card-content">
                        <table class="monthly-table">
                            <thead>
                                <tr>
                                    <th>Months</th>
                                    <th>Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($monthly_breakdown as $month): ?>
                                <tr>
                                    <td>
                                        <?php 
                                        $months = array_filter([
                                            $month['month1_name'],
                                            $month['month2_name'],
                                            $month['month3_name'],
                                            $month['month4_name']
                                        ]);
                                        echo htmlspecialchars(implode(', ', $months));
                                        ?>
                                    </td>
                                    <td>$<?php echo number_format($month['month_balance'], 2); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Action Buttons -->
                <div class="action-buttons">
                    <button onclick="window.print()" class="btn btn-primary">
                        <i class="fas fa-print"></i>
                        Print Bill
                    </button>
                    <a href="dashboard.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i>
                        Back to Dashboard
                    </a>
                    <?php if ($bill_details['application_id']): ?>
                    <a href="view_applications.php?id=<?php echo $bill_details['application_id']; ?>" class="btn btn-success">
                        <i class="fas fa-eye"></i>
                        View Application
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="error-message">
                <i class="fas fa-exclamation-triangle"></i>
                No bill details found. Please check the URL and try again.
            </div>
        <?php endif; ?>
    </main>

    <script>
        // Add smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Add loading states for buttons
        document.querySelectorAll('.btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                if (this.onclick !== window.print) {
                    const icon = this.querySelector('i');
                    if (icon) {
                        const originalClass = icon.className;
                        icon.className = 'fas fa-spinner fa-spin';
                        
                        setTimeout(() => {
                            icon.className = originalClass;
                        }, 1000);
                    }
                }
            });
        });
    </script>
</body>
</html>
