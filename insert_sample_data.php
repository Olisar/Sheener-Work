<?php
/* File: sheener/insert_sample_data.php */

// Script to insert sample PHA data
require_once 'php/database.php';

try {
    $db = new Database();
    $pdo = $db->getConnection();
    // Read the SQL file
    $sql = file_get_contents('insert_sample_pha_data.sql');

    // Split into individual statements
    $statements = array_filter(array_map('trim', explode(';', $sql)));

    $pdo->beginTransaction();

    foreach ($statements as $i => $statement) {
        if (!empty($statement) && !preg_match('/^--/', $statement)) {
            try {
                $pdo->exec($statement);
                echo "Executed statement " . ($i+1) . " successfully\n";
            } catch (Exception $e) {
                echo "Error on statement " . ($i+1) . ": " . $e->getMessage() . "\n";
                echo "Statement: " . trim($statement) . "\n";
                throw $e;
            }
        }
    }

    $pdo->commit();
    echo "Sample PHA data inserted successfully!\n";

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "Error inserting sample data: " . $e->getMessage() . "\n";
}
?>
