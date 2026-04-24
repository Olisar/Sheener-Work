<?php
/* File: sheener/php/delete_vendor.php */

error_reporting(0);
ini_set('display_errors', 0);

require_once 'database.php';

header('Content-Type: application/json');

$vendorId = isset($_GET['vendor_id']) ? intval($_GET['vendor_id']) : null;

if (!$vendorId) {
    echo json_encode(['success' => false, 'error' => 'Missing required Vendor ID']);
    exit;
}

try {
    $database = new Database();
    $pdo = $database->getConnection();

    $query = "DELETE FROM vendor WHERE company_id = :vendor_id";
    $stmt = $pdo->prepare($query);
    $result = $stmt->execute([':vendor_id' => $vendorId]);

    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Vendor deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to delete vendor']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>
