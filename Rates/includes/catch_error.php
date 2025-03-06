
<?php
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
    } catch (PDOException $e) {
        
        error_log("Database error: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine(), 3, "../logfile/database_errors.log");
        

        die("An error occurred. Please try again later.");
    }

    // Include the error handler class
    require_once("../includes/error_handler.php");
    ErrorHandler::initialize();
?>