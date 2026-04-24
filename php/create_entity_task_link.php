<?php
/* File: sheener/php/create_entity_task_link.php */

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once 'database.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data) {
        $data = $_POST;
    }
    
    $sourcetype = isset($data['sourcetype']) ? $data['sourcetype'] : null;
    $sourceid = isset($data['sourceid']) ? intval($data['sourceid']) : null;
    $taskid = isset($data['taskid']) ? intval($data['taskid']) : null;
    $createdby = isset($data['createdby']) ? intval($data['createdby']) : null;
    
    // Validation
    if (!$sourcetype || !$sourceid || !$taskid) {
        throw new Exception('Missing required fields: sourcetype, sourceid, taskid');
    }
    
    $allowedTypes = ['Communication', 'Meeting', 'TrainingSession', 'ObservationReport', 'EventFinding', 'Investigation', 'RCA'];
    if (!in_array($sourcetype, $allowedTypes)) {
        throw new Exception('Invalid sourcetype');
    }
    
    // Check if task exists
    $taskCheck = $pdo->prepare("SELECT task_id FROM tasks WHERE task_id = :taskid");
    $taskCheck->execute([':taskid' => $taskid]);
    if (!$taskCheck->fetch()) {
        throw new Exception('Task not found');
    }
    
    // Check if link already exists
    $existingCheck = $pdo->prepare("SELECT id FROM entity_task_links 
                                   WHERE sourcetype = :sourcetype 
                                   AND sourceid = :sourceid 
                                   AND taskid = :taskid");
    $existingCheck->execute([
        ':sourcetype' => $sourcetype,
        ':sourceid' => $sourceid,
        ':taskid' => $taskid
    ]);
    if ($existingCheck->fetch()) {
        throw new Exception('Link already exists');
    }
    
    // Default createdby to 1 if not provided (system user)
    if (!$createdby) {
        $createdby = 1;
    }
    
    // Insert link
    $insertQuery = "INSERT INTO entity_task_links 
                   (sourcetype, sourceid, taskid, createdby, createdat) 
                   VALUES (:sourcetype, :sourceid, :taskid, :createdby, NOW())";
    
    $stmt = $pdo->prepare($insertQuery);
    $stmt->execute([
        ':sourcetype' => $sourcetype,
        ':sourceid' => $sourceid,
        ':taskid' => $taskid,
        ':createdby' => $createdby
    ]);
    
    $linkId = $pdo->lastInsertId();
    
    // Log to auditlog
    try {
        $auditQuery = "INSERT INTO auditlog (Action, PerformedBy, PerformedAt, TableAffected, Details, ActionDetails) 
                       VALUES (:action, :performedby, NOW(), 'entity_task_links', :details, :actiondetails)";
        $auditStmt = $pdo->prepare($auditQuery);
        $auditStmt->execute([
            ':action' => 'CREATE_LINK',
            ':performedby' => $createdby,
            ':details' => "Created link: {$sourcetype} #{$sourceid} -> Task #{$taskid}",
            ':actiondetails' => json_encode([
                'link_id' => $linkId,
                'sourcetype' => $sourcetype,
                'sourceid' => $sourceid,
                'taskid' => $taskid
            ])
        ]);
    } catch (Exception $e) {
        // Don't fail if audit log fails
        error_log("Audit log error: " . $e->getMessage());
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Link created successfully',
        'link_id' => $linkId
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

