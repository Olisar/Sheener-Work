<?php
/* File: sheener/php/delete_energy.php */

header('Content-Type: application/json');

require_once 'database.php';

$energyId = isset($_GET['energy_id']) ? intval($_GET['energy_id']) : null;

if (!$energyId) {
    echo json_encode(["success" => false, "error" => "Energy ID is required"]);
    exit;
}

try {
    $database = new Database();
    $pdo = $database->getConnection();

    $query = "DELETE FROM Energy WHERE EnergyID = :energy_id";
    $stmt = $pdo->prepare($query);
    $stmt->execute([':energy_id' => $energyId]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false, "error" => "Energy entry not found"]);
    }
} catch (PDOException $e) {
    echo json_encode(["success" => false, "error" => "Database error: " . $e->getMessage()]);
}
?>
