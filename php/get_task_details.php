<?php
/* File: sheener/php/get_task_details.php */

// php/get_task_details.php
session_start();
require_once 'database.php';

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: http://172.21.10.99");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

if (empty($_GET['task_id']) || !ctype_digit($_GET['task_id'])) {
    echo json_encode(['success' => false, 'error' => 'Task ID required']);
    exit;
}

$taskId = (int)$_GET['task_id'];

try {
    $db  = new Database();
    $pdo = $db->getConnection();

    $sql = "
        SELECT
            t.task_id,
            t.task_name,
            t.task_description,
            DATE(COALESCE(t.start_date, t.due_date, t.finish_date, t.created_date, t.created_at)) AS task_date
        FROM tasks t
        WHERE t.task_id = :id
        LIMIT 1
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $taskId]);
    $task = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($task) {
        echo json_encode(['success' => true, 'task' => $task]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Task not found']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
