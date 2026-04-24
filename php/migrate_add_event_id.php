<?php
/* File: sheener/php/migrate_add_event_id.php */

/**
 * Migration script to add event_id column to attachments table
 * Run this script once to update your database schema
 * 
 * Usage: Navigate to http://localhost/sheener/php/migrate_add_event_id.php in your browser
 * Or run via command line: php migrate_add_event_id.php
 */

header('Content-Type: text/html; charset=utf-8');
require_once 'database.php';

echo "<h2>Database Migration: Adding event_id to attachments table</h2>";
echo "<pre>";

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Check if column already exists
    $checkColumn = $pdo->query("SHOW COLUMNS FROM attachments LIKE 'event_id'");
    if ($checkColumn->rowCount() > 0) {
        echo "✓ Column 'event_id' already exists in attachments table.\n";
        echo "Migration not needed.\n";
        exit;
    }
    
    echo "Starting migration...\n\n";
    
    // Add event_id column
    echo "1. Adding event_id column...\n";
    $pdo->exec("ALTER TABLE `attachments` ADD COLUMN `event_id` int(11) DEFAULT NULL AFTER `permit_id`");
    echo "   ✓ Column added successfully\n\n";
    
    // Add index
    echo "2. Adding index on event_id...\n";
    $pdo->exec("ALTER TABLE `attachments` ADD KEY `event_id` (`event_id`)");
    echo "   ✓ Index added successfully\n\n";
    
    // Optional: Add foreign key constraint (commented out by default)
    // Uncomment the lines below if you want referential integrity
    /*
    echo "3. Adding foreign key constraint...\n";
    $pdo->exec("ALTER TABLE `attachments` 
                ADD CONSTRAINT `attachments_ibfk_event` 
                FOREIGN KEY (`event_id`) REFERENCES `events` (`event_id`) ON DELETE CASCADE");
    echo "   ✓ Foreign key constraint added successfully\n\n";
    */
    
    echo "========================================\n";
    echo "Migration completed successfully! ✓\n";
    echo "========================================\n";
    echo "\nYou can now use attachments with events.\n";
    
} catch (PDOException $e) {
    echo "\n❌ Error during migration:\n";
    echo "   " . $e->getMessage() . "\n";
    echo "\nPlease check your database connection and try again.\n";
} catch (Exception $e) {
    echo "\n❌ Unexpected error:\n";
    echo "   " . $e->getMessage() . "\n";
}

echo "</pre>";
?>

