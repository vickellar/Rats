<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

require_once('../Database/db.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['property_id'])) {
    $property_id = intval($_POST['property_id']);
    $user_id = $_SESSION['user_id'];
    
    try {
        // Verify property belongs to user
        $verify_query = "SELECT COUNT(*) as count FROM properties WHERE property_id = :property_id AND user_id = :user_id";
        $stmt_verify = $pdo->prepare($verify_query);
        $stmt_verify->execute([':property_id' => $property_id, ':user_id' => $user_id]);
        $verification = $stmt_verify->fetch(PDO::FETCH_ASSOC);
        
        if ($verification['count'] == 0) {
            echo json_encode(['success' => false, 'message' => 'Property not found']);
            exit();
        }
        
        // Get payment requirements
        $query = "SELECT 
                    COALESCE(SUM(total_balance), 0) as total_balance_sum,
                    COALESCE(SUM(processing_fee), 0) as processing_fee_sum,
                    COALESCE(SUM(overall_total), 0) as overall_total_sum
                  FROM calculated_bills 
                  WHERE property_id = :property_id";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([':property_id' => $property_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            echo json_encode([
                'success' => true,
                'total_balance' => $result['total_balance_sum'],
                'processing_fee' => $result['processing_fee_sum'],
                'overall_total' => $result['overall_total_sum'],
                'minimum_amount' => $result['overall_total_sum']
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'No calculated bills found'
            ]);
        }
        
    } catch (PDOException $e) {
        error_log("Error fetching payment requirements: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>
