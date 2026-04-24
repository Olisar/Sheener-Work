<?php
require_once 'database.php';
$db = new Database();
$pdo = $db->getConnection();
$tables = ['hazard_assessment', 'hazard_raci', 'ra_assessorlinkt', 'controls', 'hazard_control_actions'];
foreach($tables as $table) {
    echo "--- Table: $table ---\n";
    try {
        $stmt = $pdo->query("SHOW CREATE TABLE $table");
        $res = $stmt->fetch();
        echo $res['Create Table'] . "\n\n";
    } catch(Exception $e) {
        echo "Error: " . $e->getMessage() . "\n\n";
    }
}
?>
