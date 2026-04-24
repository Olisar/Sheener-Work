<?php
/* File: sheener/php/delete_change_request.php */

// php/delete_change_request.php
header('Content-Type: application/json');
require_once 'database.php';

$id = isset($_GET['change_request_id']) ? intval($_GET['change_request_id']) : null;

try {
    if (!$id) throw new Exception('Missing Change Request ID');

    $database = new Database();
    $pdo = $database->getConnection();

    $query = "DELETE FROM change_requests WHERE change_request_id = :id";
    $stmt = $pdo->prepare($query);
    $stmt->execute([':id' => $id]);

    echo json_encode(["success" => true, "message" => "Change Request deleted successfully."]);
} catch (Exception $e) {
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}
?>
