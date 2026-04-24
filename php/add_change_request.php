<?php
/* File: sheener/php/add_change_request.php */

// php/add_change_request.php
header('Content-Type: application/json');
require_once 'database.php';

$data = json_decode(file_get_contents('php://input'), true);

try {
    $database = new Database();
    $pdo = $database->getConnection();

    $query = "INSERT INTO change_requests (request_name, request_description, requested_by, assigned_to, event_id, status, compliance_reference, project_id) 
              VALUES (:request_name, :request_description, :requested_by, :assigned_to, :event_id, :status, :compliance_reference, :project_id)";

    $stmt = $pdo->prepare($query);
    $stmt->execute([
        ':request_name' => $data['request_name'],
        ':request_description' => $data['request_description'],
        ':requested_by' => $data['requested_by'],
        ':assigned_to' => $data['assigned_to'],
        ':event_id' => $data['event_id'],
        ':status' => 'Submitted',
        ':compliance_reference' => $data['compliance_reference'],
        ':project_id' => $data['project_id']
    ]);

    echo json_encode(["success" => true, "message" => "Change Request added successfully."]);
} catch (PDOException $e) {
    echo json_encode(["success" => false, "error" => "Database error: " . $e->getMessage()]);
}
?>
