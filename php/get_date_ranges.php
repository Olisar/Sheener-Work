<?php
/* File: sheener/php/get_date_ranges.php */

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: http://172.21.10.99");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

try {
    // Define years, quarters, and months explicitly
    $years = ['2023', '2024', '2025'];
    $quarters = ['Q1', 'Q2', 'Q3', 'Q4'];
    $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

    // Generate weeks, fortnights, and days
    $weeks = generateWeeks($years);
    $fortnights = generateFortnights($years);
    $days = generateDays($years);

    // Ensure arrays are not empty to prevent JavaScript errors
    $data = [
        'year' => $years ?: [],
        'quarters' => $quarters ?: [],
        'months' => $months ?: [],
        'weeks' => $weeks ?: [],
        'fortnights' => $fortnights ?: [],
        'days' => $days ?: []
    ];

    // Output JSON response
    echo json_encode(['success' => true, 'data' => $data], JSON_PRETTY_PRINT);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

// Function to generate weeks for the given years
function generateWeeks($years) {
    $weeks = [];
    foreach ($years as $year) {
        for ($week = 1; $week <= 52; $week++) {
            $weeks[] = sprintf('%s W%d', $year, $week);
        }
    }
    return $weeks;
}

// Function to generate fortnights for the given years
function generateFortnights($years) {
    $fortnights = [];
    foreach ($years as $year) {
        for ($fortnight = 1; $fortnight <= 26; $fortnight++) {
            $fortnights[] = sprintf('%s F%d', $year, $fortnight);
        }
    }
    return $fortnights;
}

// Function to generate days for the given years
function generateDays($years) {
    $days = [];
    foreach ($years as $year) {
        for ($month = 1; $month <= 12; $month++) {
            for ($day = 1; $day <= 31; $day++) {
                if (checkdate($month, $day, $year)) {
                    $days[] = sprintf('%04d-%02d-%02d', $year, $month, $day);
                }
            }
        }
    }
    return $days;
}
