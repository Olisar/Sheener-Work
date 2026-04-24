<?php
/* File: sheener/php/get_quiz_attempts.php */

// Returns all quiz attempts
require_once 'database.php';

header('Content-Type: application/json');

try {
    $pdo = (new Database())->getConnection();
    
    // Check if quiz_attempts table exists, if not return empty array
    $tableCheck = $pdo->query("SHOW TABLES LIKE 'quiz_attempts'");
    if ($tableCheck->rowCount() == 0) {
        // Table doesn't exist, return empty data
        echo json_encode(['success' => true, 'data' => []]);
        exit;
    }
    
    // Get all quiz attempts with document version info
    // Check if doc_version_id column exists, if not, add it
    $columns = $pdo->query("SHOW COLUMNS FROM quiz_attempts LIKE 'doc_version_id'");
    if ($columns->rowCount() == 0) {
        // Add missing columns if they don't exist
        try {
            $pdo->exec("ALTER TABLE quiz_attempts 
                ADD COLUMN doc_version_id INT(11) NULL AFTER quiz_id,
                ADD COLUMN doc_version_number INT(11) NULL AFTER doc_version_id,
                ADD COLUMN attempt_datetime DATETIME NULL AFTER passed,
                ADD KEY idx_doc_version (doc_version_id)");
            
            // Update existing records with doc_version_id from quizzes table
            $pdo->exec("
                UPDATE quiz_attempts qa
                INNER JOIN quizzes q ON qa.quiz_id = q.id
                INNER JOIN documentversions dv ON q.doc_version_id = dv.VersionID
                SET qa.doc_version_id = q.doc_version_id,
                    qa.doc_version_number = dv.VersionNumber,
                    qa.attempt_datetime = COALESCE(qa.pass_datetime, qa.created_at)
                WHERE qa.doc_version_id IS NULL
            ");
        } catch (Exception $e) {
            // Column might already exist or other issue, continue
        }
    }
    
    // Get all quiz attempts
    $stmt = $pdo->prepare('
        SELECT 
            person_id,
            quiz_id,
            COALESCE(doc_version_id, 0) AS doc_version_id,
            doc_version_number,
            score,
            total,
            passed,
            COALESCE(attempt_datetime, created_at, pass_datetime) AS attempt_datetime,
            pass_datetime,
            qr_value,
            created_at
        FROM quiz_attempts
        ORDER BY created_at DESC
    ');
    
    $stmt->execute();
    $attempts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $attempts]);
} catch (Exception $e) {
    // If table doesn't exist or any error, return empty array
    echo json_encode(['success' => true, 'data' => []]);
}

