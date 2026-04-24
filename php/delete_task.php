<?php
/* File: sheener/php/delete_task.php */

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

$taskId = isset($_POST['task_id']) ? intval($_POST['task_id']) : 0;

if ($taskId > 0) {
    try {
        $database = new Database();
        $pdo = $database->getConnection();

        $query = "DELETE FROM tasks WHERE task_id = :task_id";
        $stmt = $pdo->prepare($query);
        $stmt->execute([':task_id' => $taskId]);

        if ($stmt->rowCount() > 0) {
            echo json_encode(["success" => true]);
        } else {
            echo json_encode(["success" => false, "error" => "No task found with the specified ID"]);
        }
    } catch (PDOException $e) {
        echo json_encode(["success" => false, "error" => "Database error: " . $e->getMessage()]);
    }
} else {
    echo json_encode(["success" => false, "error" => "Invalid task ID"]);
}
?>
