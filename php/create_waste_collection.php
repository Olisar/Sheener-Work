<?php
/* File: sheener/php/create_waste_collection.php */

error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');
require_once 'database.php';

$db = new Database();
$conn = $db->getConnection();

$wasteCategoryID   = isset($_POST['WasteCategoryID']) ? $_POST['WasteCategoryID'] : null;
$wasteSubCategoryID = isset($_POST['WasteSubCategoryID']) ? $_POST['WasteSubCategoryID'] : null;
$amount            = isset($_POST['Amount']) ? $_POST['Amount'] : null;
$unitID            = isset($_POST['UnitID']) ? $_POST['UnitID'] : null;
$disposalDate      = isset($_POST['DisposalDate']) ? $_POST['DisposalDate'] : null;
$comments          = isset($_POST['Comments']) ? $_POST['Comments'] : '';

try {
    $sql = "INSERT INTO WasteManagementRecord (WasteCategoryID, WasteSubCategoryID, Amount, UnitID, DisposalDate, Comments) 
            VALUES (:WasteCategoryID, :WasteSubCategoryID, :Amount, :UnitID, :DisposalDate, :Comments)";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':WasteCategoryID', $wasteCategoryID);
    $stmt->bindParam(':WasteSubCategoryID', $wasteSubCategoryID);
    $stmt->bindParam(':Amount', $amount);
    $stmt->bindParam(':UnitID', $unitID);
    $stmt->bindParam(':DisposalDate', $disposalDate);
    $stmt->bindParam(':Comments', $comments);
    $stmt->execute();
    
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
