<?php
/* File: sheener/php/delete_change_control.php */

// php/delete_change_control.php
header('Content-Type: application/json');
require_once 'database.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : null;

try {
    if (!$id) throw new Exception('Missing Change Control ID');

    $database = new Database();
    $pdo = $database->getConnection();

    $query = "DELETE FROM changecontrol WHERE id = :id";
    $stmt = $pdo->prepare($query);
    $stmt->execute([':id' => $id]);

    echo json_encode(["success" => true, "message" => "Change Control deleted successfully."]);
} catch (Exception $e) {
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}
?>
