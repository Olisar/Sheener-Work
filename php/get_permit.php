<?php
/* File: sheener/php/get_permit.php */

session_start();
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: http://172.21.10.99");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Check authentication
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized - Please log in']);
    exit();
}

require_once 'database.php';

$permit_id = $_GET['permit_id'] ?? null;

if (!$permit_id) {
    echo json_encode(['success' => false, 'error' => 'No permit_id provided']);
    exit;
}

try {
    $database = new Database();
    $pdo = $database->getConnection();

    $query = "
SELECT 
    p.permit_id,
    p.task_id,
    p.permit_type,
    p.issued_by,
    CONCAT(ib.FirstName, ' ', ib.LastName) AS issued_by_name,
    ib.Email AS issued_by_email,
    p.approved_by,
    CONCAT(ab.FirstName, ' ', ab.LastName) AS approved_by_name,
    ab.Email AS approved_by_email,
    p.issue_date,
    p.expiry_date,
    p.status AS permit_status,
    p.conditions,
    p.Dep_owner,
    CONCAT(do_person.FirstName, ' ', do_person.LastName) AS dep_owner_name,
    do_person.Email AS dep_owner_email,
    t.task_name,
    t.task_description,
    t.task_type,
    t.start_date,
    t.finish_date,
    t.due_date,
    t.priority,
    t.status AS task_status
FROM permits p
LEFT JOIN tasks t ON p.task_id = t.task_id
LEFT JOIN people ib ON p.issued_by = ib.people_id
LEFT JOIN people ab ON p.approved_by = ab.people_id
LEFT JOIN people do_person ON p.Dep_owner = do_person.people_id
WHERE p.permit_id = :permit_id
    ";

    $stmt = $pdo->prepare($query);
    $stmt->execute([':permit_id' => $permit_id]);
    $permit = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($permit) {
        // Fetch associated steps
        $steps = [];
        $stmtSteps = $pdo->prepare("
            SELECT step_id, step_number, step_description, hazard_description, control_description
            FROM permit_steps
            WHERE permit_id = ?
            ORDER BY step_number
        ");
        $stmtSteps->execute([$permit_id]);
        while ($row = $stmtSteps->fetch(PDO::FETCH_ASSOC)) {
            $steps[] = $row;
        }
        $permit['steps'] = $steps;
        echo json_encode(['success' => true, 'permit' => $permit]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Permit not found']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>
