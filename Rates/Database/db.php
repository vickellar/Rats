<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', ''); // No password for root user
define('DB_NAME', 'rate_clearance_system');

try {
    // Create PDO connection
    $pdo = new PDO(
    "mysql:host=" . DB_HOST . ";port=3307;dbname=" . DB_NAME,

        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    file_put_contents('../logfile/database_errors.log', date('Y-m-d H:i:s') . " - Database connection failed: " . $e->getMessage() . PHP_EOL, FILE_APPEND);
    die("Database connection failed.");
}
?>
