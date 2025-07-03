<?php
session_start();
require_once("../Database/db.php");

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: ../signin.php");
    exit();
}

// Include FPDF library (download from http://www.fpdf.org/)
require_once('../lib/fpdf186/fpdf.php');


class PropertyPDF extends FPDF
{
    private $username;
    
    public function __construct($username) {
        parent::__construct();
        $this->username = $username;
    }
    
    // Page header
    function Header()
    {
        // Logo placeholder (you can add your logo here)
        // $this->Image('logo.png', 10, 6, 30);
        
        // Arial bold 15
        $this->SetFont('Arial', 'B', 16);
        // Move to the right
        $this->Cell(80);
        // Title
        $this->Cell(30, 10, 'Properties Report', 0, 0, 'C');
        // Line break
        $this->Ln(10);
        
        // User info
        $this->SetFont('Arial', '', 10);
        $this->Cell(0, 5, 'Generated on: ' . date('Y-m-d H:i:s'), 0, 1, 'R');
        $this->Cell(0, 5, 'User: ' . $this->username, 0, 1, 'R');
        $this->Ln(5);
    }
    
    // Page footer
    function Footer()
    {
        // Position at 1.5 cm from bottom
        $this->SetY(-15);
        // Arial italic 8
        $this->SetFont('Arial', 'I', 8);
        // Page number
        $this->Cell(0, 10, 'Page ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }
    
    // Table header
    function TableHeader()
    {
        // Colors, line width and bold font
        $this->SetFillColor(240, 240, 240);
        $this->SetTextColor(0);
        $this->SetDrawColor(128, 128, 128);
        $this->SetLineWidth(.3);
        $this->SetFont('Arial', 'B', 10);
        
        // Header
        $headers = array('Owner', 'Address', 'Size (sq m)', 'Type', 'Accounts');
        $widths = array(35, 60, 25, 30, 40);
        
        for($i = 0; $i < count($headers); $i++) {
            $this->Cell($widths[$i], 8, $headers[$i], 1, 0, 'C', true);
        }
        $this->Ln();
    }
    
    // Table row
    function TableRow($data, $isEven = false)
    {
        // Set fill color for alternating rows
        if ($isEven) {
            $this->SetFillColor(248, 249, 250);
        } else {
            $this->SetFillColor(255, 255, 255);
        }
        
        $this->SetTextColor(0);
        $this->SetFont('Arial', '', 9);
        
        $widths = array(35, 60, 25, 30, 40);
        $height = 8;
        
        // Calculate the height needed for the row (in case of text wrapping)
        $maxLines = 1;
        for($i = 0; $i < count($data); $i++) {
            $lines = $this->GetStringWidth($data[$i]) / $widths[$i];
            if ($lines > 1) {
                $maxLines = max($maxLines, ceil($lines));
            }
        }
        
        $rowHeight = $height * $maxLines;
        
        // Check if we need a new page
        if ($this->GetY() + $rowHeight > $this->PageBreakTrigger) {
            $this->AddPage();
            $this->TableHeader();
        }
        
        // Print the cells
        for($i = 0; $i < count($data); $i++) {
            $this->Cell($widths[$i], $rowHeight, $this->TruncateText($data[$i], $widths[$i]), 1, 0, 'L', true);
        }
        $this->Ln();
    }
    
    // Truncate text to fit in cell
    function TruncateText($text, $maxWidth)
    {
        $this->SetFont('Arial', '', 9);
        if ($this->GetStringWidth($text) <= $maxWidth - 4) {
            return $text;
        }
        
        while ($this->GetStringWidth($text . '...') > $maxWidth - 4 && strlen($text) > 0) {
            $text = substr($text, 0, -1);
        }
        return $text . '...';
    }
    
    // Summary section
    function AddSummary($totalProperties, $propertyTypes, $totalSize)
    {
        $this->Ln(10);
        
        // Summary title
        $this->SetFont('Arial', 'B', 14);
        $this->Cell(0, 10, 'Summary', 0, 1, 'L');
        
        $this->SetFont('Arial', '', 10);
        
        // Total properties
        $this->Cell(0, 6, 'Total Properties: ' . $totalProperties, 0, 1, 'L');
        
        // Total size
        $this->Cell(0, 6, 'Total Size: ' . number_format($totalSize) . ' sq meters', 0, 1, 'L');
        
        $this->Ln(5);
        
        // Properties by type
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(0, 8, 'Properties by Type:', 0, 1, 'L');
        
        $this->SetFont('Arial', '', 10);
        foreach ($propertyTypes as $type => $count) {
            $this->Cell(0, 5, '• ' . $type . ': ' . $count . ' properties', 0, 1, 'L');
        }
    }
}

try {
    // Fetch properties data
    $username = $_SESSION['username'];
    $sql = "SELECT properties.*, GROUP_CONCAT(accounts.account_number) AS account_numbers 
            FROM properties 
            LEFT JOIN accounts ON properties.property_id = accounts.property_id 
            WHERE properties.user_id = :user_id 
            GROUP BY properties.property_id";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':user_id' => $_SESSION['user_id']]);
    $properties = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Create PDF instance
    $pdf = new PropertyPDF($username);
    $pdf->AliasNbPages();
    $pdf->AddPage();
    
    // Add title section
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 8, 'Property Listing Report', 0, 1, 'L');
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(0, 6, 'Total Properties Found: ' . count($properties), 0, 1, 'L');
    $pdf->Ln(5);
    
    // Add table header
    $pdf->TableHeader();
    
    // Add data rows
    if (count($properties) > 0) {
        $rowCount = 0;
        foreach ($properties as $property) {
            $rowData = array(
                $property['owner'],
                $property['address'],
                $property['size'],
                $property['type'],
                $property['account_numbers'] ?: 'None'
            );
            
            $pdf->TableRow($rowData, $rowCount % 2 == 0);
            $rowCount++;
        }
        
        // Calculate summary data
        $propertyTypes = array();
        $totalSize = 0;
        
        foreach ($properties as $property) {
            $type = $property['type'];
            $propertyTypes[$type] = isset($propertyTypes[$type]) ? $propertyTypes[$type] + 1 : 1;
            $totalSize += (float)$property['size'];
        }
        
        // Add summary
        $pdf->AddSummary(count($properties), $propertyTypes, $totalSize);
        
    } else {
        // No properties found
        $pdf->SetFont('Arial', 'I', 12);
        $pdf->Cell(0, 20, 'No properties found for this user.', 0, 1, 'C');
    }
    
    // Generate filename
    $filename = 'properties_report_' . date('Y-m-d_H-i-s') . '.pdf';
    
    // Output PDF
    $pdf->Output('D', $filename); // 'D' for download
    
} catch (Exception $e) {
    error_log("PDF Export Error: " . $e->getMessage());
    header("Location: view_properties.php?error=pdf_export_failed");
    exit();
}
?>
