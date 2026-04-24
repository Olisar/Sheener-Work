<?php
/* File: sheener/php/save_KPI_EHS.php */

header('Content-Type: application/json');
require_once 'database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'error' => 'No data provided']);
    exit;
}

$year = (int)($input['year'] ?? date('Y'));
$month_id = (int)($input['month_id'] ?? date('n'));

try {
    $database = new Database();
    $pdo = $database->getConnection();
    $pdo->beginTransaction();

    // 1. Get all metric mappings
    $stmt = $pdo->query("SELECT id, key_name FROM kpi_metrics");
    $metrics = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    // 2. Save each KPI value provided in input
    $upsert_query = "INSERT INTO kpi_values (metric_id, year, month_id, actual_value) 
                     VALUES (:metric_id, :year, :month_id, :value)
                     ON DUPLICATE KEY UPDATE actual_value = :update_value";
    
    $stmt = $pdo->prepare($upsert_query);

    foreach ($metrics as $metric_id => $key_name) {
        if (isset($input[$key_name])) {
            $value = (float)$input[$key_name];
            $stmt->execute([
                ':metric_id' => $metric_id,
                ':year' => $year,
                ':month_id' => $month_id,
                ':value' => $value,
                ':update_value' => $value
            ]);
        }
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Data saved successfully in normalized format']);

} catch (Exception $e) {
    if (isset($pdo)) $pdo->rollBack();
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>
