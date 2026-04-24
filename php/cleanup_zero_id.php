<?php
require_once 'database.php';
$db = new Database();
$pdo = $db->getConnection();
// Clean up any zero IDs that might have been inserted when auto_increment was missing
$pdo->exec('DELETE FROM ra_assessorlinkt WHERE RAAssessorLinkID = 0');
echo "Deleted any orphaned zero-ID assessor links";
?>
