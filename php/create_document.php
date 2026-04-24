<?php
/* File: sheener/php/create_document.php */

// php/create_document.php
// Creates a document in the documents table (stable properties only)
// If a file is provided, automatically creates the first version in documentversions table
header('Content-Type: application/json');
session_start();

require_once __DIR__ . '/database.php';

try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    // Handle both JSON (from form) and FormData (with file)
    $input = [];
    $hasFile = false;
    $uploadedFile = null;
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Check if it's a file upload (FormData)
        if (!empty($_FILES['document_file']['name'])) {
            $hasFile = true;
            $uploadedFile = $_FILES['document_file'];
            // Get other fields from POST
            $input = [
                'DocCode' => $_POST['doc_code'] ?? null,
                'Title' => $_POST['title'] ?? null,
                'Description' => $_POST['description'] ?? null,
                'EffectiveDate' => $_POST['effective_date'] ?? null,
                'OwnerUserID' => !empty($_POST['owner_user_id']) ? (int)$_POST['owner_user_id'] : null,
                'DepartmentID' => !empty($_POST['department_id']) ? (int)$_POST['department_id'] : 1,
                'StatusID' => !empty($_POST['status_id']) ? (int)$_POST['status_id'] : 1,
                'IsControlled' => !empty($_POST['is_controlled']) ? (int)$_POST['is_controlled'] : 1
            ];
        } else {
            // JSON input (no file)
            $rawInput = file_get_contents('php://input');
            $input = json_decode($rawInput, true);
            if (!$input) {
                throw new Exception('Invalid JSON input');
            }
        }
    } else {
        throw new Exception('POST method required');
    }
    
    // Validate required fields
    if (empty($input['Title'])) {
        throw new Exception('Title is required');
    }
    
    // Get user ID from session or default
    $uploadedBy = $_SESSION['user_id'] ?? 1;
    
    // Check if BaseDocumentID column exists (migration status)
    $hasBaseDocumentId = false;
    try {
        $colStmt = $pdo->query("SHOW COLUMNS FROM documents LIKE 'BaseDocumentID'");
        $hasBaseDocumentId = $colStmt->fetch() !== false;
    } catch (Exception $e) {
        $hasBaseDocumentId = false;
    }
    
    // Start transaction
    $pdo->beginTransaction();
    
    // STEP 1: Insert document (structure depends on migration status)
    // If file is provided, include file details; otherwise FilePath is empty
    $filePath = '';
    $originalFilename = null;
    $fileSizeBytes = null;
    $mimeType = null;
    $checksum = null;
    $changeSummary = null;
    
    if ($hasFile && $uploadedFile['error'] === UPLOAD_ERR_OK) {
        // Validate file
        $origName = $uploadedFile['name'];
        $tmpPath = $uploadedFile['tmp_name'];
        $fileSize = (int)$uploadedFile['size'];
        $mimeType = mime_content_type($tmpPath) ?: 'application/octet-stream';
        
        // Create upload directory
        $webRoot = realpath(__DIR__ . DIRECTORY_SEPARATOR . '..');
        $targetDir = $webRoot . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'docs';
        
        // Use a temporary ID for directory (will update after insert)
        $tempId = time();
        $targetDir = $targetDir . DIRECTORY_SEPARATOR . $tempId;
        
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }
        
        // Generate filename for version 1
        $ext = pathinfo($origName, PATHINFO_EXTENSION);
        $serverName = 'doc' . $tempId . '_v1' . ($ext ? '.' . $ext : '');
        $targetPath = $targetDir . DIRECTORY_SEPARATOR . $serverName;
        
        // Move file
        if (!move_uploaded_file($tmpPath, $targetPath)) {
            throw new Exception('Failed to move uploaded file');
        }
        
        $filePath = 'uploads/docs/' . $tempId . '/' . $serverName;
        $originalFilename = $origName;
        $fileSizeBytes = $fileSize;
        $mimeType = $mimeType;
        $checksum = hash_file('sha256', $targetPath);
        $changeSummary = 'Initial version';
        
        // After getting DocumentID, we'll rename the directory
    }
    
    // Insert base document (structure depends on migration)
    if ($hasBaseDocumentId) {
        // New structure: single table with version columns
        $stmt = $pdo->prepare("
            INSERT INTO documents 
            (BaseDocumentID, VersionNumber, DocCode, Title, Description, EffectiveDate, OwnerUserID, 
             FilePath, OriginalFilename, FileSizeBytes, MimeType, Checksum, ChangeSummary,
             UploadedBy, DepartmentID, StatusID, IsControlled)
            VALUES (NULL, 1, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
    } else {
        // Old structure: documents table without version columns
        // FilePath is NOT stored in documents table - only in documentversions
        $stmt = $pdo->prepare("
            INSERT INTO documents 
            (DocCode, Title, Description, EffectiveDate, OwnerUserID, FilePath, UploadedBy, DepartmentID, StatusID, IsControlled)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
    }
    
    if ($hasBaseDocumentId) {
        // New structure: execute with all version columns
        $stmt->execute([
            $input['DocCode'] ?? null,
            $input['Title'],
            $input['Description'] ?? null,
            $input['EffectiveDate'] ?? null,
            $input['OwnerUserID'] ?? null,
            $filePath,
            $originalFilename,
            $fileSizeBytes,
            $mimeType,
            $checksum,
            $changeSummary,
            $uploadedBy,
            $input['DepartmentID'] ?? 1,
            $input['StatusID'] ?? 1,
            $input['IsControlled'] ?? 1
        ]);
    } else {
        // Old structure: execute without version columns
        // FilePath is empty string - actual file path goes in documentversions
        $stmt->execute([
            $input['DocCode'] ?? null,
            $input['Title'],
            $input['Description'] ?? null,
            $input['EffectiveDate'] ?? null,
            $input['OwnerUserID'] ?? null,
            '', // FilePath is empty - actual file path goes in documentversions
            $uploadedBy,
            $input['DepartmentID'] ?? 1,
            $input['StatusID'] ?? 1,
            $input['IsControlled'] ?? 1
        ]);
    }
    
    $documentId = $pdo->lastInsertId();
    $versionId = null;
    
    // If file was uploaded, handle it based on structure
    if ($hasFile && $uploadedFile['error'] === UPLOAD_ERR_OK) {
        $oldDir = realpath(__DIR__ . DIRECTORY_SEPARATOR . '..') . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . $tempId;
        $newDir = realpath(__DIR__ . DIRECTORY_SEPARATOR . '..') . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . $documentId;
        
        if (is_dir($oldDir)) {
            rename($oldDir, $newDir);
        }
        
        // Update file path with correct document ID
        $ext = pathinfo($originalFilename, PATHINFO_EXTENSION);
        $serverName = 'doc' . $documentId . '_v1' . ($ext ? '.' . $ext : '');
        $newFilePath = 'uploads/docs/' . $documentId . '/' . $serverName;
        
        // Rename file if needed
        $oldFilePath = $newDir . DIRECTORY_SEPARATOR . 'doc' . $tempId . '_v1' . ($ext ? '.' . $ext : '');
        $newFilePathFull = $newDir . DIRECTORY_SEPARATOR . $serverName;
        if (file_exists($oldFilePath) && $oldFilePath !== $newFilePathFull) {
            rename($oldFilePath, $newFilePathFull);
        }
        
        if ($hasBaseDocumentId) {
            // New structure: update file path in documents table
            $stmt = $pdo->prepare("UPDATE documents SET FilePath = ? WHERE DocumentID = ?");
            $stmt->execute([$newFilePath, $documentId]);
            
            // Set CurrentVersionID to self (it's the base document and version 1)
            $stmt = $pdo->prepare("UPDATE documents SET CurrentVersionID = ? WHERE DocumentID = ?");
            $stmt->execute([$documentId, $documentId]);
            $versionId = $documentId;
        } else {
            // Old structure: create first version in documentversions table
            // Check which columns exist in documentversions
            $columns = $pdo->query("SHOW COLUMNS FROM documentversions")->fetchAll(PDO::FETCH_COLUMN);
            $hasExtendedColumns = in_array('OriginalFilename', $columns) && 
                                 in_array('FileSizeBytes', $columns) &&
                                 in_array('MimeType', $columns);
            
            if ($hasExtendedColumns) {
                $stmt = $pdo->prepare("
                    INSERT INTO documentversions 
                    (DocumentID, VersionNumber, RevisionLabel, FilePath, OriginalFilename, FileSizeBytes, MimeType, Checksum, CreatedBy, ChangeSummary, EffectiveDate)
                    VALUES (?, 1, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                $stmt->execute([
                    $documentId,
                    null, // RevisionLabel
                    $newFilePath,
                    $originalFilename,
                    $fileSizeBytes,
                    $mimeType,
                    $checksum,
                    $uploadedBy,
                    $changeSummary,
                    $input['EffectiveDate'] ?? null
                ]);
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO documentversions 
                    (DocumentID, VersionNumber, FilePath, CreatedBy)
                    VALUES (?, 1, ?, ?)
                ");
                
                $stmt->execute([
                    $documentId,
                    $newFilePath,
                    $uploadedBy
                ]);
            }
            
            $versionId = (int)$pdo->lastInsertId();
            
            // Update document with CurrentVersionID
            $stmt = $pdo->prepare("UPDATE documents SET CurrentVersionID = ? WHERE DocumentID = ?");
            $stmt->execute([$versionId, $documentId]);
        }
    } else {
        // No file uploaded
        if ($hasBaseDocumentId) {
            // New structure: set CurrentVersionID to self
            $stmt = $pdo->prepare("UPDATE documents SET CurrentVersionID = ? WHERE DocumentID = ?");
            $stmt->execute([$documentId, $documentId]);
            $versionId = $documentId;
        } else {
            // Old structure: no version created, CurrentVersionID stays NULL
            $versionId = null;
        }
    }
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'document_id' => $documentId,
        'version_id' => $versionId,
        'version_created' => $versionId !== null,
        'message' => $versionId ? 'Document and first version created successfully' : 'Document created successfully (no file uploaded)'
    ]);
    
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

