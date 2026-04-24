<?php
/* File: sheener/php/delete_event.php */

header('Content-Type: application/json');
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    die(json_encode(['success' => false, 'error' => 'Unauthorized access.']));
}

// CSRF check
if (empty($_POST['csrf_token']) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    http_response_code(403);
    die(json_encode(['success' => false, 'error' => 'Invalid CSRF token.']));
}

require_once 'database.php';

$eventId = isset($_POST['event_id']) ? intval($_POST['event_id']) : 0;

if ($eventId > 0) {
    try {
        $database = new Database();
        $pdo = $database->getConnection();

        // Optional: Add audit log before deletion
        $auditStmt = $pdo->prepare("INSERT INTO auditlog (Action, PerformedBy, PerformedAt, TableAffected, Details) VALUES ('DELETE_EVENT', ?, NOW(), 'events', ?)");
        $auditStmt->execute([$_SESSION['user_id'], "Deleting Event #{$eventId}"]);

        $query = "DELETE FROM events WHERE event_id = :event_id";
        $stmt = $pdo->prepare($query);
        $stmt->execute([':event_id' => $eventId]);

        if ($stmt->rowCount() > 0) {
            echo json_encode(["success" => true, "message" => "Event deleted successfully"]);
        } else {
            echo json_encode(["success" => false, "error" => "No event found with the specified ID"]);
        }
    } catch (PDOException $e) {
        echo json_encode(["success" => false, "error" => "Database error: " . $e->getMessage()]);
    }
} else {
    echo json_encode(["success" => false, "error" => "Invalid event ID"]);
}
?>
