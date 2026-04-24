<?php
require_once 'database.php';
$db = new Database();
$pdo = $db->getConnection();
$stmt = $pdo->query('SHOW TABLES');
print_r($stmt->fetchAll());
?>
