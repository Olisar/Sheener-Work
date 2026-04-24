<?php
/* File: sheener/php/get_all_tasks.php */

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: http://172.21.10.99");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once 'database.php';



$taskId = isset($_GET['task_id']) ? intval($_GET['task_id']) : null;

function formatTaskDates(&$task) {
    // Format each date field if present
    $dateFields = ['start_date','finish_date','due_date','created_date','updated_date'];
    foreach ($dateFields as $field) {
        if (!empty($task[$field])) {
            $task[$field] = date('d-M-Y', strtotime($task[$field]));
        }
    }
}

try {
    $database = new Database();
    $pdo = $database->getConnection();

    if ($taskId) {
        $query = "
SELECT 
    t.task_id,
    t.task_name,
    t.task_description,
    t.task_type,
    t.start_date,
    t.finish_date,
    t.priority,
    t.status,
    t.due_date,
    t.created_date,  
    t.updated_date,
    t.department_id,          -- ADD THIS
    d.DepartmentName,
    t.assigned_to,            -- ADD THIS
    CONCAT(p.FirstName, ' ', p.LastName) as assigned_name
FROM 
    tasks t
LEFT JOIN 
    departments d ON t.department_id = d.department_id
LEFT JOIN 
    people p ON t.assigned_to = p.people_id
WHERE 
    t.task_id = :task_id
        ";
        $stmt = $pdo->prepare($query);
        $stmt->execute([':task_id' => $taskId]);
        $task = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($task) {
            formatTaskDates($task);
            echo json_encode(["success" => true, "data" => $task]);
        } else {
            echo json_encode(["success" => false, "error" => "Task not found"]);
        }
    } else {
        $query = "
SELECT 
    t.task_id,
    t.task_name,
    t.task_description,
    t.task_type,
    t.start_date,
    t.finish_date,
    t.priority,
    t.status,
    t.due_date,
    t.created_date,  
    t.updated_date,
    t.department_id,          -- ADD THIS
    d.DepartmentName,
    t.assigned_to,            -- ADD THIS
    CONCAT(p.FirstName, ' ', p.LastName) as assigned_name
FROM 
    tasks t
LEFT JOIN 
    departments d ON t.department_id = d.department_id
LEFT JOIN 
    people p ON t.assigned_to = p.people_id

        ";
        $stmt = $pdo->query($query);
        $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if ($tasks) {
            foreach ($tasks as &$task) {
                formatTaskDates($task);
            }
            echo json_encode(["success" => true, "data" => $tasks]);
        } else {
            echo json_encode(["success" => false, "error" => "No tasks found"]);
        }
    }
} catch (PDOException $e) {
    echo json_encode(["success" => false, "error" => "Database error: " . $e->getMessage()]);
}
?>
