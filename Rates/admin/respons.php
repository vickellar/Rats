<?php
session_start();
//include 'db.php'; // Include your database connection

// Check if the admin is logged in
if ($_SESSION['role'] !== 'admin') {
    header("Location: unauthorized.php");
    exit();
}

// Get the application ID from the URL
$application_id = $_GET['id'];

// Fetch application details
$stmt = $pdo->prepare("SELECT * FROM applications WHERE id = ?");
$stmt->execute([$application_id]);
$application = $stmt->fetch();

if (!$application) {
    echo "Application not found.";
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <title>Admin Response</title>
</head>
<body>
    <div class="container">
        <h1>Application Details</h1>
        <p><strong>House Details:</strong> <?php echo htmlspecialchars($application['house_details']); ?></p>
        
        <h2>Upload Response</h2>
        <form action="submit_response.php" method="post" enctype="multipart/form-data">
            <input type="hidden" name="application_id" value="<?php echo $application_id; ?>">
            <div class="form-group">
                <label for="response_file">Upload PDF Response:</label>
                <input type="file" class="form-control-file" name="response_file" accept=".pdf" required>
            </div>
            <button type="submit" class="btn btn-primary">Submit Response</button>
        </form>
    </div>
</body>
</html>