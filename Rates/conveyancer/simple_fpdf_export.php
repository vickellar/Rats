<?php
session_start();
require_once("../Database/db.php");

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: ../signin.php");
    exit();
}

// Include FPDF library
require_once('fpdf/fpdf.php');

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

    // Create PDF
    $pdf = new FPDF();
    $pdf->AddPage();
    
    // Set title
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->Cell(0, 15, 'Properties Report', 0, 1, 'C');
    
    // Add user info
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(0, 8, 'Generated on: ' . date('Y-m-d H:i:s'), 0, 1, 'R');
    $pdf->Cell(0, 8, 'User: ' . $username, 0, 1, 'R');
    $pdf->Ln(10);
    
    // Table header
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetFillColor(230, 230, 230);
    
    // Column widths
    $w = array(40, 70, 25, 30, 25);
    
    // Headers
    $header = array('Owner', 'Address', 'Size (sq m)', 'Type', 'Accounts');
    for($i = 0; $i < count($header); $i++) {
        $pdf->Cell($w[$i], 8, $header[$i], 1, 0, 'C', true);
    }
    $pdf->Ln();
    
    // Data
    $pdf->SetFont('Arial', '', 9);
    $pdf->SetFillColor(248, 248, 248);
    
    $fill = false;
    foreach($properties as $row) {
        // Truncate long text to fit in cells
        $owner = strlen($row['owner']) > 25 ? substr($row['owner'], 0, 22) . '...' : $row['owner'];
        $address = strlen($row['address']) > 45 ? substr($row['address'], 0, 42) . '...' : $row['address'];
        $accounts = strlen($row['account_numbers']) > 15 ? substr($row['account_numbers'], 0, 12) . '...' : ($row['account_numbers'] ?: 'None');
        
        $pdf->Cell($w[0], 6, $owner, 'LR', 0, 'L', $fill);
        $pdf->Cell($w[1], 6, $address, 'LR', 0, 'L', $fill);
        $pdf->Cell($w[2], 6, $row['size'], 'LR', 0, 'C', $fill);
        $pdf->Cell($w[3], 6, $row['type'], 'LR', 0, 'L', $fill);
        $pdf->Cell($w[4], 6, $accounts, 'LR', 0, 'L', $fill);
        $pdf->Ln();
        $fill = !$fill;
    }
    
    // Closing line
    $pdf->Cell(array_sum($w), 0, '', 'T');
    
    // Summary
    $pdf->Ln(15);
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 8, 'Summary', 0, 1, 'L');
    
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(0, 6, 'Total Properties: ' . count($properties), 0, 1, 'L');
    
    // Count by type
    $types = array();
    $totalSize = 0;
    foreach($properties as $property) {
        $type = $property['type'];
        $types[$type] = isset($types[$type]) ? $types[$type] + 1 : 1;
        $totalSize += (float)$property['size'];
    }
    
    $pdf->Cell(0, 6, 'Total Size: ' . number_format($totalSize) . ' sq meters', 0, 1, 'L');
    $pdf->Ln(5);
    
    foreach($types as $type => $count) {
        $pdf->Cell(0, 5, $type . ': ' . $count . ' properties', 0, 1, 'L');
    }
    
    // Generate filename
    $filename = 'properties_report_' . date('Y-m-d_H-i-s') . '.pdf';
    
    // Output
    $pdf->Output('D', $filename);
    
} catch (Exception $e) {
    error_log("PDF Export Error: " . $e->getMessage());
    header("Location: view_properties.php?error=pdf_export_failed");
    exit();
}
?>
