<?php
session_start();
require_once("../Database/db.php");

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: ../signin.php");
    exit();
}

// Include PhpSpreadsheet library (install via Composer: composer require phpoffice/phpspreadsheet)
require_once 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

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

    // Create new Spreadsheet object
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Set document properties
    $spreadsheet->getProperties()
        ->setCreator($_SESSION['username'])
        ->setLastModifiedBy($_SESSION['username'])
        ->setTitle('Properties Report')
        ->setSubject('Property Data Export')
        ->setDescription('Export of property data from Property Management System')
        ->setKeywords('properties real estate export')
        ->setCategory('Report');

    // Set sheet title
    $sheet->setTitle('Properties');

    // Add header information
    $sheet->setCellValue('A1', 'Properties Report');
    $sheet->setCellValue('A2', 'Generated on: ' . date('Y-m-d H:i:s'));
    $sheet->setCellValue('A3', 'User: ' . $_SESSION['username']);
    $sheet->setCellValue('A4', 'Total Properties: ' . count($properties));

    // Style the header
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
    $sheet->getStyle('A2:A4')->getFont()->setSize(10);

    // Set column headers starting from row 6
    $headers = ['Owner', 'Address', 'Size (sq meters)', 'Type', 'Account Numbers'];
    $column = 'A';
    foreach ($headers as $header) {
        $sheet->setCellValue($column . '6', $header);
        $column++;
    }

    // Style the column headers
    $headerRange = 'A6:E6';
    $sheet->getStyle($headerRange)->getFont()->setBold(true);
    $sheet->getStyle($headerRange)->getFill()
        ->setFillType(Fill::FILL_SOLID)
        ->getStartColor()->setRGB('E9ECEF');
    
    $sheet->getStyle($headerRange)->getAlignment()
        ->setHorizontal(Alignment::HORIZONTAL_CENTER);

    // Add data starting from row 7
    $row = 7;
    if (count($properties) > 0) {
        foreach ($properties as $property) {
            $sheet->setCellValue('A' . $row, $property['owner']);
            $sheet->setCellValue('B' . $row, $property['address']);
            $sheet->setCellValue('C' . $row, $property['size']);
            $sheet->setCellValue('D' . $row, $property['type']);
            $sheet->setCellValue('E' . $row, $property['account_numbers']);
            $row++;
        }
    } else {
        $sheet->setCellValue('A7', 'No properties found');
        $sheet->mergeCells('A7:E7');
        $sheet->getStyle('A7')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }

    // Auto-size columns
    foreach (range('A', 'E') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    // Add borders to the data table
    $dataRange = 'A6:E' . ($row - 1);
    $sheet->getStyle($dataRange)->getBorders()->getAllBorders()
        ->setBorderStyle(Border::BORDER_THIN);

    // Set alternating row colors
    for ($i = 7; $i < $row; $i += 2) {
        $sheet->getStyle('A' . $i . ':E' . $i)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('F8F9FA');
    }

    // Add summary section
    $summaryRow = $row + 2;
    $sheet->setCellValue('A' . $summaryRow, 'Summary');
    $sheet->getStyle('A' . $summaryRow)->getFont()->setBold(true)->setSize(14);

    $summaryRow++;
    $sheet->setCellValue('A' . $summaryRow, 'Total Properties:');
    $sheet->setCellValue('B' . $summaryRow, count($properties));

    // Count properties by type
    $propertyTypes = [];
    foreach ($properties as $property) {
        $type = $property['type'];
        $propertyTypes[$type] = isset($propertyTypes[$type]) ? $propertyTypes[$type] + 1 : 1;
    }

    $summaryRow++;
    $sheet->setCellValue('A' . $summaryRow, 'Properties by Type:');
    $summaryRow++;

    foreach ($propertyTypes as $type => $count) {
        $sheet->setCellValue('A' . $summaryRow, $type . ':');
        $sheet->setCellValue('B' . $summaryRow, $count);
        $summaryRow++;
    }

    // Calculate total size
    $totalSize = array_sum(array_column($properties, 'size'));
    $sheet->setCellValue('A' . $summaryRow, 'Total Size (sq meters):');
    $sheet->setCellValue('B' . $summaryRow, $totalSize);

    // Generate filename
    $filename = 'properties_report_' . date('Y-m-d_H-i-s') . '.xlsx';

    // Set headers for download
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: max-age=0');

    // Create writer and save to output
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');

} catch (Exception $e) {
    error_log("Excel Export Error: " . $e->getMessage());
    header("Location: view_properties.php?error=excel_export_failed");
    exit();
}
?>
