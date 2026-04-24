<?php
/* File: sheener/php/insert_task.php */

include 'database.php';
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: http://172.21.10.99");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}
$data = json_decode(file_get_contents("php://input"), true);

$task_name = $data['task_name'];
$task_description = $data['task_description'];
$due_date = $data['due_date'];
$priority = $data['priority'];
$status = $data['status'];

$sql = "INSERT INTO Tasks (task_name, task_description, due_date, priority, status) VALUES (?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sssss", $task_name, $task_description, $due_date, $priority, $status);

$response = [];
if ($stmt->execute()) {
    $response["success"] = true;
    $response["task_id"] = $stmt->insert_id;
} else {
    $response["success"] = false;
    $response["error"] = $stmt->error;
}

echo json_encode($response);
$stmt->close();
$conn->close();
