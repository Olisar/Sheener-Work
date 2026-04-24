<?php
/* File: sheener/php/get_documentversions.php */

// Returns all document versions
error_reporting(0);
ini_set('display_errors', 0);
ob_start();

require_once 'database.php';

ob_clean();
header('Content-Type: application/json');

try {
    $pdo = (new Database())->getConnection();
    
    // Get all document versions with document info
    $stmt = $pdo->prepare('
        SELECT 
            dv.VersionID,
            dv.DocumentID,
            dv.VersionNumber,
            dv.RevisionLabel,
            dv.FilePath,
            dv.OriginalFilename,
            dv.MimeType,
            dv.FileSizeBytes,
            dv.CreatedAt,
            d.DocCode,
            d.Title AS DocumentTitle
        FROM documentversions dv
        INNER JOIN documents d ON dv.DocumentID = d.DocumentID
        ORDER BY d.DocCode, dv.VersionNumber DESC
    ');
    
    $stmt->execute();
    $versions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Build file URLs
    foreach ($versions as &$v) {
        if (!empty($v['FilePath'])) {
            // Normalize path: remove 'sheener/' prefix if present
            $normalizedPath = $v['FilePath'];
            if (strpos($normalizedPath, 'sheener/') === 0) {
                $normalizedPath = substr($normalizedPath, 8); // Remove 'sheener/' (8 characters)
            }
            // Prepend project base to create correct URL
            $v['fileUrl'] = '/sheener/' . ltrim($normalizedPath, '/');
        }
    }
    unset($v);
    
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

