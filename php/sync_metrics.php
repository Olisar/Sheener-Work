<?php
/* File: sheener/php/sync_metrics.php */

require_once 'd:/xampp/htdocs/sheener/php/database.php';

try {
    $db = new Database();
    $pdo = $db->getConnection();

    // Map of old keys to what we want to record
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
        
        ['Energy', 'Electricity Usage', 'electricity_change_percentage', 'kWh', 0],
        ['Energy', 'Gas Usage', 'gas_change_percentage', 'm3', 0],
        ['Energy', 'Total Energy', 'total_energy_change_percentage', 'kWh', 0],
        ['Energy', 'CO2 Emissions', 'co2_emission_change_percentage', 'kg', 0],
        ['Energy', 'CO2 vs Baseline', 'co2_em_change_baseline', '%', 1],
        
        ['Waste', 'Hazardous Waste', 'hazardous_waste_change_percentage', 'kg', 0],
        ['Waste', 'Non-Hazardous Waste', 'non_hazardous_waste_change_percentage', 'kg', 0],
        ['Waste', 'Water Usage', 'water_change_percentage', 'm3', 0]
    ];

    $stmt = $pdo->prepare("INSERT INTO kpi_metrics (category, name, key_name, unit, is_percentage) VALUES (?, ?, ?, ?, ?) 
                           ON DUPLICATE KEY UPDATE category=VALUES(category), name=VALUES(name), unit=VALUES(unit), is_percentage=VALUES(is_percentage)");
    foreach ($metrics as $m) {
        $stmt->execute($m);
    }

    echo "✅ KPI metrics keys synchronized with form!";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>
