<?php
/* File: sheener/php/update_change_request.php */

// php/update_change_request.php
header('Content-Type: application/json');
require_once 'database.php';

$data = json_decode(file_get_contents('php://input'), true);

try {
    $database = new Database();
    $pdo = $database->getConnection();

    $query = "UPDATE change_requests SET request_name = :request_name, request_description = :request_description, requested_by = :requested_by, assigned_to = :assigned_to, status = :status, compliance_reference = :compliance_reference, project_id = :project_id WHERE change_request_id = :change_request_id";

    $stmt = $pdo->prepare($query);
    $stmt->execute([
        ':change_request_id' => $data['change_request_id'],
        ':request_name' => $data['request_name'],
        ':request_description' => $data['request_description'],
        ':requested_by' => $data['requested_by'],
        ':assigned_to' => $data['assigned_to'],
        ':status' => $data['status'],
        ':compliance_reference' => $data['compliance_reference'],
        ':project_id' => $data['project_id']
    ]);

    echo json_encode(["success" => true, "message" => "Change Request updated successfully."]);
} catch (PDOException $e) {
    echo json_encode(["success" => false, "error" => "Database error: " . $e->getMessage()]);
}
?>
