<?php

// erro handler for pdf uploads
class ErrorHandler {
    private static $logFile = __DIR__ . '/../logfile/application_errors.log'; // Adjust path as necessary
    private static $dbLogFile = __DIR__ . '/../logfile/database_errors.log'; // New log file for database errors




    public static function handleError($errno, $errstr, $errfile, $errline) {
        $errorMsg = "[".date('Y-m-d H:i:s')."] Error: $errstr in $errfile on line $errline\n";
        error_log($errorMsg, 3, self::$logFile);
        return true; // Log database connection errors
    }

    public static function logDatabaseError($message, $query = null) {
        $errorMsg = "[".date('Y-m-d H:i:s')."] Database Error: $message";
        if ($query) {
            $errorMsg .= " | Query: $query";
        }
        $errorMsg .= "\n";
        error_log($errorMsg, 3, self::$dbLogFile);

    }
    
    public static function handleException($exception) {
        $errorMsg = "[".date('Y-m-d H:i:s')."] Exception: ".$exception->getMessage()." in ".$exception->getFile()." on line ".$exception->getLine()."\n"; // Log exception details
        self::logDatabaseError($exception->getMessage()); // Log the exception as a database error

        error_log($errorMsg, 3, self::$logFile);
        
        // Show user-friendly error message
        http_response_code(500);
        echo "An error occurred. Please try again later.";
        exit;
    }
    
    public static function initialize() {
        set_error_handler([self::class, 'handleError']);
        set_exception_handler([self::class, 'handleException']);
    }
}
?>
