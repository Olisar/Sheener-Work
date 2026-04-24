<?php
/* File: sheener/tmp_check_cols.php */

require_once 'php/database.php';
$db = new Database();
$pdo = $db->getConnection();
echo "--- kpi_data columns ---\n";
$res = $pdo->query('SHOW COLUMNS FROM kpi_data');
print_r($res->fetchAll(PDO::FETCH_COLUMN));
?>
