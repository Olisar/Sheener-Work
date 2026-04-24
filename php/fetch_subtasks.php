<?php
/* File: sheener/php/fetch_subtasks.php */

include_once __DIR__ . '/database.php';

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

$response = ["success" => false, "subtasks" => []];

try {
    // Create a new database instance
    $db = new Database();
    $pdo = $db->getConnection();

    // Get task_id from the request
    $task_id = isset($_GET['task_id']) ? intval($_GET['task_id']) : 0;
    if ($task_id === 0) {
        throw new Exception("Invalid task_id");
    }

    // Prepare and execute the query
    $stmt = $pdo->prepare("SELECT * FROM Subtasks WHERE task_id = :task_id");
    $stmt->bindParam(':task_id', $task_id, PDO::PARAM_INT);
    $stmt->execute();

    // Fetch subtasks
    $subtasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Return data
    $response["success"] = true;
    $response["subtasks"] = $subtasks;
} catch (PDOException $e) {
    $response["error"] = "Database query failed: " . $e->getMessage();
} catch (Exception $e) {
    $response["error"] = $e->getMessage();
}

// Output JSON
echo json_encode($response);
?>
