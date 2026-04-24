<?php
/* File: sheener/php/get_all_events.php */

header('Content-Type: application/json');
require_once 'database.php';

$eventId = isset($_GET['event_id']) ? intval($_GET['event_id']) : null;

try {
    $database = new Database();
    $pdo = $database->getConnection();

    if ($eventId) {
        // Fetch a single event by ID
        $stmt = $pdo->prepare("
            SELECT 
                e.event_id,
                e.event_type,
                e.reported_by,
                e.reported_date,
                e.description,
                e.status,
                e.event_subcategory,
                e.likelihood,
                e.severity,
                e.risk_rating,
                e.department_id,
                e.cc_title,
                e.cc_justification,
                e.cc_change_from,
                e.cc_change_to,
                e.cc_change_type,
                e.cc_logged_ref,
                e.cc_logged_date,
                CONCAT(p.FirstName, ' ', p.LastName) AS reported_by_name,
                d.DepartmentName
            FROM events e
            LEFT JOIN people p ON e.reported_by = p.people_id
            LEFT JOIN departments d ON e.department_id = d.department_id
            WHERE e.event_id = :event_id
        ");
        $stmt->execute([':event_id' => $eventId]);
        $event = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($event) {
            echo json_encode([
                "success" => true,
                "data" => $event
            ]);
        } else {
            echo json_encode([
                "success" => false,
                "error" => "Event not found"
            ]);
        }
    } else {
        // Fetch all events
        $stmt = $pdo->prepare("
            SELECT 
                e.event_id,
                e.event_type,
                e.reported_by,
                e.reported_date,
                e.description,
                e.status,
                e.event_subcategory,
                e.likelihood,
                e.severity,
                e.risk_rating,
                e.department_id,
                e.cc_title,
                e.cc_justification,
                e.cc_change_from,
                e.cc_change_to,
                e.cc_change_type,
                e.cc_logged_ref,
                e.cc_logged_date,
                CONCAT(p.FirstName, ' ', p.LastName) AS reported_by_name,
                d.DepartmentName
            FROM events e
            LEFT JOIN people p ON e.reported_by = p.people_id
            LEFT JOIN departments d ON e.department_id = d.department_id
            ORDER BY e.reported_date DESC, e.event_id DESC
        ");
        $stmt->execute();
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            "success" => true,
            "data" => $events
        ]);
    }
} catch (PDOException $e) {
    echo json_encode([
        "success" => false,
        "error" => "Database error: " . $e->getMessage()
    ]);
}
?>

