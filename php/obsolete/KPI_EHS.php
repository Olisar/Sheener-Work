<?php
/* File: sheener/php/obsolete/KPI_EHS.php */

header('Content-Type: application/json');

// Include the database connection file
require_once 'php/database.php';


// Validate input and set default values
$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
$month_id = isset($_GET['month_id']) ? (int)$_GET['month_id'] : date('n'); // Default to current month

try {
    // Create a database connection
    $database = new Database();
    $pdo = $database->getConnection();

    // Prepare and execute the query
    $query = "SELECT * FROM kpi_data WHERE year = :year AND month_id = :month_id";
    $stmt = $pdo->prepare($query);
    $stmt->execute([
        ':year' => $year,
        ':month_id' => $month_id
    ]);

    // Fetch the KPI data
    $kpi_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($kpi_data) {
        // Send the data as a JSON response
        echo json_encode(["success" => true, "data" => $kpi_data]);
    } else {
        // Handle the case where no data is found
        echo json_encode(["success" => false, "error" => "No KPI data available for Year: $year, Month: $month_id"]);
    }
} catch (PDOException $e) {
    // Handle database errors
    echo json_encode(["success" => false, "error" => "Database error: " . $e->getMessage()]);
}
?>
