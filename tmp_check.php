<?php
/* File: sheener/tmp_check.php */

require_once 'php/database.php';
$db = new Database();
$pdo = $db->getConnection();
echo "--- Metrics ---\n";
$res = $pdo->query('SELECT * FROM kpi_metrics');
print_r($res->fetchAll());
echo "\n--- Summary of Feb 2026 in kpi_values ---\n";
$res = $pdo->query("SELECT v.*, m.key_name FROM kpi_values v JOIN kpi_metrics m ON v.metric_id = m.id WHERE v.year = 2026 AND v.month_id = 2");
print_r($res->fetchAll());
?>
