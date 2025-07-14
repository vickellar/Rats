<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'finance_director') {
    error_log("Redirecting to index.php due to invalid role: " . (isset($_SESSION['role']) ? $_SESSION['role'] : 'No role set'));
    header("Location: ../index.php");
    exit();
}

include '../Database/db.php';

$payments = [];
$payment_approvals = [];

try {
    // Fetch payments with related data
    $stmt = $pdo->query("SELECT * FROM payments ORDER BY payment_date DESC");
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Fetch payment approvals with all related information
    $approval_query = "
        SELECT 
            p.payment_id,
        p.property_id,
        p.account_id,
        p.user_id,
        p.amount_paid,
        p.payment_date,
        p.payment_method,
        COALESCE(p.payment_status, p.payment_status) as payment_status,
        p.invoice_number,
        p.receipt_number,
        p.bill_id,
        p.notes,
        prop.address as property_address,
        prop.owner as property_owner,
        u.first_name,
        u.surname,
        cb.due_date,
        cb.invoice_number as bill_invoice_number,
        cb.total_balance,
        cb.overall_total
    FROM payments p
    LEFT JOIN properties prop ON p.property_id = prop.property_id
    LEFT JOIN users u ON p.user_id = u.user_id
    LEFT JOIN calculated_bills cb ON p.bill_id = cb.bill_id
    WHERE (p.payment_status = 'pending' OR p.payment_status IS NULL OR 
           p.payment_status = 'pending' OR p.payment_status IS NULL)
    ORDER BY p.payment_date DESC
";
    
    $approval_stmt = $pdo->prepare($approval_query);
    $approval_stmt->execute();
    $payment_approvals = $approval_stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Error fetching payments: " . $e->getMessage());
}

// Calculate financial data
try {
    // Count pending invoices from calculated_bills
    $pending_stmt = $pdo->query("SELECT COUNT(*) as count FROM calculated_bills WHERE due_date >= NOW()");
    $pending_result = $pending_stmt->fetch(PDO::FETCH_ASSOC);
    $pending_invoices = $pending_result['count'];
    
    // Count approved payments
    $approved_stmt = $pdo->query("
    SELECT COUNT(*) as count 
    FROM payments 
    WHERE payment_status = 'approved' OR payment_status = 'approved'
");
    $approved_result = $approved_stmt->fetch(PDO::FETCH_ASSOC);
    $approved_payments = $approved_result['count'];
    
    // Calculate total pending amount
    $pending_amount_stmt = $pdo->query("
    SELECT SUM(amount_paid) as total 
    FROM payments 
    WHERE payment_status = 'pending' OR payment_status IS NULL OR
          payment_status = 'pending' OR payment_status IS NULL
");
    $pending_amount_result = $pending_amount_stmt->fetch(PDO::FETCH_ASSOC);
    $total_amount_pending = $pending_amount_result['total'] ?? 0;
    
    $monthly_budget = 500000; // This could also come from a budget table
    
} catch (PDOException $e) {
    error_log("Error calculating financial data: " . $e->getMessage());
    $pending_invoices = 0;
    $approved_payments = 0;
    $total_amount_pending = 0;
    $monthly_budget = 500000;
}

foreach ($payments as $payment) {
    $payment['payment_id'];
    $payment['property_id'];
    $payment['account_id'];
    $payment['user_id'];
    $payment['receipt_name'];
    $payment['receipt_fpath'];
    $payment['amount_paid'];
    $payment['payment_date'];
    $payment['payment_method'];
    $payment['payment_status'];
    $payment['invoice_number'];
    $payment['receipt_number'];
    $payment['bill_id'];
    $payment['notes'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finance Director Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <style>
.large-modal .modal-content {
    max-width: 900px;
    width: 90%;
}

.review-section {
    margin-bottom: 30px;
    background: #f8f9fa;
    border-radius: 8px;
    padding: 20px;
    border-left: 4px solid #007bff;
}

.review-section h3 {
    color: #2c3e50;
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.review-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 15px;
    margin-bottom: 15px;
}

.review-item {
    background: white;
    padding: 12px;
    border-radius: 6px;
    border: 1px solid #dee2e6;
}

.review-item label {
    font-weight: 600;
    color: #495057;
    display: block;
    margin-bottom: 5px;
    font-size: 0.9rem;
}

.review-item span {
    color: #212529;
    font-size: 1rem;
}

.monthly-breakdown {
    background: white;
    border-radius: 6px;
    overflow: hidden;
    border: 1px solid #dee2e6;
}

.monthly-breakdown table {
    width: 100%;
    border-collapse: collapse;
}

.monthly-breakdown th,
.monthly-breakdown td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #dee2e6;
}

.monthly-breakdown th {
    background: #e9ecef;
    font-weight: 600;
    color: #495057;
}

.receipt-preview {
    background: white;
    border-radius: 6px;
    padding: 15px;
    border: 1px solid #dee2e6;
    text-align: center;
}

.receipt-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: #007bff;
    color: white;
    padding: 10px 20px;
    border-radius: 6px;
    text-decoration: none;
    transition: background-color 0.3s;
}

.receipt-link:hover {
    background: #0056b3;
    color: white;
}

.loading-spinner {
    text-align: center;
    padding: 40px;
    color: #6c757d;
}

.loading-spinner i {
    font-size: 2rem;
    margin-bottom: 10px;
}

.amount-highlight {
    font-size: 1.2rem;
    font-weight: 700;
    color: #28a745;
}

.status-pending {
    background: #fff3cd;
    color: #856404;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.85rem;
    font-weight: 500;
}

.status-approved {
    background: #d4edda;
    color: #155724;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.85rem;
    font-weight: 500;
}

.status-rejected {
    background: #f8d7da;
    color: #721c24;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.85rem;
    font-weight: 500;
}

/* New Styles for Invoice Layout */
.invoice-header {
    background-color: #f0f0f0;
    padding: 15px;
    border-bottom: 2px solid #ddd;
}

.invoice-section {
    margin-bottom: 20px;
    border: 1px solid #ddd;
    border-radius: 8px;
    overflow: hidden;
}

.section-header-blue {
    background-color: #007bff;
    color: white;
    padding: 10px;
    text-align: left;
    font-weight: bold;
    border-bottom: 1px solid #ddd;
}

.invoice-details-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 10px;
    padding: 15px;
    background: white;
}

.detail-row {
    display: flex;
    flex-direction: column;
}

.detail-label {
    font-weight: bold;
    color: #555;
    margin-bottom: 5px;
}

.detail-value {
    color: #333;
}

.rate-breakdown {
    padding: 15px;
    background: white;
}

.account-info {
    margin-bottom: 15px;
    padding: 10px;
    background-color: #e9ecef;
    border-left: 5px solid #007bff;
    font-style: italic;
}

.breakdown-item {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    border-bottom: 1px dashed #ccc;
}

.breakdown-label {
    font-weight: 500;
    color: #444;
}

.breakdown-value {
    font-weight: bold;
    color: #333;
}

.monthly-rates {
    margin-top: 10px;
    padding: 10px;
    background-color: #f8f9fa;
    border-radius: 6px;
}

.month-item {
    display: flex;
    justify-content: space-between;
    padding: 6px 0;
}

.month-label {
    color: #666;
}

.month-value {
    font-weight: bold;
}

.financial-summary {
    padding: 15px;
    background: white;
}

.summary-item {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
}

.summary-label {
    font-weight: bold;
    color: #555;
}

.summary-value {
    color: #333;
}

.total-section {
    text-align: right;
    padding-top: 10px;
}

.total-value {
    font-size: 1.5rem;
    font-weight: bold;
    color: #28a745;
}

/* Invoice Layout Styles */
.invoice-header {
    background: white;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.invoice-section {
    margin-bottom: 20px;
    background: white;
    border-radius: 8px;
    overflow: hidden;
    border: 1px solid #dee2e6;
}

.section-header-blue {
    background: #6c7ae0;
    color: white;
    padding: 12px 15px;
    font-weight: 600;
    font-size: 0.95rem;
}

.invoice-details-grid {
    padding: 15px;
}

.detail-row {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    border-bottom: 1px solid #f0f0f0;
}

.detail-row:last-child {
    border-bottom: none;
}

.detail-label {
    font-weight: 500;
    color: #333;
}

.detail-value {
    color: #666;
}

.rate-breakdown {
    padding: 15px;
}

.account-info {
    margin-bottom: 15px;
    font-size: 1rem;
}

.breakdown-item {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    border-bottom: 1px solid #f0f0f0;
}

.breakdown-label {
    font-weight: 500;
    color: #333;
}

.breakdown-value {
    color: #666;
    text-align: right;
}

.monthly-rates {
    margin-top: 15px;
}

.month-item {
    display: flex;
    justify-content: space-between;
    padding: 5px 0;
    padding-left: 20px;
}

.month-label {
    color: #333;
}

.month-value {
    color: #666;
    text-align: right;
}

.financial-summary {
    padding: 15px;
}

.summary-item {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
}

.summary-label {
    font-weight: 500;
    color: #333;
}

.summary-value {
    color: #666;
    text-align: right;
}

.total-section {
    background: #6c7ae0;
    color: white;
    padding: 12px 15px;
    margin: 10px -15px -15px -15px;
    text-align: right;
    font-weight: bold;
    font-size: 1.1rem;
}

.total-value {
    font-size: 1.2rem;
    font-weight: bold;
}
</style>
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
                    <li class="nav-item active">
                        <a href="#dashboard" class="nav-link" data-section="dashboard">
                            <i class="fas fa-tachometer-alt"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#invoices" class="nav-link" data-section="invoices">
                            <i class="fas fa-file-invoice-dollar"></i>
                            <span>Invoice Verification</span>
                            <span class="badge"><?php echo $pending_invoices; ?></span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#payments" class="nav-link" data-section="payments">
                            <i class="fas fa-credit-card"></i>
                            <span>Payment Approvals</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#reports" class="nav-link" data-section="reports">
                            <i class="fas fa-chart-bar"></i>
                            <span>Financial Reports</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#audit" class="nav-link" data-section="audit">
                            <i class="fas fa-search"></i>
                            <span>Audit Trail</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#budget" class="nav-link" data-section="budget">
                            <i class="fas fa-calculator"></i>
                            <span>Budget Management</span>
                        </a>
                    </li>
                </ul>
            </nav>
            
            <div class="sidebar-footer">
                <div class="user-profile">
                    <img src="/placeholder.svg?height=40&width=40" alt="Profile" class="profile-avatar">
                    <div class="user-info">
                        <span class="user-name"><?php echo $_SESSION['username'] ?? 'Finance Director'; ?></span>
                        <span class="user-role">Finance Director</span>
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
                    <h1 class="page-title">Finance Director Dashboard</h1>
                </div>
                
                <div class="header-right">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" placeholder="Search invoices, payments...">
                    </div>
                    
                    <div class="notifications">
                        <button class="notification-btn">
                            <i class="fas fa-bell"></i>
                            <span class="notification-badge"><?php echo $pending_invoices; ?></span>
                        </button>
                    </div>
                </div>
            </header>

            <!-- Dashboard Section -->
            <section id="dashboard" class="content-section active">
                <div class="stats-grid">
                    <div class="stat-card primary">
                        <div class="stat-icon">
                            <i class="fas fa-file-invoice"></i>
                        </div>
                        <div class="stat-content">
                            <h3>Pending Invoices</h3>
                            <p class="stat-number"><?php echo $pending_invoices; ?></p>
                            <span class="stat-change positive">+3 from yesterday</span>
                        </div>
                    </div>
                    
                    <div class="stat-card success">
                        <div class="stat-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-content">
                            <h3>Approved Payments</h3>
                            <p class="stat-number"><?php echo $approved_payments; ?></p>
                            <span class="stat-change positive">+12 this week</span>
                        </div>
                    </div>
                    
                    <div class="stat-card warning">
                        <div class="stat-icon">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                        <div class="stat-content">
                            <h3>Pending Amount</h3>
                            <p class="stat-number">$<?php echo number_format($total_amount_pending); ?></p>
                            <span class="stat-change negative">-5% from last month</span>
                        </div>
                    </div>
                    
                    <div class="stat-card info">
                        <div class="stat-icon">
                            <i class="fas fa-chart-pie"></i>
                        </div>
                        <div class="stat-content">
                            <h3>Budget Utilization</h3>
                            <p class="stat-number"><?php echo $monthly_budget > 0 ? round(($total_amount_pending / $monthly_budget) * 100) : 0; ?>%</p>
                            <span class="stat-change neutral">On track</span>
                        </div>
                    </div>
                </div>

                <div class="dashboard-grid">
                    <div class="dashboard-card">
                        <div class="card-header">
                            <h3>Recent Invoice Submissions</h3>
                            <button class="btn-secondary">View All</button>
                        </div>
                        <div class="card-content">
                            <div class="invoice-list">
                                <?php 
                                // Display recent invoices from calculated_bills
                                try {
                                    $recent_invoices_query = "
                                        SELECT cb.invoice_number, cb.overall_total, cb.calculated_at, p.owner
                                        FROM calculated_bills cb
                                        LEFT JOIN properties p ON cb.property_id = p.property_id
                                        ORDER BY cb.calculated_at DESC
                                        LIMIT 3
                                    ";
                                    $recent_stmt = $pdo->prepare($recent_invoices_query);
                                    $recent_stmt->execute();
                                    $recent_invoices = $recent_stmt->fetchAll(PDO::FETCH_ASSOC);
                                    
                                    foreach ($recent_invoices as $index => $invoice): 
                                        $urgency_class = $index === 0 ? 'urgent' : '';
                                        $status_badge = $index === 0 ? 'urgent' : 'pending';
                                        $time_ago = $index === 0 ? '2 hours ago' : ($index === 1 ? '5 hours ago' : '1 day ago');
                                ?>
                                <div class="invoice-item <?php echo $urgency_class; ?>">
                                    <div class="invoice-info">
                                        <span class="invoice-number"><?php echo htmlspecialchars($invoice['invoice_number']); ?></span>
                                        <span class="property-owner"><?php echo htmlspecialchars($invoice['owner'] ?? 'Unknown Owner'); ?></span>
                                        <span class="amount">$<?php echo number_format($invoice['overall_total']); ?></span>
                                    </div>
                                    <div class="invoice-status">
                                        <span class="status-badge <?php echo $status_badge; ?>"><?php echo ucfirst($status_badge); ?></span>
                                        <span class="time"><?php echo $time_ago; ?></span>
                                    </div>
                                </div>
                                <?php endforeach; 
                                } catch (PDOException $e) {
                                    echo '<div class="invoice-item"><div class="invoice-info">No recent invoices found</div></div>';
                                }
                                ?>
                            </div>
                        </div>
                    </div>

                    <div class="dashboard-card">
                        <div class="card-header">
                            <h3>Payment Approval Queue</h3>
                            <button class="btn-secondary">Manage Queue</button>
                        </div>
                        <div class="card-content">
                            <div class="approval-queue">
                                <?php 
                                // Display first 2 payment approvals
                                $queue_items = array_slice($payment_approvals, 0, 2);
                                foreach ($queue_items as $index => $approval): 
                                    $priority_class = $index === 0 ? 'high-priority' : '';
                                ?>
                                <div class="queue-item <?php echo $priority_class; ?>">
                                    <div class="priority-indicator"></div>
                                    <div class="queue-info">
                                        <span class="payment-ref">PAY-<?php echo htmlspecialchars($approval['payment_id']); ?></span>
                                        <span class="description"><?php echo htmlspecialchars($approval['property_owner'] ?? 'Unknown Owner'); ?> - Payment</span>
                                        <span class="amount">$<?php echo number_format($approval['amount_paid']); ?></span>
                                    </div>
                                    <div class="queue-actions">
                                        <button class="btn-approve" onclick="approvePaymentWithConfirmation(<?php echo $approval['payment_id']; ?>)">Approve</button>
                                        <button class="btn-review" onclick="reviewPayment(<?php echo $approval['payment_id']; ?>)">Review</button>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Invoice Verification Section -->
            <section id="invoices" class="content-section">
                <div class="section-header">
                    <h2>Invoice Verification</h2>
                    <div class="section-actions">
                        <button class="btn-primary">
                            <i class="fas fa-upload"></i>
                            Upload Invoice
                        </button>
                        <button class="btn-secondary">
                            <i class="fas fa-filter"></i>
                            Filter
                        </button>
                    </div>
                </div>
                <div class="invoice-verification-container">
                    <div class="invoice-table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Invoice #</th>
                                    <th>Property Owner</th>
                                    <th>Amount</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Priority</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                try {
                                    $invoice_query = "
                                        SELECT cb.invoice_number, cb.overall_total, cb.calculated_at, cb.due_date, p.owner
                                        FROM calculated_bills cb
                                        LEFT JOIN properties p ON cb.property_id = p.property_id
                                        ORDER BY cb.calculated_at DESC
                                        LIMIT 10
                                    ";
                                    $invoice_stmt = $pdo->prepare($invoice_query);
                                    $invoice_stmt->execute();
                                    $invoices = $invoice_stmt->fetchAll(PDO::FETCH_ASSOC);
                                    
                                    foreach ($invoices as $invoice): 
                                        $status = (strtotime($invoice['due_date']) < time()) ? 'urgent' : 'pending';
                                        $priority = (strtotime($invoice['due_date']) < time()) ? 'high' : 'medium';
                                ?>
                                <tr class="invoice-row" data-invoice="<?php echo htmlspecialchars($invoice['invoice_number']); ?>">
                                    <td><?php echo htmlspecialchars($invoice['invoice_number']); ?></td>
                                    <td><?php echo htmlspecialchars($invoice['owner'] ?? 'Unknown Owner'); ?></td>
                                    <td>$<?php echo number_format($invoice['overall_total']); ?></td>
                                    <td><?php echo date('Y-m-d', strtotime($invoice['calculated_at'])); ?></td>
                                    <td><span class="status-badge <?php echo $status; ?>"><?php echo ucfirst($status); ?></span></td>
                                    <td><span class="priority-badge <?php echo $priority; ?>"><?php echo ucfirst($priority); ?></span></td>
                                    <td>
                                        <button class="btn-action" onclick="openInvoiceModal('<?php echo htmlspecialchars($invoice['invoice_number']); ?>')">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn-action">
                                            <i class="fas fa-download"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; 
                                } catch (PDOException $e) {
                                    echo '<tr><td colspan="7">Error loading invoices</td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <!-- Payment Approvals Section -->
            <section id="payments" class="content-section">
                <div class="section-header">
                    <h2>Payment Approvals</h2>
                    <div class="section-actions">
                        <button class="btn-primary">Bulk Approve</button>
                        <button class="btn-secondary">Export Report</button>
                    </div>
                </div>
                <div class="payment-approvals-container">
                    <div class="approval-filters">
                        <select class="filter-select">
                            <option>All Payments</option>
                            <option>Pending Approval</option>
                            <option>Approved</option>
                            <option>Rejected</option>
                        </select>
                        <select class="filter-select">
                            <option>All Amounts</option>
                            <option>Under $1,000</option>
                            <option>$1,000 - $10,000</option>
                            <option>Over $10,000</option>
                        </select>
                    </div>
                    <div class="payment-grid">
                        <?php foreach ($payment_approvals as $approval): ?>
                        <div class="payment-card">
                            <div class="payment-header">
                                <span class="payment-id">PAY-<?php echo htmlspecialchars($approval['payment_id']); ?></span>
                                <span class="payment-amount">$<?php echo number_format($approval['amount_paid']); ?></span>
                            </div>
                            <div class="payment-details">
                                <p><strong>Property Owner:</strong> <?php echo htmlspecialchars($approval['property_owner'] ?? 'Unknown Owner'); ?></p>
                                <p><strong>Property Address:</strong> <?php echo htmlspecialchars($approval['property_address'] ?? 'Address not available'); ?></p>
                                <p><strong>Due Date:</strong> 
                                    <?php echo $approval['due_date'] ? date('d-m-Y', strtotime($approval['due_date'])) : 'Not set'; ?>
                                </p>
                                <p><strong>Requested by:</strong> <?php echo htmlspecialchars(($approval['first_name'] ?? '') . ' ' . ($approval['surname'] ?? '')); ?></p>
                                <?php if ($approval['notes']): ?>
                                <p><strong>Notes:</strong> <?php echo htmlspecialchars($approval['notes']); ?></p>
                                <?php endif; ?>
                            </div>
                            <div class="payment-actions">
                                <button class="btn-approve" onclick="approvePaymentWithConfirmation(<?php echo $approval['payment_id']; ?>)">
                                    <i class="fas fa-check"></i>
                                    Approve
                                </button>
                                <button class="btn-reject" onclick="rejectPayment(<?php echo $approval['payment_id']; ?>)">
                                    <i class="fas fa-times"></i>
                                    Reject
                                </button>
                                <button class="btn-review" onclick="reviewPayment(<?php echo $approval['payment_id']; ?>)">
                                    <i class="fas fa-eye"></i>
                                    Review
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        
                        <?php if (empty($payment_approvals)): ?>
                        <div class="payment-card">
                            <div class="payment-details">
                                <p>No pending payment approvals at this time.</p>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </section>

            <!-- Financial Reports Section -->
            <section id="reports" class="content-section">
                <div class="section-header">
                    <h2>Financial Reports</h2>
                    <div class="section-actions">
                        <button class="btn-primary">Generate Report</button>
                        <button class="btn-secondary">Schedule Report</button>
                    </div>
                </div>
                <div class="reports-container">
                    <div class="report-cards">
                        <div class="report-card">
                            <div class="report-icon">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <div class="report-info">
                                <h3>Monthly Financial Summary</h3>
                                <p>Comprehensive overview of monthly finances</p>
                                <button class="btn-generate">Generate</button>
                            </div>
                        </div>
                        <div class="report-card">
                            <div class="report-icon">
                                <i class="fas fa-file-invoice-dollar"></i>
                            </div>
                            <div class="report-info">
                                <h3>Invoice Analysis Report</h3>
                                <p>Detailed analysis of invoice processing</p>
                                <button class="btn-generate">Generate</button>
                            </div>
                        </div>
                        <div class="report-card">
                            <div class="report-icon">
                                <i class="fas fa-chart-pie"></i>
                            </div>
                            <div class="report-info">
                                <h3>Budget Variance Report</h3>
                                <p>Compare actual vs budgeted expenses</p>
                                <button class="btn-generate">Generate</button>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Audit Trail Section -->
            <section id="audit" class="content-section">
                <div class="section-header">
                    <h2>Audit Trail</h2>
                    <div class="section-actions">
                        <button class="btn-secondary">Export Audit Log</button>
                    </div>
                </div>
                <div class="audit-container">
                    <div class="audit-filters">
                        <input type="date" class="filter-input" placeholder="From Date">
                        <input type="date" class="filter-input" placeholder="To Date">
                        <select class="filter-select">
                            <option>All Actions</option>
                            <option>Invoice Approved</option>
                            <option>Payment Approved</option>
                            <option>Payment Rejected</option>
                        </select>
                        <button class="btn-filter">Apply Filters</button>
                    </div>
                    <div class="audit-timeline">
                        <?php 
                        // Display recent payment activities
                        try {
                            $audit_query = "
                                SELECT p.payment_id, p.payment_status, p.payment_date, u.first_name, u.surname
                                FROM payments p
                                LEFT JOIN users u ON p.user_id = u.user_id
                                WHERE p.payment_status IN ('approved', 'rejected')
                                ORDER BY p.payment_date DESC
                                LIMIT 5
                            ";
                            $audit_stmt = $pdo->prepare($audit_query);
                            $audit_stmt->execute();
                            $audit_items = $audit_stmt->fetchAll(PDO::FETCH_ASSOC);
                            
                            foreach ($audit_items as $audit): 
                                $action_class = $audit['payment_status'] === 'approved' ? 'approved' : 'rejected';
                                $action_text = $audit['payment_status'] === 'approved' ? 'approved' : 'rejected';
                        ?>
                        <div class="audit-item">
                            <div class="audit-time"><?php echo date('Y-m-d H:i', strtotime($audit['payment_date'])); ?></div>
                            <div class="audit-action <?php echo $action_class; ?>">Payment PAY-<?php echo $audit['payment_id']; ?> <?php echo $action_text; ?></div>
                            <div class="audit-user">by Finance Director</div>
                        </div>
                        <?php endforeach; 
                        } catch (PDOException $e) {
                            echo '<div class="audit-item"><div class="audit-action">No audit trail available</div></div>';
                        }
                        ?>
                    </div>
                </div>
            </section>

            <!-- Budget Management Section -->
            <section id="budget" class="content-section">
                <div class="section-header">
                    <h2>Budget Management</h2>
                    <div class="section-actions">
                        <button class="btn-primary">Update Budget</button>
                        <button class="btn-secondary">Budget Forecast</button>
                    </div>
                </div>
                <div class="budget-container">
                    <div class="budget-overview">
                        <div class="budget-card">
                            <h3>Monthly Budget</h3>
                            <div class="budget-amount">$<?php echo number_format($monthly_budget); ?></div>
                            <div class="budget-progress">
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?php echo $monthly_budget > 0 ? ($total_amount_pending / $monthly_budget) * 100 : 0; ?>%"></div>
                                </div>
                                <span><?php echo $monthly_budget > 0 ? round(($total_amount_pending / $monthly_budget) * 100) : 0; ?>% utilized</span>
                            </div>
                        </div>
                        <div class="budget-card">
                            <h3>Remaining Budget</h3>
                            <div class="budget-amount">$<?php echo number_format($monthly_budget - $total_amount_pending); ?></div>
                            <div class="budget-status positive">Within limits</div>
                        </div>
                    </div>
                    <div class="budget-breakdown">
                        <h3>Budget Breakdown by Category</h3>
                        <div class="category-list">
                            <div class="category-item">
                                <span class="category-name">Operations</span>
                                <span class="category-amount">$150,000</span>
                                <div class="category-bar">
                                    <div class="category-fill" style="width: 60%"></div>
                                </div>
                            </div>
                            
                            <div class="category-item">
                                <span class="category-name">Marketing</span>
                                <span class="category-amount">$100,000</span>
                                <div class="category-bar">
                                    <div class="category-fill" style="width: 45%"></div>
                                </div>
                            </div>
                            
                            <div class="category-item">
                                <span class="category-name">Technology</span>
                                <span class="category-amount">$200,000</span>
                                <div class="category-bar">
                                    <div class="category-fill" style="width: 75%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <!-- Invoice Modal -->
    <div id="invoiceModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Invoice Verification</h2>
                <button class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <div class="invoice-details">
                    <div class="invoice-info-grid">
                        <div class="info-item">
                            <label>Invoice Number:</label>
                            <span id="modalInvoiceNumber">INV-2024-001</span>
                        </div>
                        <div class="info-item">
                            <label>Property Owner:</label>
                            <span id="modalPropertyOwner">Acme Corporation</span>
                        </div>
                        <div class="info-item">
                            <label>Amount:</label>
                            <span id="modalAmount">$15,000.00</span>
                        </div>
                        <div class="info-item">
                            <label>Date:</label>
                            <span id="modalDate">2024-01-15</span>
                        </div>
                    </div>
                    
                    <div class="invoice-verification">
                        <h3>Verification Checklist</h3>
                        <div class="verification-items">
                            <label class="verification-item">
                                <input type="checkbox" checked>
                                <span>Invoice amount matches purchase order</span>
                            </label>
                            <label class="verification-item">
                                <input type="checkbox" checked>
                                <span>Property Owner information is correct</span>
                            </label>
                            <label class="verification-item">
                                <input type="checkbox">
                                <span>Goods/services have been received</span>
                            </label>
                            <label class="verification-item">
                                <input type="checkbox">
                                <span>Invoice is within budget allocation</span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="invoice-document">
                        <h3>Invoice Document</h3>
                        <div class="document-viewer">
                            <iframe src="/placeholder.svg?height=400&width=600" width="100%" height="400"></iframe>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn-approve">
                    <i class="fas fa-check"></i>
                    Approve Invoice
                </button>
                <button class="btn-reject">
                    <i class="fas fa-times"></i>
                    Reject Invoice
                </button>
                <button class="btn-secondary">
                    <i class="fas fa-comment"></i>
                    Add Comment
                </button>
            </div>
        </div>
    </div>

    


        <!-- Payment Review Modal -->
    <div id="paymentReviewModal" class="modal" style="display:none;">
        <div class="modal-content large-modal">
            <div class="modal-header">
                <h2><i class="fas fa-file-invoice-dollar"></i> Payment Review</h2>
                <button class="modal-close" onclick="closePaymentReviewModal()">&times;</button>
            </div>
            <div class="modal-body" id="paymentReviewBody">
                <div class="loading-spinner">
                    <i class="fas fa-spinner fa-spin"></i>
                    <p>Loading payment details...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn-approve" id="approvePaymentBtn">
                    <i class="fas fa-check"></i>
                    Approve Payment
                </button>
                <button class="btn-reject" id="rejectPaymentBtn">
                    <i class="fas fa-times"></i>
                    Reject Payment
                </button>
                <button class="btn-secondary" onclick="closePaymentReviewModal()">
                    <i class="fas fa-times-circle"></i>
                    Close
                </button>
            </div>
        </div>
    </div>

 <!-- Rejection Modal -->
    <div id="rejectionModal" class="rejection-modal">
        <div class="rejection-modal-content">
            <h3>Reject Payment</h3>
            <div class="rejection-form">
                <label for="rejectionReason">Reason for Rejection:</label>
                <textarea id="rejectionReason" placeholder="Please provide a detailed reason for rejecting this payment..." required></textarea>
                <div class="rejection-form-buttons">
                    <button class="btn-secondary" onclick="closeRejectionModal()">Cancel</button>
                    <button class="btn-reject" onclick="confirmRejection()">Confirm Rejection</button>
                </div>
            </div>
        </div>
    </div>



    <script>
        // JavaScript functions aprove payment with confirnamtion
        function approvePaymentWithConfirmation(paymentId) {
            if (confirm('Are you sure you want to approve this payment?')) {
                // AJAX call to approve payment
                fetch('approve_payment.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        payment_id: paymentId,
                        action: 'approve'
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Payment approved successfully');
                        location.reload();
                    } else {
                        alert('Error approving payment: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error approving payment');
                });
            }
        }

        function rejectPayment(paymentId) {
            if (confirm('Are you sure you want to reject this payment?')) {
                // AJAX call to reject payment
                fetch('approve_payment.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        payment_id: paymentId,
                        action: 'reject'
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Payment rejected successfully');
                        location.reload();
                    } else {
                        alert('Error rejecting payment: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error rejecting payment');
                });
            }
        }

        function reviewPayment(paymentId) {
            // Show modal with loading state
            document.getElementById('paymentReviewModal').style.display = 'block';
            document.getElementById('paymentReviewBody').innerHTML = `
                <div class="loading-spinner">
                    <i class="fas fa-spinner fa-spin"></i>
                    <p>Loading payment details...</p>
                </div>
            `;
            
            // Fetch payment and invoice details via AJAX
            fetch('get_payment_details.php?payment_id=' + paymentId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        let html = '';
                        
                        // Invoice Details Section - New Invoice Layout
                        if (data.invoice) {
                            html += `
                                <div class="review-section">
                                    <div class="invoice-header">
                                        <h2 style="text-align: center; margin: 0; font-size: 1.5rem; font-weight: bold;">RATES CLEARANCE INVOICE</h2>
                                        <p style="text-align: right; margin: 10px 0; font-size: 0.9rem; color: #666;">
                                            Generated on: ${new Date().toLocaleDateString()} ${new Date().toLocaleTimeString()}
                                        </p>
                                        <hr style="border: 1px solid #333; margin: 10px 0;">
                                    </div>
                                    
                                    <!-- Invoice Details -->
                                    <div class="invoice-section">
                                        <div class="section-header-blue">Invoice Details</div>
                                        <div class="invoice-details-grid">
                                            <div class="detail-row">
                                                <span class="detail-label">Invoice Number:</span>
                                                <span class="detail-value">${data.invoice.invoice_number}</span>
                                            </div>
                                            <div class="detail-row">
                                                <span class="detail-label">Invoice Date:</span>
                                                <span class="detail-value">${new Date(data.invoice.calculated_at).toLocaleDateString()}</span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Rate Breakdown -->
                                    <div class="invoice-section">
                                        <div class="section-header-blue">Rate Breakdown</div>
                                        <div class="rate-breakdown">
                                            <div class="account-info">
                                                <strong>Account: ${data.account ? data.account.account_number : 'N/A'}</strong>
                                            </div>
                                            <div class="breakdown-item">
                                                <span class="breakdown-label">Account Balance:</span>
                                                <span class="breakdown-value">$${Number(data.account ? data.account.account_balance : 0).toFixed(2)}</span>
                                            </div>
                                            <div class="monthly-rates">
                                                <div class="breakdown-label" style="margin-bottom: 10px;"><strong>Monthly Rates:</strong></div>
            `;
            
            // Add monthly breakdown if available
            if (data.monthly_breakdown && data.monthly_breakdown.length > 0) {
                data.monthly_breakdown.forEach(month => {
                    if (month.month1_name) {
                        html += `<div class="month-item">
                            <span class="month-label">${month.month1_name}:</span>
                            <span class="month-value">$${Number(month.monthly_balance / 3).toFixed(2)}</span>
                        </div>`;
                    }
                    if (month.month2_name) {
                        html += `<div class="month-item">
                            <span class="month-label">${month.month2_name}:</span>
                            <span class="month-value">$${Number(month.monthly_balance / 3).toFixed(2)}</span>
                        </div>`;
                    }
                    if (month.month3_name) {
                        html += `<div class="month-item">
                            <span class="month-label">${month.month3_name}:</span>
                            <span class="month-value">$${Number(month.monthly_balance / 3).toFixed(2)}</span>
                        </div>`;
                    }
                    if (month.month4_name) {
                        html += `<div class="month-item">
                            <span class="month-label">${month.month4_name}:</span>
                            <span class="month-value">$${Number(month.monthly_balance / 4).toFixed(2)}</span>
                        </div>`;
                    }
                });
            } else {
                // Default monthly breakdown if no data
                html += `
                    <div class="month-item">
                        <span class="month-label">February:</span>
                        <span class="month-value">$${Number(data.invoice.total_balance / 3).toFixed(2)}</span>
                    </div>
                    <div class="month-item">
                        <span class="month-label">March:</span>
                        <span class="month-value">$${Number(data.invoice.total_balance / 3).toFixed(2)}</span>
                    </div>
                    <div class="month-item">
                        <span class="month-label">April:</span>
                        <span class="month-value">$${Number(data.invoice.total_balance / 3).toFixed(2)}</span>
                    </div>
                `;
            }
            
            html += `
                            </div>
                        </div>
                    </div>
                    
                    <!-- Financial Summary -->
                    <div class="invoice-section">
                        <div class="section-header-blue">Financial Summary</div>
                        <div class="financial-summary">
                            <div class="summary-item">
                                <span class="summary-label">Subtotal (All Rates):</span>
                                <span class="summary-value">$${Number(data.invoice.total_balance).toFixed(2)}</span>
                            </div>
                            <div class="summary-item">
                                <span class="summary-label">Processing Fee:</span>
                                <span class="summary-value">$${Number(data.invoice.processing_fee).toFixed(2)}</span>
                            </div>
                            <hr style="border: 1px solid #333; margin: 10px 0;">
                            <div class="total-section">
                                <span class="total-value">$${Number(data.invoice.overall_total).toFixed(2)}</span>
                            </div>
                        </div>
                    </div>
                    
                    ${data.invoice.comments ? `
                        <div class="invoice-section">
                            <div class="section-header-blue">Comments</div>
                            <div style="padding: 10px; background: white;">
                                ${data.invoice.comments}
                            </div>
                        </div>
                    ` : ''}
                </div>
            `;
        }
                        
                        // Payment Details Section
                        if (data.payment) {
                            const statusClass = data.payment.payment_status ? 
                                `status-${data.payment.payment_status.toLowerCase()}` : 'status-pending';
                            
                            html += `
                                <div class="review-section">
                                    <h3><i class="fas fa-credit-card"></i> Payment Details</h3>
                                    <div class="review-grid">
                                        <div class="review-item">
                                            <label>Payment ID:</label>
                                            <span>PAY-${data.payment.payment_id}</span>
                                        </div>
                                        <div class="review-item">
                                            <label>Amount Paid:</label>
                                            <span class="amount-highlight">$${Number(data.payment.amount_paid).toLocaleString()}</span>
                                        </div>
                                        <div class="review-item">
                                            <label>Payment Date:</label>
                                            <span>${new Date(data.payment.payment_date).toLocaleDateString()}</span>
                                        </div>
                                        <div class="review-item">
                                            <label>Payment Method:</label>
                                            <span>${data.payment.payment_method}</span>
                                        </div>
                                        <div class="review-item">
                                            <label>Transaction Status:</label>
                                            <span class="${statusClass}">${data.payment.payment_status || 'Pending'}</span>
                                        </div>
                                        <div class="review-item">
                                            <label>Receipt Number:</label>
                                            <span>${data.payment.receipt_number}</span>
                                        </div>
                                    </div>
                                    
                                    ${data.payment.notes ? `
                                        <div class="review-item" style="margin-top: 15px;">
                                            <label>Notes:</label>
                                            <span>${data.payment.notes}</span>
                                        </div>
                                    ` : ''}
                                    
                                    ${data.payment.receipt_fpath ? `
                                        <div style="margin-top: 20px;">
                                            <h4>Receipt File</h4>
                                            <div class="receipt-preview">
                                                <p><strong>File:</strong> ${data.payment.receipt_name}</p>
                                                <a href="${data.payment.receipt_fpath}" target="_blank" class="receipt-link">
                                                    <i class="fas fa-external-link-alt"></i>
                                                    View Receipt
                                                </a>
                                            </div>
                                        </div>
                                    ` : ''}
                                </div>
                            `;
                        }
                        
                        // Property Details Section
                        if (data.property) {
                            html += `
                                <div class="review-section">
                                    <h3><i class="fas fa-building"></i> Property Details</h3>
                                    <div class="review-grid">
                                        <div class="review-item">
                                            <label>Property Address:</label>
                                            <span>${data.property.address}</span>
                                        </div>
                                        <div class="review-item">
                                            <label>Property Owner:</label>
                                            <span>${data.property.owner}</span>
                                        </div>
                                    </div>
                                </div>
                            `;
                        }
                        
                        document.getElementById('paymentReviewBody').innerHTML = html;
                        
                        // Set approve/reject button actions
                        document.getElementById('approvePaymentBtn').onclick = function() {
                            approvePaymentWithConfirmation(paymentId);
                            closePaymentReviewModal();
                        };
                        document.getElementById('rejectPaymentBtn').onclick = function() {
                            rejectPayment(paymentId);
                            closePaymentReviewModal();
                        };
            } else {
                document.getElementById('paymentReviewBody').innerHTML = `
                    <div class="review-section">
                        <p style="color: #dc3545; text-align: center;">
                            <i class="fas fa-exclamation-triangle"></i>
                            Error loading payment details: ${data.message || 'Unknown error'}
                        </p>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('paymentReviewBody').innerHTML = `
                <div class="review-section">
                    <p style="color: #dc3545; text-align: center;">
                        <i class="fas fa-exclamation-triangle"></i>
                        Error loading payment details. Please try again.
                    </p>
                </div>
            `;
        });
}

        function openInvoiceModal(invoiceNumber) {
            document.getElementById('invoiceModal').style.display = 'block';
            document.getElementById('modalInvoiceNumber').textContent = invoiceNumber;
        }

        // Close modal functionality
        document.querySelector('.modal-close').addEventListener('click', function() {
            document.getElementById('invoiceModal').style.display = 'none';
        });

        // Navigation functionality
        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Remove active class from all nav items and sections
                document.querySelectorAll('.nav-item').forEach(item => item.classList.remove('active'));
                document.querySelectorAll('.content-section').forEach(section => section.classList.remove('active'));
                
                // Add active class to clicked nav item and corresponding section
                this.parentElement.classList.add('active');
                const sectionId = this.getAttribute('data-section');
                document.getElementById(sectionId).classList.add('active');
            });
        });

        //close rejection modal when clicking outside
        window.onclick = function(event){
            const modal = document.getElementById('rejectionModal');
            if(event.target = modal){
                closeRejectionModal();
            }
        }

        function closePaymentReviewModal() {
            document.getElementById('paymentReviewModal').style.display = 'none';
        }

    </script>
</body>
</html>
