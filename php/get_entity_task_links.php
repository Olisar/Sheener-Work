<?php
/* File: sheener/php/get_entity_task_links.php */

error_reporting(0);
ini_set('display_errors', 0);
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
    
    $sourcetype = isset($_GET['sourcetype']) ? $_GET['sourcetype'] : null;
    $sourceid = isset($_GET['sourceid']) ? intval($_GET['sourceid']) : null;
    $taskid = isset($_GET['taskid']) ? intval($_GET['taskid']) : null;
    
    // Check if tables exist safely
    $tasksTableExists = $pdo->query("SHOW TABLES LIKE 'tasks'")->rowCount() > 0;
    $peopleTableExists = $pdo->query("SHOW TABLES LIKE 'people'")->rowCount() > 0;
    
    $query = "SELECT etl.*";
    if ($tasksTableExists) {
        $query .= ", t.task_name, t.task_description, t.status as task_status";
    }
    if ($peopleTableExists) {
        $query .= ", CONCAT(p.FirstName, ' ', p.LastName) as created_by_name";
    }
    
    $query .= " FROM entity_task_links etl";
    if ($tasksTableExists) { $query .= " LEFT JOIN tasks t ON etl.taskid = t.task_id"; }
    if ($peopleTableExists) { $query .= " LEFT JOIN people p ON etl.createdby = p.people_id"; }
    
    $query .= " WHERE 1=1";
    $params = [];
    if ($sourcetype && $sourceid) {
        $query .= " AND etl.sourcetype = :sourcetype AND etl.sourceid = :sourceid";
        $params[':sourcetype'] = $sourcetype;
        $params[':sourceid'] = $sourceid;
    }
    if ($taskid) {
        $query .= " AND etl.taskid = :taskid";
        $params[':taskid'] = $taskid;
    }
    
    $query .= " ORDER BY etl.createdat DESC";
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $links = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($links as &$link) {
        if ($link['sourcetype'] === 'EventFinding' && $link['sourceid']) {
            $sourceQuery = "SELECT event_id, event_type, description, status FROM events WHERE event_id = :id";
            $sourceStmt = $pdo->prepare($sourceQuery);
            $sourceStmt->execute([':id' => $link['sourceid']]);
            $source = $sourceStmt->fetch(PDO::FETCH_ASSOC);
            if ($source) {
                $link['source_details'] = [
                    'id' => $source['event_id'], 'type' => $source['event_type'],
                    'description' => $source['description'], 'status' => $source['status']
                ];
            }
        }
    }
    
    echo json_encode(['success' => true, 'data' => $links]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Server error']);
}
?>
