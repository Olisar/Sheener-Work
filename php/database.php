<?php
/* File: sheener/php/database.php */

// php/database.php
// Suppress errors in database class
@error_reporting(0);
@ini_set('display_errors', 0);

class Database {
    private $pdo;

    public function __construct() {
        // Database connection variables
        $host = 'localhost'; // Database host
        $db   = 'sheener'; // Database name
        $user = 'root'; // Database username
        $pass = ''; // Database password
        $charset = 'utf8mb4'; // Character encoding

        // Data Source Name (DSN)
        $dsn = "mysql:host=$host;dbname=$db;charset=$charset";

        // PDO options
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Enable exceptions for errors
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // Set default fetch mode to associative array
            PDO::ATTR_EMULATE_PREPARES   => false, // Disable emulated prepared statements
        ];

        try {
            $this->pdo = new PDO($dsn, $user, $pass, $options);
        } catch (PDOException $e) {
            // Log the error but don't throw - let calling code handle it
            error_log("Database connection failed: " . $e->getMessage());
            throw new Exception("Database configuration error"); // Throw generic message for security
        }
    }

    public function getConnection() {
        if (!$this->pdo) {
            throw new Exception("Database connection not available");
        }
        return $this->pdo;
    }

    /**
     * Log an action to the auditlog table
     */
    public function logAudit($pdo, $action, $table, $id, $details, $actionDetails = null) {
        try {
            $query = "INSERT INTO auditlog (Action, PerformedBy, PerformedAt, TableAffected, Details, ActionDetails) 
                      VALUES (?, ?, NOW(), ?, ?, ?)";
            $stmt = $pdo->prepare($query);
            $userId = (int)($_SESSION['user_id'] ?? 0);
            
            // Ensure no null values are passed to internal execute if strings are expected
            $stmt->execute([
                (string)$action,
                $userId,
                (string)$table,
                (string)$details,
                $actionDetails ? (string)json_encode($actionDetails) : null
            ]);
            return true;
        } catch (Exception $e) {
            error_log("Audit log error in Database::logAudit: " . $e->getMessage());
            return false;
        }
    }
}
?>
