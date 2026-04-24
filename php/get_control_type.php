<?php
/* File: sheener/php/get_control_type.php */

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: http://172.21.10.99");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once 'database.php';

$controlTypeId = isset($_GET['control_type_id']) ? intval($_GET['control_type_id']) : null;

try {
    $database = new Database();
    $pdo = $database->getConnection();

    if ($controlTypeId) {
        $query = "SELECT control_type_id, type_name, description FROM control_types WHERE control_type_id = :control_type_id LIMIT 1";
        $stmt = $pdo->prepare($query);
        $stmt->execute([':control_type_id' => $controlTypeId]);
        $controlType = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($controlType) {
            echo json_encode(["success" => true, "type_name" => $controlType['type_name'], "data" => $controlType]);
        } else {
            echo json_encode(["success" => false, "error" => "Control type not found"]);
        }
    } else {
        echo json_encode(["success" => false, "error" => "Control type ID is required"]);
    }
} catch (PDOException $e) {
    echo json_encode(["success" => false, "error" => "Database error: " . $e->getMessage()]);
}
?>

