<?php
/* File: sheener/php/create_material.php */

/*  Sheener / php / create_material.php  */
error_reporting(E_ALL);
ini_set('display_errors', 0);
require_once 'database.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['MaterialName'])) {
    echo json_encode(['success' => false, 'error' => 'Missing required Material Name']);
    exit;
}

try {
    $db = new Database();
    $pdo = $db->getConnection();

    $query = "INSERT INTO Materials (MaterialName, MaterialType, is_hazardous, Description, UnitOfMeasure, StorageConditions) 
              VALUES (:MaterialName, :MaterialType, :is_hazardous, :Description, :UnitOfMeasure, :StorageConditions)";
    
    $stmt = $pdo->prepare($query);
    $result = $stmt->execute([
        ':MaterialName' => $data['MaterialName'],
        ':MaterialType' => $data['MaterialType'] ?? null,
        ':is_hazardous' => isset($data['is_hazardous']) ? ($data['is_hazardous'] ? 1 : 0) : 0,
        ':Description' => $data['Description'] ?? null,
        ':UnitOfMeasure' => $data['UnitOfMeasure'] ?? null,
        ':StorageConditions' => $data['StorageConditions'] ?? null
    ]);

    if ($result) {
        $id = $pdo->lastInsertId();
        echo json_encode(['success' => true, 'message' => 'Material created successfully', 'material_id' => $id]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to create material']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>
