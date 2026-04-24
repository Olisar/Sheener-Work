<?php
/* File: sheener/tmp_check_metrics.php */

require_once 'php/database.php';
$db = new Database();
$pdo = $db->getConnection();
echo "--- ALL Metrics --- \n";
$res = $pdo->query('SELECT id, key_name FROM kpi_metrics');
print_r($res->fetchAll());
?>
