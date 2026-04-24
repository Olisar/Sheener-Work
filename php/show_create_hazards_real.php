<?php
require_once 'database.php';
$db = new Database();
$pdo = $db->getConnection();
$stmt = $pdo->query('SHOW CREATE TABLE hazards');
print_r($stmt->fetch());
?>
