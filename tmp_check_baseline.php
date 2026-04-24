<?php
/* File: sheener/tmp_check_baseline.php */

require_once 'php/database.php';
$db = new Database();
$pdo = $db->getConnection();
echo "--- CO2 Tonnes for Feb 2018 (Baseline) ---\n";
$res = $pdo->query("SELECT * FROM kpi_values WHERE metric_id = 42 AND year = 2018 AND month_id = 2");
print_r($res->fetchAll());
?>
