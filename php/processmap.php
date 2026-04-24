<?php
/* File: sheener/php/processmap.php */

// Include the database connection file
require_once 'database.php';



// Data Source Name (DSN)
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

// PDO options
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Enable exceptions for errors
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // Set default fetch mode to associative array
    PDO::ATTR_EMULATE_PREPARES   => false, // Disable emulated prepared statements
];

try {
    // Create a PDO instance (database connection)
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    // Handle connection errors
    throw new PDOException('Connection failed: ' . $e->getMessage(), (int)$e->getCode());
}
?>
