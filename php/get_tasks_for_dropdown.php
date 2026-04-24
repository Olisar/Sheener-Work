<?php
/* File: sheener/php/get_tasks_for_dropdown.php */

// php/get_tasks_for_dropdown.php
session_start();
require_once 'database.php';

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: http://172.21.10.99");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

try {
    $db = new Database();
    $pdo = $db->getConnection();

    $sql = "
        SELECT
            t.task_id,
            t.task_name,
            t.task_description,
            DATE(COALESCE(t.start_date, t.due_date, t.finish_date, t.created_date, t.created_at)) AS task_date
        FROM tasks t
        ORDER BY COALESCE(t.start_date, t.due_date, t.finish_date, t.created_date, t.created_at) DESC,
                 t.task_id DESC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    echo json_encode(['success' => true, 'tasks' => $tasks]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
