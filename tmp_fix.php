<?php
/* File: sheener/tmp_fix.php */

require_once 'php/database.php';
$db = new Database();
$pdo = $db->getConnection();

try {
    $pdo->beginTransaction();
    echo "Deleting conflicting placeholder record (id 639)...\n";
    $pdo->exec("DELETE FROM kpi_values WHERE id = 639");

    echo "Updating record 659 to set year = 2026...\n";
    $pdo->exec("UPDATE kpi_values SET year = 2026 WHERE id = 659");

    $pdo->commit();
    echo "✅ Success! Year corrected to 2026 for record 659.";

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo "❌ Error: " . $e->getMessage();
}
?>
