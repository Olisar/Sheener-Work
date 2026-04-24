<?php
/* File: sheener/tmp_check_2025.php */

require_once 'php/database.php';
$db = new Database();
$pdo = $db->getConnection();
echo "\n--- Data for 2025 in kpi_data ---\n";
$res = $pdo->query("SELECT year, month_id, ehs_ncr, sor FROM kpi_data WHERE year = 2025");
print_r($res->fetchAll());
?>
