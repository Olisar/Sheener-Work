<?php
require_once 'database.php';
$db = new Database();
$pdo = $db->getConnection();
$stmt = $pdo->query('SHOW CREATE TABLE process_hazard_assessments');
print_r($stmt->fetch());
?>
