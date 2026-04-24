<?php
/* File: sheener/tmp_check_2018.php */

require_once 'php/database.php';
$db = new Database();
$pdo = $db->getConnection();
echo "--- CO2 Tonnes for 2018 in kpi_data ---\n";
$res = $pdo->query("SELECT year, month_id, co2_tonnes FROM kpi_data WHERE year = 2018");
print_r($res->fetchAll());
?>
