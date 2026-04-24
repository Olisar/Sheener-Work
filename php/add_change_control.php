<?php
/* File: sheener/php/add_change_control.php */

// php/add_change_control.php
header('Content-Type: application/json');
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    die(json_encode(['success' => false, 'error' => 'Unauthorized access.']));
}

require_once 'database.php';

$data = json_decode(file_get_contents('php://input'), true);

try {
    $database = new Database();
    $pdo = $database->getConnection();

    $query = "INSERT INTO changecontrol (
        target_date, impacted_sites, market, title, change_from, change_to, 
        change_type, justification, regulatory_approval, status,
        product_details, combination_product, material_component, 
        document_type_details, risk_assessment, visual_aide, 
        logbooks_impact, rf_smart_impact, training_required, training_type
    ) VALUES (
        :target_date, :impacted_sites, :market, :title, :change_from, :change_to, 
        :change_type, :justification, :regulatory_approval, :status,
        :product_details, :combination_product, :material_component, 
        :document_type_details, :risk_assessment, :visual_aide, 
        :logbooks_impact, :rf_smart_impact, :training_required, :training_type
    )";

    $stmt = $pdo->prepare($query);
    $stmt->execute([
        ':target_date' => $data['target_date'],
        ':impacted_sites' => $data['impacted_sites'],
        ':market' => $data['market'],
        ':title' => $data['title'],
        ':change_from' => $data['change_from'],
        ':change_to' => $data['change_to'],
        ':change_type' => $data['change_type'],
        ':justification' => $data['justification'],
        ':regulatory_approval' => $data['regulatory_approval'],
        ':status' => 'Pending',
        ':product_details' => $data['product_details'] ?? '',
        ':combination_product' => $data['combination_product'] ?? 'No',
        ':material_component' => $data['material_component'] ?? '',
        ':document_type_details' => $data['document_type_details'] ?? '',
        ':risk_assessment' => $data['risk_assessment'] ?? '',
        ':visual_aide' => $data['visual_aide'] ?? 'No',
        ':logbooks_impact' => $data['logbooks_impact'] ?? '',
        ':rf_smart_impact' => $data['rf_smart_impact'] ?? 'No',
        ':training_required' => $data['training_required'] ?? 'No',
        ':training_type' => $data['training_type'] ?? 'Classroom'
    ]);

    echo json_encode(["success" => true, "message" => "Change Control added successfully."]);
} catch (PDOException $e) {
    echo json_encode(["success" => false, "error" => "Database error: " . $e->getMessage()]);
}
?>
