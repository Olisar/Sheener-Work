<?php
/* File: sheener/php/get_sub_permits.php */

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: http://172.21.10.99");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}
require_once 'database.php';

$permit_id = $_GET['permit_id'] ?? null;
$response = ['success' => false, 'sub_permits' => []];

if ($permit_id) {
    try {
        $database = new Database();
        $pdo = $database->getConnection();

        $query = "
            SELECT p.permit_id, p.permit_type, p.issue_date, p.expiry_date
            FROM sub_permits sp
            JOIN permits p ON sp.permit_id = p.permit_id
            WHERE sp.main_permit_id = :permit_id
        ";

        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':permit_id', $permit_id, PDO::PARAM_INT);
        $stmt->execute();

        $sub_permits = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $response['sub_permits'] = $sub_permits;
        $response['success'] = true;
    } catch (PDOException $e) {
        $response['error'] = "Database error: " . $e->getMessage();
    }
} else {
    $response['error'] = "Missing permit_id parameter.";
}

echo json_encode($response);
