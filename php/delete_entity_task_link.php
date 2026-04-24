<?php
/* File: sheener/php/delete_entity_task_link.php */

header('Content-Type: application/json');
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    die(json_encode(['success' => false, 'error' => 'Unauthorized access.']));
}

require_once 'database.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) {
        $data = $_POST;
    }
    
    // CSRF check
    $csrfToken = $data['csrf_token'] ?? '';
    if (empty($csrfToken) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrfToken)) {
        http_response_code(403);
        die(json_encode(['success' => false, 'error' => 'Invalid CSRF token.']));
    }

    $linkid = isset($data['link_id']) ? intval($data['link_id']) : null;
    $sourcetype = isset($data['sourcetype']) ? $data['sourcetype'] : null;
    $sourceid = isset($data['sourceid']) ? intval($data['sourceid']) : null;
    $taskid = isset($data['taskid']) ? intval($data['taskid']) : null;
    
    if ($linkid) {
        $getLink = $pdo->prepare("SELECT * FROM entity_task_links WHERE id = :linkid");
        $getLink->execute([':linkid' => $linkid]);
        $link = $getLink->fetch(PDO::FETCH_ASSOC);
        
        if (!$link) {
            throw new Exception('Link not found');
        }
        
        $deleteQuery = "DELETE FROM entity_task_links WHERE id = :linkid";
        $stmt = $pdo->prepare($deleteQuery);
        $stmt->execute([':linkid' => $linkid]);
        
        // Log to auditlog
        $auditQuery = "INSERT INTO auditlog (Action, PerformedBy, PerformedAt, TableAffected, Details, ActionDetails) 
                      VALUES ('DELETE_LINK', :performedby, NOW(), 'entity_task_links', :details, :actiondetails)";
        $auditStmt = $pdo->prepare($auditQuery);
        $auditStmt->execute([
            ':performedby' => $_SESSION['user_id'],
            ':details' => "Deleted link: {$link['sourcetype']} #{$link['sourceid']} -> Task #{$link['taskid']}",
            ':actiondetails' => json_encode([
                'link_id' => $linkid,
                'sourcetype' => $link['sourcetype'],
                'sourceid' => $link['sourceid'],
                'taskid' => $link['taskid']
            ])
        ]);
        
    } elseif ($sourcetype && $sourceid && $taskid) {
        $deleteQuery = "DELETE FROM entity_task_links 
                       WHERE sourcetype = :sourcetype 
                       AND sourceid = :sourceid 
                       AND taskid = :taskid";
        $stmt = $pdo->prepare($deleteQuery);
        $stmt->execute([
            ':sourcetype' => $sourcetype,
            ':sourceid' => $sourceid,
            ':taskid' => $taskid
        ]);
    } else {
        throw new Exception('Missing required parameters.');
    }
    
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
