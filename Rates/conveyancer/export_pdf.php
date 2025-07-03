<?php
session_start();
require_once("../Database/db.php");

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: ../signin.php");
    exit();
}

// Verify CSRF token (implement your CSRF protection)
// if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
//     die('CSRF token mismatch');
// }

// Include TCPDF library (you need to install this via Composer)
require_once('vendor/autoload.php'); // If using Composer
// OR include TCPDF manually:
// require_once('tcpdf/tcpdf.php');

use TCPDF;

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

    // Create new PDF document
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

    // Set document information
    $pdf->SetCreator('Property Management System');
    $pdf->SetAuthor($_SESSION['username']);
    $pdf->SetTitle('Properties Report');
    $pdf->SetSubject('Property Data Export');

    // Set default header data
    $pdf->SetHeaderData('', 0, 'Properties Report', 'Generated on ' . date('Y-m-d H:i:s') . "\nUser: " . $_SESSION['username']);

    // Set header and footer fonts
    $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
    $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

    // Set default monospaced font
    $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

    // Set margins
    $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
    $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
    $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

    // Set auto page breaks
    $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

    // Add a page
    $pdf->AddPage();

    // Set font
    $pdf->SetFont('helvetica', '', 10);

    // Create HTML content for the table
    $html = '
    <style>
        table {
            border-collapse: collapse;
            width: 100%;
        }
        th {
            background-color: #f2f2f2;
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
            font-weight: bold;
        }
        td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
    </style>
    
    <div class="header">
        <h2>Properties Report</h2>
        <p>Total Properties: ' . count($properties) . '</p>
    </div>
    
    <table>
        <thead>
            <tr>
                <th>Owner</th>
                <th>Address</th>
                <th>Size (sq meters)</th>
                <th>Type</th>
                <th>Account Numbers</th>
            </tr>
        </thead>
        <tbody>';

    if (count($properties) > 0) {
        foreach ($properties as $property) {
            $html .= '<tr>
                <td>' . htmlspecialchars($property['owner']) . '</td>
                <td>' . htmlspecialchars($property['address']) . '</td>
                <td>' . htmlspecialchars($property['size']) . '</td>
                <td>' . htmlspecialchars($property['type']) . '</td>
                <td>' . htmlspecialchars($property['account_numbers']) . '</td>
            </tr>';
        }
    } else {
        $html .= '<tr><td colspan="5" style="text-align: center;">No properties found.</td></tr>';
    }

    $html .= '</tbody></table>';

    // Print text using writeHTMLCell()
    $pdf->writeHTML($html, true, false, true, false, '');

    // Generate filename
    $filename = 'properties_report_' . date('Y-m-d_H-i-s') . '.pdf';

    // Close and output PDF document
    $pdf->Output($filename, 'D'); // 'D' for download

} catch (Exception $e) {
    error_log("PDF Export Error: " . $e->getMessage());
    header("Location: view_properties.php?error=pdf_export_failed");
    exit();
}
?>
