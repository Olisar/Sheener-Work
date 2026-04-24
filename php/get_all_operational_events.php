<?php
/* File: sheener/php/get_all_operational_events.php */

header('Content-Type: application/json');
require_once 'database.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();

    // Fetch all operational events (which includes observations)
    $stmt = $pdo->prepare("
        SELECT 
            e.event_id,
            e.event_type,
            e.reported_by,
            e.created_at as reported_date,
            e.description,
            e.status,
            e.event_subcategory,
            e.likelihood,
            e.severity,
            e.risk_rating,
            e.department_id,
            CONCAT(p.FirstName, ' ', p.LastName) AS reported_by_name,
            d.DepartmentName
        FROM operational_events e
        LEFT JOIN people p ON e.reported_by = p.people_id
        LEFT JOIN departments d ON e.department_id = d.department_id
        ORDER BY e.created_at DESC, e.event_id DESC
    ");
    $stmt->execute();
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "success" => true,
        "data" => $events
    ]);
} catch (PDOException $e) {
    echo json_encode([
        "success" => false,
        "error" => "Database error: " . $e->getMessage()
    ]);
}
?>

