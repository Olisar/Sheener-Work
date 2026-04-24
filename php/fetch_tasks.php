<?php
/* File: sheener/php/fetch_tasks.php */

include_once __DIR__ . '/database.php';

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: http://172.21.10.99");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}
error_reporting(E_ALL);
ini_set('display_errors', 1);

$response = ["success" => false, "tasks" => []];

try {
    // Create a new database instance
    $db = new Database();
    $pdo = $db->getConnection();

    // ✅ Ensure case-sensitive table name is correct
    $stmt = $pdo->prepare("SELECT task_id, task_name, status FROM tasks"); 
    $stmt->execute();
    
    // Fetch tasks as an associative array
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ✅ Ensure the response structure
    $response["success"] = true;
    $response["tasks"] = $tasks;
} catch (PDOException $e) {
    // ✅ Better error handling
    $response["error"] = "Database query failed: " . $e->getMessage();
}

// ✅ Return JSON response
echo json_encode($response);
?>
