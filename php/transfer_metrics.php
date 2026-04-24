<?php
/* File: sheener/php/transfer_metrics.php */

require_once 'php/database.php';

try {
    $db = new Database();
    $pdo = $db->getConnection();

    // The specific list provided by the user
    $metrics_to_transfer = [
        1 => 'ehs_ncr',
        2 => 'sor',
        3 => 'days_lost',
        4 => 'action_raised',
        5 => 'first_aid',
        6 => 'action_closed_percentage',
        7 => 'hsa',
        8 => 'epa',
        9 => 'people_trained',
        10 => 'safety_meeting',
        16 => 'hazardous_waste',
        17 => 'non_hazardous_waste',
        18 => 'water_usage',
        34 => 'hazardous_waste_change_percentage',
        35 => 'non_hazardous_waste_change_percentage',
        36 => 'water_change_percentage',
        39 => 'non_hazardous_reading',
        40 => 'hazardous_reading',
        41 => 'water_reading'
    ];

    echo "Starting transfer from kpi_data to kpi_values...\n";

    // 1. Get all data from old table
    $stmt = $pdo->query("SELECT * FROM kpi_data");
    $old_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Found " . count($old_data) . " rows in kpi_data.\n";

    // 2. Check which columns actually exist in kpi_data to avoid errors
    $res = $pdo->query('SHOW COLUMNS FROM kpi_data');
    $existing_columns = $res->fetchAll(PDO::FETCH_COLUMN);

    $pdo->beginTransaction();
    
    // Using redundant parameters for PDO emulation = false support
    $upsert_stmt = $pdo->prepare("INSERT INTO kpi_values (metric_id, year, month_id, actual_value) 
                                 VALUES (:metric_id, :year, :month_id, :value)
                                 ON DUPLICATE KEY UPDATE actual_value = :value_update");

    $count = 0;
    $skipped_metrics = [];

    foreach ($old_data as $row) {
        foreach ($metrics_to_transfer as $id => $key) {
            // Check if column exists IN OLD TABLE and has data
            if (in_array($key, $existing_columns)) {
                if (isset($row[$key]) && $row[$key] !== '' && $row[$key] !== null) {
                    $upsert_stmt->execute([
                        ':metric_id' => $id,
                        ':year' => $row['year'],
                        ':month_id' => $row['month_id'],
                        ':value' => $row[$key],
                        ':value_update' => $row[$key]
                    ]);
                    $count++;
                }
            } else {
                if (!in_array($key, $skipped_metrics)) {
                    $skipped_metrics[] = $key;
                }
            }
        }
    }

    $pdo->commit();
    echo "✅ Transfer completed: $count metric values inserted/updated.\n";
    
    if (!empty($skipped_metrics)) {
        echo "⚠️ Note: The following requested keys were not found in kpi_data table:\n - " . implode("\n - ", $skipped_metrics) . "\n";
    }

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
