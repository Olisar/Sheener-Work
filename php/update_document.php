<?php
/* File: sheener/php/update_document.php */

// php/update_document.php
header('Content-Type: application/json');

require_once __DIR__ . '/database.php';

try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['DocumentID'])) {
        throw new Exception('DocumentID is required');
    }
    
    $documentId = (int)$input['DocumentID'];
    
    // Check if document is obsolete/deleted - prevent editing
    $stmt = $pdo->prepare("
        SELECT s.StatusName 
        FROM documents d
        LEFT JOIN documentstatuses s ON d.StatusID = s.StatusID
        WHERE d.DocumentID = ?
    ");
    $stmt->execute([$documentId]);
    $status = $stmt->fetchColumn();
    
    if ($status === 'Obsolete' || $status === 'Deleted') {
        throw new Exception('Cannot update a deleted/obsolete document');
    }
    
    // Build update query dynamically
    $updates = [];
    $params = [':document_id' => $documentId];
    
    $allowedFields = [
        'DocCode', 'Title', 'Description', 'EffectiveDate', 
        'OwnerUserID', 'DepartmentID', 'StatusID', 'IsControlled'
    ];
    
    foreach ($allowedFields as $field) {
        if (isset($input[$field])) {
            $updates[] = "$field = :$field";
            $params[":$field"] = $input[$field];
        }
    }
    
    if (empty($updates)) {
        throw new Exception('No fields to update');
    }
    
    $sql = "UPDATE documents SET " . implode(', ', $updates) . " WHERE DocumentID = :document_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    echo json_encode([
        'success' => true,
        'message' => 'Document updated successfully'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

