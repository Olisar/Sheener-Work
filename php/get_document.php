<?php
/* File: sheener/php/get_document.php */

// php/get_document.php
header('Content-Type: application/json');

// Define project base for URL generation
define('PROJECT_BASE', 'sheener');

// Global error handler to catch ANY fatal error
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== null && $error['type'] === E_ERROR) {
        http_response_code(500);
        error_log("get_document.php FATAL ERROR: " . print_r($error, true));
        echo json_encode(['error' => 'Server error occurred in get_document.php']);
        exit;
    }
});

try {
    // Ensure errors are logged but not displayed
    error_reporting(E_ALL);
    ini_set('display_errors', 0);

    // Use Database class
    require_once __DIR__ . '/database.php';
    
    $db = new Database();
    $pdo = $db->getConnection();

    // Validate ID
    $documentId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    if (!$documentId) {
        http_response_code(400);
        throw new Exception('Invalid document ID parameter');
    }

    // Fetch document with current version
    // Note: LEFT JOIN handles NULL CurrentVersionID gracefully (ON DELETE SET NULL)
    $sqlDoc = "
        SELECT 
            d.DocumentID,
            d.DocCode,
            d.Title,
            d.Description,
            d.StatusID,
            d.DocumentTypeID,
            d.EffectiveDate,
            d.CurrentVersionID,
            s.StatusName,
            dt.Name AS DocumentType,
            dv.VersionID AS CurrentVersionVersionID,
            dv.VersionNumber AS CurrentVersionNumber,
            dv.RevisionLabel AS CurrentRevisionLabel,
            dv.FilePath AS CurrentFilePath,
            dv.OriginalFilename AS CurrentOriginalFilename,
            dv.MimeType AS CurrentMimeType,
            dv.FileSizeBytes AS CurrentFileSizeBytes
        FROM documents d
        LEFT JOIN documentstatuses s ON d.StatusID = s.StatusID
        LEFT JOIN document_types dt ON d.DocumentTypeID = dt.DocumentTypeID
        LEFT JOIN documentversions dv ON d.CurrentVersionID = dv.VersionID
        WHERE d.DocumentID = :docId
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sqlDoc);
    $stmt->execute([':docId' => $documentId]);
    $document = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$document) {
        http_response_code(404);
        throw new Exception('Document not found with ID: ' . $documentId);
    }

    // Fetch all versions
    $sqlVersions = "
        SELECT 
            v.VersionID,
            v.DocumentID,
            v.VersionNumber,
            v.RevisionLabel,
            v.FilePath,
            v.OriginalFilename,
            v.FileSizeBytes,
            v.MimeType,
            v.CreatedBy,
            v.CreatedAt,
            v.ChangeSummary
        FROM documentversions v
        WHERE v.DocumentID = :docId
        ORDER BY v.VersionNumber DESC
    ";

    $stmt = $pdo->prepare($sqlVersions);
    $stmt->execute([':docId' => $documentId]);
    $versions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Build file URLs with project subdirectory
    foreach ($versions as &$v) {
        if (!empty($v['FilePath'])) {
            // Normalize path: remove 'sheener/' prefix if present
            $normalizedPath = $v['FilePath'];
            if (strpos($normalizedPath, 'sheener/') === 0) {
                $normalizedPath = substr($normalizedPath, 8); // Remove 'sheener/' (8 characters)
            }
            // Prepend project base to create correct URL
            $v['fileUrl'] = '/' . PROJECT_BASE . '/' . ltrim($normalizedPath, '/');
        }
    }
    unset($v);
    
    if (!empty($document['CurrentFilePath'])) {
        // Normalize path: remove 'sheener/' prefix if present
        $normalizedPath = $document['CurrentFilePath'];
        if (strpos($normalizedPath, 'sheener/') === 0) {
            $normalizedPath = substr($normalizedPath, 8); // Remove 'sheener/' (8 characters)
        }
        $document['CurrentFileUrl'] = '/' . PROJECT_BASE . '/' . ltrim($normalizedPath, '/');
    }

    // Success response
    echo json_encode([
        'success' => true,
        'document' => $document,
        'versions' => $versions
    ]);

} catch (Exception $e) {
    // Only send error response if headers not sent yet
    if (!headers_sent()) {
        http_response_code(500);
    }
    error_log("get_document.php exception (doc ID: " . ($documentId ?? 'unknown') . "): " . $e->getMessage());
    echo json_encode(['error' => $e->getMessage()]);
}
