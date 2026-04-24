// File: lib/Logger.php
<?php
require_once 'database.php';

class Logger {
    public static function logAudit(
        int $userId, 
        string $action, 
        array $details = [], 
        ?string $tableAffected = null
    ): bool {
        try {
            $db = new Database();
            $pdo = $db->getConnection();
            
            $stmt = $pdo->prepare("
                INSERT INTO auditlog 
                (Action, PerformedBy, Details, TableAffected, ActionDetails)
                VALUES (:action, :userId, :details, :table, :actionDetails)
            ");
            
            $stmt->execute([
                ':action' => substr($action, 0, 255),
                ':userId' => $userId,
                ':details' => json_encode($details, JSON_UNESCAPED_UNICODE),
                ':table' => $tableAffected,
                ':actionDetails' => json_encode([
                    'timestamp' => microtime(true),
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? null
                ], JSON_UNESCAPED_SLASHES)
            ]);
            
            return (bool)$pdo->lastInsertId();
        } catch (PDOException $e) {
            error_log("Audit log failure: " . $e->getMessage());
            return false;
        }
    }
}
