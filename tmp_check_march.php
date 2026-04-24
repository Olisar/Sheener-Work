<?php
/* File: sheener/tmp_check_march.php */

require_once 'php/database.php';
$db = new Database();
$pdo = $db->getConnection();
echo "\n--- Data for March 2026 ---\n";
$res = $pdo->query("SELECT v.*, m.key_name FROM kpi_values v JOIN kpi_metrics m ON v.metric_id = m.id WHERE v.year = 2026 AND v.month_id = 3");
print_r($res->fetchAll());
?>
