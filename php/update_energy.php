<?php
/* File: sheener/php/update_energy.php */

header('Content-Type: application/json');

require_once 'database.php';

$data = json_decode(file_get_contents('php://input'), true);

if (empty($data['EnergyID']) || empty($data['EnergyName']) || empty($data['EnergyTypeID']) || empty($data['Description']) || empty($data['Examples'])) {
    echo json_encode(["success" => false, "error" => "All fields are required"]);
    exit;
}

try {
    $database = new Database();
    $pdo = $database->getConnection();

    $query = "UPDATE Energy SET EnergyName = :energy_name, EnergyTypeID = :energy_type_id, Description = :description, Examples = :examples WHERE EnergyID = :energy_id";
    $stmt = $pdo->prepare($query);
    $stmt->execute([
        ':energy_name' => $data['EnergyName'],
        ':energy_type_id' => $data['EnergyTypeID'],
        ':description' => $data['Description'],
        ':examples' => $data['Examples'],
        ':energy_id' => $data['EnergyID']
    ]);

    echo json_encode(["success" => true]);
} catch (PDOException $e) {
    echo json_encode(["success" => false, "error" => "Database error: " . $e->getMessage()]);
}
?>
