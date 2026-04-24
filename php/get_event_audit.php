<?php
/* File: sheener/php/get_event_audit.php */

header('Content-Type: application/json');
session_start();
require_once 'database.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();

    $event_id = $_GET['event_id'] ?? null;

    if (!$event_id) {
        echo json_encode(["success" => false, "error" => "Event ID is required"]);
        exit;
    }

    $query = "SELECT l.LogID, l.Action, l.PerformedAt, l.Details, l.ActionDetails, 
                     CONCAT(p.first_name, ' ', p.last_name) AS performed_by_name
              FROM auditlog l
              LEFT JOIN people p ON l.PerformedBy = p.people_id
              WHERE l.TableAffected = 'events' AND l.ActionDetails LIKE :event_id_pattern
              OR (l.TableAffected = 'entity_task_links' AND l.ActionDetails LIKE :event_id_pattern_link)
              OR (l.TableAffected = 'entity_process_links' AND l.ActionDetails LIKE :event_id_pattern_link)
              ORDER BY l.PerformedAt DESC";

    $stmt = $pdo->prepare($query);
    $pattern = '%"event_id":' . $event_id . '%';
    $pattern_link = '%"sourceid":' . $event_id . '%';
    
    // Also match if it's just the ID in Details (fallback)
    // but the ActionDetails approach is more reliable if we structure it right
    
    $stmt->bindValue(':event_id_pattern', $pattern);
    $stmt->bindValue(':event_id_pattern_link', $pattern_link);
    $stmt->execute();

    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(["success" => true, "data" => $logs]);

} catch (PDOException $e) {
    echo json_encode(["success" => false, "error" => "Database error: " . $e->getMessage()]);
}
?>
