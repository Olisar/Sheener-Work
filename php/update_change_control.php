<?php
/* File: sheener/php/update_change_control.php */

// php/update_change_control.php
header('Content-Type: application/json');
require_once 'database.php';

$data = json_decode(file_get_contents('php://input'), true);

try {
    $database = new Database();
    $pdo = $database->getConnection();

    $query = "UPDATE changecontrol SET 
        target_date = :target_date, 
        impacted_sites = :impacted_sites, 
        market = :market, 
        title = :title, 
        change_from = :change_from, 
        change_to = :change_to, 
        change_type = :change_type, 
        justification = :justification, 
        regulatory_approval = :regulatory_approval, 
        status = :status,
        product_details = :product_details,
        combination_product = :combination_product,
        material_component = :material_component,
        document_type_details = :document_type_details,
        risk_assessment = :risk_assessment,
        visual_aide = :visual_aide,
        logbooks_impact = :logbooks_impact,
        rf_smart_impact = :rf_smart_impact,
        training_required = :training_required,
        training_type = :training_type
        WHERE id = :cc_id";

    $stmt = $pdo->prepare($query);
    $stmt->execute([
        ':cc_id' => $data['cc_id'],
        ':target_date' => $data['target_date'],
        ':impacted_sites' => $data['impacted_sites'],
        ':market' => $data['market'],
        ':title' => $data['title'],
        ':change_from' => $data['change_from'],
        ':change_to' => $data['change_to'],
        ':change_type' => $data['change_type'],
        ':justification' => $data['justification'],
        ':regulatory_approval' => $data['regulatory_approval'],
        ':status' => $data['status'],
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

    echo json_encode(["success" => true, "message" => "Change Control updated successfully."]);
} catch (PDOException $e) {
    echo json_encode(["success" => false, "error" => "Database error: " . $e->getMessage()]);
}
?>
