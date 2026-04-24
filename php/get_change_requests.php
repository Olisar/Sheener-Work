<?php
/* File: sheener/php/get_change_requests.php */

// php/get_change_requests.php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: http://172.21.10.99");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}
require_once 'database.php';

$changeControlId = isset($_GET['change_control_id']) ? intval($_GET['change_control_id']) : null;

try {
    $database = new Database();
    $pdo = $database->getConnection();

    $query = "SELECT * FROM change_requests WHERE event_id = :change_control_id";
    $stmt = $pdo->prepare($query);
    $stmt->execute([':change_control_id' => $changeControlId]);

    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(["success" => true, "data" => $requests]);
} catch (PDOException $e) {
    echo json_encode(["success" => false, "error" => "Database error: " . $e->getMessage()]);
}
?>
