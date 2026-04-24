<?php
/* File: sheener/php/delete_material.php */

/*  Sheener / php / delete_material.php  */
error_reporting(E_ALL);
ini_set('display_errors', 0);
require_once 'database.php';
header('Content-Type: application/json');

$materialId = isset($_GET['material_id']) ? intval($_GET['material_id']) : null;

if (!$materialId) {
    echo json_encode(['success' => false, 'error' => 'Missing required Material ID']);
    exit;
}

try {
    $db = new Database();
    $pdo = $db->getConnection();

    $query = "DELETE FROM Materials WHERE MaterialID = :material_id";
    $stmt = $pdo->prepare($query);
    $result = $stmt->execute([':material_id' => $materialId]);

    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Material deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to delete material']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>
