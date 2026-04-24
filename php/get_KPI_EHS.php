<?php
// file name sheener/php/get_KPI_EHS.php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: http://172.21.10.99");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once 'database.php';

$year = isset($_GET['year']) ? (int)$_GET['year'] : 2024;

try {
    $database = new Database();
    $pdo = $database->getConnection();

    $query = "SELECT * FROM kpi_data WHERE year = :year";
    $stmt = $pdo->prepare($query);
    $stmt->execute([':year' => $year]);
    $kpi_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(["success" => true, "data" => $kpi_data]);
} catch (PDOException $e) {
    echo json_encode(["success" => false, "error" => "Database error: " . $e->getMessage()]);
}
?>
