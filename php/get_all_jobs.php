<?php
/* File: sheener/php/get_all_jobs.php */

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: http://172.21.10.99");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}
require_once 'db_connection.php'; // adjust path if needed

$response = ['success' => false, 'data' => []];

$sql = "SELECT job_id, job_name FROM job ORDER BY job_name ASC";
$result = $conn->query($sql);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $response['data'][] = $row;
    }
    $response['success'] = true;
} else {
    $response['error'] = $conn->error;
}

echo json_encode($response);
$conn->close();
?>
