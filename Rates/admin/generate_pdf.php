<?php
require_once '../lib/fpdf186/fpdf.php'; // Adjust path to your FPDF library
require_once '../Database/db.php';

// Start session
session_start();

class RatesClearancePDF extends FPDF
{
    public $propertyAddress = '';

    function __construct($propertyAddress = '')
    {
        parent::__construct();
        $this->propertyAddress = $propertyAddress;
    }

    // Page header
    function Header()
    {
        // Save current position
        $currentY = $this->GetY();
        
        // Left side - City information
        $this->SetFont('Arial', 'B', 10);
        $this->SetXY(10, 10);
        $this->Cell(0, 5, 'City of Masvingo', 0, 1, 'L');
        $this->SetFont('Arial', '', 10);
        $this->SetX(10);
        $this->MultiCell(70, 5, "Robert Mugabe Way, Box 17\ntreasury@masingocity.org.zw\n+263 39 226241-4", 0, 'L');
        
        // Logo in center
        if (file_exists('../assets/images/mslogo.png')) {
            $this->Image('../assets/images/mslogo.png', 85, 10, 30); // Centered logo
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
    function BreakdownItem($description, $amount, $indent)
    {
        $this->SetFont('Arial', '', 8);
        $this->Cell($indent, 5, '', 0, 0); // Indentation
        $this->Cell(10, 5, $description, 0, 0, 'L');
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

// Check if form data was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Fetch data from the database
    $userId = $_POST['user_id'] ?? null;
    $propertyId = $_POST['property_id'] ?? 'N/A';

    // Fetch property address and user_id from the database
    $propertyAddress = 'N/A';
    if ($propertyId !== 'N/A') {
        $stmt = $pdo->prepare("SELECT address, user_id FROM properties WHERE property_id = :property_id LIMIT 1");
        $stmt->execute([':property_id' => $propertyId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            if (!empty($row['address'])) {
                $propertyAddress = $row['address'];
            }
            if (empty($userId) && !empty($row['user_id'])) {
                $userId = $row['user_id'];
            }
        }
    }
    if (empty($userId)) {
        $userId = 'N/A';
    }
    
    $applicationId = $_POST['application_id'] ?? 'N/A';
    
    // Create instance of PDF class
    $pdf = new RatesClearancePDF($propertyAddress);
    $pdf->AliasNbPages();
    $pdf->AddPage();
    
    // Invoice details section
    $pdf->SectionHeader('Invoice Details');
    $invoiceNumber = "INV-$userId$propertyId$applicationId-" . date('Ymd');
    $pdf->DataRow('Invoice Number', $invoiceNumber);
    $pdf->DataRow('Invoice Date', date('Y-m-d'));
    
    // Account Details - Simplified without table
    $accountCount = intval($_POST['accounts'] ?? 0);
    $totalAccountBalance = 0;
    
    if ($accountCount > 0) {
        $pdf->SectionHeader('Rate Breakdown');
        
        for ($i = 1; $i <= $accountCount; $i++) {
            $accountNumber = $_POST["account_{$i}_number"] ?? 'N/A';
            $accountBalance = $_POST["account_{$i}_balance"] ?? '$0.00';
            
            // Add account section header
            $pdf->BreakdownHeader("Account: $accountNumber");
            
            // Add account balance if exists
            $cleanAccountBalance = floatval(preg_replace('/[^\d.-]/', '', $accountBalance));
            if ($cleanAccountBalance > 0) {
                $pdf->BreakdownItem('Account Balance:', '$' . number_format($cleanAccountBalance, 2), 10);
                $totalAccountBalance += $cleanAccountBalance;
            }
            
            // Add monthly breakdown
            $hasMonthlyData = false;
            for ($month = 1; $month <= 4; $month++) {
                $monthName = $_POST["month{$month}_name_account{$i}"] ?? '';
                $monthBalance = $_POST["month{$month}_balance_account{$i}"] ?? '';
                
                if (!empty($monthName) && !empty($monthBalance)) {
                    $cleanMonthBalance = floatval(preg_replace('/[^\d.-]/', '', $monthBalance));
                    if ($cleanMonthBalance > 0) {
                        if (!$hasMonthlyData) {
                            $pdf->SetFont('Arial', 'B', 8);
                            $pdf->Cell(10, 5, '', 0, 0); // Indent
                            $pdf->Cell(0, 5, 'Monthly Rates:', 0, 1, 'L');
                            $hasMonthlyData = true;
                        }
                        $pdf->BreakdownItem($monthName . ':', '$' . number_format($cleanMonthBalance, 2), 20);
                        $totalAccountBalance += $cleanMonthBalance;
                    }
                }
            }
            
            // Add spacing between accounts
            if ($i < $accountCount) {
                $pdf->Ln(3);
            }
        }
    }
    
    // Financial Summary
    $pdf->Ln(5);
    $pdf->SectionHeader('Financial Summary');
    
    $processingFee = floatval(preg_replace('/[^\d.-]/', '', $_POST['processing_fee'] ?? '0'));
    $grandTotal = $totalAccountBalance + $processingFee;
    
    // Summary without table - using simple layout
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->BreakdownItem('Subtotal (All Rates):', '$' . number_format($totalAccountBalance,2),10 );
    $pdf->BreakdownItem('Processing Fee:', '$' . number_format($processingFee,2),10);
   
    
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
    $pdf->Cell(0, 8, '$' . number_format($grandTotal, 2), 0, 1, 'R', true);
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
    
    // Output PDF
    $filename = 'rates_clearance_invoice_' . $invoiceNumber . '.pdf';
    $pdf->Output('D', $filename); // 'D' forces download
    
} else {
    // If accessed directly without POST data
    header('Location: ' . $_SERVER['HTTP_REFERER'] ?? 'index.php');
    exit();
}
?>