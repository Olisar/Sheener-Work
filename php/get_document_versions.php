<?php
/* File: sheener/php/get_document_versions.php */

// php/get_document_versions.php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once __DIR__ . '/database.php';

try {
    // Get document ID
    $documentId = isset($_GET['document_id']) ? (int)$_GET['document_id'] : 0;
    
    if (!$documentId) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'document_id is required'
        ]);
        exit;
    }
    
    // Connect to database
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
    
    if ($hasBaseDocumentId) {
        // New structure: single table
        // Verify base document exists
        $stmt = $pdo->prepare("SELECT DocumentID, CurrentVersionID FROM documents WHERE DocumentID = ? AND BaseDocumentID IS NULL");
        $stmt->execute([$documentId]);
        $document = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$document) {
            echo json_encode(['success' => true, 'data' => []]);
            exit;
        }
        
        $currentVersionId = $document['CurrentVersionID'] ?? null;
        
        // Build SQL query to get all versions (base document + all version rows)
        $sql = "SELECT 
                    d.DocumentID AS VersionID,
                    d.DocumentID,
                    d.BaseDocumentID,
                    d.VersionNumber,
                    d.RevisionLabel,
                    d.FilePath,
                    d.OriginalFilename,
                    d.FileSizeBytes,
                    d.MimeType,
                    d.ChangeSummary,
                    d.EffectiveDate,
                    d.UploadedAt AS CreatedAt,
                    d.UploadedBy AS CreatedBy,
                    d.StatusID,
                    base.CurrentVersionID,
                    CASE WHEN base.CurrentVersionID IS NOT NULL AND base.CurrentVersionID = d.DocumentID THEN 1 ELSE 0 END AS IsCurrent,
                    ds.StatusName,
                    p.first_name,
                    p.last_name
                FROM documents d
                INNER JOIN documents base ON (d.DocumentID = base.DocumentID AND d.BaseDocumentID IS NULL) OR (d.BaseDocumentID = base.DocumentID)
                LEFT JOIN documentstatuses ds ON d.StatusID = ds.StatusID
                LEFT JOIN people p ON d.UploadedBy = p.people_id
                WHERE (d.DocumentID = ? AND d.BaseDocumentID IS NULL) OR d.BaseDocumentID = ?
                ORDER BY d.VersionNumber DESC";
    } else {
        // Old structure: documentversions table
        // Verify document exists
        $stmt = $pdo->prepare("SELECT DocumentID, CurrentVersionID FROM documents WHERE DocumentID = ?");
        $stmt->execute([$documentId]);
        $document = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$document) {
            echo json_encode(['success' => true, 'data' => []]);
            exit;
        }
        
        $currentVersionId = $document['CurrentVersionID'] ?? null;
        
        // Get all columns from documentversions table
        $columns = [];
        try {
            $colStmt = $pdo->query("SHOW COLUMNS FROM documentversions");
            while ($col = $colStmt->fetch(PDO::FETCH_ASSOC)) {
                $columns[] = $col['Field'];
            }
        } catch (PDOException $e) {
            error_log("Error getting columns: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Database error: ' . $e->getMessage()
            ]);
            exit;
        }
        
        // Build SELECT fields - only include columns that exist
        $selectFields = ['dv.VersionID', 'dv.DocumentID', 'dv.VersionNumber'];
        
        if (in_array('RevisionLabel', $columns)) {
            $selectFields[] = 'dv.RevisionLabel';
        }
        if (in_array('FilePath', $columns)) {
            $selectFields[] = 'dv.FilePath';
        }
        if (in_array('OriginalFilename', $columns)) {
            $selectFields[] = 'dv.OriginalFilename';
        }
        if (in_array('FileSizeBytes', $columns)) {
            $selectFields[] = 'dv.FileSizeBytes';
        }
        if (in_array('MimeType', $columns)) {
            $selectFields[] = 'dv.MimeType';
        }
        if (in_array('ChangeSummary', $columns)) {
            $selectFields[] = 'dv.ChangeSummary';
        }
        if (in_array('EffectiveDate', $columns)) {
            $selectFields[] = 'dv.EffectiveDate';
        }
        if (in_array('CreatedAt', $columns)) {
            $selectFields[] = 'dv.CreatedAt';
        }
        if (in_array('CreatedBy', $columns)) {
            $selectFields[] = 'dv.CreatedBy';
        }
        if (in_array('StatusID', $columns)) {
            $selectFields[] = 'dv.StatusID';
        }
        
        // Add CurrentVersionID from documents table
        $selectFields[] = 'd.CurrentVersionID';
        
        // Build SQL query
        // Handle NULL CurrentVersionID: if CurrentVersionID is NULL, IsCurrent will be 0
        $sql = "SELECT " . implode(', ', $selectFields);
        $sql .= ", CASE WHEN d.CurrentVersionID IS NOT NULL AND d.CurrentVersionID = dv.VersionID THEN 1 ELSE 0 END AS IsCurrent";
        $sql .= " FROM documentversions dv";
        $sql .= " INNER JOIN documents d ON dv.DocumentID = d.DocumentID";
        
        // Add optional JOINs
        if (in_array('StatusID', $columns)) {
            $sql .= " LEFT JOIN documentstatuses ds ON dv.StatusID = ds.StatusID";
            $selectFields[] = 'ds.StatusName';
        }
        
        if (in_array('CreatedBy', $columns)) {
            $sql .= " LEFT JOIN people p ON dv.CreatedBy = p.people_id";
            $selectFields[] = 'p.first_name';
            $selectFields[] = 'p.last_name';
        }
        
        $sql .= " WHERE dv.DocumentID = ? ORDER BY dv.VersionNumber DESC";
    }
    
    // Execute query
    try {
        $stmt = $pdo->prepare($sql);
        if ($hasBaseDocumentId) {
            $stmt->execute([$documentId, $documentId]);
        } else {
            $stmt->execute([$documentId]);
        }
        $versions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("SQL Error: " . $e->getMessage() . " | SQL: " . $sql);
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Database error: ' . $e->getMessage()
        ]);
        exit;
    }
    
    // If no versions, return empty array
    if (empty($versions)) {
        echo json_encode(['success' => true, 'data' => []]);
        exit;
    }
    
    // Process versions
    $processedVersions = [];
    foreach ($versions as $v) {
        $version = [
            'VersionID' => $v['VersionID'] ?? null,  // DocumentID of this version
            'DocumentID' => $v['DocumentID'] ?? null,
            'VersionNumber' => $v['VersionNumber'] ?? null,
            'RevisionLabel' => $v['RevisionLabel'] ?? null,
            'FilePath' => $v['FilePath'] ?? null,
            'OriginalFilename' => $v['OriginalFilename'] ?? null,
            'FileSizeBytes' => $v['FileSizeBytes'] ?? null,
            'MimeType' => $v['MimeType'] ?? null,
            'ChangeSummary' => $v['ChangeSummary'] ?? null,
            'EffectiveDate' => $v['EffectiveDate'] ?? null,
            'CreatedAt' => $v['CreatedAt'] ?? null,
            'CreatedBy' => $v['CreatedBy'] ?? null,
            'StatusID' => $v['StatusID'] ?? null,
            'StatusName' => $v['StatusName'] ?? null,
            'IsCurrent' => isset($v['IsCurrent']) ? (int)$v['IsCurrent'] : 0,
            'attachment_count' => 0
        ];
        
        // Build file URL
        if (!empty($version['FilePath'])) {
            $filePath = $version['FilePath'];
            // Remove 'sheener/' prefix if present
            if (strpos($filePath, 'sheener/') === 0) {
                $filePath = substr($filePath, 8);
            }
            $version['fileUrl'] = '/' . ltrim($filePath, '/');
        }
        
        // Get creator name
        $firstName = $v['first_name'] ?? '';
        $lastName = $v['last_name'] ?? '';
        $version['created_by_name'] = trim($firstName . ' ' . $lastName);
        
        // Get attachment count (version_id now references documents.DocumentID)
        try {
            $attStmt = $pdo->query("SHOW COLUMNS FROM attachments LIKE 'version_id'");
            $hasVersionId = $attStmt->fetch();
            
            if ($hasVersionId && $version['VersionID']) {
                $countStmt = $pdo->prepare("SELECT COUNT(*) FROM attachments WHERE version_id = ?");
                $countStmt->execute([$version['VersionID']]);
                $version['attachment_count'] = (int)$countStmt->fetchColumn();
            }
        } catch (Exception $e) {
            // Ignore attachment count errors
            $version['attachment_count'] = 0;
        }
        
        $processedVersions[] = $version;
    }
    
    echo json_encode(['success' => true, 'data' => $processedVersions]);
    
} catch (PDOException $e) {
    http_response_code(500);
    error_log("PDO Error in get_document_versions.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(500);
    error_log("Error in get_document_versions.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Error: ' . $e->getMessage()
    ]);
}
