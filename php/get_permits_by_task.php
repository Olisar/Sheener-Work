<?php
/* File: sheener/php/get_permits_by_task.php */

session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    die(json_encode(['success' => false, 'error' => 'Unauthorized access.']));
}

require_once 'database.php';
header('Content-Type: application/json');

$task_id = $_GET['task_id'] ?? '';

if (empty($task_id)) {
    echo json_encode(['success' => false, 'error' => 'Task ID required']);
    exit;
}

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    // First, get the permits
    $stmt = $pdo->prepare("
        SELECT 
            p.*, 
            CONCAT(issuer.FirstName, ' ', issuer.LastName) as issued_by_name,
            CONCAT(approver.FirstName, ' ', approver.LastName) as approved_by_name,
            t.task_name,
            t.task_description
        FROM permits p
        LEFT JOIN tasks t ON p.task_id = t.task_id
        LEFT JOIN people issuer ON p.issued_by = issuer.people_id
        LEFT JOIN people approver ON p.approved_by = approver.people_id
        WHERE p.task_id = ?
        ORDER BY p.issue_date DESC
    ");
    
    $stmt->execute([$task_id]);
    $permits = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Now, for each permit, get the steps
    foreach ($permits as &$permit) {
        $steps_stmt = $pdo->prepare("
            SELECT 
                step_number,
                step_description,
                hazard_description,
                control_description
            FROM permit_steps 
            WHERE permit_id = ?
            ORDER BY step_number ASC
        ");
        
        $steps_stmt->execute([$permit['permit_id']]);
        $permit['steps'] = $steps_stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    unset($permit);
    
    echo json_encode([
        'success' => true,
        'permits' => $permits
    ]);
    
} catch (PDOException $e) {
    error_log("Database error in get_permits_by_task.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
?>
