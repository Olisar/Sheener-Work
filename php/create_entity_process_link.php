<?php
/* File: sheener/php/create_entity_process_link.php */

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
    $processid = isset($data['processid']) ? intval($data['processid']) : null;
    $createdby = isset($data['createdby']) ? intval($data['createdby']) : null;
    
    // Validation
    if (!$sourcetype || !$sourceid || !$processid) {
        throw new Exception('Missing required fields: sourcetype, sourceid, processid');
    }
    
    $allowedTypes = ['Communication', 'Meeting', 'TrainingSession', 'ObservationReport', 'EventFinding'];
    if (!in_array($sourcetype, $allowedTypes)) {
        throw new Exception('Invalid sourcetype');
    }
    
    // Check if process exists
    $processCheck = $pdo->prepare("SELECT id FROM process_map WHERE id = :processid");
    $processCheck->execute([':processid' => $processid]);
    if (!$processCheck->fetch()) {
        throw new Exception('Process not found');
    }
    
    // Check if entity_process_links table exists, if not create it
    $tableCheck = $pdo->query("SHOW TABLES LIKE 'entity_process_links'");
    if ($tableCheck === false || $tableCheck->rowCount() === 0) {
        // Create the table
        $createTable = "CREATE TABLE IF NOT EXISTS entity_process_links (
            id INT(11) NOT NULL AUTO_INCREMENT,
            sourcetype ENUM('Communication','Meeting','TrainingSession','ObservationReport','EventFinding') NOT NULL,
            sourceid BIGINT(11) NOT NULL,
            processid INT(11) NOT NULL,
            createdby INT(11) NOT NULL,
            createdat TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_entity_process_link (sourcetype, sourceid, processid),
            KEY idx_source (sourcetype, sourceid),
            KEY idx_process (processid),
            KEY fk_process_link_creator (createdby),
            CONSTRAINT fk_process_link_creator FOREIGN KEY (createdby) REFERENCES people (people_id),
            CONSTRAINT fk_process_link_process FOREIGN KEY (processid) REFERENCES process_map (id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
        $pdo->exec($createTable);
    }
    
    // Check if link already exists
    $existingCheck = $pdo->prepare("SELECT id FROM entity_process_links 
                                   WHERE sourcetype = :sourcetype 
                                   AND sourceid = :sourceid 
                                   AND processid = :processid");
    $existingCheck->execute([
        ':sourcetype' => $sourcetype,
        ':sourceid' => $sourceid,
        ':processid' => $processid
    ]);
    if ($existingCheck->fetch()) {
        throw new Exception('Link already exists');
    }
    
    // Default createdby to 1 if not provided (system user)
    if (!$createdby) {
        $createdby = 1;
    }
    
    // Insert link
    $insertQuery = "INSERT INTO entity_process_links 
                   (sourcetype, sourceid, processid, createdby, createdat) 
                   VALUES (:sourcetype, :sourceid, :processid, :createdby, NOW())";
    
    $stmt = $pdo->prepare($insertQuery);
    $stmt->execute([
        ':sourcetype' => $sourcetype,
        ':sourceid' => $sourceid,
        ':processid' => $processid,
        ':createdby' => $createdby
    ]);
    
    $linkId = $pdo->lastInsertId();
    
    // Log to auditlog
    try {
        $auditQuery = "INSERT INTO auditlog (Action, PerformedBy, PerformedAt, TableAffected, Details, ActionDetails) 
                       VALUES (:action, :performedby, NOW(), 'entity_process_links', :details, :actiondetails)";
        $auditStmt = $pdo->prepare($auditQuery);
        $auditStmt->execute([
            ':action' => 'CREATE_LINK',
            ':performedby' => $createdby,
            ':details' => "Created link: {$sourcetype} #{$sourceid} -> Process #{$processid}",
            ':actiondetails' => json_encode([
                'link_id' => $linkId,
                'sourcetype' => $sourcetype,
                'sourceid' => $sourceid,
                'processid' => $processid
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

