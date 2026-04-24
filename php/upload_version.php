<?php
/* File: sheener/php/upload_version.php */

// php/upload_version.php

// STEP 1: Enable error logging to a file (critical for debugging on Windows)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/upload_errors.log'); // Log to this file

// Log the start of request
error_log("=== UPLOAD REQUEST STARTED ===");
error_log("POST data: " . print_r($_POST, true));
error_log("FILES data: " . print_r($_FILES, true));

// Global error handler
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== null && $error['type'] === E_ERROR) {
        http_response_code(500);
        error_log("FATAL ERROR: " . print_r($error, true));
        if (!headers_sent()) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Server crash: ' . $error['message']]);
        }
        exit;
    }
});

try {
    header('Content-Type: application/json');

    // Validate request
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        throw new Exception('POST method required');
    }

    // Database connection
    require_once __DIR__ . '/database.php';
    $db = new Database();
    $pdo = $db->getConnection();
    error_log("Database connection successful");

    // Check if BaseDocumentID column exists (migration status)
    $hasBaseDocumentId = false;
    try {
        $colStmt = $pdo->query("SHOW COLUMNS FROM documents LIKE 'BaseDocumentID'");
        $hasBaseDocumentId = $colStmt->fetch() !== false;
    } catch (Exception $e) {
        $hasBaseDocumentId = false;
    }
    error_log("Database structure: " . ($hasBaseDocumentId ? "New (BaseDocumentID)" : "Old (documentversions)"));

     // Validate input
     $documentId = filter_input(INPUT_POST, 'document_id', FILTER_VALIDATE_INT);
     $changeSummary = trim($_POST['change_summary'] ?? '');
     $revisionLabel = trim($_POST['revision_label'] ?? '');
     
     // Get a valid CreatedBy user ID (use first active person or default to 1)
     $stmt = $pdo->query("SELECT people_id FROM people WHERE IsActive = 1 LIMIT 1");
     $createdBy = $stmt->fetchColumn() ?: 1;
     error_log("Using CreatedBy: $createdBy");

    if (!$documentId) {
        http_response_code(400);
        throw new Exception('Invalid or missing document_id');
    }
    error_log("Document ID: $documentId");

    // VERIFY DOCUMENT EXISTS (prevents foreign key error)
    $stmt = $pdo->prepare("SELECT DocumentID FROM documents WHERE DocumentID = ?");
    $stmt->execute([$documentId]);
    if (!$stmt->fetch()) {
        http_response_code(404);
        throw new Exception("Document ID $documentId does not exist in documents table");
    }
    error_log("Document exists in database");

    // Validate file upload
    if (!isset($_FILES['file'])) {
        http_response_code(400);
        throw new Exception('No file uploaded');
    }

    $uploadedFile = $_FILES['file'];
    if ($uploadedFile['error'] !== UPLOAD_ERR_OK) {
        $errorMsg = match($uploadedFile['error']) {
            UPLOAD_ERR_INI_SIZE => 'File too large (exceeds php.ini limit of ' . ini_get('upload_max_filesize') . ')',
            UPLOAD_ERR_FORM_SIZE => 'File too large (exceeds form limit)',
            UPLOAD_ERR_PARTIAL => 'File only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder on server',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the upload',
            default => 'Unknown upload error code: ' . $uploadedFile['error']
        };
        throw new Exception($errorMsg);
    }
    error_log("File upload validated: " . $uploadedFile['name']);

    // File details
    $origName = $uploadedFile['name'];
    $tmpPath = $uploadedFile['tmp_name'];
    $fileSize = (int)$uploadedFile['size'];
    $mimeType = mime_content_type($tmpPath) ?: 'application/octet-stream';
    error_log("File details - Name: $origName, Size: $fileSize, Type: $mimeType");

    // Get base document info
    if ($hasBaseDocumentId) {
        // New structure: check for base document (BaseDocumentID IS NULL)
        $stmt = $pdo->prepare("SELECT DocumentID, DocCode, Title, Description, OwnerUserID, DepartmentID, StatusID, IsControlled, DocumentTypeID FROM documents WHERE DocumentID = ? AND BaseDocumentID IS NULL");
        $stmt->execute([$documentId]);
        $baseDoc = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$baseDoc) {
            http_response_code(404);
            throw new Exception("Base document not found. Document ID $documentId is not a base document.");
        }
        
        // Calculate next version number (from documents table with BaseDocumentID)
        $stmt = $pdo->prepare("SELECT COALESCE(MAX(VersionNumber), 0) + 1 FROM documents WHERE BaseDocumentID = ?");
        $stmt->execute([$documentId]);
        $nextVersion = (int)$stmt->fetchColumn();
    } else {
        // Old structure: just get document info (no BaseDocumentID check)
        $stmt = $pdo->prepare("SELECT DocumentID, DocCode, Title, Description, OwnerUserID, DepartmentID, StatusID, IsControlled, DocumentTypeID FROM documents WHERE DocumentID = ?");
        $stmt->execute([$documentId]);
        $baseDoc = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$baseDoc) {
            http_response_code(404);
            throw new Exception("Document ID $documentId not found.");
        }
        
        // Calculate next version number (from documentversions table)
        $stmt = $pdo->prepare("SELECT COALESCE(MAX(VersionNumber), 0) + 1 FROM documentversions WHERE DocumentID = ?");
        $stmt->execute([$documentId]);
        $nextVersion = (int)$stmt->fetchColumn();
    }
    error_log("Next version number: $nextVersion");

    // WINDOWS-SPECIFIC: Get correct upload path
    // For XAMPP on Windows, use this approach:
    $webRoot = realpath(__DIR__ . DIRECTORY_SEPARATOR . '..');
    if (!$webRoot) {
        throw new Exception('Cannot determine web root directory from: ' . __DIR__);
    }
    error_log("Web root: $webRoot");

    // Create uploads/docs/{DocumentID} directory
    $targetDir = $webRoot . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . $documentId;
    error_log("Target directory: $targetDir");

    // Ensure parent directories exist
    $parentDir = dirname($targetDir);
    if (!is_dir($parentDir)) {
        error_log("Parent directory does not exist, creating: $parentDir");
        if (!@mkdir($parentDir, 0777, true)) {
            $error = error_get_last();
            throw new Exception('Failed to create parent directory: ' . $parentDir . ' - ' . ($error['message'] ?? 'Unknown error'));
        }
    }

    if (!is_dir($targetDir)) {
        error_log("Directory does not exist, creating: $targetDir");
        if (!@mkdir($targetDir, 0777, true)) {
            $error = error_get_last();
            throw new Exception('Failed to create directory: ' . $targetDir . ' - ' . ($error['message'] ?? 'Unknown error'));
        }
        // On Windows, try to set permissions explicitly (may not work but worth trying)
        @chmod($targetDir, 0777);
    }

    // Verify directory is writable by attempting to write a test file
    // is_writable() can be unreliable on Windows, so we test with actual file write
    $testFile = $targetDir . DIRECTORY_SEPARATOR . '.write_test_' . time() . '.tmp';
    $testWrite = @file_put_contents($testFile, 'test');
    if ($testWrite === false) {
        // Check if directory exists
        if (!is_dir($targetDir)) {
            throw new Exception('Directory does not exist: ' . $targetDir);
        }
        // Check parent directory permissions
        $parentWritable = is_writable($parentDir);
        throw new Exception('Directory is NOT writable: ' . $targetDir . 
            ' - Parent directory writable: ' . ($parentWritable ? 'Yes' : 'No') . 
            ' - Please check Windows folder permissions. Right-click the folder, Properties > Security, and ensure the web server user has Write permissions.');
    }
    // Clean up test file
    @unlink($testFile);
    error_log("Directory is writable (verified by test write)");

    // Generate filename
    $ext = pathinfo($origName, PATHINFO_EXTENSION);
    $serverName = 'doc' . $documentId . '_v' . $nextVersion . ($ext ? '.' . $ext : '');
    $targetPath = $targetDir . DIRECTORY_SEPARATOR . $serverName;
    error_log("Target path: $targetPath");

    // Move file
    if (!move_uploaded_file($tmpPath, $targetPath)) {
        throw new Exception('move_uploaded_file() FAILED - Check: 1) Directory permissions, 2) Disk space, 3) Antivirus blocking');
    }
    error_log("File moved successfully");

    $dbFilePath = 'uploads/docs/' . $documentId . '/' . $serverName;

    // Insert new version
    error_log("Inserting new version...");
    $checksum = hash_file('sha256', $targetPath);
    
    if ($hasBaseDocumentId) {
        // New structure: insert into documents table as a version row
        $stmt = $pdo->prepare("
            INSERT INTO documents 
            (BaseDocumentID, VersionNumber, RevisionLabel, FilePath, OriginalFilename, FileSizeBytes, 
             MimeType, Checksum, ChangeSummary, EffectiveDate, UploadedBy,
             DocCode, Title, Description, OwnerUserID, DepartmentID, StatusID, IsControlled, DocumentTypeID)
            VALUES 
            (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $documentId,  // BaseDocumentID - links to base document
            $nextVersion,
            $revisionLabel ?: null,
            $dbFilePath,
            $origName,
            $fileSize,
            $mimeType,
            $checksum,
            $changeSummary ?: null,
            $baseDoc['EffectiveDate'] ?? null,
            $createdBy,
            $baseDoc['DocCode'],  // Copy from base document
            $baseDoc['Title'],    // Copy from base document
            $baseDoc['Description'],  // Copy from base document
            $baseDoc['OwnerUserID'],
            $baseDoc['DepartmentID'],
            $baseDoc['StatusID'],
            $baseDoc['IsControlled'],
            $baseDoc['DocumentTypeID']
        ]);

        $newVersionId = (int)$pdo->lastInsertId();
        error_log("Database insert successful, new DocumentID (version): $newVersionId");

        // Update base document's CurrentVersionID to point to this new version
        $stmt = $pdo->prepare("UPDATE documents SET CurrentVersionID = ? WHERE DocumentID = ?");
        $stmt->execute([$newVersionId, $documentId]);
        error_log("Base document updated with CurrentVersionID: $newVersionId");
    } else {
        // Old structure: insert into documentversions table
        // Check which columns exist in documentversions
        $columns = $pdo->query("SHOW COLUMNS FROM documentversions")->fetchAll(PDO::FETCH_COLUMN);
        $hasExtendedColumns = in_array('OriginalFilename', $columns) && 
                             in_array('FileSizeBytes', $columns) &&
                             in_array('MimeType', $columns) &&
                             in_array('Checksum', $columns) &&
                             in_array('ChangeSummary', $columns) &&
                             in_array('EffectiveDate', $columns);
        
        if ($hasExtendedColumns) {
            $stmt = $pdo->prepare("
                INSERT INTO documentversions 
                (DocumentID, VersionNumber, RevisionLabel, FilePath, OriginalFilename, FileSizeBytes, 
                 MimeType, Checksum, CreatedBy, ChangeSummary, EffectiveDate)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $documentId,
                $nextVersion,
                $revisionLabel ?: null,
                $dbFilePath,
                $origName,
                $fileSize,
                $mimeType,
                $checksum,
                $createdBy,
                $changeSummary ?: null,
                $baseDoc['EffectiveDate'] ?? null
            ]);
        } else {
            // Minimal columns
            $stmt = $pdo->prepare("
                INSERT INTO documentversions 
                (DocumentID, VersionNumber, FilePath, CreatedBy)
                VALUES (?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $documentId,
                $nextVersion,
                $dbFilePath,
                $createdBy
            ]);
        }

        $newVersionId = (int)$pdo->lastInsertId();
        error_log("Database insert successful, new VersionID: $newVersionId");

        // Update base document's CurrentVersionID to point to this new version
        // Note: CurrentVersionID references documentversions.VersionID in old structure
        $stmt = $pdo->prepare("UPDATE documents SET CurrentVersionID = ? WHERE DocumentID = ?");
        $stmt->execute([$newVersionId, $documentId]);
        error_log("Base document updated with CurrentVersionID: $newVersionId");
    }

    // SUCCESS
    echo json_encode([
        'success' => true,
        'document_id' => $documentId,
        'version_id' => $newVersionId,
        'version_number' => $nextVersion,
        'file_path' => $dbFilePath
    ]);
    error_log("=== UPLOAD COMPLETED SUCCESSFULLY ===");

} catch (PDOException $e) {
    error_log("PDO EXCEPTION: " . $e->getMessage() . " | SQL: " . $e->getTrace()[0]['args'][0] ?? '');
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log("APPLICATION ERROR: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
