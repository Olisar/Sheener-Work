<?php
/* File: sheener/php/get_change_controls.php */

header('Content-Type: application/json');
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    die(json_encode(['success' => false, 'error' => 'Unauthorized access.']));
}

header("Access-Control-Allow-Origin: http://172.21.10.99");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}
require_once 'database.php'; // Ensure correct DB connection

try {
    // Database connection
    $db = new Database();
    $pdo = $db->getConnection();

    // Fetch Change Controls and related tasks
    $query = "
        SELECT 
            cc.id AS cc_id, 
            cc.target_date,
            cc.impacted_sites,
            cc.market,
            cc.title,
            cc.change_from,
            cc.change_to,
            cc.change_type,
            cc.justification,
            cc.regulatory_approval,
            cc.status,
            cc.product_details,
            cc.combination_product,
            cc.material_component,
            cc.document_type_details,
            cc.risk_assessment,
            cc.visual_aide,
            cc.logbooks_impact,
            cc.rf_smart_impact,
            cc.training_required,
            cc.training_type,
            cc.created_at,
            t.task_id AS task_id, 
            t.task_name AS task_name, 
            t.status AS task_status
        FROM changecontrol cc
        LEFT JOIN tasks t ON cc.id = t.cc_id
        ORDER BY cc.target_date DESC;
    ";

    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $changeControls = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Return JSON response
    echo json_encode(["success" => true, "data" => $changeControls], JSON_PRETTY_PRINT);
} catch (Exception $e) {
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}
?>
