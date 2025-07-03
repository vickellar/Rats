<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session to access user data
session_start();

// Include database connection
require_once '../Database/db.php';



$response = [
    'notifications' => [],
    'error' => null,
    'debug' => []
];
$userId = $_SESSION['user_id'];

try {
    // Add debug info
    $response['debug'][] = "User ID: " . $_SESSION['user_id'];
    
    
    
    $response['debug'][] = "Database connection OK";
    
    // Query to get bills with property information
    $query = "
    SELECT 
        cb.bill_id,
        cb.account_id,
        cb.application_id,
        cb.total_balance,
        cb.processing_fee,
        cb.overall_total,
        cb.calculated_at,
        p.address as address
    FROM 
        calculated_bills cb
    LEFT JOIN 
        rate_clearance_applications rca ON cb.application_id = rca.application_id
    LEFT JOIN 
        properties p ON rca.property_id = p.property_id
    WHERE 
        cb.user_id = :userId
    ORDER BY 
        cb.calculated DESC
    LIMIT 5";

    $stmt = $pdo->prepare($query);
    $stmt->execute([':userId' => $_SESSION['user_id']]);
    $bills = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $response['debug'][] = "Found " . count($bills) . " bills";

    // If we have bills, process them
    if (count($bills) > 0) {
        foreach ($bills as &$bill) {
            // Format currency values
            $bill['total_balance'] = '$' . number_format($bill['total_balance'], 2);
            $bill['processing_fee'] = '$' . number_format($bill['processing_fee'], 2);
            $bill['overall_total'] = '$' . number_format($bill['overall_total'], 2);
            
            // Format date
            $bill['created_at'] = date('M j, Y', strtotime($bill['created_at']));
            
            // Add view link
            $bill['view_link'] = 'view_bill_details.php?bill_id=' . $bill['bill_id'];
            
            // Get monthly breakdown
            $monthQuery = "
            SELECT 
                month1_name,
                month2_name,
                month3_name,
                month4_name,
                month_balance
            FROM 
                calculated_bill_months
            WHERE 
                bill_id = :billId";
                
            $monthStmt = $pdo->prepare($monthQuery);
            $monthStmt->execute([':billId' => $bill['bill_id']]);
            $months = $monthStmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Format month balances
            foreach ($months as &$month) {
                $month['month_balance'] = '$' . number_format($month['month_balance'], 2);
            }
            
            $bill['months'] = $months;
        }
        
        $response['notifications'] = $bills;
    } else {
        // Try a fallback query if no bills found
        $response['debug'][] = "No bills found, trying fallback query";
        
        $fallbackQuery = "
        SELECT 
            a.application_id,
            CONCAT('APP-', a.application_id) as account_id,
            a.application_id,
            '0.00' as total_balance,
            '0.00' as processing_fee,
            '0.00' as overall_total,
            a.created_at,
            a.created_at as calculated_at,
            p.address as address
        FROM 
            rate_clearance_applications a
        LEFT JOIN 
            properties p ON a.property_id = p.property_id
        WHERE 
            a.user_id = :userId AND a.status = 'approved'
        ORDER BY 
            a.created_at DESC
        LIMIT 5";
        
        $fallbackStmt = $pdo->prepare($fallbackQuery);
        $fallbackStmt->execute([':userId' => $_SESSION['user_id']]);
        $applications = $fallbackStmt->fetchAll(PDO::FETCH_ASSOC);
        
        $response['debug'][] = "Found " . count($applications) . " applications";
        
        if (count($applications) > 0) {
            foreach ($applications as &$app) {
                // Format currency values
                $app['total_balance'] = '$0.00';
                $app['processing_fee'] = '$0.00';
                $app['overall_total'] = '$0.00';
                
                // Format date
                $app['created_at'] = date('M j, Y', strtotime($app['created_at']));
                
                // Add view link
                $app['view_link'] = 'view_bill_details.php?application_id=' . $app['application_id'];
                
                // Empty months array
                $app['months'] = [];
            }
            
            $response['notifications'] = $applications;
        }
    }

    // Log the number of notifications found
    error_log("Found " . count($response['notifications']) . " notifications for user ID: " . $_SESSION['user_id']);

} catch (PDOException $e) {
    // Log the error
    $errorMessage = "[" . date('Y-m-d H:i:s') . "] Database Error: " . $e->getMessage() . "\n";
    $errorMessage .= "User ID: " . $_SESSION['user_id'] . "\n";
    error_log($errorMessage, 3, __DIR__ . '/../logfile/php_error.log');

    $response['error'] = 'Database error: ' . $e->getMessage();
    $response['debug'][] = 'PDO Error: ' . $e->getMessage();
    
} catch (Exception $e) {
    $response['error'] = 'Error: ' . $e->getMessage();
    $response['debug'][] = 'General Error: ' . $e->getMessage();
}

// Return the JSON response
echo json_encode($response);

$conn->close();
?>
