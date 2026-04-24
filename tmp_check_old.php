<?php
/* File: sheener/tmp_check_old.php */

require_once 'php/database.php';
$db = new Database();
$pdo = $db->getConnection();
echo "\n--- Data for 2026 in kpi_data ---\n";
$res = $pdo->query("SELECT * FROM kpi_data WHERE year = 2026");
print_r($res->fetchAll());
?>
