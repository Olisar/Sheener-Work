<?php
/* File: sheener/php/get_waste_collection.php */

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: http://172.21.10.99");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}
require_once 'database.php';

if (isset($_GET['id'])) {
    $recordID = $_GET['id'];
    $db = new Database();
    $conn = $db->getConnection();
    
    try {
        $sql = "SELECT RecordID, WasteCategoryID, WasteSubCategoryID, Amount, UnitID, DisposalDate, Comments
                FROM WasteManagementRecord
                WHERE RecordID = :RecordID";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':RecordID', $recordID);
        $stmt->execute();
        $record = $stmt->fetch(PDO::FETCH_ASSOC);
        echo json_encode($record);
    } catch (PDOException $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['error' => 'No record ID provided.']);
}
?>
