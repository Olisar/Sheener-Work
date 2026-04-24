<?php
/* File: sheener/php/create_energy.php */

header('Content-Type: application/json');

require_once 'database.php';

$data = json_decode(file_get_contents('php://input'), true);

if (empty($data['EnergyName']) || empty($data['EnergyTypeID']) || empty($data['Description']) || empty($data['Examples'])) {
    echo json_encode(["success" => false, "error" => "All fields are required"]);
    exit;
}

try {
    $database = new Database();
    $pdo = $database->getConnection();

    $query = "INSERT INTO Energy (EnergyName, EnergyTypeID, Description, Examples) VALUES (:energy_name, :energy_type_id, :description, :examples)";
    $stmt = $pdo->prepare($query);
    $stmt->execute([
        ':energy_name' => $data['EnergyName'],
        ':energy_type_id' => $data['EnergyTypeID'],
        ':description' => $data['Description'],
        ':examples' => $data['Examples']
    ]);

    echo json_encode(["success" => true]);
} catch (PDOException $e) {
    echo json_encode(["success" => false, "error" => "Database error: " . $e->getMessage()]);
}
?>
