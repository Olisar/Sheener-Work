<?php
/* File: sheener/php/get_KPI_EHS_list.php */

header('Content-Type: application/json');
require_once 'database.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();

    // Get all records with some summary data
    $query = "SELECT v.year, v.month_id, 
              MAX(CASE WHEN m.key_name = 'ehs_ncr' THEN v.actual_value ELSE 0 END) as ncr_count,
              MAX(CASE WHEN m.key_name = 'sor' THEN v.actual_value ELSE 0 END) as sor_count
              FROM kpi_values v
              JOIN kpi_metrics m ON v.metric_id = m.id
              GROUP BY v.year, v.month_id
              ORDER BY v.year DESC, v.month_id DESC";
    
    $stmt = $pdo->query($query);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Map month_id to names
    $month_names = [
        1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
        5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
        9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
    ];

    $formatted_records = array_map(function($row) use ($month_names) {
        $row['month_name'] = $month_names[$row['month_id']] ?? 'Unknown';
        return $row;
    }, $records);

    echo json_encode(["success" => true, "data" => $formatted_records]);
} catch (PDOException $e) {
    echo json_encode(["success" => false, "error" => "Database error: " . $e->getMessage()]);
}
?>
