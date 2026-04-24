<?php
/* File: sheener/php/get_all_equip.php */

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: http://172.21.10.99");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}
require_once 'database.php';

$equipmentId = isset($_GET['equipment_id']) ? intval($_GET['equipment_id']) : null;

try {
    $db = new Database();
    $pdo = $db->getConnection();

    if ($equipmentId) {
        // Fetch a single equipment entry by ID
        $stmt = $pdo->prepare("
            SELECT 
                equipment_id, item_name, equipment_type, serial_number, location, status, 
                next_inspection_date, responsible_department, responsible_person_id
            FROM equipment
            WHERE equipment_id = :equipment_id
        ");
        $stmt->execute([':equipment_id' => $equipmentId]);
        $equipment = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($equipment) {
            echo json_encode([
                "success" => true,
                "data" => $equipment
            ]);
        } else {
            echo json_encode([
                "success" => false,
                "error" => "Equipment not found"
            ]);
        }
    } else {
        // Adjust JOINs as needed for names from other tables (e.g., departments, persons)
        $stmt = $pdo->prepare("
            SELECT 
                equipment_id, item_name, equipment_type, serial_number, location, status, 
                next_inspection_date, responsible_department, responsible_person_id
            FROM equipment
            ORDER BY equipment_id DESC
        ");
        $stmt->execute();
        $equipments = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Optionally resolve responsible_person_name here if you JOIN persons
        // For now, set responsible_person_name to blank unless you add the JOIN

        foreach ($equipments as &$equip) {
            $equip['responsible_person_name'] = ''; // Placeholder for now
        }

        echo json_encode([
            "success" => true,
            "data" => $equipments
        ]);
    }
} catch (PDOException $e) {
    echo json_encode([
        "success" => false,
        "error" => "Database error: " . $e->getMessage()
    ]);
}
?>
