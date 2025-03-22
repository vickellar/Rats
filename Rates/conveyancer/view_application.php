<?php
session_start();

// Check if user is logged in and has required session data
if (empty($_SESSION['role']) || $_SESSION['role'] !== 'conveyancer' || 
    empty($_SESSION['user_id']) || empty($_SESSION['username'])) {
    header("Location: ../index.php");
    exit();
}

require_once '../Database/db.php';

// Get application ID from URL
$application_id = $_GET['application_id'] ?? null;

if (!$application_id) {
    header("Location: cdashboard.php");
    exit();
}

// Fetch application details
$query = "SELECT application_id, user_id, property_id, applicant_address, email_address, relationship_to_owner, description, title_deed, identity_proof, additional_documents, status, created_at FROM rate_clearance_applications WHERE application_id = :application_id AND user_id = :user_id";

$stmt = $pdo->prepare($query);
$stmt->bindValue(':id', $application_id, PDO::PARAM_INT);
$stmt->bindValue(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
$stmt->execute();
$application = $stmt->fetch();

if (!$application) {
    header("Location: cdashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Application - <?php echo htmlspecialchars($application['application_ref']); ?></title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="dashboard-container">
        <div class="dashboard-nav">
            <a href="cdashboard.php">Back to Dashboard</a>
            <a href="logout.php">Logout</a>
        </div>
        
        <h1>Application Details</h1>
        
        <div class="application-details">
            <h2><?php echo htmlspecialchars($application['application_id']); ?></h2>
            
            <div class="detail-row">
                <span class="label">Status:</span>
                <span class="value"><?php echo htmlspecialchars($application['status']); ?></span>
            </div>
            
            <div class="detail-row">
                <span class="label">Submitted On:</span>
                <span class="value"><?php echo date('M j, Y H:i', strtotime($application['created_at'])); ?></span>
            </div>
            
            <div class="detail-row">
                <span class="label">Property Address:</span>
                <span class="value"><?php echo htmlspecialchars($application['property_address']); ?></span>
                <span class="value"><?php echo htmlspecialchars($application['property_type']); ?></span>
            </div>
            
            <div class="detail-row">
                <span class="label">Property Type:</span>
                <span class="value"><?php echo htmlspecialchars($application['property_type']); ?></span>
            </div>
            
            <div class="detail-row">
                <span class="label">Lot Number:</span>
                <span class="value"><?php echo htmlspecialchars($application['lot_number']); ?></span>
            </div>
            
            <?php if (!empty($application['notes'])): ?>
            <div class="detail-row">
                <span class="label">Notes:</span>
                <span class="value"><?php echo htmlspecialchars($application['notes']); ?></span>
            </div>
            <?php endif; ?>

            <h3>Replies</h3>
            <?php
            $reply_query = "SELECT * FROM application_replies WHERE application_id = :application_id ORDER BY created_at ASC";
            $reply_stmt = $pdo->prepare($reply_query);
            $reply_stmt->bindValue(':application_id', $application['id'], PDO::PARAM_INT);
            $reply_stmt->execute();
            $replies = $reply_stmt->fetchAll();
            
            if (count($replies) > 0): ?>
                <div class="replies-list">
                    <?php foreach ($replies as $reply): ?>
                        <div class="reply">
                            <p><?php echo htmlspecialchars($reply['message']); ?></p>
                            <small>Posted on <?php echo date('M j, Y H:i', strtotime($reply['created_at'])); ?></small>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p>No replies yet.</p>
            <?php endif; ?>

            <h3>Add Reply</h3>
            <form method="POST" action="add_reply.php">
                <input type="hidden" name="application_id" value="<?php echo $application['id']; ?>">
                <textarea name="message" required placeholder="Enter your reply"></textarea>
                <button type="submit">Submit Reply</button>
            </form>

            <h3>Forward Application</h3>
            <form method="POST" action="forward_application.php">
                <input type="hidden" name="application_id" value="<?php echo $application['id']; ?>">
                <label for="to_user_id">Forward To:</label>
                <select name="to_user_id" required>
                    <?php
                    $users_query = "SELECT id, username FROM users WHERE id != :user_id";
                    $users_stmt = $pdo->prepare($users_query);
                    $users_stmt->bindValue(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
                    $users_stmt->execute();
                    $users = $users_stmt->fetchAll();
                    
                    foreach ($users as $user): ?>
                        <option value="<?php echo $user['id']; ?>"><?php echo htmlspecialchars($user['username']); ?></option>
                    <?php endforeach; ?>
                </select>
                <label for="message">Message (optional):</label>
                <textarea name="message" placeholder="Add a message"></textarea>
                <button type="submit">Forward Application</button>
            </form>
        </div>
    </div>
</body>
</html>
