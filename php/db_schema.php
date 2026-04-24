<?php
require_once 'database.php';
// sheener/php/db_schema.php
// Assuming database.php sets up a PDO connection in $pdo variable

// Load JSON Data
$json_data = file_get_contents('db_schema.json');
$data = json_decode($json_data, true);

// Helper function to sanitize data (prevent SQL injection)
function sanitize($data) {
    return trim($data);
}

try {
    // Start transaction
    $pdo->beginTransaction();

    // Insert data into "tables" table
    if (isset($data['nodes']) && is_array($data['nodes'])) {
        $stmt = $pdo->prepare("INSERT INTO tables (id, name) VALUES (:id, :name)");
        foreach ($data['nodes'] as $node) {
            $id = sanitize($node['id']);
            $name = sanitize($node['name']);
            
            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':name', $name);
            $stmt->execute();
            
            echo "New table record created successfully<br>";
        }
    } else {
        echo "Nodes data not found or invalid.<br>";
    }

    // Insert data into "relationships" table
    if (isset($data['links']) && is_array($data['links'])) {
        $stmt = $pdo->prepare("INSERT INTO relationships (id, source_table, target_table, source_column, target_column, relationship_type) 
                               VALUES (:id, :source_table, :target_table, :source_column, :target_column, :relationship_type)");
        foreach ($data['links'] as $link) {
            $id = sanitize($link['id']);
            $source_table = sanitize($link['source']['table']);
            $target_table = sanitize($link['target']['table']);
            $source_column = isset($link['source']['column']) ? sanitize($link['source']['column']) : null;
            $target_column = isset($link['target']['column']) ? sanitize($link['target']['column']) : null;
            $relationship_type = sanitize($link['relationship_type']);

            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':source_table', $source_table);
            $stmt->bindParam(':target_table', $target_table);
            $stmt->bindParam(':source_column', $source_column);
            $stmt->bindParam(':target_column', $target_column);
            $stmt->bindParam(':relationship_type', $relationship_type);
            $stmt->execute();

            echo "New relationship record created successfully<br>";
        }
    } else {
        echo "Links data not found or invalid.<br>";
    }

    // Commit transaction
    $pdo->commit();

    echo "All data inserted successfully.<br>";

} catch (PDOException $e) {
    // Rollback transaction on error
    $pdo->rollBack();
    echo "Error: " . $e->getMessage() . "<br>";
}
