<?php
/* File: sheener/php/get_all_materials.php */

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: http://172.21.10.99");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}
require_once 'database.php'; // Ensure database connection is included

$materialId = isset($_GET['material_id']) ? intval($_GET['material_id']) : null;

try {
    $db = new Database();
    $pdo = $db->getConnection();

    // Auto-schema check for is_hazardous
    try {
        $pdo->query("SELECT is_hazardous FROM Materials LIMIT 1");
    } catch (PDOException $e) {
        $pdo->exec("ALTER TABLE Materials ADD COLUMN is_hazardous TINYINT(1) DEFAULT 0 AFTER MaterialType");
    }

    if ($materialId) {
        // Fetch a single material entry by ID
        $stmt = $pdo->prepare("SELECT MaterialID, MaterialName, MaterialType, is_hazardous, Description, UnitOfMeasure, StorageConditions FROM Materials WHERE MaterialID = :material_id");
        $stmt->execute([':material_id' => $materialId]);
        $material = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($material) {
            echo json_encode(["success" => true, "data" => $material]);
        } else {
            echo json_encode(["success" => false, "error" => "Material not found"]);
        }
    } else {
        // Prepare SQL query to fetch all materials
        $stmt = $pdo->prepare("SELECT MaterialID, MaterialName, MaterialType, is_hazardous, Description, UnitOfMeasure, StorageConditions FROM Materials");
        $stmt->execute(); // Execute query

        // Fetch results
        $materials = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Return response in JSON format
        echo json_encode(["success" => true, "data" => $materials]);
    }

} catch (PDOException $e) {
    echo json_encode(["success" => false, "error" => "Database error: " . $e->getMessage()]);
}
?>
