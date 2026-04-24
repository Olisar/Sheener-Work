<?php
/* File: sheener/php/find_duplicates.php */

require_once 'd:/xampp/htdocs/sheener/php/database.php';

try {
    $db = new Database();
    $pdo = $db->getConnection();

    // Check for duplicates in kpi_data
    $query = "SELECT year, month_id, COUNT(*) as count 
              FROM kpi_data 
              GROUP BY year, month_id 
              HAVING count > 1";
    
    $stmt = $pdo->query($query);
    $duplicates = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "🔍 Found " . count($duplicates) . " duplicate Year/Month pairs in kpi_data:\n";
    foreach ($duplicates as $d) {
        echo "Year: " . $d['year'] . " | Month: " . $d['month_id'] . " | Rows: " . $d['count'] . "\n";
    }

    if (count($duplicates) > 0) {
        echo "\n📄 Details for first duplicate:\n";
        $y = $duplicates[0]['year'];
        $m = $duplicates[0]['month_id'];
        $stmt = $pdo->prepare("SELECT * FROM kpi_data WHERE year = ? AND month_id = ?");
        $stmt->execute([$y, $m]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        print_r($rows);
    }

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>
