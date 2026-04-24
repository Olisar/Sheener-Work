<?php
/* File: sheener/php/upload_version_attachment.php */

// upload_version_attachment.php

// Basic error reporting to log file only
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/upload_errors.log');

// Helper to send JSON and exit
function json_response($data, int $statusCode = 200): void
{
    if (!headers_sent()) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
    }
    echo json_encode($data);
    exit;
}

// Log request metadata
error_log('UPLOAD VERSION ATTACHMENT REQUEST at ' . date('Y-m-d H:i:s'));
error_log('POST: ' . print_r($_POST, true));
error_log('FILES: ' . print_r($_FILES, true));
error_log('REQUEST_METHOD: ' . ($_SERVER['REQUEST_METHOD'] ?? 'NA'));
error_log('REQUEST_URI: ' . ($_SERVER['REQUEST_URI'] ?? 'NA'));

try {
    // Method check
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        json_response(['success' => false, 'error' => 'Invalid request method'], 405);
    }

    // Session (for uploadedBy)
    try {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    } catch (Exception $e) {
        error_log('Session start error: ' . $e->getMessage());
    }

    // Database bootstrap
    $dbFile = __DIR__ . '/database.php';
    if (!file_exists($dbFile)) {
        error_log('database.php not found at: ' . $dbFile);
        json_response(['success' => false, 'error' => 'Database configuration missing'], 500);
    }

    require_once $dbFile;

    if (!class_exists('Database')) {
        json_response(['success' => false, 'error' => 'Database class not found'], 500);
    }

    $db = new Database();
    $pdo = $db->getConnection();

    // versionid is required
    $versionId = isset($_POST['versionid']) ? (int)$_POST['versionid'] : null;
    if (!$versionId) {
        json_response(['success' => false, 'error' => 'versionid is required'], 400);
    }

    // Detect if BaseDocumentID exists (new structure)
    $hasBaseDocumentId = false;
    try {
        $colStmt = $pdo->query("SHOW COLUMNS FROM documents LIKE 'BaseDocumentID'");
        $hasBaseDocumentId = ($colStmt->fetch() !== false);
    } catch (Exception $e) {
        $hasBaseDocumentId = false;
        error_log('Error checking BaseDocumentID column: ' . $e->getMessage());
    }

    // Resolve document / version relationship
    $documentExists   = false;
    $actualDocumentId = null;

    if ($hasBaseDocumentId) {
        // New structure: versionid is actually DocumentID in documents table
        $stmt = $pdo->prepare("SELECT DocumentID FROM documents WHERE DocumentID = ?");
        $stmt->execute([$versionId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            $documentExists   = true;
            $actualDocumentId = (int)$result['DocumentID'];
        }
    } else {
        // Old structure: versionid is VersionID in documentversions table
        $stmt = $pdo->prepare("SELECT DocumentID FROM documentversions WHERE VersionID = ?");
        $stmt->execute([$versionId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            $documentExists   = true;
            $actualDocumentId = (int)$result['DocumentID'];
        }
    }

    if (!$documentExists || !$actualDocumentId) {
        error_log('Document/Version not found for versionid=' . $versionId . ', hasBaseDocumentId=' . ($hasBaseDocumentId ? 'true' : 'false'));
        json_response(['success' => false, 'error' => 'Document/Version not found'], 404);
    }

    // Ensure attachments.versionid column exists (dual-use)
    try {
        $colStmt = $pdo->query("SHOW COLUMNS FROM attachments LIKE 'versionid'");
        $columnExists = ($colStmt->fetch() !== false);

        if (!$columnExists) {
            // Try version_id as fallback
            $colStmt2 = $pdo->query("SHOW COLUMNS FROM attachments LIKE 'version_id'");
            $columnExists2 = ($colStmt2->fetch() !== false);
            
            if (!$columnExists2) {
                $pdo->exec("ALTER TABLE attachments ADD COLUMN versionid INT(11) DEFAULT NULL");
                $pdo->exec("ALTER TABLE attachments ADD INDEX idx_versionid (versionid)");
                error_log('Added versionid column to attachments table');
            } else {
                // version_id exists, we'll use that
                error_log('Using existing version_id column');
            }
        }
    } catch (PDOException $e) {
        error_log('Error ensuring versionid column on attachments: ' . $e->getMessage());
        json_response([
            'success' => false,
            'error'   => 'attachments.versionid column does not exist and could not be created: ' . $e->getMessage()
        ], 500);
    }

    // Handle uploaded file
    $uploadedFile = null;
    if (!empty($_FILES['attachment']['name'])) {
        $uploadedFile = $_FILES['attachment'];
    } elseif (!empty($_FILES['file']['name'])) {
        // Backward compatibility
        $uploadedFile = $_FILES['file'];
    }

    if (!$uploadedFile) {
        error_log('No file found in $_FILES. Keys: ' . implode(', ', array_keys($_FILES)));
        json_response(['success' => false, 'error' => 'No file uploaded'], 400);
    }

    if (!isset($uploadedFile['error']) || $uploadedFile['error'] !== UPLOAD_ERR_OK) {
        $errCode = $uploadedFile['error'] ?? -1;
        json_response(['success' => false, 'error' => 'File upload error code: ' . $errCode], 400);
    }

    // Prepare upload directory
    $uploadDir = __DIR__ . '/uploads';
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0777, true) && !is_dir($uploadDir)) {
            json_response(['success' => false, 'error' => 'Failed to create upload directory'], 500);
        }
    }

    $originalName = $uploadedFile['name'];
    $safeName     = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9\.\-]/', '_', $originalName);
    $targetPath   = $uploadDir . '/' . $safeName;

    if (!move_uploaded_file($uploadedFile['tmp_name'], $targetPath)) {
        json_response(['success' => false, 'error' => 'Failed to move uploaded file'], 500);
    }

    // Relative path for front-end (adjust if needed)
    $relativePath = 'php/uploads/' . $safeName;

    $uploadedBy   = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : (isset($_SESSION['userid']) ? (int)$_SESSION['userid'] : 1);
    $description  = isset($_POST['description']) ? trim((string)$_POST['description']) : null;

    // For attachments.versionid:
    // - New structure: store DocumentID
    // - Old structure: store VersionID
    $versionIdForAttachment = $hasBaseDocumentId ? $actualDocumentId : $versionId;

    // Check which column name exists (versionid or version_id)
    $colStmt = $pdo->query("SHOW COLUMNS FROM attachments LIKE 'versionid'");
    $hasVersionId = ($colStmt->fetch() !== false);
    
    if (!$hasVersionId) {
        $colStmt2 = $pdo->query("SHOW COLUMNS FROM attachments LIKE 'version_id'");
        $hasVersionId = ($colStmt2->fetch() !== false);
        $columnName = $hasVersionId ? 'version_id' : 'versionid';
    } else {
        $columnName = 'versionid';
    }

    // Check which field names exist in attachments table
    $colStmt = $pdo->query("SHOW COLUMNS FROM attachments");
    $columns = [];
    while ($col = $colStmt->fetch(PDO::FETCH_ASSOC)) {
        $columns[] = $col['Field'];
    }

    // Use appropriate field names based on what exists
    $fileField = in_array('file_name', $columns) ? 'file_name' : (in_array('filename', $columns) ? 'filename' : 'file_name');
    $typeField = in_array('file_type', $columns) ? 'file_type' : (in_array('filetype', $columns) ? 'filetype' : 'file_type');
    $sizeField = in_array('file_size', $columns) ? 'file_size' : (in_array('filesize', $columns) ? 'filesize' : 'file_size');
    $pathField = in_array('file_path', $columns) ? 'file_path' : (in_array('filepath', $columns) ? 'filepath' : 'file_path');
    $byField = in_array('uploaded_by', $columns) ? 'uploaded_by' : (in_array('uploadedby', $columns) ? 'uploadedby' : 'uploaded_by');

    $stmt = $pdo->prepare("
        INSERT INTO attachments ({$columnName}, {$fileField}, {$typeField}, {$sizeField}, {$pathField}, {$byField}, description)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $versionIdForAttachment,
        $originalName,
        $uploadedFile['type'] ?? null,
        $uploadedFile['size'] ?? null,
        $relativePath,
        $uploadedBy,
        $description
    ]);

    $attachmentId = (int)$pdo->lastInsertId();

    error_log('Attachment inserted with id=' . $attachmentId . ', versionid=' . $versionIdForAttachment);

    json_response([
        'success'      => true,
        'attachment_id' => $attachmentId,
        'file_path'     => $relativePath,
        'message'      => 'Attachment uploaded successfully'
    ], 200);

} catch (Throwable $e) {
    error_log('FATAL in upload_version_attachment.php: ' . $e->getMessage() . ' | Trace: ' . $e->getTraceAsString());
    json_response([
        'success' => false,
        'error'   => 'Server error: ' . $e->getMessage()
    ], 500);
}
