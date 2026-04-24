<?php
/* File: sheener/php/kpi_setup_normalized.php */

require_once 'd:/xampp/htdocs/sheener/php/database.php';

try {
    $db = new Database();
    $pdo = $db->getConnection();

    // 1. Create kpi_metrics table
    $pdo->exec("CREATE TABLE IF NOT EXISTS kpi_metrics (
        id INT AUTO_INCREMENT PRIMARY KEY,
        category VARCHAR(50) NOT NULL,
        name VARCHAR(100) NOT NULL,
        key_name VARCHAR(100) NOT NULL UNIQUE,
        unit VARCHAR(20),
        is_percentage TINYINT(1) DEFAULT 0
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // 2. Create kpi_values table
    $pdo->exec("CREATE TABLE IF NOT EXISTS kpi_values (
        id INT AUTO_INCREMENT PRIMARY KEY,
        metric_id INT NOT NULL,
        year INT NOT NULL,
        month_id INT NOT NULL,
        actual_value DECIMAL(15,2) DEFAULT 0,
        target_value DECIMAL(15,2) DEFAULT 0,
        UNIQUE KEY idx_unique_entry (metric_id, year, month_id),
        FOREIGN KEY (metric_id) REFERENCES kpi_metrics(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // 3. Seed initial metrics
    $metrics = [
        ['Health & Safety', 'NCR & Incident', 'ehs_ncr', 'count', 0],
        ['Health & Safety', 'Total of SOR', 'sor', 'count', 0],
        ['Health & Safety', 'Days Lost', 'days_lost', 'days', 0],
        ['Health & Safety', 'Action Raised', 'action_raised', 'count', 0],
        ['Health & Safety', 'First Aid', 'first_aid', 'count', 0],
        ['Health & Safety', 'SOR % Action Closed', 'action_closed_percentage', '%', 1],
        ['Health & Safety', 'HSA Inspections', 'hsa', 'count', 0],
        ['Health & Safety', 'EPA Inspections', 'epa', 'count', 0],
        ['Health & Safety', 'People Trained', 'people_trained', 'count', 0],
        ['Health & Safety', 'Safety Committee Meeting', 'safety_meeting', 'count', 0],
        
        ['Energy', 'Electricity Usage', 'electricity_usage', 'kWh', 0],
        ['Energy', 'Gas Usage', 'gas_usage', 'm3', 0],
        ['Energy', 'Total Energy', 'total_energy', 'kWh', 0],
        ['Energy', 'CO2 Emissions', 'co2_emissions', 'kg', 0],
        ['Energy', 'CO2 vs Baseline', 'co2_em_change_baseline', '%', 1],
        
        ['Waste', 'Hazardous Waste', 'hazardous_waste', 'kg', 0],
        ['Waste', 'Non-Hazardous Waste', 'non_hazardous_waste', 'kg', 0],
        ['Waste', 'Water Usage', 'water_usage', 'm3', 0]
    ];

    $stmt = $pdo->prepare("INSERT IGNORE INTO kpi_metrics (category, name, key_name, unit, is_percentage) VALUES (?, ?, ?, ?, ?)");
    foreach ($metrics as $m) {
        $stmt->execute($m);
    }

    echo "✅ Normalized KPI structure created successfully!";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>
