<?php
/* File: sheener/php/get_version_attachments.php */

// php/get_version_attachments.php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once __DIR__ . '/database.php';

try {
    $versionId = isset($_GET['version_id']) ? (int)$_GET['version_id'] : 0;
    
    if (!$versionId) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'version_id is required'
        ]);
        exit;
    }
    
    $db = new Database();
    $pdo = $db->getConnection();
    
    // Check if BaseDocumentID column exists (migration status)
    $hasBaseDocumentId = false;
    try {
        $colStmt = $pdo->query("SHOW COLUMNS FROM documents LIKE 'BaseDocumentID'");
        $hasBaseDocumentId = $colStmt->fetch() !== false;
    } catch (Exception $e) {
        $hasBaseDocumentId = false;
    }
    
    // Check which column name exists (versionid or version_id)
    $colStmt = $pdo->query("SHOW COLUMNS FROM attachments LIKE 'versionid'");
    $hasVersionId = $colStmt->fetch();
    
    if (!$hasVersionId) {
        $colStmt2 = $pdo->query("SHOW COLUMNS FROM attachments LIKE 'version_id'");
        $hasVersionId = $colStmt2->fetch();
        $columnName = $hasVersionId ? 'version_id' : null;
    } else {
        $columnName = 'versionid';
    }
    
    if (!$hasVersionId) {
        // Column doesn't exist - return empty array
        echo json_encode(['success' => true, 'data' => []]);
        exit;
    }
    
    // Determine what version_id/versionid in attachments references
    // Old structure: attachments.versionid references documentversions.VersionID
    // New structure: attachments.versionid references documents.DocumentID
    // The versionId parameter could be either, so we need to handle both cases
    
    // In old structure: CurrentVersionID contains documentversions.VersionID
    // In new structure: CurrentVersionID contains documents.DocumentID
    // So we can use the versionId directly in the WHERE clause
    
    $sql = "SELECT attachment_id, file_name, file_type, file_size, file_path, 
                   uploaded_by, uploaded_at, description
            FROM attachments 
            WHERE {$columnName} = ?
            ORDER BY uploaded_at DESC";
    
    error_log("get_version_attachments.php: Querying attachments with {$columnName} = {$versionId}");
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$versionId]);
    
    $attachments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    error_log("get_version_attachments.php: Found " . count($attachments) . " attachments");
    
    // Process attachments
    $processedAttachments = [];
    foreach ($attachments as $att) {
        $attachment = [
            'attachment_id' => $att['attachment_id'] ?? null,
            'file_name' => $att['file_name'] ?? null,
            'filename' => $att['file_name'] ?? null, // Alias for compatibility
            'file_type' => $att['file_type'] ?? null,
            'type' => $att['file_type'] ?? null, // Alias for compatibility
            'mime_type' => $att['file_type'] ?? null, // Alias for compatibility
            'file_size' => $att['file_size'] ?? null,
            'size' => $att['file_size'] ?? null, // Alias for compatibility
            'file_path' => $att['file_path'] ?? null,
            'path' => $att['file_path'] ?? null, // Alias for compatibility
            'uploaded_by' => $att['uploaded_by'] ?? null,
            'uploaded_at' => $att['uploaded_at'] ?? null,
            'uploadedAt' => $att['uploaded_at'] ?? null, // Alias for compatibility
            'created_at' => $att['uploaded_at'] ?? null, // Alias for compatibility
            'createdAt' => $att['uploaded_at'] ?? null, // Alias for compatibility
            'description' => $att['description'] ?? null,
            'desc' => $att['description'] ?? null // Alias for compatibility
        ];
        
        // Build file URL
        if (!empty($attachment['file_path'])) {
            $filePath = $attachment['file_path'];
            // Remove 'sheener/' prefix if present
            if (strpos($filePath, 'sheener/') === 0) {
                $filePath = substr($filePath, 8);
            }
            // Handle both absolute and relative paths
            if (strpos($filePath, 'http') === 0) {
                $attachment['fileUrl'] = $filePath;
                $attachment['file_url'] = $filePath;
            } else {
                $attachment['fileUrl'] = '/' . ltrim($filePath, '/');
                $attachment['file_url'] = '/' . ltrim($filePath, '/');
            }
        }
        
        $processedAttachments[] = $attachment;
    }
    
    echo json_encode(['success' => true, 'data' => $processedAttachments]);
    
} catch (PDOException $e) {
    http_response_code(500);
    error_log("PDO Error in get_version_attachments.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(500);
    error_log("Error in get_version_attachments.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Error: ' . $e->getMessage()
    ]);
}

