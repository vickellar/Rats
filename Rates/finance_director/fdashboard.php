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
            p.payment_status,
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
        WHERE p.payment_status = 'pending' OR p.payment_status IS NULL
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
    $approved_stmt = $pdo->query("SELECT COUNT(*) as count FROM payments WHERE payment_status = 'approved'");
    $approved_result = $approved_stmt->fetch(PDO::FETCH_ASSOC);
    $approved_payments = $approved_result['count'];
    
    // Calculate total pending amount
    $pending_amount_stmt = $pdo->query("SELECT SUM(amount_paid) as total FROM payments WHERE payment_status = 'pending' OR payment_status IS NULL");
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
                        <a href="#settings" class="nav-link" data-section="settings">
                            <i class="fas fa-cog"></i>
                            <span>Settings</span>
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
                                        <button class="btn-approve" onclick="approvePayment(<?php echo $approval['payment_id']; ?>)">Approve</button>
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
                                <p><strong>Invoice Due Date:</strong> <?php echo $approval['due_date'] ? date('Y-m-d', strtotime($approval['due_date'])) : 'Not set'; ?></p>
                                <p><strong>Requested by:</strong> <?php echo htmlspecialchars(($approval['first_name'] ?? '') . ' ' . ($approval['surname'] ?? '')); ?></p>
                                <?php if ($approval['notes']): ?>
                                <p><strong>Notes:</strong> <?php echo htmlspecialchars($approval['notes']); ?></p>
                                <?php endif; ?>
                            </div>
                            <div class="payment-actions">
                                <button class="btn-approve" onclick="approvePayment(<?php echo $approval['payment_id']; ?>)">
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

    <script>
        // JavaScript functions for payment actions
        function approvePayment(paymentId) {
            if (confirm('Are you sure you want to approve this payment?')) {
                // AJAX call to approve payment
                fetch('approve_payment.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
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
                        'Content-Type': 'application/json',
                    },
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
            // Open payment review modal or redirect to review page
            alert('Opening payment review for PAY-' + paymentId);
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
    </script>
</body>
</html>
