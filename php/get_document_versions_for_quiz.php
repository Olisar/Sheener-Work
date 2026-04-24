<?php
/* File: sheener/php/get_document_versions_for_quiz.php */

// Returns document versions for a specific document ID
error_reporting(0);
ini_set('display_errors', 0);
ob_start();

require_once 'database.php';

ob_clean();
header('Content-Type: application/json');

try {
    $pdo = (new Database())->getConnection();
    
    $documentId = isset($_GET['document_id']) ? intval($_GET['document_id']) : null;
    
    if (!$documentId) {
        echo json_encode(['success' => false, 'error' => 'Document ID is required']);
        exit;
    }
    
    // Get all document versions for the specified document
    $stmt = $pdo->prepare('
        SELECT 
            dv.VersionID,
            dv.DocumentID,
            dv.VersionNumber,
            dv.RevisionLabel,
            d.Title AS DocumentTitle,
            d.DocCode AS DocumentCode
        FROM documentversions dv
        INNER JOIN documents d ON dv.DocumentID = d.DocumentID
        WHERE dv.DocumentID = ?
        ORDER BY dv.VersionNumber DESC
    ');
    
    $stmt->execute([$documentId]);
    $versions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $versions]);
} catch (Exception $e) {
    ob_clean();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    exit;
} catch (Error $e) {
    ob_clean();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error: ' . $e->getMessage()]);
    exit;
}

