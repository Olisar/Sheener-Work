<?php
/* File: sheener/php/get_all_areas.php */

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: http://172.21.10.99");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// file name: Sheener/php/get_all_areas.php
require_once 'database.php';

$areaId = isset($_GET['area_id']) ? intval($_GET['area_id']) : null;

try {
    $database = new Database();
    $pdo = $database->getConnection();

    if ($areaId) {
        // Fetch a single area entry by ID
        $stmt = $pdo->prepare("SELECT area_id, area_name, area_type, description, location_code, is_active FROM areas WHERE area_id = :area_id");
        $stmt->execute([':area_id' => $areaId]);
        $area = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($area) {
            echo json_encode([
                "success" => true,
                "data" => $area
            ]);
        } else {
            echo json_encode([
                "success" => false,
                "error" => "Area not found"
            ]);
        }
    } else {
        // Fetch all areas
        $stmt = $pdo->prepare("SELECT area_id, area_name, area_type, description, location_code, is_active FROM areas WHERE is_active = 1 ORDER BY area_name ASC");
        $stmt->execute();
        $areas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            "success" => true,
            "data" => $areas
        ]);
    }

} catch (PDOException $e) {
    echo json_encode([
        "success" => false,
        "error" => "Database error: " . $e->getMessage()
    ]);
}
?>
