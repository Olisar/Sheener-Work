<?php
/* File: sheener/php/get_documents.php */

// php/get_documents.php
header('Content-Type: application/json');

// Define project base for URL generation
define('PROJECT_BASE', 'sheener');

// GLOBAL error handler to catch ANY crash
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== null) {
        http_response_code(500);
        // Log full error, but send safe message to client
        error_log("Fatal error in get_documents.php: " . print_r($error, true));
        echo json_encode(['error' => 'Server error occurred']);
        exit;
    }
});

try {
    // Set error handling
    error_reporting(E_ALL);
    ini_set('display_errors', 0);

    require_once __DIR__ . '/database.php';
    
    $db = new Database();
    $pdo = $db->getConnection();

    // Check if BaseDocumentID column exists (migration status)
    $hasBaseDocumentId = false;
    try {
        $colStmt = $pdo->query("SHOW COLUMNS FROM documents LIKE 'BaseDocumentID'");
        $hasBaseDocumentId = $colStmt->fetch() !== false;
    } catch (Exception $e) {
        // Column doesn't exist - use old structure
        $hasBaseDocumentId = false;
    }

    $documentId = isset($_GET['document_id']) ? intval($_GET['document_id']) : null;

    if ($documentId) {
        // Fetch a single document by ID
        if ($hasBaseDocumentId) {
            // New structure: base document only
            $sql = "SELECT d.DocumentID, d.DocCode, d.Title, d.Description, 
                           d.EffectiveDate, d.OwnerUserID, d.UploadedBy, d.StatusID,
                           d.CurrentVersionID, d.VersionNumber, d.RevisionLabel,
                           s.StatusName, dt.Name AS DocumentType
                    FROM documents d
                    LEFT JOIN documentstatuses s ON d.StatusID = s.StatusID
                    LEFT JOIN document_types dt ON d.DocumentTypeID = dt.DocumentTypeID
                    WHERE d.DocumentID = :document_id AND d.BaseDocumentID IS NULL";
        } else {
            // Old structure: join with documentversions
            // LEFT JOIN handles NULL CurrentVersionID gracefully (ON DELETE SET NULL constraint)
            $sql = "SELECT d.DocumentID, d.DocCode, d.Title, d.Description, 
                           d.EffectiveDate, d.OwnerUserID, d.UploadedBy, d.StatusID,
                           d.CurrentVersionID,
                           s.StatusName, dt.Name AS DocumentType, 
                           dv.VersionNumber, dv.RevisionLabel
                    FROM documents d
                    LEFT JOIN documentstatuses s ON d.StatusID = s.StatusID
                    LEFT JOIN document_types dt ON d.DocumentTypeID = dt.DocumentTypeID
                    LEFT JOIN documentversions dv ON d.CurrentVersionID = dv.VersionID
                    WHERE d.DocumentID = :document_id";
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':document_id' => $documentId]);
        $doc = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($doc) {
            echo json_encode(['success' => true, 'data' => $doc]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Document not found']);
        }
    } else {
        // Fetch all documents
        // Optional: add ?include_deleted=1 to show deleted documents
        $includeDeleted = isset($_GET['include_deleted']) && $_GET['include_deleted'] == '1';
        
        if ($hasBaseDocumentId) {
            // New structure: base documents only
            $sql = "SELECT d.DocumentID, d.DocCode, d.Title, d.Description, 
                           s.StatusName, dt.Name AS DocumentType, 
                           d.CurrentVersionID, d.VersionNumber, d.RevisionLabel, 
                           d.EffectiveDate, d.StatusID
                    FROM documents d
                    LEFT JOIN documentstatuses s ON d.StatusID = s.StatusID
                    LEFT JOIN document_types dt ON d.DocumentTypeID = dt.DocumentTypeID
                    WHERE d.BaseDocumentID IS NULL";
            
            if (!$includeDeleted) {
                $sql .= " AND (s.StatusName NOT IN ('Obsolete', 'Deleted') OR s.StatusName IS NULL)";
            }
        } else {
            // Old structure: join with documentversions
            // LEFT JOIN handles NULL CurrentVersionID gracefully (ON DELETE SET NULL constraint)
            $sql = "SELECT d.DocumentID, d.DocCode, d.Title, d.Description, 
                           s.StatusName, dt.Name AS DocumentType, 
                           d.CurrentVersionID,
                           dv.VersionNumber, dv.RevisionLabel, d.EffectiveDate, d.StatusID
                    FROM documents d
                    LEFT JOIN documentstatuses s ON d.StatusID = s.StatusID
                    LEFT JOIN document_types dt ON d.DocumentTypeID = dt.DocumentTypeID
                    LEFT JOIN documentversions dv ON d.CurrentVersionID = dv.VersionID";
            
            if (!$includeDeleted) {
                $sql .= " WHERE (s.StatusName NOT IN ('Obsolete', 'Deleted') OR s.StatusName IS NULL)";
            }
        }
        
        $sql .= " ORDER BY d.DocCode, d.Title";
        
        // Debug: Log SQL for troubleshooting (remove in production)
        // error_log("SQL Query: " . $sql);

        $stmt = $pdo->query($sql);
        $docs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Return in consistent format
        echo json_encode(['success' => true, 'data' => $docs ?: []]);
    }

} catch (Exception $e) {
    http_response_code(500);
    error_log("get_documents.php exception: " . $e->getMessage());
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
