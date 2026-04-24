<?php
/* File: sheener/php/create_equipment.php */

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

    // Support both JSON and form data
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data) {
        // Fallback to POST form data
        $data = $_POST;
    }

    // Retrieve and sanitize inputs
    $item_name = isset($data['item_name']) ? trim($data['item_name']) : '';
    $equipment_type = isset($data['equipment_type']) ? trim($data['equipment_type']) : 'General';
    $serial_number = isset($data['serial_number']) && $data['serial_number'] !== '' ? trim($data['serial_number']) : null;
    $location = isset($data['location']) && $data['location'] !== '' ? trim($data['location']) : null;
    $status = isset($data['status']) ? trim($data['status']) : 'Active';
    $next_inspection_date = isset($data['next_inspection_date']) && $data['next_inspection_date'] !== '' ? trim($data['next_inspection_date']) : null;
    $responsible_department = isset($data['responsible_department']) && $data['responsible_department'] !== '' ? trim($data['responsible_department']) : null;
    $responsible_person_id = isset($data['responsible_person_id']) && $data['responsible_person_id'] !== '' ? intval($data['responsible_person_id']) : null;

    // Validate required fields
    if (empty($item_name)) {
        echo json_encode([
            "success" => false,
            "error" => "Item name is required"
        ]);
        exit;
    }

    // Validate status enum
    $validStatuses = ['Active', 'Inactive', 'Maintenance'];
    if (!in_array($status, $validStatuses)) {
        $status = 'Active';
    }

    // Prepare the INSERT query
    $query = "INSERT INTO equipment (
                item_name,
                equipment_type,
                serial_number,
                location,
                status,
                next_inspection_date,
                responsible_department,
                responsible_person_id,
                created_at,
                updated_at
              ) VALUES (
                :item_name,
                :equipment_type,
                :serial_number,
                :location,
                :status,
                :next_inspection_date,
                :responsible_department,
                :responsible_person_id,
                NOW(),
                NOW()
              )";
              
    $stmt = $pdo->prepare($query);
    
    // Bind parameters
    $stmt->bindValue(':item_name', $item_name);
    $stmt->bindValue(':equipment_type', $equipment_type);
    $stmt->bindValue(':serial_number', $serial_number, PDO::PARAM_NULL);
    $stmt->bindValue(':location', $location, PDO::PARAM_NULL);
    $stmt->bindValue(':status', $status);
    $stmt->bindValue(':next_inspection_date', $next_inspection_date, PDO::PARAM_NULL);
    $stmt->bindValue(':responsible_department', $responsible_department, PDO::PARAM_NULL);
    
    if ($responsible_person_id !== null) {
        $stmt->bindValue(':responsible_person_id', $responsible_person_id, PDO::PARAM_INT);
    } else {
        $stmt->bindValue(':responsible_person_id', null, PDO::PARAM_NULL);
    }
    
    $stmt->execute();

    // Get the newly created equipment ID
    $equipment_id = $pdo->lastInsertId();

    echo json_encode([
        "success" => true,
        "message" => "Equipment created successfully",
        "equipment_id" => $equipment_id
    ]);

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

