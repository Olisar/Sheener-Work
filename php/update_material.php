<?php
/* File: sheener/php/update_material.php */

/*  Sheener / php / update_material.php  */
error_reporting(E_ALL);
ini_set('display_errors', 0);
require_once 'database.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['MaterialID'])) {
    echo json_encode(['success' => false, 'error' => 'Missing required Material ID']);
    exit;
}

try {
    $db = new Database();
    $pdo = $db->getConnection();

    $query = "UPDATE Materials SET 
                MaterialName = :MaterialName,
                MaterialType = :MaterialType,
                is_hazardous = :is_hazardous,
                Description = :Description,
                UnitOfMeasure = :UnitOfMeasure,
                StorageConditions = :StorageConditions
              WHERE MaterialID = :MaterialID";
    
    $stmt = $pdo->prepare($query);
    $result = $stmt->execute([
        ':MaterialID' => $data['MaterialID'],
        ':MaterialName' => $data['MaterialName'],
        ':MaterialType' => $data['MaterialType'] ?? null,
        ':is_hazardous' => isset($data['is_hazardous']) ? ($data['is_hazardous'] ? 1 : 0) : 0,
        ':Description' => $data['Description'] ?? null,
        ':UnitOfMeasure' => $data['UnitOfMeasure'] ?? null,
        ':StorageConditions' => $data['StorageConditions'] ?? null
    ]);

    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Material updated successfully']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to update material']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>
