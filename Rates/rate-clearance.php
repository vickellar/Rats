<?php
session_start();
/*
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['finance_director', 'accountant', 'manager'])) {
    header("Location: ./index.php");
    exit();
}

include './Database/db.php';

// Fetch rate clearance data
$pending_rates = getPendingRates($pdo);
$approved_rates = getApprovedRates($pdo);
$rate_categories = getRateCategories($pdo);
$compliance_status = getComplianceStatus($pdo);

function getPendingRates($pdo) {
    $stmt = $pdo->prepare("
        SELECT rc.*, rt.name as rate_type_name, u.username as submitted_by_name,
               v.name as vendor_name, p.name as project_name
        FROM rate_clearances rc
        LEFT JOIN rate_types rt ON rc.rate_type_id = rt.id
        LEFT JOIN users u ON rc.submitted_by = u.id
        LEFT JOIN vendors v ON rc.vendor_id = v.id
        LEFT JOIN projects p ON rc.project_id = p.id
        WHERE rc.status IN ('pending', 'under_review')
        ORDER BY rc.priority DESC, rc.created_at ASC
    ");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getApprovedRates($pdo) {
    $stmt = $pdo->prepare("
        SELECT rc.*, rt.name as rate_type_name, u.username as approved_by_name
        FROM rate_clearances rc
        LEFT JOIN rate_types rt ON rc.rate_type_id = rt.id
        LEFT JOIN users u ON rc.approved_by = u.id
        WHERE rc.status = 'approved'
        ORDER BY rc.approved_at DESC
        LIMIT 10
    ");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getRateCategories($pdo) {
    $stmt = $pdo->prepare("SELECT * FROM rate_types WHERE is_active = 1 ORDER BY name");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getComplianceStatus($pdo) {
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_rates,
            SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_rates,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_rates,
            SUM(CASE WHEN expires_at < NOW() THEN 1 ELSE 0 END) as expired_rates,
            SUM(CASE WHEN expires_at BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as expiring_soon
        FROM rate_clearances 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)
    ");
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
    */
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rate Clearance Service - Finance Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../styles.css">
    <link rel="stylesheet" href="rate-clearance.css">
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <i class="fas fa-chart-line"></i>
                    <span>FinanceHub</span>
                </div>
            </div>
            
            <nav class="sidebar-nav">
                <ul>
                    <li class="nav-item">
                        <a href="../index.php" class="nav-link">
                            <i class="fas fa-tachometer-alt"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li class="nav-item active">
                        <a href="#rate-clearance" class="nav-link" data-section="rate-clearance">
                            <i class="fas fa-percentage"></i>
                            <span>Rate Clearance</span>
                            <span class="badge"><?php echo count($pending_rates); ?></span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#rate-management" class="nav-link" data-section="rate-management">
                            <i class="fas fa-cogs"></i>
                            <span>Rate Management</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#compliance" class="nav-link" data-section="compliance">
                            <i class="fas fa-shield-alt"></i>
                            <span>Compliance</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#reports" class="nav-link" data-section="reports">
                            <i class="fas fa-chart-bar"></i>
                            <span>Rate Reports</span>
                        </a>
                    </li>
                </ul>
            </nav>
            
            <div class="sidebar-footer">
                <div class="user-profile">
                    <img src="/placeholder.svg?height=40&width=40" alt="Profile" class="profile-avatar">
                    <div class="user-info">
                        <span class="user-name"><?php echo $_SESSION['username'] ?? 'User'; ?></span>
                        <span class="user-role"><?php echo ucfirst($_SESSION['role']); ?></span>
                    </div>
                </div>
                <a href="../includes/logout.php?page=logout" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Header -->
            <header class="header">
                <div class="header-left">
                    <button class="sidebar-toggle">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h1 class="page-title">Rate Clearance Service</h1>
                </div>
                
                <div class="header-right">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" placeholder="Search rates, vendors, projects...">
                    </div>
                    
                    <div class="notifications">
                        <button class="notification-btn">
                            <i class="fas fa-bell"></i>
                            <span class="notification-badge"><?php echo count($pending_rates); ?></span>
                        </button>
                    </div>
                </div>
            </header>

            <!-- Rate Clearance Overview Section -->
            <section id="rate-clearance" class="content-section active">
                <div class="section-header">
                    <h2>Rate Clearance Overview</h2>
                    <div class="section-actions">
                        <button class="btn-primary" onclick="openNewRateModal()">
                            <i class="fas fa-plus"></i>
                            New Rate Request
                        </button>
                        <button class="btn-secondary" onclick="exportRateData()">
                            <i class="fas fa-download"></i>
                            Export Data
                        </button>
                    </div>
                </div>

                <!-- Stats Grid -->
                <div class="stats-grid">
                    <div class="stat-card primary">
                        <div class="stat-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-content">
                            <h3>Pending Clearances</h3>
                            <p class="stat-number"><?php echo $compliance_status['pending_rates']; ?></p>
                            <span class="stat-change neutral">Awaiting approval</span>
                        </div>
                    </div>
                    
                    <div class="stat-card success">
                        <div class="stat-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-content">
                            <h3>Approved Rates</h3>
                            <p class="stat-number"><?php echo $compliance_status['approved_rates']; ?></p>
                            <span class="stat-change positive">This year</span>
                        </div>
                    </div>
                    
                    <div class="stat-card warning">
                        <div class="stat-icon">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div class="stat-content">
                            <h3>Expiring Soon</h3>
                            <p class="stat-number"><?php echo $compliance_status['expiring_soon']; ?></p>
                            <span class="stat-change negative">Next 30 days</span>
                        </div>
                    </div>
                    
                    <div class="stat-card info">
                        <div class="stat-icon">
                            <i class="fas fa-percentage"></i>
                        </div>
                        <div class="stat-content">
                            <h3>Compliance Rate</h3>
                            <p class="stat-number"><?php echo round(($compliance_status['approved_rates'] / max($compliance_status['total_rates'], 1)) * 100); ?>%</p>
                            <span class="stat-change positive">On track</span>
                        </div>
                    </div>
                </div>

                <!-- Pending Rate Clearances -->
                <div class="rate-clearance-container">
                    <div class="clearance-section">
                        <div class="section-title">
                            <h3>Pending Rate Clearances</h3>
                            <span class="count-badge"><?php echo count($pending_rates); ?></span>
                        </div>
                        
                        <div class="clearance-filters">
                            <select class="filter-select" id="priorityFilter">
                                <option value="">All Priorities</option>
                                <option value="high">High Priority</option>
                                <option value="medium">Medium Priority</option>
                                <option value="low">Low Priority</option>
                            </select>
                            <select class="filter-select" id="typeFilter">
                                <option value="">All Types</option>
                                <?php foreach ($rate_categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button class="btn-filter" onclick="applyFilters()">Apply Filters</button>
                        </div>

                        <div class="rate-table-container">
                            <table class="rate-table">
                                <thead>
                                    <tr>
                                        <th>Rate ID</th>
                                        <th>Type</th>
                                        <th>Vendor/Project</th>
                                        <th>Proposed Rate</th>
                                        <th>Current Rate</th>
                                        <th>Priority</th>
                                        <th>Submitted</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pending_rates as $rate): ?>
                                    <tr class="rate-row" data-rate-id="<?php echo $rate['id']; ?>">
                                        <td class="rate-id"><?php echo htmlspecialchars($rate['rate_code']); ?></td>
                                        <td>
                                            <span class="rate-type"><?php echo htmlspecialchars($rate['rate_type_name']); ?></span>
                                        </td>
                                        <td>
                                            <div class="entity-info">
                                                <span class="entity-name"><?php echo htmlspecialchars($rate['vendor_name'] ?: $rate['project_name']); ?></span>
                                                <span class="entity-type"><?php echo $rate['vendor_name'] ? 'Vendor' : 'Project'; ?></span>
                                            </div>
                                        </td>
                                        <td class="proposed-rate">
                                            <span class="rate-amount">$<?php echo number_format($rate['proposed_rate'], 2); ?></span>
                                            <span class="rate-unit">/<?php echo htmlspecialchars($rate['rate_unit']); ?></span>
                                        </td>
                                        <td class="current-rate">
                                            <?php if ($rate['current_rate']): ?>
                                                <span class="rate-amount">$<?php echo number_format($rate['current_rate'], 2); ?></span>
                                                <span class="rate-unit">/<?php echo htmlspecialchars($rate['rate_unit']); ?></span>
                                            <?php else: ?>
                                                <span class="no-rate">New Rate</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="priority-badge <?php echo $rate['priority']; ?>">
                                                <?php echo ucfirst($rate['priority']); ?>
                                            </span>
                                        </td>
                                        <td class="submitted-date">
                                            <span class="date"><?php echo date('M j, Y', strtotime($rate['created_at'])); ?></span>
                                            <span class="submitter">by <?php echo htmlspecialchars($rate['submitted_by_name']); ?></span>
                                        </td>
                                        <td class="actions">
                                            <button class="btn-action" onclick="reviewRate('<?php echo $rate['id']; ?>')" title="Review">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn-action approve" onclick="approveRate('<?php echo $rate['id']; ?>')" title="Approve">
                                                <i class="fas fa-check"></i>
                                            </button>
                                            <button class="btn-action reject" onclick="rejectRate('<?php echo $rate['id']; ?>')" title="Reject">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Rate Management Section -->
            <section id="rate-management" class="content-section">
                <div class="section-header">
                    <h2>Rate Management</h2>
                    <div class="section-actions">
                        <button class="btn-primary" onclick="openRateTypeModal()">
                            <i class="fas fa-plus"></i>
                            Add Rate Type
                        </button>
                        <button class="btn-secondary" onclick="bulkUpdateRates()">
                            <i class="fas fa-edit"></i>
                            Bulk Update
                        </button>
                    </div>
                </div>

                <div class="rate-management-container">
                    <div class="rate-categories-grid">
                        <?php foreach ($rate_categories as $category): ?>
                        <div class="rate-category-card">
                            <div class="category-header">
                                <h4><?php echo htmlspecialchars($category['name']); ?></h4>
                                <div class="category-actions">
                                    <button class="btn-icon" onclick="editRateType('<?php echo $category['id']; ?>')">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn-icon" onclick="viewRateHistory('<?php echo $category['id']; ?>')">
                                        <i class="fas fa-history"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="category-content">
                                <p class="category-description"><?php echo htmlspecialchars($category['description']); ?></p>
                                <div class="category-stats">
                                    <div class="stat-item">
                                        <span class="stat-label">Base Rate:</span>
                                        <span class="stat-value">$<?php echo number_format($category['base_rate'], 2); ?></span>
                                    </div>
                                    <div class="stat-item">
                                        <span class="stat-label">Active Rates:</span>
                                        <span class="stat-value"><?php echo $category['active_count'] ?? 0; ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>

            <!-- Compliance Section -->
            <section id="compliance" class="content-section">
                <div class="section-header">
                    <h2>Compliance Monitoring</h2>
                    <div class="section-actions">
                        <button class="btn-primary" onclick="generateComplianceReport()">
                            <i class="fas fa-file-alt"></i>
                            Generate Report
                        </button>
                        <button class="btn-secondary" onclick="scheduleAudit()">
                            <i class="fas fa-calendar"></i>
                            Schedule Audit
                        </button>
                    </div>
                </div>

                <div class="compliance-container">
                    <div class="compliance-dashboard">
                        <div class="compliance-card">
                            <div class="compliance-header">
                                <h3>Rate Compliance Status</h3>
                                <div class="compliance-score">
                                    <span class="score"><?php echo round(($compliance_status['approved_rates'] / max($compliance_status['total_rates'], 1)) * 100); ?>%</span>
                                    <span class="score-label">Compliant</span>
                                </div>
                            </div>
                            <div class="compliance-metrics">
                                <div class="metric">
                                    <span class="metric-label">Total Rates</span>
                                    <span class="metric-value"><?php echo $compliance_status['total_rates']; ?></span>
                                </div>
                                <div class="metric">
                                    <span class="metric-label">Approved</span>
                                    <span class="metric-value success"><?php echo $compliance_status['approved_rates']; ?></span>
                                </div>
                                <div class="metric">
                                    <span class="metric-label">Pending</span>
                                    <span class="metric-value warning"><?php echo $compliance_status['pending_rates']; ?></span>
                                </div>
                                <div class="metric">
                                    <span class="metric-label">Expired</span>
                                    <span class="metric-value danger"><?php echo $compliance_status['expired_rates']; ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="compliance-alerts">
                            <h4>Compliance Alerts</h4>
                            <div class="alert-list">
                                <?php if ($compliance_status['expiring_soon'] > 0): ?>
                                <div class="alert-item warning">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    <div class="alert-content">
                                        <span class="alert-title"><?php echo $compliance_status['expiring_soon']; ?> rates expiring soon</span>
                                        <span class="alert-description">Review and renew rates expiring in the next 30 days</span>
                                    </div>
                                    <button class="alert-action" onclick="viewExpiringRates()">View</button>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($compliance_status['expired_rates'] > 0): ?>
                                <div class="alert-item danger">
                                    <i class="fas fa-times-circle"></i>
                                    <div class="alert-content">
                                        <span class="alert-title"><?php echo $compliance_status['expired_rates']; ?> expired rates</span>
                                        <span class="alert-description">Immediate action required for expired rates</span>
                                    </div>
                                    <button class="alert-action" onclick="viewExpiredRates()">View</button>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($compliance_status['pending_rates'] > 5): ?>
                                <div class="alert-item info">
                                    <i class="fas fa-info-circle"></i>
                                    <div class="alert-content">
                                        <span class="alert-title">High volume of pending rates</span>
                                        <span class="alert-description"><?php echo $compliance_status['pending_rates']; ?> rates awaiting approval</span>
                                    </div>
                                    <button class="alert-action" onclick="showSection('rate-clearance')">Review</button>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Reports Section -->
            <section id="reports" class="content-section">
                <div class="section-header">
                    <h2>Rate Reports & Analytics</h2>
                    <div class="section-actions">
                        <button class="btn-primary" onclick="generateCustomReport()">
                            <i class="fas fa-chart-line"></i>
                            Custom Report
                        </button>
                        <button class="btn-secondary" onclick="scheduleReport()">
                            <i class="fas fa-clock"></i>
                            Schedule Report
                        </button>
                    </div>
                </div>

                <div class="reports-container">
                    <div class="report-cards">
                        <div class="report-card">
                            <div class="report-icon">
                                <i class="fas fa-chart-bar"></i>
                            </div>
                            <div class="report-info">
                                <h3>Rate Trend Analysis</h3>
                                <p>Analyze rate changes and trends over time</p>
                                <button class="btn-generate" onclick="generateTrendReport()">Generate</button>
                            </div>
                        </div>

                        <div class="report-card">
                            <div class="report-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="report-info">
                                <h3>Vendor Rate Comparison</h3>
                                <p>Compare rates across different vendors</p>
                                <button class="btn-generate" onclick="generateVendorReport()">Generate</button>
                            </div>
                        </div>

                        <div class="report-card">
                            <div class="report-icon">
                                <i class="fas fa-shield-alt"></i>
                            </div>
                            <div class="report-info">
                                <h3>Compliance Summary</h3>
                                <p>Comprehensive compliance status report</p>
                                <button class="btn-generate" onclick="generateComplianceReport()">Generate</button>
                            </div>
                        </div>

                        <div class="report-card">
                            <div class="report-icon">
                                <i class="fas fa-dollar-sign"></i>
                            </div>
                            <div class="report-info">
                                <h3>Cost Impact Analysis</h3>
                                <p>Analyze financial impact of rate changes</p>
                                <button class="btn-generate" onclick="generateCostReport()">Generate</button>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <!-- Rate Review Modal -->
    <div id="rateReviewModal" class="modal">
        <div class="modal-content large">
            <div class="modal-header">
                <h2>Rate Clearance Review</h2>
                <button class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <div class="rate-review-container">
                    <div class="rate-details-section">
                        <h3>Rate Details</h3>
                        <div class="rate-info-grid">
                            <div class="info-item">
                                <label>Rate Code:</label>
                                <span id="reviewRateCode">RC-2024-001</span>
                            </div>
                            <div class="info-item">
                                <label>Rate Type:</label>
                                <span id="reviewRateType">Consulting Services</span>
                            </div>
                            <div class="info-item">
                                <label>Vendor/Project:</label>
                                <span id="reviewEntity">Acme Consulting</span>
                            </div>
                            <div class="info-item">
                                <label>Current Rate:</label>
                                <span id="reviewCurrentRate">$150.00/hour</span>
                            </div>
                            <div class="info-item">
                                <label>Proposed Rate:</label>
                                <span id="reviewProposedRate">$175.00/hour</span>
                            </div>
                            <div class="info-item">
                                <label>Rate Change:</label>
                                <span id="reviewRateChange" class="rate-increase">+$25.00 (+16.7%)</span>
                            </div>
                        </div>
                    </div>

                    <div class="justification-section">
                        <h3>Justification</h3>
                        <div class="justification-content">
                            <p id="reviewJustification">Rate increase requested due to market conditions and increased expertise requirements.</p>
                        </div>
                    </div>

                    <div class="supporting-documents">
                        <h3>Supporting Documents</h3>
                        <div class="document-list">
                            <div class="document-item">
                                <i class="fas fa-file-pdf"></i>
                                <span>Market Analysis Report.pdf</span>
                                <button class="btn-link">View</button>
                            </div>
                            <div class="document-item">
                                <i class="fas fa-file-excel"></i>
                                <span>Rate Comparison.xlsx</span>
                                <button class="btn-link">View</button>
                            </div>
                        </div>
                    </div>

                    <div class="approval-section">
                        <h3>Approval Decision</h3>
                        <div class="approval-form">
                            <div class="form-group">
                                <label for="approvalDecision">Decision:</label>
                                <select id="approvalDecision" class="form-control">
                                    <option value="">Select Decision</option>
                                    <option value="approve">Approve</option>
                                    <option value="reject">Reject</option>
                                    <option value="request_revision">Request Revision</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="approvalComments">Comments:</label>
                                <textarea id="approvalComments" class="form-control" rows="4" placeholder="Add your comments..."></textarea>
                            </div>
                            <div class="form-group" id="effectiveDateGroup" style="display: none;">
                                <label for="effectiveDate">Effective Date:</label>
                                <input type="date" id="effectiveDate" class="form-control">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn-primary" onclick="submitRateDecision()">
                    <i class="fas fa-check"></i>
                    Submit Decision
                </button>
                <button class="btn-secondary" onclick="closeModal('rateReviewModal')">
                    Cancel
                </button>
            </div>
        </div>
    </div>

    <!-- New Rate Request Modal -->
    <div id="newRateModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>New Rate Clearance Request</h2>
                <button class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <form id="newRateForm" class="rate-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="rateType">Rate Type:</label>
                            <select id="rateType" class="form-control" required>
                                <option value="">Select Rate Type</option>
                                <?php foreach ($rate_categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="entityType">Entity Type:</label>
                            <select id="entityType" class="form-control" required>
                                <option value="">Select Type</option>
                                <option value="vendor">Vendor</option>
                                <option value="project">Project</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="proposedRate">Proposed Rate:</label>
                            <input type="number" id="proposedRate" class="form-control" step="0.01" required>
                        </div>
                        <div class="form-group">
                            <label for="rateUnit">Rate Unit:</label>
                            <select id="rateUnit" class="form-control" required>
                                <option value="hour">Per Hour</option>
                                <option value="day">Per Day</option>
                                <option value="month">Per Month</option>
                                <option value="project">Per Project</option>
                                <option value="unit">Per Unit</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="priority">Priority:</label>
                        <select id="priority" class="form-control" required>
                            <option value="low">Low</option>
                            <option value="medium" selected>Medium</option>
                            <option value="high">High</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="justification">Justification:</label>
                        <textarea id="justification" class="form-control" rows="4" required placeholder="Provide justification for this rate request..."></textarea>
                    </div>

                    <div class="form-group">
                        <label for="effectiveFrom">Effective From:</label>
                        <input type="date" id="effectiveFrom" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label for="expiresAt">Expires At:</label>
                        <input type="date" id="expiresAt" class="form-control">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn-primary" onclick="submitNewRate()">
                    <i class="fas fa-plus"></i>
                    Submit Request
                </button>
                <button class="btn-secondary" onclick="closeModal('newRateModal')">
                    Cancel
                </button>
            </div>
        </div>
    </div>

    <script src="../script.js"></script>
    <script src="rate-clearance.js"></script>
</body>
</html>
