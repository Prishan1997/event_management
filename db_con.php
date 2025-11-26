<?php
    // db_initialize.php - Defines reusable connection functions
    // Database Connection Credentials
    $host    = 'localhost';
    $user    = 'root';
    $pass    = '';
    $charset = 'utf8mb4';

    // Data Source Name (DSN)
    $dsn = "mysql:host=$host;charset=$charset";
    
    // PDO options
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    /**
     * Creates and returns a new PDO database connection object.
     * @return PDO The active database connection object.
     * @throws PDOException if connection fails.
     */
    function open_db_connection() {
        global $dsn, $user, $pass, $options;
        try {
            $pdo = new PDO($dsn, $user, $pass, $options);
            return $pdo;
        } catch (PDOException $e) {
            // Log error and handle gracefully
            error_log("Database connection failed: " . $e->getMessage());
            die("A required service is unavailable."); 
        }
    }

    /**
     * Explicitly clears the PDO connection variable.
     * @param PDO|null $pdo The connection object to close.
     */
    function close_db_connection(&$pdo) {
        // Clearing the variable disconnects the PDO object from the underlying resource
        $pdo = null; 
    }
?>