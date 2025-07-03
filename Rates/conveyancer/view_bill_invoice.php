<?php
session_start();
require_once("../Database/db.php");
require_once('../lib/fpdf186/fpdf.php');

if (!isset($_GET['bill_id'])) {
    echo "Error: bill_id parameter is required.";
    exit;
}

$billId = intval($_GET['bill_id']);

try {
    // Fetch bill details
    $stmtBill = $pdo->prepare("
        SELECT cb.*, p.address AS property_address, a.account_number
        FROM calculated_bills cb
        JOIN rate_clearance_applications rca ON cb.application_id = rca.application_id
        JOIN properties p ON rca.property_id = p.property_id
        JOIN accounts a ON cb.account_id = a.account_id
        WHERE cb.bill_id = :billId
        LIMIT 1
    ");
    $stmtBill->execute(['billId' => $billId]);
    $bill = $stmtBill->fetch(PDO::FETCH_ASSOC);

    if (!$bill) {
        echo "Bill not found.";
        exit;
    }

    // Fetch monthly fees for this bill's account
    $stmtMonths = $pdo->prepare("
        SELECT mf.month_name, mf.month_balance
        FROM months_fees mf
        WHERE mf.account_id = :accountId
    ");
    $stmtMonths->execute(['accountId' => $bill['account_id']]);
    $months = $stmtMonths->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Error fetching bill data for PDF: " . $e->getMessage(), 3, __DIR__ . '/../logfile/database_errors.log');
    echo "Error fetching bill data.";
    exit;
}

// Generate PDF
$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial','B',16);
$pdf->Cell(0,10,'Bill Invoice',0,1,'C');

$pdf->SetFont('Arial','',12);
$pdf->Cell(0,10,'Account Number: ' . $bill['account_number'],0,1);
$pdf->Cell(0,10,'Property Address: ' . $bill['property_address'],0,1);
$pdf->Cell(0,10,'Total Balance: $' . number_format($bill['total_balance'], 2),0,1);
$pdf->Cell(0,10,'Processing Fee: $' . number_format($bill['processing_fee'], 2),0,1);
$pdf->Cell(0,10,'Overall Total: $' . number_format($bill['overall_total'], 2),0,1);

$pdf->Ln(10);
$pdf->SetFont('Arial','B',14);
$pdf->Cell(0,10,'Monthly Fees:',0,1);

$pdf->SetFont('Arial','',12);
if ($months) {
    foreach ($months as $month) {
        $pdf->Cell(0,8, $month['month_name'] . ': $' . number_format($month['month_balance'], 2), 0, 1);
    }
} else {
    $pdf->Cell(0,8, 'No monthly fees found.', 0, 1);
}

$pdf->Output('I', 'Bill_Invoice_' . $billId . '.pdf');
exit;
?>
