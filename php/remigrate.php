<?php
/* File: sheener/php/remigrate.php */

require_once 'd:/xampp/htdocs/sheener/php/database.php';

try {
    $db = new Database();
    $pdo = $db->getConnection();

    echo "Cleaning up kpi_values for fresh migration...\n";
    $pdo->exec("DELETE FROM kpi_values");

    // 1. Get all data from old table
    $stmt = $pdo->query("SELECT * FROM kpi_data");
    $old_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Found " . count($old_data) . " records in kpi_data.\n";

    // 2. Get metric mapping
    $stmt = $pdo->query("SELECT id, key_name FROM kpi_metrics");
    $metrics = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    echo "Found " . count($metrics) . " metrics defined.\n";

    $pdo->beginTransaction();
    $stmt = $pdo->prepare("INSERT INTO kpi_values (metric_id, year, month_id, actual_value) VALUES (?, ?, ?, ?)
                          ON DUPLICATE KEY UPDATE actual_value = VALUES(actual_value)");

    $inserted_count = 0;
    foreach ($old_data as $row) {
        $year = (int)$row['year'];
        $month = (int)$row['month_id'];
        
        if ($month == 0) {
            echo "Skipping row ID {$row['id']} because month_id is 0\n";
            continue;
        }

        foreach ($metrics as $metric_id => $key) {
            if (isset($row[$key]) && $row[$key] !== '') {
                $stmt->execute([$metric_id, $year, $month, $row[$key]]);
                $inserted_count++;
            }
        }
    }

    $pdo->commit();
    echo "✅ Migration completed: $inserted_count metric values inserted.\n";

    // Check distinct months now
    $stmt = $pdo->query("SELECT DISTINCT year, month_id FROM kpi_values");
    $distinct = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Distinct months in kpi_values: " . count($distinct) . "\n";
    foreach($distinct as $d) {
        echo " - {$d['year']}-{$d['month_id']}\n";
    }

} catch (Exception $e) {
    if (isset($pdo)) $pdo->rollBack();
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
