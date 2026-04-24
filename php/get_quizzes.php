<?php
// sheener/php/get_quizzes.php
// Returns all active quizzes with their document version info
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ob_start();

require_once 'database.php';

ob_clean();
header('Content-Type: application/json');

try {
    $pdo = (new Database())->getConnection();
    
    // Optional filter by doc_version_id
    $docVersionId = isset($_GET['doc_version_id']) ? intval($_GET['doc_version_id']) : null;
    
    // Get quizzes with their document version information
    // Use LEFT JOIN for documentversions to show quizzes even if version was deleted
    // But filter out quizzes with invalid doc_version_id references
    $sql = '
        SELECT 
            q.id,
            q.title AS name,
            COALESCE(dv.VersionNumber, 0) AS version,
            q.description,
            q.passing_score,
            q.doc_version_id,
            COALESCE(d.Title, q.title) AS document_title,
            d.DocCode AS document_code,
            dv.VersionID AS version_exists
        FROM quizzes q
        LEFT JOIN documentversions dv ON q.doc_version_id = dv.VersionID
        LEFT JOIN documents d ON dv.DocumentID = d.DocumentID
        WHERE q.active = 1';
    
    if ($docVersionId) {
        $sql .= ' AND q.doc_version_id = ?';
    }
    
    $sql .= ' ORDER BY COALESCE(d.Title, q.title), COALESCE(dv.VersionNumber, 0), q.title';
    
    $stmt = $pdo->prepare($sql);
    
    if ($docVersionId) {
        $stmt->execute([$docVersionId]);
    } else {
        $stmt->execute();
    }
    
    $quizzes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Filter out quizzes where the document version doesn't exist (version_exists is NULL)
    // This helps identify orphaned quizzes
    $validQuizzes = array_filter($quizzes, function($q) {
        return $q['version_exists'] !== null;
    });
    
    // Always return success with data array (even if empty)
    echo json_encode(['success' => true, 'data' => array_values($validQuizzes)]);
} catch (PDOException $e) {
    ob_clean();
    http_response_code(500);
    error_log("Get quizzes PDO error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    exit;
} catch (Exception $e) {
    ob_clean();
    http_response_code(500);
    error_log("Get quizzes error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Error: ' . $e->getMessage()]);
    exit;
} catch (Error $e) {
    ob_clean();
    http_response_code(500);
    error_log("Get quizzes fatal error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Fatal error: ' . $e->getMessage()]);
    exit;
}

