<?php
/* File: sheener/php/get_all_energy.php */

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: http://172.21.10.99");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once 'database.php';

$energyId = isset($_GET['energy_id']) ? intval($_GET['energy_id']) : null;

try {
    $database = new Database();
    $pdo = $database->getConnection();

    if ($energyId) {
        // Fetch a single energy entry by ID
        $query = "
            SELECT 
                e.EnergyID,
                e.EnergyName,
                e.EnergyTypeID,
                et.EnergyTypeName AS EnergyType,
                e.Description,
                e.Examples
            FROM 
                Energy e
            LEFT JOIN 
                EnergyType et ON e.EnergyTypeID = et.EnergyTypeID
            WHERE 
                e.EnergyID = :energy_id
        ";
        $stmt = $pdo->prepare($query);
        $stmt->execute([':energy_id' => $energyId]);
        $energy = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($energy) {
            echo json_encode(["success" => true, "data" => $energy]);
        } else {
            echo json_encode(["success" => false, "error" => "Energy entry not found"]);
        }
    } else {
        // Fetch all energy entries
        $query = "
            SELECT 
                e.EnergyID,
                e.EnergyName,
                et.EnergyTypeName AS EnergyType,
                e.Description,
                e.Examples
            FROM 
                Energy e
            LEFT JOIN 
                EnergyType et ON e.EnergyTypeID = et.EnergyTypeID
        ";
        $stmt = $pdo->query($query);
        $energyEntries = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($energyEntries) {
            echo json_encode(["success" => true, "data" => $energyEntries]);
        } else {
            echo json_encode(["success" => false, "error" => "No energy entries found"]);
        }
    }
} catch (PDOException $e) {
    echo json_encode(["success" => false, "error" => "Database error: " . $e->getMessage()]);
}
?>
