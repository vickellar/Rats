<?php
session_start();
require_once("../Database/db.php");
require_once('../lib/fpdf186/fpdf.php');

// Check if user is logged in
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    echo "<h3>User not logged in.</h3>";
    exit();
}

// Get required parameters from URL
$property_id = isset($_GET['property_id']) ? intval($_GET['property_id']) : null;
$account_id = isset($_GET['account_id']) ? intval($_GET['account_id']) : null;

if (!$property_id || !$account_id) {
    echo "<h3>Error: Property ID and Account ID are required in URL parameters.</h3>";
    echo "<p>Usage: view-bills.php?property_id=1&account_id=123</p>";
    exit();
}

// Handle individual bill PDF generation
if(isset($_POST['generate_bill_pdf']) && isset($_POST['bill_id'])) {
    generateBillPDF($_POST['bill_id'], $pdo, $property_id, $account_id, $user_id);
    exit;
}

// Handle all bills PDF generation
if(isset($_POST['generate_all_pdf'])) {
    generateAllBillsPDF($pdo, $property_id, $account_id, $user_id);
    exit;
}

// Handle bill preview
$preview_bill_id = isset($_GET['preview_bill']) && isset($_GET['bill_id']) ? $_GET['bill_id'] : null;

// Fetch property and account information
function getPropertyAndAccountInfo($pdo, $property_id, $account_id, $user_id) {
    $sql = "SELECT p.address, a.account_number 
            FROM properties p 
            JOIN accounts a ON p.property_id = a.property_id 
            WHERE p.property_id = :property_id AND a.account_id = :account_id AND p.user_id = :user_id";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':property_id' => $property_id,
        ':account_id' => $account_id,
        ':user_id' => $user_id
    ]);
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Fetch bills using calculated_bills table
function fetchAccountBills($pdo, $property_id, $account_id, $user_id) {
    // Get bills from calculated_bills table
    $sql = "SELECT cb.bill_id, cb.processing_fee, cb.total_balance, cb.overall_total,
                   cb.account_id, cb.property_id, cb.application_id, cb.calculated_at
            FROM calculated_bills cb
            WHERE cb.property_id = :property_id 
            AND cb.account_id = :account_id
            AND cb.user_id = :user_id
            ORDER BY cb.calculated_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':property_id' => $property_id,
        ':account_id' => $account_id,
        ':user_id' => $user_id
    ]);
    
    $bills = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // For each bill, get the monthly breakdown from calculated_bill_months table
    foreach ($bills as &$bill) {
        $bill['months'] = getMonthlyBreakdown($pdo, $bill['bill_id']);
        $bill['payment_status'] = 'unpaid'; // Default status
        $bill['created_date'] = $bill['calculated_at'];
    }
    
    return $bills;
}

// Get monthly breakdown using calculated_bill_months table
function getMonthlyBreakdown($pdo, $bill_id) {
    $sql = "SELECT month1_name, month2_name, month3_name, month4_name, monthly_balance 
            FROM calculated_bill_months 
            WHERE bill_id = :bill_id
            ORDER BY month_id";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':bill_id' => $bill_id]);
    
    $monthsData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $months = [];
    
    // Convert the month columns to individual month entries
    foreach ($monthsData as $monthRow) {
        if (!empty($monthRow['month1_name'])) {
            $months[] = [
                'month_name' => $monthRow['month1_name'],
                'monthly_balance' => $monthRow['monthly_balance']
            ];
        }
        if (!empty($monthRow['month2_name'])) {
            $months[] = [
                'month_name' => $monthRow['month2_name'],
                'monthly_balance' => $monthRow['monthly_balance']
            ];
        }
        if (!empty($monthRow['month3_name'])) {
            $months[] = [
                'month_name' => $monthRow['month3_name'],
                'monthly_balance' => $monthRow['monthly_balance']
            ];
        }
        if (!empty($monthRow['month4_name'])) {
            $months[] = [
                'month_name' => $monthRow['month4_name'],
                'monthly_balance' => $monthRow['monthly_balance']
            ];
        }
    }
    
    return $months;
}


function generateBillPDF($bill_id, $pdo, $property_id, $account_id, $user_id) {
    // Get bill details from calculated_bills
    $sqlBill = "SELECT cb.*, a.account_number, p.address 
                FROM calculated_bills cb
                JOIN accounts a ON cb.account_id = a.account_id
                JOIN properties p ON cb.property_id = p.property_id
                WHERE cb.bill_id = :bill_id 
                AND cb.property_id = :property_id 
                AND cb.account_id = :account_id
                AND cb.user_id = :user_id";
    
    $stmtBill = $pdo->prepare($sqlBill);
    $stmtBill->execute([
        ':bill_id' => $bill_id,
        ':property_id' => $property_id,
        ':account_id' => $account_id,
        ':user_id' => $user_id
    ]);
    $billInfo = $stmtBill->fetch(PDO::FETCH_ASSOC);
    
    if (!$billInfo) {
        echo "Bill not found.";
        return;
    }

    // Professional PDF class based on your design
class RatesClearancePDF extends FPDF
{
    private $propertyAddress;
    
    function __construct($propertyAddress = '') {
        parent::__construct();
        $this->propertyAddress = $propertyAddress;
    }
    
    // Page header
    function Header()
    {
        // Left side - City information
        $this->SetFont('Arial', 'B', 10);
        $this->SetXY(10, 10);
        $this->Cell(0, 5, 'City of Masvingo', 0, 1, 'L');
        $this->SetFont('Arial', '', 10);
        $this->SetX(10);
        $this->MultiCell(70, 5, "Robert Mugabe Way, Box 17\ntreasury@masingocity.org.zw\n+263 39 226241-4", 0, 'L');
        
        // Logo in center (if exists)
        if (file_exists('../assets/images/mslogo.png')) {
            $this->Image('../assets/images/mslogo.png', 85, 10, 30);
        }
        
        // Right side - Property information
        $this->SetFont('Arial', 'B', 10);
        $this->SetXY(140, 10);
        $this->Cell(0, 5, 'Property Address', 0, 1, 'L');
        $this->SetFont('Arial', '', 10);
        $this->SetX(140);
        $this->MultiCell(60, 3, $this->propertyAddress, 0, 'L');
        
        // Main title
        $this->SetFont('Arial', 'B', 14);
        $this->SetXY(10, 35);
        $this->Cell(0, 8, 'RATES CLEARANCE INVOICE', 0, 1, 'C');
        
        // Date and time
        $this->SetFont('Arial', '', 7);
        $this->SetXY(10, 45);
        $this->Cell(0, 5, 'Generated on: ' . date('Y-m-d H:i:s'), 0, 1, 'R');
        
        // Add a line separator
        $this->SetXY(10, 50);
        $this->Cell(0, 0, '', 'T', 1);
        
        // Set position for content to start
        $this->SetY(55);
    }

    // Page footer
    function Footer()
    {
        // Position at 1.5 cm from bottom
        $this->SetY(-20);
        
        // Add a line separator
        $this->Cell(0, 0, '', 'T', 1);
        $this->Ln(2);
        
        // Footer text
        $this->SetFont('Arial', 'I', 6);
        $this->Cell(0, 5, 'This is a computer-generated document from City of Masvingo Rates Department', 0, 1, 'C');
        
        // Page number
        $this->Cell(0, 5, 'Page ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }
    
    // Add section header
    function SectionHeader($title)
    {
        $this->Ln(3);
        $this->SetFont('Arial', 'B', 10);
        $this->SetFillColor(102, 126, 234);
        $this->SetTextColor(255, 255, 255);
        $this->Cell(0, 6, $title, 0, 1, 'L', true);
        $this->SetTextColor(0, 0, 0);
        $this->Ln(2);
    }
    
    // Add data row
    function DataRow($label, $value, $border = 0)
    {
        $this->SetFont('Arial', 'B', 8);
        $this->Cell(70, 5, $label . ':', $border, 0, 'L');
        $this->SetFont('Arial', '', 8);
        $this->Cell(0, 5, $value, $border, 1, 'L');
    }
    
    // Add breakdown item
    function BreakdownItem($description, $amount, $indent = 10)
    {
        $this->SetFont('Arial', '', 8);
        $this->Cell($indent, 5, '', 0, 0); // Indentation
        $this->Cell(120 - $indent, 5, $description, 0, 0, 'L');
        $this->Cell(0, 5, $amount, 0, 1, 'R');
    }
    
    // Add breakdown header
    function BreakdownHeader($title)
    {
        $this->SetFont('Arial', 'B', 9);
        $this->SetFillColor(240, 240, 240);
        $this->Cell(0, 6, $title, 0, 1, 'L', true);
        $this->Ln(1);
    }
}

    // Create instance of PDF class
    $pdf = new RatesClearancePDF($billInfo['address']);
    $pdf->AliasNbPages();
    $pdf->AddPage();
    
    // Invoice details section
    $pdf->SectionHeader('Invoice Details');
    $invoiceNumber = "INV-{$user_id}-{$property_id}-{$bill_id}-" . date('Ymd');
    $pdf->DataRow('Invoice Number', $invoiceNumber);
    $pdf->DataRow('Invoice Date', date('Y-m-d', strtotime($billInfo['calculated_at'])));
    $pdf->DataRow('Account Number', $billInfo['account_number']);
    $pdf->DataRow('Application ID', $billInfo['application_id']);
    
    // Rate Breakdown
    $pdf->SectionHeader('Rate Breakdown');
    
    // Account section header
    $pdf->BreakdownHeader("Account: {$billInfo['account_number']}");
    
    // Fetch monthly breakdown
    $months = getMonthlyBreakdown($pdo, $bill_id);
    
    if (!empty($months)) {
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell(10, 5, '', 0, 0); // Indent
        $pdf->Cell(0, 5, 'Monthly Rates:', 0, 1, 'L');
        
        foreach ($months as $month) {
            $pdf->BreakdownItem($month['month_name'] . ':', '$' . number_format($month['monthly_balance'], 2), 20);
        }
    }
    
    // Financial Summary
    $pdf->Ln(5);
    $pdf->SectionHeader('Financial Summary');
    
    // Summary breakdown
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->BreakdownItem('Subtotal (All Rates):', '$' . number_format($billInfo['total_balance'], 2), 10);
    $pdf->BreakdownItem('Processing Fee:', '$' . number_format($billInfo['processing_fee'], 2), 10);
    
    // Add separator line
    $pdf->Ln(2);
    $pdf->Cell(0, 0, '', 'T', 1);
    $pdf->Ln(2);
    
    // Grand total with emphasis
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetFillColor(102, 126, 234);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Cell(120, 8, 'TOTAL AMOUNT DUE:', 0, 0, 'L');
    $pdf->SetFillColor(102, 126, 234);
    $pdf->Cell(0, 8, '$' . number_format($billInfo['overall_total'], 2), 0, 1, 'R', true);
    $pdf->SetTextColor(0, 0, 0);
    
    // Payment Information
    $pdf->Ln(10);
    $pdf->SectionHeader('Payment Information');
    $pdf->SetFont('Arial', '', 8);
    $pdf->MultiCell(0, 5, "Payment Methods:\nBank Transfer: Account No. 123456789, City Bank\nCash Payment: City of Masvingo Treasury Office\nOnline Payment: www.masvingocity.org.zw/payments\n\nPayment Due Date: " . date('Y-m-d', strtotime('+30 days')));
    
    // Terms and Conditions
    $pdf->Ln(5);
    $pdf->SectionHeader('Terms and Conditions');
    $pdf->SetFont('Arial', '', 7);
    $pdf->MultiCell(0, 4, "1. Payment is due within 30 days of invoice date.\n2. Late payments may incur additional charges.\n3. All amounts are in USD unless otherwise specified.\n4. This invoice is computer-generated and valid without signature.\n5. For queries, contact treasury@masingocity.org.zw or +263 39 226241-4.");
    
    // Final notes
    $pdf->Ln(10);
    $pdf->SetFont('Arial', 'I', 6);
    $pdf->SetTextColor(128, 128, 128);
    $pdf->MultiCell(0, 4, 'Thank you for your prompt payment. This document was automatically generated by the City of Masvingo Rates Clearance System on ' . date('Y-m-d H:i:s') . '.');
    
    $filename = 'rates_clearance_invoice_' . $invoiceNumber . '.pdf';
    $pdf->Output('I', $filename);
}

function generateAllBillsPDF($pdo, $property_id, $account_id, $user_id) {
    $pdf = new FPDF();
    $pdf->AddPage();
    $pdf->SetFont('Arial','B',16);
    $pdf->Cell(0,10,'All Account Bills',0,1,'C');
    $pdf->SetFont('Arial','',12);

    $bills = fetchAccountBills($pdo, $property_id, $account_id, $user_id);
    $propertyInfo = getPropertyAndAccountInfo($pdo, $property_id, $account_id, $user_id);

    if ($propertyInfo) {
        $pdf->Ln(5);
        $pdf->SetFont('Arial','B',12);
        $pdf->Cell(0,10,'Property: ' . $propertyInfo['address'],0,1);
        $pdf->Cell(0,10,'Account Number: ' . $propertyInfo['account_number'],0,1);
        $pdf->SetFont('Arial','',12);
    }

    foreach ($bills as $bill) {
        $pdf->Ln(5);
        $pdf->SetFont('Arial','B',12);
        $pdf->Cell(0,8,'Bill ID: ' . $bill['bill_id'],0,1);
        $pdf->SetFont('Arial','',12);

        $pdf->Cell(60,8,'Processing Fee: $' . number_format($bill['processing_fee'],2),0,0);
        $pdf->Cell(60,8,'Total Balance: $' . number_format($bill['total_balance'],2),0,0);
        $pdf->Cell(60,8,'Overall Total: $' . number_format($bill['overall_total'],2),0,1);

        if (!empty($bill['months'])) {
            $pdf->Cell(0,8,'Monthly Breakdown:',0,1);
            foreach ($bill['months'] as $month) {
                $pdf->Cell(0,7," - {$month['month_name']}: $" . number_format($month['monthly_balance'],2),0,1);
            }
        }
        $pdf->Ln(2);
    }

    $pdf->Output('I', 'All_Account_Bills.pdf');
}

function renderBillPreview($bill_id, $bills) {
    $bill = null;
    foreach ($bills as $b) {
        if ($b['bill_id'] == $bill_id) {
            $bill = $b;
            break;
        }
    }
    
    if (!$bill) {
        return '<div class="preview-error">Bill not found.</div>';
    }

    $invoiceNumber = "INV-{$_SESSION['user_id']}{$bill['property_id']}{$bill['bill_id']}-" . date('Ymd');

    $output = '
    <div class="bill-preview professional">
        <div class="preview-header professional-header">
            <h2>RATES CLEARANCE INVOICE</h2>
            <div class="close-preview">
                <a href="?property_id=' . $_GET['property_id'] . '&account_id=' . $_GET['account_id'] . '">✕</a>
            </div>
        </div>
        <div class="preview-content professional-content">
            <!-- Header Section -->
            <div class="invoice-header">
                <div class="city-info">
                    <h4>City of Masvingo</h4>
                    <p>Robert Mugabe Way, Box 17<br>
                    treasury@masingocity.org.zw<br>
                    +263 39 226241-4</p>
                </div>
                <div class="logo-section">
                    <!-- Logo placeholder <div class="logo-placeholder">LOGO</div>-->
                    <img src="mslogo.png" alt="Logo" class="logo-placeholder"><br>
                    <h3>Rate Clearance Invoice</h3>
                </div>
                <div class="property-info">
                    <h4>Property Address</h4>
                    <p>' . htmlspecialchars($GLOBALS['propertyInfo']['address'] ?? 'N/A') . '</p>
                </div>
            </div>
            
            <div class="generated-date">Generated on: ' . date('Y-m-d H:i:s') . '</div>
            
            <!-- Invoice Details Section -->
            <div class="section">
                <div class="section-header">Invoice Details</div>
                <div class="section-content">
                    <div class="data-row">
                        <span class="label">Invoice Number:</span>
                        <span class="value">' . $invoiceNumber . '</span>
                    </div>
                    <div class="data-row">
                        <span class="label">Invoice Date:</span>
                        <span class="value">' . date('Y-m-d', strtotime($bill['calculated_at'])) . '</span>
                    </div>
                    <div class="data-row">
                        <span class="label">Account Number:</span>
                        <span class="value">' . htmlspecialchars($GLOBALS['propertyInfo']['account_number'] ?? 'N/A') . '</span>
                    </div>
                    <div class="data-row">
                        <span class="label">Application ID:</span>
                        <span class="value">' . htmlspecialchars($bill['application_id']) . '</span>
                    </div>
                </div>
            </div>
            
            <!-- Rate Breakdown Section -->
            <div class="section">
                <div class="section-header">Rate Breakdown</div>
                <div class="section-content">
                    <div class="breakdown-header">Account: ' . htmlspecialchars($GLOBALS['propertyInfo']['account_number'] ?? 'N/A') . '</div>';
                    
    if (!empty($bill['months'])) {
        $output .= '<div class="monthly-rates">
                        <div class="breakdown-title">Monthly Rates:</div>';
        foreach ($bill['months'] as $month) {
            $output .= '<div class="breakdown-item">
                            <span class="description">' . htmlspecialchars($month['month_name']) . ':</span>
                            <span class="amount">$' . number_format($month['monthly_balance'], 2) . '</span>
                        </div>';
        }
        $output .= '</div>';
    }
    
    $output .= '
                </div>
            </div>
            
            <!-- Financial Summary Section -->
            <div class="section">
                <div class="section-header">Financial Summary</div>
                <div class="section-content">
                    <div class="summary-item">
                        <span class="description">Subtotal (All Rates):</span>
                        <span class="amount">$' . number_format($bill['total_balance'], 2) . '</span>
                    </div>
                    <div class="summary-item">
                        <span class="description">Processing Fee:</span>
                        <span class="amount">$' . number_format($bill['processing_fee'], 2) . '</span>
                    </div>
                    <div class="total-separator"></div>
                    <div class="grand-total">
                        <span class="total-label">TOTAL AMOUNT DUE:</span>
                        <span class="total-amount">$' . number_format($bill['overall_total'], 2) . '</span>
                    </div>
                </div>
            </div>
            
            <!-- Payment Information Section -->
            <div class="section">
                <div class="section-header">Payment Information</div>
                <div class="section-content">
                    <div class="payment-methods">
                        <strong>Payment Methods:</strong><br>
                        Bank Transfer: Account No. 123456789, City Bank<br>
                        Cash Payment: City of Masvingo Treasury Office<br>
                        Online Payment: www.masvingocity.org.zw/payments<br><br>
                        <strong>Payment Due Date:</strong> ' . date('Y-m-d', strtotime('+30 days')) . '
                    </div>
                </div>
            </div>
            
            <!-- Terms and Conditions Section -->
            <div class="section">
                <div class="section-header">Terms and Conditions</div>
                <div class="section-content terms">
                    1. Payment is due within 30 days of invoice date.<br>
                    2. Late payments may incur additional charges.<br>
                    3. All amounts are in USD unless otherwise specified.<br>
                    4. This invoice is computer-generated and valid without signature.<br>
                    5. For queries, contact treasury@masingocity.org.zw or +263 39 226241-4.
                </div>
            </div>
            
            <div class="preview-actions">
                <form method="post" style="display: inline;">
                    <input type="hidden" name="bill_id" value="' . $bill_id . '">
                    <button type="submit" name="generate_bill_pdf" class="btn btn-primary">
                        Download PDF
                    </button>
                </form>
            </div>
            
            <div class="footer-note">
                Thank you for your prompt payment. This document was automatically generated by the City of Masvingo Rates Clearance System.
            </div>
        </div>
    </div>';
    
    return $output;
}

// Fetch data
$propertyInfo = getPropertyAndAccountInfo($pdo, $property_id, $account_id, $user_id);
if (!$propertyInfo) {
    echo "<h3>Error: Property or Account not found, or you don't have access to it.</h3>";
    exit();
}

$bills = fetchAccountBills($pdo, $property_id, $account_id, $user_id);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Bills - <?= htmlspecialchars($propertyInfo['address']) ?></title>
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
            position: relative;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }
        
        .header p {
            font-size: 1.1rem;
            opacity: 0.9;
        }
        
        .content {
            padding: 30px;
        }
        
        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: #6c757d;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            margin-bottom: 20px;
            transition: background 0.3s;
        }
        
        .back-button:hover {
            background: #5a6268;
        }
        
        .account-info {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 30px;
            border-left: 5px solid #3498db;
        }
        
        .account-info h2 {
            color: #2c3e50;
            margin-bottom: 15px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .info-item {
            background: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        
        .info-label {
            font-size: 0.9rem;
            color: #6c757d;
            margin-bottom: 5px;
        }
        
        .info-value {
            font-size: 1.2rem;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .bill-card {
            background: #f8f9fa;
            border-radius: 12px;
            margin-bottom: 25px;
            border: 1px solid #dee2e6;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
        
        .bill-header {
            background: #e9ecef;
            padding: 20px;
            border-bottom: 1px solid #dee2e6;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .bill-title {
            font-size: 1.3rem;
            color: #343a40;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .bill-status {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            background: #f8d7da;
            color: #721c24;
        }
        
        .bill-content {
            padding: 25px;
        }
        
        .bill-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }
        
        .summary-item {
            background: white;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        
        .summary-item.highlight {
            background: #e3f2fd;
            border: 1px solid #2196f3;
        }
        
        .summary-label {
            font-size: 0.85rem;
            color: #6c757d;
            margin-bottom: 8px;
        }
        
        .summary-value {
            font-size: 1.3rem;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .monthly-breakdown {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        
        .monthly-breakdown h4 {
            color: #495057;
            margin-bottom: 15px;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .month-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        
        .month-item:last-child {
            border-bottom: none;
        }
        
        .month-name {
            color: #495057;
        }
        
        .month-amount {
            font-weight: 600;
            color: #2c3e50;
        }
        
        .bill-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 20px;
        }
        
        .btn {
            display: inline-block;
            padding: 10px 20px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-size: 0.9rem;
            text-align: center;
        }
        
        .btn-primary {
            background: #3498db;
            color: white;
        }
        
        .btn-primary:hover {
            background: #2980b9;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        .btn-outline {
            background: transparent;
            border: 1px solid #3498db;
            color: #3498db;
        }
        
        .btn-outline:hover {
            background: #f1f7fb;
        }
        
        .btn-large {
            padding: 12px 24px;
            font-size: 1rem;
        }
        
        .no-data {
            text-align: center;
            color: #6c757d;
            font-style: italic;
            padding: 50px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .actions {
            background: #f8f9fa;
            padding: 25px;
            text-align: center;
            border-top: 1px solid #dee2e6;
        }
        
        /* Bill Preview Styles */
        .bill-preview {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 90%;
            max-width: 800px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            z-index: 1000;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .preview-header {
            background: #3498db;
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        
        .preview-header h2 {
            margin: 0;
            font-size: 1.5rem;
        }
        
        .close-preview a {
            color: white;
            text-decoration: none;
            font-size: 1.5rem;
            font-weight: bold;
            transition: opacity 0.3s;
        }
        
        .close-preview a:hover {
            opacity: 0.8;
        }
        
        .preview-content {
            padding: 25px;
        }
        
        .preview-bill-id {
            margin-bottom: 20px;
            font-size: 1.1rem;
        }
        
        .preview-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 25px 0;
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
        }
        
        .preview-summary-item {
            text-align: center;
        }
        
        .preview-label {
            font-size: 0.9rem;
            color: #6c757d;
            margin-bottom: 8px;
        }
        
        .preview-value {
            font-size: 1.4rem;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .preview-months {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 25px;
        }
        
        .preview-months h3 {
            margin-bottom: 15px;
            font-size: 1.2rem;
            color: #343a40;
        }
        
        .preview-month-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #dee2e6;
        }
        
        .preview-month-item:last-child {
            border-bottom: none;
        }
        
        .preview-month-name {
            color: #495057;
        }
        
        .preview-month-amount {
            font-weight: bold;
            color: #2c3e50;
        }
        
        .preview-actions {
            text-align: center;
            margin-top: 20px;
            display: flex;
            gap: 15px;
            justify-content: center;
        }
        
        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 999;
        }
        
        @media (max-width: 768px) {
            .container {
                margin: 10px;
                border-radius: 10px;
            }
            
            .header {
                padding: 20px;
            }
            
            .header h1 {
                font-size: 2rem;
            }
            
            .content {
                padding: 15px;
            }
            
            .bill-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .bill-actions {
                width: 100%;
                justify-content: space-between;
            }
            
            .bill-summary {
                grid-template-columns: 1fr;
            }
            
            .bill-preview {
                width: 95%;
            }
            
            .info-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Professional Invoice Styles */
        .bill-preview.professional {
            max-width: 900px;
        }

        .professional-header {
            background: #667eea !important;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        }

        .professional-content {
            font-family: Arial, sans-serif;
        }

        .invoice-header {
            display: grid;
            grid-template-columns: 1fr auto 1fr;
            gap: 20px;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #ddd;
        }

        .city-info h3 {
            font-weight: bold;
            margin-bottom: 8px;
            color: #2c3e50;
        }

        .city-info p {
            font-size: 0.9rem;
            line-height: 1.4;
            color: #666;
        }

        .logo-placeholder {
            width: 100px;
            height: 100px;
            background:#ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            color: #999;
        }

        .property-info {
            text-align: right;
        }

        .property-info h4 {
            font-weight: bold;
            margin-bottom: 8px;
            color: #2c3e50;
        }

        .property-info p {
            font-size: 0.9rem;
            line-height: 1.4;
            color: #666;
        }

        .generated-date {
            text-align: right;
            font-size: 0.8rem;
            color: #666;
            margin-bottom: 20px;
        }

        .section {
            margin-bottom: 25px;
        }

        .section-header {
            background: #667eea;
            color: white;
            padding: 8px 12px;
            font-weight: bold;
            font-size: 0.95rem;
            margin-bottom: 10px;
        }

        .section-content {
            padding: 0 12px;
        }

        .data-row {
            display: flex;
            margin-bottom: 8px;
        }

        .data-row .label {
            font-weight: bold;
            width: 140px;
            color: #2c3e50;
        }

        .data-row .value {
            color: #666;
        }

        .breakdown-header {
            background: #f0f0f0;
            padding: 8px 12px;
            font-weight: bold;
            margin-bottom: 10px;
            font-size: 0.9rem;
        }

        .monthly-rates {
            margin-left: 20px;
        }

        .breakdown-title {
            font-weight: bold;
            margin-bottom: 8px;
            color: #2c3e50;
        }

        .breakdown-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
            padding-left: 20px;
        }

        .breakdown-item .description {
            color: #666;
        }

        .breakdown-item .amount {
            font-weight: bold;
            color: #2c3e50;
        }

        .summary-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-weight: bold;
        }

        .summary-item .description {
            color: #2c3e50;
        }

        .summary-item .amount {
            color: #2c3e50;
        }

        .total-separator {
            border-top: 1px solid #ddd;
            margin: 15px 0 10px 0;
        }

        .grand-total {
            background: #667eea;
            color: white;
            padding: 12px;
            display: flex;
            justify-content: space-between;
            font-weight: bold;
            font-size: 1.1rem;
        }

        .payment-methods {
            font-size: 0.9rem;
            line-height: 1.6;
            color: #666;
        }

        .terms {
            font-size: 0.85rem;
            line-height: 1.5;
            color: #666;
        }

        .footer-note {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #ddd;
            font-size: 0.8rem;
            color: #999;
            font-style: italic;
            text-align: center;
        }

        @media (max-width: 768px) {
            .invoice-header {
                grid-template-columns: 1fr;
                text-align: center;
            }
            
            .property-info {
                text-align: center;
            }
            
            .data-row {
                flex-direction: column;
            }
            
            .data-row .label {
                width: auto;
                margin-bottom: 3px;
            }
        }
    </style>
</head>
<body>
    <?php if ($preview_bill_id): ?>
    <div class="overlay"></div>
    <?php echo renderBillPreview($preview_bill_id, $bills); ?>
    <?php endif; ?>
    
    <div class="container">
        <div class="header">
            <h1>Property Bills</h1>
            <p>View and manage your property account bills</p>
        </div>
        
        <div class="content">
            <a href="cdashboard.php" class="back-button">
                ← Back to Dashboard
            </a>
            
            <!-- Account Information -->
            <div class="account-info">
                <h2>Account Information</h2>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Property Address</div>
                        <div class="info-value"><?= htmlspecialchars($propertyInfo['address']) ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Account Number</div>
                        <div class="info-value"><?= htmlspecialchars($propertyInfo['account_number']) ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Property ID</div>
                        <div class="info-value"><?= htmlspecialchars($property_id) ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Account ID</div>
                        <div class="info-value"><?= htmlspecialchars($account_id) ?></div>
                    </div>
                </div>
            </div>
            
            <?php if (empty($bills)): ?>
                <div class="no-data">
                    <h3>No Bills Found</h3>
                    <p>No bills found for this account in the accounts_Fees table.</p>
                </div>
            <?php else: ?>
                <?php foreach ($bills as $bill): ?>
                    <div class="bill-card">
                        <div class="bill-header">
                            <div class="bill-title">
                                Bill ID: <?= htmlspecialchars($bill['bill_id']) ?>
                                <span class="bill-status">
                                    <?= ucfirst($bill['payment_status']) ?>
                                </span>
                            </div>
                            <div class="bill-actions">
                                <a href="?property_id=<?= $property_id ?>&account_id=<?= $account_id ?>&preview_bill=1&bill_id=<?= $bill['bill_id'] ?>" class="btn btn-outline">
                                    👁 Preview
                                </a>
                                <form method="post" style="display: inline;">
                                    <input type="hidden" name="bill_id" value="<?= $bill['bill_id'] ?>">
                                    <button type="submit" name="generate_bill_pdf" class="btn btn-primary">
                                        📄 Export to PDF
                                    </button>
                                </form>
                            </div>
                        </div>
                        
                        <div class="bill-content">
                            <!-- Monthly Breakdown -->
                            <?php if (!empty($bill['months'])): ?>
                                <div class="monthly-breakdown">
                                    <h4>📅 Monthly Breakdown</h4>
                                    <?php foreach ($bill['months'] as $month): ?>
                                        <div class="month-item">
                                            <span class="month-name"><?= htmlspecialchars($month['month_name']) ?></span>
                                            <span class="month-amount">$<?= number_format($month['monthly_balance'], 2) ?></span>
                                        </div>
                                        <?php endforeach; ?>
                                        <div class="summary-item">
                                            <div class="summary-label">Total Balance</div>
                                            <div class="summary-value">$<?= number_format($bill['total_balance'], 2) ?></div>
                                        </div>

                                        <div class="summary-item">
                                            <div class="summary-label">Processing Fee</div>
                                            <div class="summary-value">$<?= number_format($bill['processing_fee'], 2) ?></div>
                                        </div>
                                    </div>
                                    <div class="summary-item highlight">
                                            <div class="summary-label">Overall Total</div>
                                            <div class="summary-value">$<?= number_format($bill['overall_total'], 2) ?></div>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <!-- Bill Summary 
                                    <div class="bill-summary">
                                        <div class="summary-item">
                                            <div class="summary-label">Processing Fee</div>
                                            <div class="summary-value">$<?= number_format($bill['processing_fee'], 2) ?></div>
                                        </div>
                                        <div class="summary-item">
                                            <div class="summary-label">Total Balance</div>
                                            <div class="summary-value">$<?= number_format($bill['total_balance'], 2) ?></div>
                                        </div>
                                        <div class="summary-item highlight">
                                            <div class="summary-label">Overall Total</div>
                                            <div class="summary-value">$<?= number_format($bill['overall_total'], 2) ?></div>
                                        </div>
                                    </div>
                                    -->

                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <?php if (!empty($bills)): ?>
        <div class="actions">
            <form method="post" style="display: inline;">
                <button type="submit" name="generate_all_pdf" class="btn btn-primary btn-large">
                    📄 Download All Bills PDF
                </button>
            </form>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
