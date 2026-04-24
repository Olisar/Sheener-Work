<?php
/* File: sheener/php/edit_waste_collection.php */

header('Content-Type: application/json');
require_once 'database.php';

$conn = getDatabaseConnection();

$recordID         = isset($_POST['RecordID']) ? $_POST['RecordID'] : null;
$wasteCategoryID  = isset($_POST['WasteCategoryID']) ? $_POST['WasteCategoryID'] : null;
$wasteSubCategoryID = isset($_POST['WasteSubCategoryID']) ? $_POST['WasteSubCategoryID'] : null;
$amount           = isset($_POST['Amount']) ? $_POST['Amount'] : null;
$unitID           = isset($_POST['UnitID']) ? $_POST['UnitID'] : null;
$disposalDate     = isset($_POST['DisposalDate']) ? $_POST['DisposalDate'] : null;
$comments         = isset($_POST['Comments']) ? $_POST['Comments'] : '';

try {
    $sql = "UPDATE WasteManagementRecord
            SET WasteCategoryID = :WasteCategoryID, 
                WasteSubCategoryID = :WasteSubCategoryID, 
                Amount = :Amount, 
                UnitID = :UnitID, 
                DisposalDate = :DisposalDate, 
                Comments = :Comments
            WHERE RecordID = :RecordID";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':WasteCategoryID', $wasteCategoryID);
    $stmt->bindParam(':WasteSubCategoryID', $wasteSubCategoryID);
    $stmt->bindParam(':Amount', $amount);
    $stmt->bindParam(':UnitID', $unitID);
    $stmt->bindParam(':DisposalDate', $disposalDate);
    $stmt->bindParam(':Comments', $comments);
    $stmt->bindParam(':RecordID', $recordID);
    $stmt->execute();
    
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
