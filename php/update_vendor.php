<?php
/* File: sheener/php/update_vendor.php */

error_reporting(0);
ini_set('display_errors', 0);

require_once 'database.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['company_id'])) {
    echo json_encode(['success' => false, 'error' => 'Missing required Vendor ID']);
    exit;
}

try {
    $database = new Database();
    $pdo = $database->getConnection();

    $query = "UPDATE vendor SET 
              CompanyName = :CompanyName,
              Address = :Address,
              City = :City,
              State = :State,
              ZipCode = :ZipCode,
              Phone = :Phone,
              Email = :Email,
              Website = :Website,
              IsActive = :IsActive
              WHERE company_id = :company_id";
    
    $stmt = $pdo->prepare($query);
    $result = $stmt->execute([
        ':company_id' => $data['company_id'],
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
        echo json_encode(['success' => true, 'message' => 'Vendor updated successfully']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to update vendor']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>
