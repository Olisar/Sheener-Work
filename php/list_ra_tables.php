<?php
require_once 'database.php';
$db = new Database();
$pdo = $db->getConnection();
$stmt = $pdo->query('SHOW TABLES LIKE "ra_%"');
print_r($stmt->fetchAll());
?>
