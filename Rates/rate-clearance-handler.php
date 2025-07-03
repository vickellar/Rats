<?php
session_start();
header('Content-Type: application/json');

// Check if user is authenticated and has proper role
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['finance_director', 'accountant', 'manager'])) {
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
        case 'rates':
            getRates($pdo);
            break;
        case 'rate':
            getRate($pdo, $_GET['id']);
            break;
        case 'rate-types':
            getRateTypes($pdo);
            break;
        case 'compliance-status':
            getComplianceStatus($pdo);
            break;
        case 'rate-history':
            getRateHistory($pdo, $_GET['id']);
            break;
        case 'expiring-rates':
            getExpiringRates($pdo);
            break;
        case 'expired-rates':
            getExpiredRates($pdo);
            break;
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
    }
}

function handlePostRequest($action, $pdo) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    switch ($action) {
        case 'approve-rate':
            approveRate($pdo, $input);
            break;
        case 'reject-rate':
            rejectRate($pdo, $input);
            break;
        case 'submit-rate':
            submitNewRate($pdo, $input);
            break;
        case 'create-rate-type':
            createRateType($pdo, $input);
            break;
        case 'generate-report':
            generateReport($pdo, $input);
            break;
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
    }
}

function handlePutRequest($action, $pdo) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    switch ($action) {
        case 'update-rate':
            updateRate($pdo, $input);
            break;
        case 'update-rate-type':
            updateRateType($pdo, $input);
            break;
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
    }
}

function handleDeleteRequest($action, $pdo) {
    switch ($action) {
        case 'delete-rate':
            deleteRate($pdo, $_GET['id']);
            break;
        case 'delete-rate-type':
            deleteRateType($pdo, $_GET['id']);
            break;
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
    }
}

function getRates($pdo) {
    $status = $_GET['status'] ?? '';
    $priority = $_GET['priority'] ?? '';
    $type_id = $_GET['type_id'] ?? '';
    
    $sql = "SELECT rc.*, rt.name as rate_type_name, u.username as submitted_by_name,
                   v.name as vendor_name, p.name as project_name,
                   CASE 
                       WHEN rc.expires_at < NOW() THEN 'expired'
                       WHEN rc.expires_at BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 30 DAY) THEN 'expiring'
                       ELSE 'active'
                   END as expiry_status
            FROM rate_clearances rc
            LEFT JOIN rate_types rt ON rc.rate_type_id = rt.id
            LEFT JOIN users u ON rc.submitted_by = u.id
            LEFT JOIN vendors v ON rc.vendor_id = v.id
            LEFT JOIN projects p ON rc.project_id = p.id
            WHERE 1=1";
    
    $params = [];
    
    if ($status) {
        $sql .= " AND rc.status = ?";
        $params[] = $status;
    }
    
    if ($priority) {
        $sql .= " AND rc.priority = ?";
        $params[] = $priority;
    }
    
    if ($type_id) {
        $sql .= " AND rc.rate_type_id = ?";
        $params[] = $type_id;
    }
    
    $sql .= " ORDER BY rc.priority DESC, rc.created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $rates]);
}

function getRate($pdo, $rateId) {
    $sql = "SELECT rc.*, rt.name as rate_type_name, rt.description as rate_type_description,
                   u.username as submitted_by_name, u.email as submitted_by_email,
                   v.name as vendor_name, v.email as vendor_email,
                   p.name as project_name, p.description as project_description,
                   au.username as approved_by_name, ru.username as rejected_by_name
            FROM rate_clearances rc
            LEFT JOIN rate_types rt ON rc.rate_type_id = rt.id
            LEFT JOIN users u ON rc.submitted_by = u.id
            LEFT JOIN vendors v ON rc.vendor_id = v.id
            LEFT JOIN projects p ON rc.project_id = p.id
            LEFT JOIN users au ON rc.approved_by = au.id
            LEFT JOIN users ru ON rc.rejected_by = ru.id
            WHERE rc.id = ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$rateId]);
    $rate = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($rate) {
        // Get supporting documents
        $docStmt = $pdo->prepare("SELECT * FROM rate_documents WHERE rate_clearance_id = ?");
        $docStmt->execute([$rateId]);
        $rate['documents'] = $docStmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'data' => $rate]);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Rate not found']);
    }
}

function getRateTypes($pdo) {
    $sql = "SELECT rt.*, 
                   COUNT(rc.id) as active_count,
                   AVG(rc.proposed_rate) as avg_rate
            FROM rate_types rt
            LEFT JOIN rate_clearances rc ON rt.id = rc.rate_type_id AND rc.status = 'approved'
            WHERE rt.is_active = 1
            GROUP BY rt.id
            ORDER BY rt.name";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $rateTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $rateTypes]);
}

function getComplianceStatus($pdo) {
    $sql = "SELECT 
                COUNT(*) as total_rates,
                SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_rates,
                SUM(CASE WHEN status IN ('pending', 'under_review') THEN 1 ELSE 0 END) as pending_rates,
                SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_rates,
                SUM(CASE WHEN expires_at < NOW() THEN 1 ELSE 0 END) as expired_rates,
                SUM(CASE WHEN expires_at BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as expiring_soon,
                AVG(DATEDIFF(approved_at, created_at)) as avg_approval_time
            FROM rate_clearances 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $compliance = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Calculate compliance percentage
    $compliance['compliance_rate'] = $compliance['total_rates'] > 0 
        ? round(($compliance['approved_rates'] / $compliance['total_rates']) * 100, 2)
        : 0;
    
    echo json_encode(['success' => true, 'data' => $compliance]);
}

function approveRate($pdo, $input) {
    $rateId = $input['rate_id'];
    $comments = $input['comments'] ?? '';
    $effectiveDate = $input['effective_date'] ?? date('Y-m-d');
    $userId = $_SESSION['user_id'];
    
    $pdo->beginTransaction();
    
    try {
        // Update rate status
        $stmt = $pdo->prepare("
            UPDATE rate_clearances 
            SET status = 'approved', 
                approved_by = ?, 
                approved_at = NOW(), 
                approval_comments = ?,
                effective_date = ?
            WHERE id = ?
        ");
        $stmt->execute([$userId, $comments, $effectiveDate, $rateId]);
        
        // Log the action
        logRateAction($pdo, $userId, 'rate_approved', "Rate {$rateId} approved", $rateId);
        
        // Create notification for submitter
        createRateNotification($pdo, $rateId, 'approved', $comments);
        
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Rate approved successfully']);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function rejectRate($pdo, $input) {
    $rateId = $input['rate_id'];
    $reason = $input['reason'] ?? '';
    $userId = $_SESSION['user_id'];
    
    $pdo->beginTransaction();
    
    try {
        // Update rate status
        $stmt = $pdo->prepare("
            UPDATE rate_clearances 
            SET status = 'rejected', 
                rejected_by = ?, 
                rejected_at = NOW(), 
                rejection_reason = ?
            WHERE id = ?
        ");
        $stmt->execute([$userId, $reason, $rateId]);
        
        // Log the action
        logRateAction($pdo, $userId, 'rate_rejected', "Rate {$rateId} rejected: {$reason}", $rateId);
        
        // Create notification for submitter
        createRateNotification($pdo, $rateId, 'rejected', $reason);
        
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Rate rejected successfully']);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function submitNewRate($pdo, $input) {
    $userId = $_SESSION['user_id'];
    $rateCode = generateRateCode($pdo);
    
    $pdo->beginTransaction();
    
    try {
        // Insert new rate clearance
        $stmt = $pdo->prepare("
            INSERT INTO rate_clearances 
            (rate_code, rate_type_id, vendor_id, project_id, proposed_rate, current_rate, 
             rate_unit, priority, justification, effective_from, expires_at, submitted_by, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
        ");
        
        $stmt->execute([
            $rateCode,
            $input['rate_type_id'],
            $input['entity_type'] === 'vendor' ? $input['entity_id'] : null,
            $input['entity_type'] === 'project' ? $input['entity_id'] : null,
            $input['proposed_rate'],
            $input['current_rate'] ?? null,
            $input['rate_unit'],
            $input['priority'],
            $input['justification'],
            $input['effective_from'],
            $input['expires_at'] ?? null,
            $userId
        ]);
        
        $rateId = $pdo->lastInsertId();
        
        // Log the action
        logRateAction($pdo, $userId, 'rate_submitted', "New rate {$rateCode} submitted", $rateId);
        
        // Create notification for approvers
        notifyApprovers($pdo, $rateId, $input['priority']);
        
        $pdo->commit();
        echo json_encode([
            'success' => true, 
            'rate_id' => $rateId,
            'rate_code' => $rateCode,
            'message' => 'Rate request submitted successfully'
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function createRateType($pdo, $input) {
    $userId = $_SESSION['user_id'];
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO rate_types 
            (name, description, base_rate, rate_unit, category, created_by, created_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $input['name'],
            $input['description'],
            $input['base_rate'],
            $input['rate_unit'],
            $input['category'],
            $userId
        ]);
        
        $typeId = $pdo->lastInsertId();
        
        // Log the action
        logRateAction($pdo, $userId, 'rate_type_created', "Rate type {$input['name']} created", $typeId);
        
        echo json_encode([
            'success' => true, 
            'type_id' => $typeId,
            'message' => 'Rate type created successfully'
        ]);
        
    } catch (Exception $e) {
        throw $e;
    }
}

function generateReport($pdo, $input) {
    $reportType = $input['report_type'];
    $dateFrom = $input['date_from'] ?? date('Y-m-01');
    $dateTo = $input['date_to'] ?? date('Y-m-t');
    
    switch ($reportType) {
        case 'trend_analysis':
            generateTrendAnalysisReport($pdo, $dateFrom, $dateTo);
            break;
        case 'vendor_comparison':
            generateVendorComparisonReport($pdo, $dateFrom, $dateTo);
            break;
        case 'compliance_summary':
            generateComplianceSummaryReport($pdo, $dateFrom, $dateTo);
            break;
        case 'cost_impact':
            generateCostImpactReport($pdo, $dateFrom, $dateTo);
            break;
        default:
            throw new Exception('Invalid report type');
    }
}

function generateTrendAnalysisReport($pdo, $dateFrom, $dateTo) {
    $sql = "SELECT 
                DATE_FORMAT(created_at, '%Y-%m') as month,
                rt.name as rate_type,
                AVG(proposed_rate) as avg_rate,
                COUNT(*) as rate_count,
                SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_count
            FROM rate_clearances rc
            JOIN rate_types rt ON rc.rate_type_id = rt.id
            WHERE rc.created_at BETWEEN ? AND ?
            GROUP BY month, rt.id
            ORDER BY month, rt.name";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$dateFrom, $dateTo]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'report_type' => 'trend_analysis',
        'data' => $data,
        'generated_at' => date('Y-m-d H:i:s')
    ]);
}

function generateVendorComparisonReport($pdo, $dateFrom, $dateTo) {
    $sql = "SELECT 
                v.name as vendor_name,
                rt.name as rate_type,
                AVG(rc.proposed_rate) as avg_proposed_rate,
                AVG(rc.current_rate) as avg_current_rate,
                COUNT(*) as total_requests,
                SUM(CASE WHEN rc.status = 'approved' THEN 1 ELSE 0 END) as approved_requests
            FROM rate_clearances rc
            JOIN vendors v ON rc.vendor_id = v.id
            JOIN rate_types rt ON rc.rate_type_id = rt.id
            WHERE rc.created_at BETWEEN ? AND ?
            GROUP BY v.id, rt.id
            ORDER BY v.name, rt.name";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$dateFrom, $dateTo]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'report_type' => 'vendor_comparison',
        'data' => $data,
        'generated_at' => date('Y-m-d H:i:s')
    ]);
}

function generateComplianceSummaryReport($pdo, $dateFrom, $dateTo) {
    $sql = "SELECT 
                status,
                priority,
                COUNT(*) as count,
                AVG(DATEDIFF(COALESCE(approved_at, rejected_at, NOW()), created_at)) as avg_processing_days
            FROM rate_clearances
            WHERE created_at BETWEEN ? AND ?
            GROUP BY status, priority
            ORDER BY status, priority";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$dateFrom, $dateTo]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'report_type' => 'compliance_summary',
        'data' => $data,
        'generated_at' => date('Y-m-d H:i:s')
    ]);
}

function generateCostImpactReport($pdo, $dateFrom, $dateTo) {
    $sql = "SELECT 
                rt.name as rate_type,
                SUM(rc.proposed_rate - COALESCE(rc.current_rate, 0)) as total_rate_increase,
                AVG(rc.proposed_rate - COALESCE(rc.current_rate, 0)) as avg_rate_increase,
                COUNT(*) as rate_changes
            FROM rate_clearances rc
            JOIN rate_types rt ON rc.rate_type_id = rt.id
            WHERE rc.created_at BETWEEN ? AND ? AND rc.status = 'approved'
            GROUP BY rt.id
            ORDER BY total_rate_increase DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$dateFrom, $dateTo]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'report_type' => 'cost_impact',
        'data' => $data,
        'generated_at' => date('Y-m-d H:i:s')
    ]);
}

// Helper Functions
function generateRateCode($pdo) {
    $year = date('Y');
    $stmt = $pdo->prepare("SELECT COUNT(*) + 1 as next_number FROM rate_clearances WHERE YEAR(created_at) = ?");
    $stmt->execute([$year]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return sprintf("RC-%s-%03d", $year, $result['next_number']);
}

function logRateAction($pdo, $userId, $action, $description, $relatedId = null) {
    $stmt = $pdo->prepare("
        INSERT INTO audit_log (user_id, action, description, related_id, related_type, created_at) 
        VALUES (?, ?, ?, ?, 'rate_clearance', NOW())
    ");
    $stmt->execute([$userId, $action, $description, $relatedId]);
}

function createRateNotification($pdo, $rateId, $type, $message) {
    // Get rate submitter
    $stmt = $pdo->prepare("SELECT submitted_by FROM rate_clearances WHERE id = ?");
    $stmt->execute([$rateId]);
    $rate = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($rate) {
        $title = $type === 'approved' ? 'Rate Approved' : 'Rate Rejected';
        
        $stmt = $pdo->prepare("
            INSERT INTO notifications (user_id, title, message, type, related_id, related_type, created_at)
            VALUES (?, ?, ?, ?, ?, 'rate_clearance', NOW())
        ");
        $stmt->execute([$rate['submitted_by'], $title, $message, $type === 'approved' ? 'success' : 'error', $rateId]);
    }
}

function notifyApprovers($pdo, $rateId, $priority) {
    // Get users with approval permissions
    $stmt = $pdo->prepare("
        SELECT id FROM users 
        WHERE role IN ('finance_director', 'manager') 
        AND is_active = 1
    ");
    $stmt->execute();
    $approvers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $title = 'New Rate Clearance Request';
    $message = "A new {$priority} priority rate clearance request requires your review.";
    
    foreach ($approvers as $approver) {
        $stmt = $pdo->prepare("
            INSERT INTO notifications (user_id, title, message, type, related_id, related_type, created_at)
            VALUES (?, ?, ?, 'info', ?, 'rate_clearance', NOW())
        ");
        $stmt->execute([$approver['id'], $title, $message, $rateId]);
    }
}

function getExpiringRates($pdo) {
    $sql = "SELECT rc.*, rt.name as rate_type_name, v.name as vendor_name, p.name as project_name
            FROM rate_clearances rc
            LEFT JOIN rate_types rt ON rc.rate_type_id = rt.id
            LEFT JOIN vendors v ON rc.vendor_id = v.id
            LEFT JOIN projects p ON rc.project_id = p.id
            WHERE rc.expires_at BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 30 DAY)
            AND rc.status = 'approved'
            ORDER BY rc.expires_at ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $rates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $rates]);
}

function getExpiredRates($pdo) {
    $sql = "SELECT rc.*, rt.name as rate_type_name, v.name as vendor_name, p.name as project_name
            FROM rate_clearances rc
            LEFT JOIN rate_types rt ON rc.rate_type_id = rt.id
            LEFT JOIN vendors v ON rc.vendor_id = v.id
            LEFT JOIN projects p ON rc.project_id = p.id
            WHERE rc.expires_at < NOW()
            AND rc.status = 'approved'
            ORDER BY rc.expires_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $rates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $rates]);
}

function getRateHistory($pdo, $rateId) {
    $sql = "SELECT al.*, u.username as user_name
            FROM audit_log al
            LEFT JOIN users u ON al.user_id = u.id
            WHERE al.related_id = ? AND al.related_type = 'rate_clearance'
            ORDER BY al.created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$rateId]);
    $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $history]);
}
?>
