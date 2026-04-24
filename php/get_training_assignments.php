<?php
/* File: sheener/php/get_training_assignments.php */

// Returns all training assignments with person and document information
error_reporting(0);
ini_set('display_errors', 0);
ob_start();

require_once 'database.php';

ob_clean();
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: http://172.21.10.99");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

try {
    $pdo = (new Database())->getConnection();
    
    // Get all training assignments with person and document information
    $stmt = $pdo->prepare('
        SELECT 
            ta.id,
            ta.person_id,
            ta.doc_version_id,
            ta.assigned_by,
            ta.assigned_date,
            ta.due_date,
            ta.status,
            ta.completion_date,
            ta.attempts_allowed,
            ta.passing_score,
            CONCAT(p.FirstName, " ", p.LastName) AS person_name,
            p.Position AS person_position,
            dv.VersionID,
            dv.RevisionLabel,
            dv.OriginalFilename,
            dv.FilePath,
            d.Title AS DocumentTitle
        FROM training_assignments ta
        INNER JOIN people p ON ta.person_id = p.people_id
        INNER JOIN documentversions dv ON ta.doc_version_id = dv.VersionID
        INNER JOIN documents d ON dv.DocumentID = d.DocumentID
        ORDER BY ta.assigned_date DESC
    ');
    
    $stmt->execute();
    $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $assignments]);
} catch (PDOException $e) {
    ob_clean();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    exit;
} catch (Exception $e) {
    ob_clean();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error: ' . $e->getMessage()]);
    exit;
}

?>

