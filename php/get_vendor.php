<?php
/* File: sheener/php/get_vendor.php */

error_reporting(0);
ini_set('display_errors', 0);
ob_start();

require_once 'database.php';

ob_clean();
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$vendorId = isset($_GET['vendor_id']) ? intval($_GET['vendor_id']) : null;

try {
    $database = new Database();
    $pdo = $database->getConnection();

    if ($vendorId) {
        $query = "SELECT * FROM vendor WHERE company_id = :vendor_id";
        $stmt = $pdo->prepare($query);
        $stmt->execute([':vendor_id' => $vendorId]);
        $vendor = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($vendor) {
            echo json_encode(['success' => true, 'data' => $vendor]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Vendor not found']);
        }
    } else {
        $query = "SELECT * FROM vendor WHERE IsActive = 1 ORDER BY CompanyName ASC";
        $stmt = $pdo->query($query);
        $vendors = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'data' => $vendors]);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error: ' . $e->getMessage()]);
}
?>
