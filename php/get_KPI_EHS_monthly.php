<?php
/* File: sheener/php/get_KPI_EHS_monthly.php */

header('Content-Type: application/json');
require_once 'database.php';

$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
$month_id = isset($_GET['month_id']) ? (int)$_GET['month_id'] : date('n');

try {
    $database = new Database();
    $pdo = $database->getConnection();

    // Current Year Data
    $query = "SELECT m.key_name, v.actual_value 
              FROM kpi_metrics m
              LEFT JOIN kpi_values v ON m.id = v.metric_id AND v.year = :year AND v.month_id = :month_id";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([':year' => $year, ':month_id' => $month_id]);
    $current_rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Previous Year Data
    $prev_year = $year - 1;
    $stmt->execute([':year' => $prev_year, ':month_id' => $month_id]);
    $prev_rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Baseline Data (2018) for CO2
    $stmt->execute([':year' => 2018, ':month_id' => $month_id]);
    $baseline_rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $data = [];
    foreach ($current_rows as $row) {
        if ($row['actual_value'] !== null) {
            $data[$row['key_name']] = $row['actual_value'];
        }
    }

    $prev_data = [];
    foreach ($prev_rows as $row) {
        if ($row['actual_value'] !== null) {
            $prev_data[$row['key_name']] = $row['actual_value'];
        }
    }

    $baseline_data = [];
    foreach ($baseline_rows as $row) {
        if ($row['actual_value'] !== null) {
            $baseline_data[$row['key_name']] = $row['actual_value'];
        }
    }

    echo json_encode([
        "success" => true, 
        "data" => $data, 
        "prev_year_data" => $prev_data,
        "baseline_data" => $baseline_data
    ]);
} catch (PDOException $e) {
    echo json_encode(["success" => false, "error" => "Database error: " . $e->getMessage()]);
}
?>
