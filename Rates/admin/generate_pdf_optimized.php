<?php
require_once '../lib/fpdf186/fpdf.php'; // Adjust path to your FPDF library
require_once '../Database/db.php';

// Start session
session_start();

class RatesClearancePDF extends FPDF
{
    private $paperSize;
    private $pageWidth;
    private $pageHeight;
    private $margins;
    private $contentWidth;
    
    function __construct($orientation = 'P', $unit = 'mm', $size = 'A4')
    {
        parent::__construct($orientation, $unit, $size);
        $this->paperSize = $size;
        $this->setPaperDimensions();
        $this->setMargins();
    }
    
    private function setPaperDimensions()
    {
        switch ($this->paperSize) {
            case 'A4':
                $this->pageWidth = 210;
                $this->pageHeight = 297;
                break;
            case 'A3':
                $this->pageWidth = 297;
                $this->pageHeight = 420;
                break;
            case 'A5':
                $this->pageWidth = 148;
                $this->pageHeight = 210;
                break;
            case 'Letter':
                $this->pageWidth = 216;
                $this->pageHeight = 279;
                break;
            case 'Legal':
                $this->pageWidth = 216;
                $this->pageHeight = 356;
                break;
            default:
                $this->pageWidth = 210;
                $this->pageHeight = 297;
        }
    }
    
    private function setMargins()
    {
        // Responsive margins based on paper size
        switch ($this->paperSize) {
            case 'A3':
                $this->margins = ['left' => 20, 'right' => 20, 'top' => 25, 'bottom' => 25];
                break;
            case 'A5':
                $this->margins = ['left' => 8, 'right' => 8, 'top' => 12, 'bottom' => 12];
                break;
            case 'Letter':
            case 'Legal':
                $this->margins = ['left' => 15, 'right' => 15, 'top' => 20, 'bottom' => 20];
                break;
            default: // A4
                $this->margins = ['left' => 10, 'right' => 10, 'top' => 15, 'bottom' => 15];
        }
        
        $this->contentWidth = $this->pageWidth - $this->margins['left'] - $this->margins['right'];
        $this->SetMargins($this->margins['left'], $this->margins['top'], $this->margins['right']);
        $this->SetAutoPageBreak(true, $this->margins['bottom']);
    }
    
    private function getResponsiveFontSize($baseSize)
    {
        // Scale font size based on paper size
        $scaleFactor = 1;
        switch ($this->paperSize) {
            case 'A3':
                $scaleFactor = 1.4;
                break;
            case 'A5':
                $scaleFactor = 0.7;
                break;
            case 'Letter':
            case 'Legal':
                $scaleFactor = 1.1;
                break;
        }
        return round($baseSize * $scaleFactor);
    }
    
    private function getLogoSize()
    {
        // Responsive logo sizing
        switch ($this->paperSize) {
            case 'A3':
                return ['width' => 60, 'height' => 45];
            case 'A5':
                return ['width' => 25, 'height' => 19];
            case 'Letter':
            case 'Legal':
                return ['width' => 45, 'height' => 34];
            default: // A4
                return ['width' => 40, 'height' => 30];
        }
    }
    
    // Page header
    function Header()
    {
        $logoSize = $this->getLogoSize();
        $headerHeight = $this->paperSize == 'A5' ? 35 : ($this->paperSize == 'A3' ? 65 : 50);
        
        // Left side - City information
        $this->SetFont('Arial', 'B', $this->getResponsiveFontSize(10));
        $this->SetXY($this->margins['left'], $this->margins['top']);
        $this->Cell(0, 6, 'City of Masvingo', 0, 1, 'L');
        
        $this->SetFont('Arial', '', $this->getResponsiveFontSize(8));
        $this->SetX($this->margins['left']);
        $addressWidth = $this->contentWidth * 0.35;
        $this->MultiCell($addressWidth, 4, "Robert Mugabe Way, Box 17\ntreasury@masingocity.org.zw\n+263 39 226241-4", 0, 'L');
        
        // Logo in center
        if (file_exists('../assets/images/mslogo.png')) {
            $logoX = ($this->pageWidth - $logoSize['width']) / 2;
            $this->Image('../assets/images/mslogo.png', $logoX, $this->margins['top'], $logoSize['width']);
        }
        
        // Right side - Property information
        $this->SetFont('Arial', 'B', $this->getResponsiveFontSize(10));
        $rightX = $this->pageWidth - $this->margins['right'] - ($this->contentWidth * 0.35);
        $this->SetXY($rightX, $this->margins['top']);
        $this->Cell($this->contentWidth * 0.35, 6, 'Property Address', 0, 1, 'L');
        
        $this->SetFont('Arial', '', $this->getResponsiveFontSize(8));
        $this->SetX($rightX);
        $this->MultiCell($this->contentWidth * 0.35, 4, "456 Elm Street\nOthertown, USA 67890", 0, 'L');
        
        // Main title
        $titleY = $this->margins['top'] + ($headerHeight * 0.6);
        $this->SetFont('Arial', 'B', $this->getResponsiveFontSize(16));
        $this->SetXY($this->margins['left'], $titleY);
        $this->Cell($this->contentWidth, 8, 'RATES CLEARANCE INVOICE', 0, 1, 'C');
        
        // Date and time
        $this->SetFont('Arial', '', $this->getResponsiveFontSize(9));
        $this->SetX($this->margins['left']);
        $this->Cell($this->contentWidth, 6, 'Generated on: ' . date('Y-m-d H:i:s'), 0, 1, 'R');
        
        // Add a line separator
        $this->SetY($titleY + 18);
        $this->Cell($this->contentWidth, 0, '', 'T', 1);
        
        // Set position for content to start
        $this->SetY($titleY + 25);
    }

    // Page footer
    function Footer()
    {
        // Position at bottom margin
        $this->SetY(-$this->margins['bottom'] - 10);
        
        // Add a line separator
        $this->Cell($this->contentWidth, 0, '', 'T', 1);
        $this->Ln(2);
        
        // Footer text
        $this->SetFont('Arial', 'I', $this->getResponsiveFontSize(7));
        $this->Cell($this->contentWidth, 4, 'This is a computer-generated document from City of Masvingo Rates Department', 0, 1, 'C');
        
        // Page number
        $this->Cell($this->contentWidth, 4, 'Page ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }
    
    // Add section header
    function SectionHeader($title)
    {
        $this->Ln(3);
        $this->SetFont('Arial', 'B', $this->getResponsiveFontSize(11));
        $this->SetFillColor(102, 126, 234);
        $this->SetTextColor(255, 255, 255);
        $this->Cell($this->contentWidth, 7, $title, 0, 1, 'L', true);
        $this->SetTextColor(0, 0, 0);
        $this->Ln(2);
    }
    
    // Add data row
    function DataRow($label, $value, $border = 0)
    {
        $this->SetFont('Arial', 'B', $this->getResponsiveFontSize(9));
        $labelWidth = $this->contentWidth * 0.4;
        $this->Cell($labelWidth, 5, $label . ':', $border, 0, 'L');
        $this->SetFont('Arial', '', $this->getResponsiveFontSize(9));
        $this->Cell($this->contentWidth - $labelWidth, 5, $value, $border, 1, 'L');
    }
    
    // Add responsive table
    function ResponsiveTable($headers, $data)
    {
        // Calculate column widths based on content width
        $numCols = count($headers);
        $colWidth = $this->contentWidth / $numCols;
        
        // Adjust column widths for better distribution
        $widths = [];
        switch ($numCols) {
            case 4:
                $widths = [
                    $this->contentWidth * 0.2,  // Month
                    $this->contentWidth * 0.35, // Description
                    $this->contentWidth * 0.25, // Account
                    $this->contentWidth * 0.2   // Amount
                ];
                break;
            case 3:
                $widths = [
                    $this->contentWidth * 0.4,  // Description
                    $this->contentWidth * 0.3,  // Account
                    $this->contentWidth * 0.3   // Amount
                ];
                break;
            default:
                for ($i = 0; $i < $numCols; $i++) {
                    $widths[] = $colWidth;
                }
        }
        
        // Table header
        $this->SetFont('Arial', 'B', $this->getResponsiveFontSize(9));
        $this->SetFillColor(230, 230, 230);
        
        for ($i = 0; $i < count($headers); $i++) {
            $this->Cell($widths[$i], 6, $headers[$i], 1, 0, 'C', true);
        }
        $this->Ln();
        
        // Table data
        $this->SetFont('Arial', '', $this->getResponsiveFontSize(8));
        foreach ($data as $row) {
            for ($i = 0; $i < count($row); $i++) {
                $this->Cell($widths[$i], 5, $row[$i], 1, 0, 'C');
            }
            $this->Ln();
        }
    }
    
    // Summary box with responsive sizing
    function SummaryBox($items, $total)
    {
        $boxWidth = $this->contentWidth * 0.4;
        $boxX = $this->contentWidth - $boxWidth + $this->margins['left'];
        
        $this->SetFont('Arial', 'B', $this->getResponsiveFontSize(10));
        $this->SetXY($boxX, $this->GetY());
        $this->Cell($boxWidth, 6, 'SUMMARY', 1, 1, 'C');
        
        $this->SetFont('Arial', '', $this->getResponsiveFontSize(9));
        foreach ($items as $label => $value) {
            $this->SetX($boxX);
            $this->Cell($boxWidth * 0.7, 5, $label . ':', 1, 0, 'L');
            $this->Cell($boxWidth * 0.3, 5, $value, 1, 1, 'R');
        }
        
        // Total with emphasis
        $this->SetFont('Arial', 'B', $this->getResponsiveFontSize(10));
        $this->SetFillColor(102, 126, 234);
        $this->SetTextColor(255, 255, 255);
        $this->SetX($boxX);
        $this->Cell($boxWidth * 0.7, 7, 'TOTAL AMOUNT:', 1, 0, 'L', true);
        $this->Cell($boxWidth * 0.3, 7, $total, 1, 1, 'R', true);
        $this->SetTextColor(0, 0, 0);
    }
}

// Get paper size from request or default to A4
$paperSize = $_POST['paper_size'] ?? $_GET['paper_size'] ?? 'A4';
$validSizes = ['A4', 'A3', 'A5', 'Letter', 'Legal'];
if (!in_array($paperSize, $validSizes)) {
    $paperSize = 'A4';
}

// Check if form data was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Create instance of PDF class with specified paper size
    $pdf = new RatesClearancePDF('P', 'mm', $paperSize);
    $pdf->AliasNbPages();
    $pdf->AddPage();
    
    // Invoice details section
    $pdf->SectionHeader('Invoice Details');
    $pdf->DataRow('Invoice Number', 'INV-' . ($_POST['property_id'] ?? 'N/A') . '-' . date('Ymd'));
    $pdf->DataRow('Property ID', $_POST['property_id'] ?? 'N/A');
    $pdf->DataRow('Application ID', $_POST['application_id'] ?? 'N/A');
    $pdf->DataRow('Period', ($_POST['period'] ?? 'N/A') . ' months');
    $pdf->DataRow('Number of Accounts', $_POST['accounts'] ?? 'N/A');
    $pdf->DataRow('Invoice Date', date('Y-m-d'));
    $pdf->DataRow('Paper Size', $paperSize);
    
    // Account Details
    $accountCount = intval($_POST['accounts'] ?? 0);
    $tableData = [];
    $totalAccountBalance = 0;
    
    if ($accountCount > 0) {
        $pdf->SectionHeader('Account Details');
        
        for ($i = 1; $i <= $accountCount; $i++) {
            $accountNumber = $_POST["account_{$i}_number"] ?? 'N/A';
            $accountBalance = $_POST["account_{$i}_balance"] ?? '$0.00';
            
            // Add account balance row
            $cleanAccountBalance = floatval(preg_replace('/[^\d.-]/', '', $accountBalance));
            if ($cleanAccountBalance > 0) {
                $tableData[] = ['', 'Account Balance', $accountNumber, '$' . number_format($cleanAccountBalance, 2)];
                $totalAccountBalance += $cleanAccountBalance;
            }
            
            // Add monthly breakdown
            for ($month = 1; $month <= 4; $month++) {
                $monthName = $_POST["month{$month}_name_account{$i}"] ?? '';
                $monthBalance = $_POST["month{$month}_balance_account{$i}"] ?? '';
                
                if (!empty($monthName) && !empty($monthBalance)) {
                    $cleanMonthBalance = floatval(preg_replace('/[^\d.-]/', '', $monthBalance));
                    if ($cleanMonthBalance > 0) {
                        $tableData[] = [$monthName, 'Monthly Rate', $accountNumber, '$' . number_format($cleanMonthBalance, 2)];
                        $totalAccountBalance += $cleanMonthBalance;
                    }
                }
            }
        }
        
        // Display table
        if (!empty($tableData)) {
            $pdf->ResponsiveTable(['Month', 'Description', 'Account', 'Amount'], $tableData);
        }
    }
    
    // Financial Summary
    $pdf->Ln(10);
    $pdf->SectionHeader('Financial Summary');
    
    $processingFee = floatval(preg_replace('/[^\d.-]/', '', $_POST['processing_fee'] ?? '0'));
    $grandTotal = $totalAccountBalance + $processingFee;
    
    // Summary box
    $summaryItems = [
        'Subtotal' => '$' . number_format($totalAccountBalance, 2),
        'Processing Fee' => '$' . number_format($processingFee, 2)
    ];
    $pdf->SummaryBox($summaryItems, '$' . number_format($grandTotal, 2));
    
    // Payment Information
    $pdf->Ln(15);
    $pdf->SectionHeader('Payment Information');
    $pdf->SetFont('Arial', '', $pdf->getResponsiveFontSize(9));
    $paymentText = "Payment Methods:\n• Bank Transfer: Account No. 123456789, City Bank\n• Cash Payment: City of Masvingo Treasury Office\n• Online Payment: www.masvingocity.org.zw/payments\n\nPayment Due Date: " . date('Y-m-d', strtotime('+30 days'));
    $pdf->MultiCell($pdf->contentWidth, 4, $paymentText);
    
    // Terms and Conditions
    $pdf->Ln(5);
    $pdf->SectionHeader('Terms and Conditions');
    $pdf->SetFont('Arial', '', $pdf->getResponsiveFontSize(8));
    $termsText = "1. Payment is due within 30 days of invoice date.\n2. Late payments may incur additional charges.\n3. All amounts are in USD unless otherwise specified.\n4. This invoice is computer-generated and valid without signature.\n5. For queries, contact treasury@masingocity.org.zw or +263 39 226241-4.";
    $pdf->MultiCell($pdf->contentWidth, 3.5, $termsText);
    
    // Final notes
    $pdf->Ln(8);
    $pdf->SetFont('Arial', 'I', $pdf->getResponsiveFontSize(7));
    $pdf->SetTextColor(128, 128, 128);
    $pdf->MultiCell($pdf->contentWidth, 3, 'Thank you for your prompt payment. This document was automatically generated by the City of Masvingo Rates Clearance System on ' . date('Y-m-d H:i:s') . '. Optimized for ' . $paperSize . ' paper size.');
    
    // Output PDF
    $filename = 'rates_clearance_invoice_' . $paperSize . '_' . ($_POST['property_id'] ?? 'unknown') . '_' . date('Y-m-d_H-i-s') . '.pdf';
    $pdf->Output('D', $filename); // 'D' forces download
    
} else {
    // If accessed directly without POST data, show paper size selection
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>PDF Paper Size Selection</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                max-width: 600px;
                margin: 50px auto;
                padding: 20px;
                background: #f5f5f5;
            }
            .container {
                background: white;
                padding: 30px;
                border-radius: 10px;
                box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            }
            h2 {
                color: #333;
                text-align: center;
                margin-bottom: 30px;
            }
            .size-option {
                display: flex;
                align-items: center;
                padding: 15px;
                margin: 10px 0;
                border: 2px solid #e0e0e0;
                border-radius: 8px;
                cursor: pointer;
                transition: all 0.3s ease;
            }
            .size-option:hover {
                border-color: #667eea;
                background: #f8f9ff;
            }
            .size-option input[type="radio"] {
                margin-right: 15px;
                transform: scale(1.2);
            }
            .size-info {
                flex: 1;
            }
            .size-name {
                font-weight: bold;
                font-size: 16px;
                color: #333;
            }
            .size-dimensions {
                color: #666;
                font-size: 14px;
                margin-top: 5px;
            }
            .size-description {
                color: #888;
                font-size: 12px;
                margin-top: 3px;
            }
            .btn {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                padding: 12px 30px;
                border: none;
                border-radius: 6px;
                cursor: pointer;
                font-size: 16px;
                width: 100%;
                margin-top: 20px;
                transition: transform 0.2s ease;
            }
            .btn:hover {
                transform: translateY(-2px);
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h2>Select PDF Paper Size</h2>
            <form method="GET" action="">
                <div class="size-option">
                    <input type="radio" name="paper_size" value="A4" id="a4" checked>
                    <div class="size-info">
                        <div class="size-name">A4</div>
                        <div class="size-dimensions">210 × 297 mm (8.27 × 11.69 in)</div>
                        <div class="size-description">Standard international paper size</div>
                    </div>
                </div>
                
                <div class="size-option">
                    <input type="radio" name="paper_size" value="A3" id="a3">
                    <div class="size-info">
                        <div class="size-name">A3</div>
                        <div class="size-dimensions">297 × 420 mm (11.69 × 16.54 in)</div>
                        <div class="size-description">Large format, good for detailed reports</div>
                    </div>
                </div>
                
                <div class="size-option">
                    <input type="radio" name="paper_size" value="A5" id="a5">
                    <div class="size-info">
                        <div class="size-name">A5</div>
                        <div class="size-dimensions">148 × 210 mm (5.83 × 8.27 in)</div>
                        <div class="size-description">Compact size, good for mobile printing</div>
                    </div>
                </div>
                
                <div class="size-option">
                    <input type="radio" name="paper_size" value="Letter" id="letter">
                    <div class="size-info">
                        <div class="size-name">Letter</div>
                        <div class="size-dimensions">216 × 279 mm (8.5 × 11 in)</div>
                        <div class="size-description">Standard US paper size</div>
                    </div>
                </div>
                
                <div class="size-option">
                    <input type="radio" name="paper_size" value="Legal" id="legal">
                    <div class="size-info">
                        <div class="size-name">Legal</div>
                        <div class="size-dimensions">216 × 356 mm (8.5 × 14 in)</div>
                        <div class="size-description">US legal document size</div>
                    </div>
                </div>
                
                <button type="submit" class="btn">Continue to Invoice Generator</button>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit();
}
?>