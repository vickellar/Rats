<?php
require_once '../Database/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['application_id'])) {
    $applicationId = $_POST['application_id'];

    $updateQuery = "UPDATE rate_clearance_applications SET status = 'pending' WHERE application_id = ?";
    $stmt = $pdo->prepare($updateQuery);
    $stmt->execute([$applicationId]);

    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false]);
}
?>