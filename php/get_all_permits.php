<?php
/* File: sheener/php/get_all_permits.php */

session_start();
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: http://172.21.10.99");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Check authentication - allow user 32 and other authenticated users
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized - Please log in']);
    exit();
}

require_once 'database.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();

    $query = "
        SELECT 
            p.permit_id,
            p.task_id,
            p.permit_type,
            CONCAT(issuer.FirstName, ' ', issuer.LastName) AS issued_by_name,
            CONCAT(approver.FirstName, ' ', approver.LastName) AS approved_by_name,
            p.issued_by,
            p.approved_by,
            p.issue_date,
            p.expiry_date,
            p.status AS permit_status,
            p.conditions,
            p.Dep_owner,
            CONCAT(do_person.FirstName, ' ', do_person.LastName) AS dep_owner_name,
            t.task_name,
            t.task_description
        FROM permits p
        LEFT JOIN tasks t ON p.task_id = t.task_id
        LEFT JOIN people issuer ON p.issued_by = issuer.people_id
        LEFT JOIN people approver ON p.approved_by = approver.people_id
        LEFT JOIN people do_person ON p.Dep_owner = do_person.people_id
        ORDER BY p.task_id, ABS(DATEDIFF(p.issue_date, CURDATE()))
    ";

    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $permits = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch steps for each permit and format dates
    foreach ($permits as &$permit) {
        $stmtSteps = $pdo->prepare(
            "SELECT step_id, step_number, step_description, hazard_description, control_description FROM permit_steps WHERE permit_id = ? ORDER BY step_number"
        );
        $stmtSteps->execute([$permit['permit_id']]);
        $permit['steps'] = $stmtSteps->fetchAll(PDO::FETCH_ASSOC);

        // Format the date fields as dd-MMM-yyyy
        $permit['issue_date'] = date('d-M-Y', strtotime($permit['issue_date']));
        $permit['expiry_date'] = date('d-M-Y', strtotime($permit['expiry_date']));
    }

    echo json_encode(['success' => true, 'permits' => $permits]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>
