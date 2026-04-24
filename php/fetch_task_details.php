<?php
/* File: sheener/php/fetch_task_details.php */

include_once __DIR__ . '/database.php';

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

$response = ["success" => false];

try {
    // ✅ Create new database connection
    $db = new Database();
    $pdo = $db->getConnection();

    if (!isset($_GET['task_id']) || !is_numeric($_GET['task_id'])) {
        throw new Exception("Invalid task ID");
    }

    $task_id = intval($_GET['task_id']);

    // ✅ Query for task details
    $stmt = $pdo->prepare("SELECT task_id, task_name, status FROM tasks WHERE task_id = ?");
    $stmt->execute([$task_id]);

    $task = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($task) {
        $response["success"] = true;
        $response["task"] = $task;
    } else {
        throw new Exception("Task not found");
    }
} catch (Exception $e) {
    $response["error"] = $e->getMessage();
}

// ✅ Return JSON response
echo json_encode($response);
?>
