<?php
/* File: sheener/php/delete_equipment.php */

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: http://172.21.10.99");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once 'database.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();

    // Get equipment_id from GET parameter
    $equipment_id = isset($_GET['equipment_id']) ? intval($_GET['equipment_id']) : null;

    // Validate equipment_id
    if (!$equipment_id || $equipment_id <= 0) {
        echo json_encode([
            "success" => false,
            "error" => "Valid equipment ID is required"
        ]);
        exit;
    }

    // Prepare the DELETE query
    $query = "DELETE FROM equipment WHERE equipment_id = :equipment_id";
              
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(':equipment_id', $equipment_id, PDO::PARAM_INT);
    $stmt->execute();

    // Check if any row was affected
    if ($stmt->rowCount() > 0) {
        echo json_encode([
            "success" => true,
            "message" => "Equipment deleted successfully"
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "error" => "Equipment not found or already deleted"
        ]);
    }

} catch (PDOException $e) {
    echo json_encode([
        "success" => false,
        "error" => "Database error: " . $e->getMessage()
    ]);
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "error" => "Error: " . $e->getMessage()
    ]);
}
?>

