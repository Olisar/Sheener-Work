<?php
require_once 'database.php';
$db = new Database();
$pdo = $db->getConnection();
$stmt = $pdo->query('SELECT * FROM ra_assessorlinkt');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
