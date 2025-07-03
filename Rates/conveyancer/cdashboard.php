<?php
// Start session
session_start();

// Include database connection
require_once '../Database/db.php';

if ($_SESSION['role'] !== 'conveyancer') {
    header("Location: ../index.php");
    exit();
}

$user_id = $_SESSION['user_id'] ?? null;
$hasBills = false;

if ($user_id) {
    // Check if there are any bills for this user in calculated_bills
    $sql = "SELECT 1 FROM calculated_bills WHERE user_id = :user_id LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':user_id' => $user_id]);
    $hasBills = $stmt->fetch() ? true : false;
}
$firstPropertyId = '';
if (!empty($recentApplications)) {
    $firstPropertyId = urlencode($recentApplications[0]['property_id']);
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Conveyancer Dashboard</title>
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
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
            position: relative;
            overflow: hidden;
        }

        header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="white" opacity="0.1"/><circle cx="75" cy="75" r="1" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            opacity: 0.3;
        }

        .header-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            position: relative;
            z-index: 1;
        }

        .logo {
            width: 100px;
            height: 100px;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1.5rem;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .header-content h1 {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .header-content p {
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.9rem;
        }

        /* Navigation Styles */
        nav {
            background: rgba(6, 182, 212, 0.9);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
            padding-top: 0.50rem;
            padding-bottom: 0.50rem;
        }

        nav a, .dropdown > a {
            color: white;
            text-decoration: none;
            padding: 0.70rem 1rem;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
            position: relative;
        }

        nav a:hover, .dropdown > a:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-1px);
        }

        .dropdown {
            position: relative;
            display: inline-block;
        }

        .dropdown-content {
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            background: white;
            min-width: 200px;
            border-radius: 12px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            border: 1px solid rgba(0, 0, 0, 0.05);
            overflow: hidden;
            z-index: 1000;
        }

        .dropdown-content a {
            color: #374151;
            padding: 0.75rem 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            border-radius: 0;
            transition: all 0.2s ease;
        }

        .dropdown-content a:hover {
            background: #f3f4f6;
            color: #1f2937;
            transform: none;
        }

        .dropdown:hover .dropdown-content {
            display: block;
            animation: fadeInUp 0.3s ease;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Main Content */
        main {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .dashboard-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(8px);
            border-radius: 16px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 7px 20px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .dashboard-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.12);
        }

        .card-header {
            padding: 1.5rem;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        }

        .card-header h3 {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 1.25rem;
            font-weight: 600;
            color: #1e293b;
            margin: 0;
        }

        .card-header i {
            font-size: 1.25rem;
        }

        .card-content {
            padding: 1.5rem;
        }

        /* Application and Notification Items */
        .application-item, .notification-item {
            margin-bottom: 1rem;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            overflow: hidden;
            background: white;
            transition: all 0.3s ease;
        }

        .application-item:hover, .notification-item:hover {
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            transform: translateY(-1px);
        }

        .application-summary, .notification-summary {
            cursor: pointer;
            padding: 1rem 1.5rem;
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            border-bottom: 1px solid #e2e8f0;
            transition: all 0.2s ease;
            position: relative;
        }

        .application-summary:hover, .notification-summary:hover {
            background: linear-gradient(135deg, #e2e8f0 0%, #cbd5e1 100%);
        }

        .application-summary::after, .notification-summary::after {
            content: '\f107';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            position: absolute;
            right: 1.5rem;
            top: 50%;
            transform: translateY(-50%);
            transition: transform 0.3s ease;
        }

        .application-summary.expanded::after, .notification-summary.expanded::after {
            transform: translateY(-50%) rotate(180deg);
        }

        .application-details, .notification-details {
            padding: 1.5rem;
            background: white;
            display: none;
            animation: slideDown 0.3s ease;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                max-height: 0;
            }
            to {
                opacity: 1;
                max-height: 500px;
            }
        }

        .details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }

        .detail-item {
            margin-bottom: 0.50rem;
        }

        .detail-label {
            font-weight: 600;
            color: #64748b;
            font-size: 0.875rem;
            margin-bottom: 0.20rem;
        }

        .detail-value {
            color: #1e293b;
            font-size: 0.9rem;
        }

        /* Status Badge */
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

        /* Quick Actions */
        .quick-actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
        }

        .quick-action-btn {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.50rem;
            padding: 1.0rem;
            background: white;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            text-decoration: none;
            color: #374151;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .quick-action-btn:hover {
            border-color: #3b82f6;
            background: #f8fafc;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(59, 130, 246, 0.15);
        }

        .quick-action-btn i {
            font-size: 2rem;
            color: #3b82f6;
        }

        /* Notification Alert */
        .notification-alert {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            color: #92400e;
            border: 1px solid #fbbf24;
            padding: 1rem 1.5rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 0.50rem;
            font-weight: 500;
        }

        .notification-alert i {
            font-size: 1.25rem;
        }

        .notification-alert a {
            color: #1d4ed8;
            text-decoration: none;
            font-weight: 600;
        }

        .notification-alert a:hover {
            text-decoration: underline;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .header-container {
                padding: 0 1rem;
            }

            .nav-container {
                padding: 0.75rem 1rem;
                flex-wrap: wrap;
                justify-content: center;
            }

            main {
                padding: 1rem;
            }

            .dashboard-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .details-grid {
                grid-template-columns: 1fr;
            }

            .quick-actions-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 480px) {
            .header-content h1 {
                font-size: 1.25rem;
            }

            .quick-actions-grid {
                grid-template-columns: 1fr;
            }

            nav a, .dropdown > a {
                padding: 0.5rem 0.75rem;
                font-size: 0.875rem;
            }
        }

        /* Loading Animation */
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid #3b82f6;
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
    <header>
        <div class="header-container">
            <img src="../assets/images/mslogo.png" alt="Logo" class="logo">
            <div class="header-content">
                <h1>Welcome, <?php echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Guest'; ?></h1>
                <p>Rate Clearance Dashboard</p>
            </div>
        </div>
    </header>

    <nav>
        <div class="nav-container">
                <div class="dropdown">
                <a href="">
                    <i class="fas fa-home"></i>
                    Property Management
                    <i class="fas fa-chevron-down"></i>
                </a>
                <div class="dropdown-content">
                    <a href=".//add_property.php">
                        <i class="fas fa-plus"></i>
                        Add Property
                    </a>
                    <a href="update_property.php">
                        <i class="fas fa-edit"></i>
                        Update Property
                    </a>
                    <a href="view_properties.php">
                        <i class="fas fa-eye"></i>
                        View Property
                    </a>
                    <a href="delete_property.php" style="color: #dc2626;">
                        <i class="fas fa-trash"></i>
                        Delete Property
                    </a>
                </div>
            </div>

            <div class="dropdown">
                <a href="">
                    <i class="fas fa-file-alt"></i>
                    Application Management
                    <i class="fas fa-chevron-down"></i>
                </a>
                <div class="dropdown-content">
                    <a href="./view_all_applications.php">
                        <i class="fas fa-eye"></i>
                        View Applications
                    </a>
                    <a href="approve_applications.php">
                        <i class="fas fa-check-circle"></i>
                        Application Status
                    </a>
                    <a href="reject_applications.php">
                        <i class="fas fa-history"></i>
                        Application History
                    </a>
                </div>
            </div>

            <a href="../includes/logout.php">
                <i class="fas fa-sign-out-alt"></i>
                Log Out
            </a>
        </div>
    </nav>

    <main>
        <?php if ($hasBills): ?>
            <div class="notification-alert">
                <i class="fas fa-bell"></i>
                You have new property/account bills.
                <a href="view_bills.php">View Bills</a>
            </div>
        <?php endif; ?>

        <div class="dashboard-grid">
            <!-- Recent Applications -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h3>
                        <i class="fas fa-clock" style="color: #3b82f6;"></i>
                        Recent Applications
                    </h3>
                </div>
                <div class="card-content">
                    <?php
                    if (isset($_SESSION['user_id'])) {
                        $userId = $_SESSION['user_id'];
                        try {
                            $query = "SELECT a.*, p.address AS property_address, p.owner AS property_owner, ac.account_id 
                                      FROM rate_clearance_applications a 
                                      JOIN properties p ON a.property_id = p.property_id 
                                      LEFT JOIN accounts ac ON ac.property_id = p.property_id 
                                      WHERE a.user_id = ? 
                                      ORDER BY a.created_at DESC LIMIT 5";
                            $stmt = $pdo->prepare($query);
                            $stmt->bindValue(1, $userId, PDO::PARAM_INT);
                            $stmt->execute();
                            $recentApplications = $stmt->fetchAll(PDO::FETCH_ASSOC);

                            if (count($recentApplications) > 0) {
                                foreach ($recentApplications as $application) {
                                    $applicationId = htmlspecialchars($application['application_id']);
                                    $propertyAddress = htmlspecialchars($application['property_address']);
                                    $propertyOwner = htmlspecialchars($application['property_owner']);
                                    $status = htmlspecialchars($application['status']);
                                    $statusClass = 'status-' . strtolower($status);
                                    $propertyId = urlencode($application['property_id']);
                                    $accountId = urlencode($application['account_id']); // fetched from accounts table
                                    ?>
                                    <div class="application-item">
                                        <div class="application-summary" onclick="toggleDetails(this, 'app-<?php echo $applicationId; ?>')">
                                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                                <p><strong>Application for:</strong> <?php echo $propertyAddress; ?></p>
                                                <span class="status-badge <?php echo $statusClass; ?>"><?php echo $status; ?></span>
                                            </div>
                                        </div>
                                        <div class="application-details" id="app-<?php echo $applicationId; ?>">
                                            <div class="details-grid">
                                                <div class="detail-item">
                                                    <div class="detail-label">Property Address</div>
                                                    <div class="detail-value"><?php echo $propertyAddress; ?></div>
                                                </div>
                                                <div class="detail-item">
                                                    <div class="detail-label">Owner</div>
                                                    <div class="detail-value"><?php echo $propertyOwner; ?></div>
                                                </div>
                                                <div class="detail-item">
                                                    <div class="detail-label">Applicant Address</div>
                                                    <div class="detail-value"><?php echo htmlspecialchars($application['applicant_address'] ?? 'N/A'); ?></div>
                                                </div>
                                                <div class="detail-item">
                                                    <div class="detail-label">Email Address</div>
                                                    <div class="detail-value"><?php echo htmlspecialchars($application['email_address'] ?? 'N/A'); ?></div>
                                                </div>
                                                <div class="detail-item">
                                                    <div class="detail-label">Relationship to Owner</div>
                                                    <div class="detail-value"><?php echo htmlspecialchars($application['relationship_to_owner'] ?? 'N/A'); ?></div>
                                                </div>
                                                <div class="detail-item">
                                                    <div class="detail-label">Description</div>
                                                    <div class="detail-value"><?php echo htmlspecialchars($application['description'] ?? 'N/A'); ?></div>
                                                </div>
                                                <div class="detail-item">
                                                    <div class="detail-value">
                                                        <a href="view_bills.php?property_id=<?php echo $propertyId; ?>&account_id=<?php echo $accountId; ?>" class="text-blue-500 hover:underline">View Invoice</a>
                                                    </div>
                                                </div>
                                                <div class="detail-item">
                                                    <a href="upload_payment.php?property_id=<?php echo $propertyId; ?>" class="text-blue-500 hover:underline">Upload Payment</a>
                                                    
                                                </div>
                                        </div>
                                    </div>
                                    <?php
                                }
                            } else {
                                echo '<p style="text-align: center; color: #64748b; padding: 2rem;">No recent applications found.</p>';
                            }
                        } catch (PDOException $e) {
                            echo '<p style="color: #dc2626; text-align: center; padding: 2rem;">' . htmlspecialchars($e->getMessage()) . '</p>';
                        }
                    } else {
                        echo '<p style="color: #dc2626; text-align: center; padding: 2rem;">User session error. Please login again.</p>';
                    }
                    ?>
                </div>
            </div>

            <!-- Notifications - NOW WITH DYNAMIC LOADING -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h3>
                        <i class="fas fa-bell" style="color: #f59e0b;"></i>
                        Notifications
                    </h3>
                </div>
                <div class="card-content" id="notifications-section">
                    <p style="text-align: center; color: #64748b; padding: 2rem;">Loading notifications...</p>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="dashboard-card">
            <div class="card-header">
                <h3>
                    <i class="fas fa-bolt" style="color: #10b981;"></i>
                    Quick Actions
                </h3>
            </div>
            <div class="card-content">
                <div class="quick-actions-grid">
                    <a href="add_property.php" class="quick-action-btn">
                        <i class="fas fa-plus"></i>
                        Add New Property
                    </a>
                    <a href="view_properties.php" class="quick-action-btn">
                        <i class="fas fa-eye"></i>
                        View Property
                    </a>
                    <a href="apply_rates.php" class="quick-action-btn">
                        <i class="fas fa-file-invoice"></i>
                        Apply for Rates
                    </a>
                    <a href="./view_all_applications.php" class="quick-action-btn">
                        <i class="fas fa-list"></i>
                        View All Applications
                    </a>
                    <a href="upload_payment.php?property_id=<?php echo $propertyId?>" class="quick-action-btn">
                        <i class="fas fa-file-invoice"></i>
                        Upload Payment
                    </a>
                </div>
            </div>
        </div>
    </main>
    
    <?php if ($hasBills): ?>
        <script>
            alert("You have new property/account bills. Visit 'View Bills' to see details.");
            </script>
    <?php endif; ?>

    <script>
function toggleDetails(summaryElement, targetId) {
    const detailsElement = document.getElementById(targetId);
    const isExpanded = detailsElement.style.display === 'block';
    
    // Close all other open details first
    document.querySelectorAll('.notification-details, .application-details').forEach(detail => {
        if (detail.id !== targetId) {
            detail.style.display = 'none';
            detail.previousElementSibling.classList.remove('expanded');
        }
    });
    
    // Toggle current details
    if (isExpanded) {
        detailsElement.style.display = 'none';
        summaryElement.classList.remove('expanded');
    } else {
        detailsElement.style.display = 'block';
        summaryElement.classList.add('expanded');
    }
}

// THIS IS THE MISSING CODE - Fetch and update notifications
function fetchNotifications() {
    fetch('./fetch_dashboard_data.php')
        .then(response => response.json())
        .then(data => {
            const notifSection = document.getElementById('notifications-section');
            if (data.error) {
                notifSection.innerHTML = `<p style="color: #dc2626; text-align: center; padding: 2rem;">${data.error}</p>`;
            } else if (data.notifications.length > 0) {
                notifSection.innerHTML = data.notifications.map((notif, idx) => {
                    const notifId = 'notif-' + idx;
                    let monthsHtml = '';
                    if (notif.months && notif.months.length > 0) {
                        monthsHtml = `<div style="margin-top:1rem;"><strong>Monthly Breakdown:</strong><ul style='margin:0; padding-left:1.2em;'>` +
                            notif.months.map(month => {
                                let names = [month.month1_name, month.month2_name, month.month3_name, month.month4_name].filter(Boolean).join(', ');
                                return `<li>${names ? names + ': ' : ''}${month.month_balance}</li>`;
                            }).join('') +
                            `</ul></div>`;
                    }
                    return `
                    <div class="notification-item">
                        <div class="notification-summary" onclick="toggleDetails(this, '${notifId}')">
                            <p><strong>Bill invoice for:</strong> ${notif.account_id}</p>
                        </div>
                        <div class="notification-details" id="${notifId}">
                            <div class="details-grid">
                                <div class="detail-item">
                                    <div class="detail-label">Account ID</div>
                                    <div class="detail-value">${notif.account_id}</div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label">Property</div>
                                    <div class="detail-value">${notif.address}</div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label">Total Balance</div>
                                    <div class="detail-value">${notif.total_balance}</div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label">Processing Fee</div>
                                    <div class="detail-value">${notif.processing_fee}</div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label">Overall Total</div>
                                    <div class="detail-value" style="font-weight: 600;">${notif.overall_total}</div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label">Calculated On</div>
                                    <div class="detail-value">${notif.created_at || notif.calculated_at}</div>
                                </div>
                            </div>
                            ${monthsHtml}
                            <div style="margin-top: 1rem;">
                                <a href="${notif.view_link || 'view_bill_details.php?application_id=' + notif.application_id}" style="color: #3b82f6; text-decoration: none; font-weight: 600;">View Full Details</a>
                            </div>
                        </div>
                    </div>
                    `;
                }).join('');
            } else {
                notifSection.innerHTML = '<p style="text-align: center; color: #64748b; padding: 2rem;">No new notifications</p>';
            }
        })
        .catch(() => {
            document.getElementById('notifications-section').innerHTML = '<p style="color: #dc2626; text-align: center; padding: 2rem;">Failed to fetch notifications.</p>';
        });
}

// Initial load when page loads
fetchNotifications();

// Auto-refresh every 30 seconds
setInterval(fetchNotifications, 30000);

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
document.querySelectorAll('.quick-action-btn').forEach(btn => {
    btn.addEventListener('click', function(e) {
        const icon = this.querySelector('i');
        const originalClass = icon.className;
        icon.className = 'loading';
        
        setTimeout(() => {
            icon.className = originalClass;
        }, 1000);
    });
});
    </script>
</body>

</html>
