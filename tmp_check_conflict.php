<?php
/* File: sheener/tmp_check_conflict.php */

require_once 'php/database.php';
$db = new Database();
$pdo = $db->getConnection();

echo "--- Record id 659 ---\n";
$res = $pdo->query("SELECT * FROM kpi_values WHERE id = 659");
print_r($res->fetchAll());

echo "\n--- Potential Conflicting Record (Metric 15, Year 2026, Month 2) ---\n";
$res = $pdo->query("SELECT * FROM kpi_values WHERE metric_id = 15 AND year = 2026 AND month_id = 2");
print_r($res->fetchAll());
?>
