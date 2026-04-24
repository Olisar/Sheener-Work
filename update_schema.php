<?php
/* File: sheener/update_schema.php */

// update_schema.php
// Script to update the database schema for Process Hazard Assessments

require_once 'php/database.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();

    // Read the SQL file
    $sql = file_get_contents('pha_create.sql');

    // Split into individual statements
    $statements = array_filter(array_map('trim', explode(';', $sql)));

    // Execute each statement
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            $pdo->exec($statement);
            echo "Executed: " . substr($statement, 0, 50) . "...\n";
        }
    }

    echo "Schema update completed successfully.\n";

} catch (Exception $e) {
    echo "Error updating schema: " . $e->getMessage() . "\n";
}
?>
