<?php
/* File: sheener/tmp_search_metric.php */

require_once 'php/database.php';
$db = new Database();
$pdo = $db->getConnection();
echo "--- Searching for ehs_ncr ---\n";
$res = $pdo->query("SELECT * FROM kpi_metrics WHERE key_name = 'ehs_ncr'");
print_r($res->fetchAll());
?>
