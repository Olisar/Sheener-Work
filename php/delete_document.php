<?php
/* File: sheener/php/delete_document.php */

// php/delete_document.php
header('Content-Type: application/json');

require_once __DIR__ . '/database.php';

try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['document_id'])) {
        throw new Exception('document_id is required');
    }
    
    $documentId = (int)$input['document_id'];
    
    // Check if BaseDocumentID column exists (migration status)
    $hasBaseDocumentId = false;
    try {
        $colStmt = $pdo->query("SHOW COLUMNS FROM documents LIKE 'BaseDocumentID'");
        $hasBaseDocumentId = $colStmt->fetch() !== false;
    } catch (Exception $e) {
        $hasBaseDocumentId = false;
    }
    
    $versionCount = 0;
    $baseDocId = null;
    
    if ($hasBaseDocumentId) {
        // New structure: single table
        // Check if document has versions (BaseDocumentID points to this document)
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM documents WHERE BaseDocumentID = ?");
        $stmt->execute([$documentId]);
        $versionCount = $stmt->fetchColumn();
        
        // Also check if this is a version (has BaseDocumentID)
        $stmt = $pdo->prepare("SELECT BaseDocumentID FROM documents WHERE DocumentID = ?");
        $stmt->execute([$documentId]);
        $baseDocId = $stmt->fetchColumn();
    } else {
        // Old structure: check documentversions table
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM documentversions WHERE DocumentID = ?");
        $stmt->execute([$documentId]);
        $versionCount = $stmt->fetchColumn();
        
        // In old structure, we're always deleting from documents table (base document)
        // Versions are in documentversions table, so baseDocId stays null
    }
    
    if ($versionCount > 0 || $baseDocId) {
        // Soft delete: set status to "Obsolete" (or "Deleted" if it exists)
        // First try to find "Deleted" status, if not found, use "Obsolete"
        $stmt = $pdo->prepare("SELECT StatusID FROM documentstatuses WHERE StatusName IN ('Deleted', 'Obsolete') ORDER BY CASE WHEN StatusName = 'Deleted' THEN 1 ELSE 2 END LIMIT 1");
        $stmt->execute();
        $deleteStatusId = $stmt->fetchColumn();
        
        if (!$deleteStatusId) {
            // If neither exists, get the first available status (shouldn't happen, but safe fallback)
            $stmt = $pdo->query("SELECT StatusID FROM documentstatuses ORDER BY StatusID LIMIT 1");
            $deleteStatusId = $stmt->fetchColumn() ?: 1;
        }
        
        // Soft delete: update status
        // Note: CurrentVersionID will be automatically set to NULL by FK constraint if version is deleted
        $stmt = $pdo->prepare("UPDATE documents SET StatusID = ? WHERE DocumentID = ?");
        $stmt->execute([$deleteStatusId, $documentId]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Document marked as obsolete (has versions, cannot hard delete)'
        ]);
    } else {
        // Hard delete if no versions
        // First check for any related records that might prevent deletion
        // Check for attachments linked to document/version (version_id now references documents.DocumentID)
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM attachments WHERE permit_id IS NULL AND event_id IS NULL AND version_id = ?");
        $stmt->execute([$documentId]);
        $attachmentCount = $stmt->fetchColumn();
        
        // Also check attachments for all versions of this document
        if ($hasBaseDocumentId) {
            // New structure: versions are in documents table with BaseDocumentID
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM attachments WHERE permit_id IS NULL AND event_id IS NULL AND version_id IN (SELECT DocumentID FROM documents WHERE BaseDocumentID = ?)");
            $stmt->execute([$documentId]);
            $versionAttachmentCount = $stmt->fetchColumn();
        } else {
            // Old structure: versions are in documentversions table
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM attachments WHERE permit_id IS NULL AND event_id IS NULL AND version_id IN (SELECT VersionID FROM documentversions WHERE DocumentID = ?)");
            $stmt->execute([$documentId]);
            $versionAttachmentCount = $stmt->fetchColumn();
        }
        $attachmentCount += $versionAttachmentCount;
        
        if ($attachmentCount > 0) {
            // Has attachments, do soft delete instead
            $stmt = $pdo->prepare("SELECT StatusID FROM documentstatuses WHERE StatusName IN ('Deleted', 'Obsolete') ORDER BY CASE WHEN StatusName = 'Deleted' THEN 1 ELSE 2 END LIMIT 1");
            $stmt->execute();
            $deleteStatusId = $stmt->fetchColumn() ?: 4; // Default to Obsolete (usually ID 4)
            
            $stmt = $pdo->prepare("UPDATE documents SET StatusID = ? WHERE DocumentID = ?");
            $stmt->execute([$deleteStatusId, $documentId]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Document marked as obsolete (has related data)'
            ]);
        } else {
            // Safe to hard delete
            // Note: If this document has CurrentVersionID pointing to a version, 
            // the FK constraint (ON DELETE SET NULL) will handle it automatically.
            // But since we're deleting the base document, we should ensure CurrentVersionID is handled.
            // The FK constraint will automatically set CurrentVersionID to NULL in other documents
            // if the referenced version is deleted, but here we're deleting the document itself.
            $stmt = $pdo->prepare("DELETE FROM documents WHERE DocumentID = ?");
            $stmt->execute([$documentId]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Document deleted successfully'
            ]);
        }
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

