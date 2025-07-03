<?php
session_start();
require_once("../Database/db.php");

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: ../signin.php");
    exit();
}

// Generate CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Fetch properties added by the logged-in user
$username = $_SESSION['username'];
$sql = "SELECT properties.*, GROUP_CONCAT(accounts.account_number) AS account_numbers 
        FROM properties 
        LEFT JOIN accounts ON properties.property_id = accounts.property_id 
        WHERE properties.user_id = :user_id 
        GROUP BY properties.property_id";

$stmt = $pdo->prepare($sql);
$stmt->execute([':user_id' => $_SESSION['user_id']]);
$properties = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Properties</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Include the CSS from the HTML file above -->
    <style>
        /* Copy the entire CSS from the HTML file above */
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2><i class="fas fa-home"></i> Your Properties</h2>
            <p>Manage and view all your registered properties</p>
        </div>
        
        <div class="content">
            <?php if (isset($_GET['success'])): ?>
                <div class="success-message" style="display: block;">
                    <i class="fas fa-check-circle"></i> 
                    <?php 
                    if ($_GET['success'] == 'pdf') echo 'PDF export completed successfully!';
                    elseif ($_GET['success'] == 'excel') echo 'Excel export completed successfully!';
                    ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['error'])): ?>
                <div style="background: #f8d7da; color: #721c24; padding: 12px 20px; border-radius: 6px; margin-bottom: 20px; border: 1px solid #f5c6cb;">
                    <i class="fas fa-exclamation-triangle"></i> 
                    Export failed. Please try again.
                </div>
            <?php endif; ?>

            <div class="toolbar">
                <div class="property-count">
                    <i class="fas fa-building"></i> <?php echo count($properties); ?> Properties Found
                </div>
                
                <div class="export-buttons">
                    <form method="POST" action="export_pdf.php" style="display: inline;">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <button type="submit" class="export-btn pdf">
                            <i class="fas fa-file-pdf"></i>
                            Export PDF
                        </button>
                    </form>
                    <form method="POST" action="export_excel.php" style="display: inline;">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <button type="submit" class="export-btn excel">
                            <i class="fas fa-file-excel"></i>
                            Export Excel
                        </button>
                    </form>
                </div>
            </div>
            
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th><i class="fas fa-user"></i> Owner</th>
                            <th><i class="fas fa-map-marker-alt"></i> Address</th>
                            <th><i class="fas fa-ruler-combined"></i> Size (sq meters)</th>
                            <th><i class="fas fa-tag"></i> Type</th>
                            <th><i class="fas fa-file-invoice"></i> Accounts</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($properties) > 0): ?>
                            <?php foreach ($properties as $property): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($property['owner']); ?></td>
                                    <td><?php echo htmlspecialchars($property['address']); ?></td>
                                    <td><?php echo htmlspecialchars($property['size']); ?></td>
                                    <td><?php echo htmlspecialchars($property['type']); ?></td>
                                    <td><span class="account-numbers"><?php echo htmlspecialchars($property['account_numbers']); ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="no-properties">No properties found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="next-steps">
                <h3><i class="fas fa-info-circle"></i> Next Steps</h3>
                <p>To apply for a rates clearance certificate, select a property from the list above and click the "Apply for Rates Clearance" button below. Make sure all your property information is up to date before proceeding.</p>
            </div>
            
            <div class="button-container">
                <a href="../conveyancer/cdashboard.php" class="btn btn-primary">
                    <i class="fas fa-arrow-left"></i>
                    Back to Dashboard
                </a>
                <a href="../conveyancer/apply_rates.php" class="btn btn-success">
                    <i class="fas fa-file-alt"></i>
                    Apply for Rates Clearance
                </a>
            </div>
        </div>
    </div>

    <script>
        // Show loading state when export buttons are clicked
        document.querySelectorAll('.export-btn').forEach(button => {
            button.addEventListener('click', function() {
                this.disabled = true;
                this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Exporting...';
            });
        });
    </script>
</body>
</html>
