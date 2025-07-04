<?php
session_start();
/*
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'finance_director') {
    error_log("Redirecting to index.php due to invalid role: " . (isset($_SESSION['role']) ? $_SESSION['role'] : 'No role set'));
    header("Location: ../index.php");
    exit();
}
*/
include '../Database/db.php';

// Fetch financial data
$pending_invoices = 12;
$approved_payments = 45;
$total_amount_pending = 125000;
$monthly_budget = 500000;
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
                            <p class="stat-number"><?php echo round(($total_amount_pending / $monthly_budget) * 100); ?>%</p>
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
                                <div class="invoice-item urgent">
                                    <div class="invoice-info">
                                        <span class="invoice-number">INV-2024-001</span>
                                        <span class="vendor">Acme Corp</span>
                                        <span class="amount">$15,000</span>
                                    </div>
                                    <div class="invoice-status">
                                        <span class="status-badge urgent">Urgent</span>
                                        <span class="time">2 hours ago</span>
                                    </div>
                                </div>
                                
                                <div class="invoice-item">
                                    <div class="invoice-info">
                                        <span class="invoice-number">INV-2024-002</span>
                                        <span class="vendor">Tech Solutions Ltd</span>
                                        <span class="amount">$8,500</span>
                                    </div>
                                    <div class="invoice-status">
                                        <span class="status-badge pending">Pending</span>
                                        <span class="time">5 hours ago</span>
                                    </div>
                                </div>
                                
                                <div class="invoice-item">
                                    <div class="invoice-info">
                                        <span class="invoice-number">INV-2024-003</span>
                                        <span class="vendor">Office Supplies Inc</span>
                                        <span class="amount">$2,300</span>
                                    </div>
                                    <div class="invoice-status">
                                        <span class="status-badge pending">Pending</span>
                                        <span class="time">1 day ago</span>
                                    </div>
                                </div>
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
                                <div class="queue-item high-priority">
                                    <div class="priority-indicator"></div>
                                    <div class="queue-info">
                                        <span class="payment-ref">PAY-2024-045</span>
                                        <span class="description">Vendor Payment - Q1 Services</span>
                                        <span class="amount">$25,000</span>
                                    </div>
                                    <div class="queue-actions">
                                        <button class="btn-approve">Approve</button>
                                        <button class="btn-review">Review</button>
                                    </div>
                                </div>
                                
                                <div class="queue-item">
                                    <div class="priority-indicator"></div>
                                    <div class="queue-info">
                                        <span class="payment-ref">PAY-2024-046</span>
                                        <span class="description">Employee Reimbursement</span>
                                        <span class="amount">$1,200</span>
                                    </div>
                                    <div class="queue-actions">
                                        <button class="btn-approve">Approve</button>
                                        <button class="btn-review">Review</button>
                                    </div>
                                </div>
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
                                    <th>Vendor</th>
                                    <th>Amount</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Priority</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="invoice-row" data-invoice="INV-2024-001">
                                    <td>INV-2024-001</td>
                                    <td>Acme Corporation</td>
                                    <td>$15,000.00</td>
                                    <td>2024-01-15</td>
                                    <td><span class="status-badge urgent">Urgent Review</span></td>
                                    <td><span class="priority-badge high">High</span></td>
                                    <td>
                                        <button class="btn-action" onclick="openInvoiceModal('INV-2024-001')">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn-action">
                                            <i class="fas fa-download"></i>
                                        </button>
                                    </td>
                                </tr>
                                <tr class="invoice-row" data-invoice="INV-2024-002">
                                    <td>INV-2024-002</td>
                                    <td>Tech Solutions Ltd</td>
                                    <td>$8,500.00</td>
                                    <td>2024-01-14</td>
                                    <td><span class="status-badge pending">Pending</span></td>
                                    <td><span class="priority-badge medium">Medium</span></td>
                                    <td>
                                        <button class="btn-action" onclick="openInvoiceModal('INV-2024-002')">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn-action">
                                            <i class="fas fa-download"></i>
                                        </button>
                                    </td>
                                </tr>
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
                        <div class="payment-card">
                            <div class="payment-header">
                                <span class="payment-id">PAY-2024-045</span>
                                <span class="payment-amount">$25,000.00</span>
                            </div>
                            <div class="payment-details">
                                <p><strong>Vendor:</strong> Global Services Inc</p>
                                <p><strong>Description:</strong> Q1 Consulting Services</p>
                                <p><strong>Due Date:</strong> 2024-01-20</p>
                                <p><strong>Requested by:</strong> John Smith</p>
                            </div>
                            <div class="payment-actions">
                                <button class="btn-approve">
                                    <i class="fas fa-check"></i>
                                    Approve
                                </button>
                                <button class="btn-reject">
                                    <i class="fas fa-times"></i>
                                    Reject
                                </button>
                                <button class="btn-review">
                                    <i class="fas fa-eye"></i>
                                    Review
                                </button>
                            </div>
                        </div>

                        <div class="payment-card">
                            <div class="payment-header">
                                <span class="payment-id">PAY-2024-046</span>
                                <span class="payment-amount">$1,200.00</span>
                            </div>
                            <div class="payment-details">
                                <p><strong>Vendor:</strong> Employee Reimbursement</p>
                                <p><strong>Description:</strong> Travel Expenses</p>
                                <p><strong>Due Date:</strong> 2024-01-18</p>
                                <p><strong>Requested by:</strong> Sarah Johnson</p>
                            </div>
                            <div class="payment-actions">
                                <button class="btn-approve">
                                    <i class="fas fa-check"></i>
                                    Approve
                                </button>
                                <button class="btn-reject">
                                    <i class="fas fa-times"></i>
                                    Reject
                                </button>
                                <button class="btn-review">
                                    <i class="fas fa-eye"></i>
                                    Review
                                </button>
                            </div>
                        </div>
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
                        <div class="audit-item">
                            <div class="audit-time">2024-01-15 14:30</div>
                            <div class="audit-action approved">Invoice INV-2024-001 approved</div>
                            <div class="audit-user">by Finance Director</div>
                        </div>
                        
                        <div class="audit-item">
                            <div class="audit-time">2024-01-15 13:45</div>
                            <div class="audit-action rejected">Payment PAY-2024-044 rejected</div>
                            <div class="audit-user">by Finance Director</div>
                        </div>
                        
                        <div class="audit-item">
                            <div class="audit-time">2024-01-15 11:20</div>
                            <div class="audit-action approved">Payment PAY-2024-043 approved</div>
                            <div class="audit-user">by Finance Director</div>
                        </div>
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
                                    <div class="progress-fill" style="width: <?php echo ($total_amount_pending / $monthly_budget) * 100; ?>%"></div>
                                </div>
                                <span><?php echo round(($total_amount_pending / $monthly_budget) * 100); ?>% utilized</span>
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
                            <label>Vendor:</label>
                            <span id="modalVendor">Acme Corporation</span>
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
                                <span>Vendor information is correct</span>
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

    <script src="script.js"></script>
</body>
</html>
