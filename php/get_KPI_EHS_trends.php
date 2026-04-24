<?php
/* File: sheener/php/get_KPI_EHS_trends.php */

header('Content-Type: application/json');
require_once 'database.php';

$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

try {
    $database = new Database();
    $pdo = $database->getConnection();

    // Fetch all values for the selected year
    $query = "SELECT v.month_id, m.key_name, v.actual_value 
              FROM kpi_values v
              JOIN kpi_metrics m ON v.metric_id = m.id
              WHERE v.year = :year
              ORDER BY v.month_id ASC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([':year' => $year]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Pivot results in PHP
    $pivoted = [];
    foreach ($results as $row) {
        $m = $row['month_id'];
        if (!isset($pivoted[$m])) {
            $pivoted[$m] = ['month_id' => $m];
        }
        $pivoted[$m][$row['key_name']] = $row['actual_value'];
    }

    echo json_encode(["success" => true, "data" => array_values($pivoted)]);
} catch (PDOException $e) {
    echo json_encode(["success" => false, "error" => "Database error: " . $e->getMessage()]);
}
?>
