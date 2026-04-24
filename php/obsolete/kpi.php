<?php
/* File: sheener/php/obsolete/kpi.php */

header('Content-Type: application/json');

require_once 'kpi.php'; // Ensure this fetches and prepares $kpi_data
$kpi_data = $stmt->fetch(PDO::FETCH_ASSOC); // Replace with actual data fetching

$year = isset($_GET['year']) ? (int)$_GET['year'] : 2024;
$month_id = 12; // Assuming you want data for December

try {
    $database = new Database();
    $pdo = $database->getConnection();

    $query = "SELECT * FROM kpi_data WHERE year = :year AND month_id = :month_id";
    $stmt = $pdo->prepare($query);
    $stmt->execute([':year' => $year, ':month_id' => $month_id]);
    $kpi_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($kpi_data) {
        echo json_encode(["success" => true, "data" => $kpi_data]);
    } else {
        echo json_encode(["success" => false, "error" => "No KPI data available for Year: $year, Month: $month_id"]);
    }
} catch (PDOException $e) {
    echo json_encode(["success" => false, "error" => "Database error: " . $e->getMessage()]);
}
?>
