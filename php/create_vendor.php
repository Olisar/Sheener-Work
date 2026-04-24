<?php
/* File: sheener/php/create_vendor.php */

error_reporting(0);
ini_set('display_errors', 0);

require_once 'database.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['CompanyName'])) {
    echo json_encode(['success' => false, 'error' => 'Missing required Company Name']);
    exit;
}

try {
    $database = new Database();
    $pdo = $database->getConnection();

    $query = "INSERT INTO vendor (CompanyName, Address, City, State, ZipCode, Phone, Email, Website, IsActive) 
              VALUES (:CompanyName, :Address, :City, :State, :ZipCode, :Phone, :Email, :Website, :IsActive)";
    
    $stmt = $pdo->prepare($query);
    $result = $stmt->execute([
        ':CompanyName' => $data['CompanyName'],
        ':Address' => $data['Address'] ?? null,
        ':City' => $data['City'] ?? null,
        ':State' => $data['State'] ?? null,
        ':ZipCode' => $data['ZipCode'] ?? null,
        ':Phone' => $data['Phone'] ?? null,
        ':Email' => $data['Email'] ?? null,
        ':Website' => $data['Website'] ?? null,
        ':IsActive' => $data['IsActive'] ?? 1
    ]);

    if ($result) {
        $vendor_id = $pdo->lastInsertId();
        echo json_encode(['success' => true, 'message' => 'Vendor created successfully', 'vendor_id' => $vendor_id]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to create vendor']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>
