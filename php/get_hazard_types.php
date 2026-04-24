<?php
/* File: sheener/php/get_hazard_types.php */

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: http://172.21.10.99");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once 'database.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();

    $query = "SELECT hazard_type_id, type_name, description FROM hazard_type ORDER BY type_name";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $hazardTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(["success" => true, "data" => $hazardTypes]);
} catch (PDOException $e) {
    echo json_encode(["success" => false, "error" => "Database error: " . $e->getMessage()]);
}
?>
