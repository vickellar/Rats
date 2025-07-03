<?php
session_start();
header('Content-Type: application/json');

// Check if user is authenticated and has proper role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'finance_director') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

include '../Database/db.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    switch ($method) {
        case 'GET':
            handleGetRequest($action, $pdo);
            break;
        case 'POST':
            handlePostRequest($action, $pdo);
            break;
        case 'PUT':
            handlePutRequest($action, $pdo);
            break;
        case 'DELETE':
            handleDeleteRequest($action, $pdo);
            break;
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error: ' . $e->getMessage()]);
}

function handleGetRequest($action, $pdo) {
    switch ($action) {
        case 'invoices':
            getInvoices($pdo);
            break;
        case 'invoice':
            getInvoice($pdo, $_GET['id']);
            break;
        case 'payments':
            getPayments($pdo);
            break;
        case 'audit-log':
            getAuditLog($pdo);
            break;
        case 'dashboard-stats':
            getDashboardStats($pdo);
            break;
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
    }
}

function handlePostRequest($action, $pdo) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    switch ($action) {
        case 'approve-invoice':
            approveInvoice($pdo, $input);
            break;
        case 'reject-invoice':
            rejectInvoice($pdo, $input);
            break;
        case 'approve-payment':
            approvePayment($pdo, $input);
            break;
        case 'reject-payment':
            rejectPayment($pdo, $input);
            break;
        case 'upload-invoice':
            uploadInvoice($pdo, $input);
            break;
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
    }
}

function getInvoices($pdo) {
    $sql = "SELECT i.*, v.name as vendor_name, v.email as vendor_email 
            FROM invoices i 
            LEFT JOIN vendors v ON i.vendor_id = v.id 
            ORDER BY i.created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $invoices]);
}

function getInvoice($pdo, $invoiceId) {
    $sql = "SELECT i.*, v.name as vendor_name, v.email as vendor_email,
                   v.address as vendor_address, v.phone as vendor_phone
            FROM invoices i 
            LEFT JOIN vendors v ON i.vendor_id = v.id 
            WHERE i.id = ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$invoiceId]);
    $invoice = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($invoice) {
        echo json_encode(['success' => true, 'data' => $invoice]);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Invoice not found']);
    }
}

function getPayments($pdo) {
    $sql = "SELECT p.*, v.name as vendor_name, u.username as requested_by_name
            FROM payments p 
            LEFT JOIN vendors v ON p.vendor_id = v.id 
            LEFT JOIN users u ON p.requested_by = u.id
            ORDER BY p.created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $payments]);
}

function getAuditLog($pdo) {
    $sql = "SELECT al.*, u.username as user_name
            FROM audit_log al 
            LEFT JOIN users u ON al.user_id = u.id
            ORDER BY al.created_at DESC 
            LIMIT 100";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $auditLog = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $auditLog]);
}

function getDashboardStats($pdo) {
    // Get pending invoices count
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM invoices WHERE status IN ('pending', 'urgent')");
    $stmt->execute();
    $pendingInvoices = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Get approved payments count
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM payments WHERE status = 'approved'");
    $stmt->execute();
    $approvedPayments = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Get total pending amount
    $stmt = $pdo->prepare("SELECT SUM(amount) as total FROM invoices WHERE status IN ('pending', 'urgent')");
    $stmt->execute();
    $totalPendingAmount = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    
    // Get monthly budget (this could be from a settings table)
    $monthlyBudget = 500000; // This should come from database
    
    $stats = [
        'pending_invoices' => $pendingInvoices,
        'approved_payments' => $approvedPayments,
        'total_pending_amount' => $totalPendingAmount,
        'monthly_budget' => $monthlyBudget,
        'budget_utilization' => round(($totalPendingAmount / $monthlyBudget) * 100, 2)
    ];
    
    echo json_encode(['success' => true, 'data' => $stats]);
}

function approveInvoice($pdo, $input) {
    $invoiceId = $input['invoice_id'];
    $comments = $input['comments'] ?? '';
    $userId = $_SESSION['user_id'];
    
    $pdo->beginTransaction();
    
    try {
        // Update invoice status
        $stmt = $pdo->prepare("UPDATE invoices SET status = 'approved', approved_by = ?, approved_at = NOW(), comments = ? WHERE id = ?");
        $stmt->execute([$userId, $comments, $invoiceId]);
        
        // Log the action
        logAuditAction($pdo, $userId, 'invoice_approved', "Invoice {$invoiceId} approved", $invoiceId);
        
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Invoice approved successfully']);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function rejectInvoice($pdo, $input) {
    $invoiceId = $input['invoice_id'];
    $reason = $input['reason'] ?? '';
    $userId = $_SESSION['user_id'];
    
    $pdo->beginTransaction();
    
    try {
        // Update invoice status
        $stmt = $pdo->prepare("UPDATE invoices SET status = 'rejected', rejected_by = ?, rejected_at = NOW(), rejection_reason = ? WHERE id = ?");
        $stmt->execute([$userId, $reason, $invoiceId]);
        
        // Log the action
        logAuditAction($pdo, $userId, 'invoice_rejected', "Invoice {$invoiceId} rejected: {$reason}", $invoiceId);
        
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Invoice rejected successfully']);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function approvePayment($pdo, $input) {
    $paymentId = $input['payment_id'];
    $comments = $input['comments'] ?? '';
    $userId = $_SESSION['user_id'];
    
    $pdo->beginTransaction();
    
    try {
        // Update payment status
        $stmt = $pdo->prepare("UPDATE payments SET status = 'approved', approved_by = ?, approved_at = NOW(), comments = ? WHERE id = ?");
        $stmt->execute([$userId, $comments, $paymentId]);
        
        // Log the action
        logAuditAction($pdo, $userId, 'payment_approved', "Payment {$paymentId} approved", $paymentId);
        
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Payment approved successfully']);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function rejectPayment($pdo, $input) {
    $paymentId = $input['payment_id'];
    $reason = $input['reason'] ?? '';
    $userId = $_SESSION['user_id'];
    
    $pdo->beginTransaction();
    
    try {
        // Update payment status
        $stmt = $pdo->prepare("UPDATE payments SET status = 'rejected', rejected_by = ?, rejected_at = NOW(), rejection_reason = ? WHERE id = ?");
        $stmt->execute([$userId, $reason, $paymentId]);
        
        // Log the action
        logAuditAction($pdo, $userId, 'payment_rejected', "Payment {$paymentId} rejected: {$reason}", $paymentId);
        
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Payment rejected successfully']);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function uploadInvoice($pdo, $input) {
    // Handle file upload logic here
    // This would typically involve processing uploaded files
    // and storing invoice data in the database
    
    echo json_encode(['success' => true, 'message' => 'Invoice uploaded successfully']);
}

function logAuditAction($pdo, $userId, $action, $description, $relatedId = null) {
    $stmt = $pdo->prepare("INSERT INTO audit_log (user_id, action, description, related_id, created_at) VALUES (?, ?, ?, ?, NOW())");
    $stmt->execute([$userId, $action, $description, $relatedId]);
}
?>
