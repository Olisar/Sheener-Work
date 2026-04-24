<?php
require_once 'database.php';
$db = new Database();
$pdo = $db->getConnection();
$pdo->exec('ALTER TABLE ra_assessorlinkt MODIFY RAAssessorLinkID int(11) NOT NULL AUTO_INCREMENT');
echo "ra_assessorlinkt modified with AUTO_INCREMENT";
?>
