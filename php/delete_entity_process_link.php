<?php
/* File: sheener/php/delete_entity_process_link.php */

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, DELETE, OPTIONS");
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
    
    // Support both link_id and combination of sourcetype/sourceid/processid
    $linkid = isset($data['link_id']) ? intval($data['link_id']) : null;
    $sourcetype = isset($data['sourcetype']) ? $data['sourcetype'] : null;
    $sourceid = isset($data['sourceid']) ? intval($data['sourceid']) : null;
    $processid = isset($data['processid']) ? intval($data['processid']) : null;
    
    // Check if entity_process_links table exists
    $tableCheck = $pdo->query("SHOW TABLES LIKE 'entity_process_links'");
    if ($tableCheck === false || $tableCheck->rowCount() === 0) {
        throw new Exception('Entity process links table does not exist');
    }
    
    if ($linkid) {
        // Get link details before deletion for audit
        $getLink = $pdo->prepare("SELECT * FROM entity_process_links WHERE id = :linkid");
        $getLink->execute([':linkid' => $linkid]);
        $link = $getLink->fetch(PDO::FETCH_ASSOC);
        
        if (!$link) {
            throw new Exception('Link not found');
        }
        
        $deleteQuery = "DELETE FROM entity_process_links WHERE id = :linkid";
        $stmt = $pdo->prepare($deleteQuery);
        $stmt->execute([':linkid' => $linkid]);
        
        // Log to auditlog
        try {
            $auditQuery = "INSERT INTO auditlog (Action, PerformedBy, PerformedAt, TableAffected, Details, ActionDetails) 
                          VALUES (:action, :performedby, NOW(), 'entity_process_links', :details, :actiondetails)";
            $auditStmt = $pdo->prepare($auditQuery);
            $auditStmt->execute([
                ':action' => 'DELETE_LINK',
                ':performedby' => $link['createdby'],
                ':details' => "Deleted link: {$link['sourcetype']} #{$link['sourceid']} -> Process #{$link['processid']}",
                ':actiondetails' => json_encode([
                    'link_id' => $linkid,
                    'sourcetype' => $link['sourcetype'],
                    'sourceid' => $link['sourceid'],
                    'processid' => $link['processid']
                ])
            ]);
        } catch (Exception $e) {
            error_log("Audit log error: " . $e->getMessage());
        }
        
    } elseif ($sourcetype && $sourceid && $processid) {
        // Delete by combination
        $deleteQuery = "DELETE FROM entity_process_links 
                       WHERE sourcetype = :sourcetype 
                       AND sourceid = :sourceid 
                       AND processid = :processid";
        $stmt = $pdo->prepare($deleteQuery);
        $stmt->execute([
            ':sourcetype' => $sourcetype,
            ':sourceid' => $sourceid,
            ':processid' => $processid
        ]);
    } else {
        throw new Exception('Missing required parameters: link_id or (sourcetype, sourceid, processid)');
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Link deleted successfully'
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

