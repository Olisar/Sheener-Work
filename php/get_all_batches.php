<?php
/* File: sheener/php/get_all_batches.php */

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once 'database.php';

$years = isset($_GET['years']) ? explode(',', $_GET['years']) : [];
$quarters = isset($_GET['quarters']) ? explode(',', $_GET['quarters']) : [];
$months = isset($_GET['months']) ? explode(',', $_GET['months']) : [];

if (empty($years) && empty($quarters) && empty($months)) {
    $years = [date('Y')];
}

$query = "SELECT DATE_FORMAT(start_time, '%Y-%m') AS period, SUM(quantity) AS total_quantity, SUM(reject_qty) AS total_reject FROM batches WHERE 1=1";

if (!empty($years)) {
    $yearsList = implode(',', $years);
    $query .= " AND YEAR(start_time) IN ($yearsList)";
}

if (!empty($quarters)) {
    $quarterMap = [
        "Q1" => [1, 2, 3],
        "Q2" => [4, 5, 6],
        "Q3" => [7, 8, 9],
        "Q4" => [10, 11, 12]
    ];

    $quarterConditions = [];
    foreach ($quarters as $quarter) {
        $year = substr($quarter, -4);
        $quarterKey = substr($quarter, 0, 2);
        $quarterMonths = $quarterMap[$quarterKey];
        $quarterConditions[] = "(YEAR(start_time) = $year AND MONTH(start_time) IN (" . implode(',', $quarterMonths) . "))";
    }

    $query .= " AND (" . implode(' OR ', $quarterConditions) . ")";
}

if (!empty($months)) {
    $monthsSQL = array_map(fn($m) => intval($m), $months);
    $query .= " AND MONTH(start_time) IN (" . implode(',', $monthsSQL) . ")";
}

$query .= " GROUP BY period ORDER BY period ASC";

try {
    $database = new Database();
    $pdo = $database->getConnection();

    $stmt = $pdo->query($query);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($data as &$row) {
        $row['total_reject'] = -abs($row['total_reject']);
    }

    echo json_encode(["success" => true, "data" => $data]);
} catch (PDOException $e) {
    echo json_encode(["success" => false, "error" => "Database error: " . $e->getMessage()]);
}
?>
