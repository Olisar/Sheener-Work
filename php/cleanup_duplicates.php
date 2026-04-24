<?php
/* File: sheener/php/cleanup_duplicates.php */

require_once 'd:/xampp/htdocs/sheener/php/database.php';

try {
    $db = new Database();
    $pdo = $db->getConnection();

    // 1. Get all unique year/month pairs that have duplicates
    $query = "SELECT year, month_id FROM kpi_data GROUP BY year, month_id HAVING COUNT(*) > 1";
    $stmt = $pdo->query($query);
    $pairs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "📊 Consolidating " . count($pairs) . " duplicate pairs...\n";

    foreach ($pairs as $p) {
        $year = $p['year'];
        $month = $p['month_id'];

        // Get all rows for this pair, ordered by ID DESC (latest first)
        $stmt = $pdo->prepare("SELECT * FROM kpi_data WHERE year = ? AND month_id = ? ORDER BY id DESC");
        $stmt->execute([$year, $month]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $master = $rows[0];
        $ids_to_delete = [];

        for ($i = 1; $i < count($rows); $i++) {
            $duplicate = $rows[$i];
            $ids_to_delete[] = $duplicate['id'];

            // Consolidate data into master
            foreach ($master as $key => $value) {
                if ($key === 'id' || $key === 'year' || $key === 'month_id') continue;
                
                // If master is empty/zero/null and duplicate has a value, pick duplicate's value
                if (($value === null || $value === '' || $value === 0 || $value === 0.0) && 
                    ($duplicate[$key] !== null && $duplicate[$key] !== '' && $duplicate[$key] != 0)) {
                    $master[$key] = $duplicate[$key];
                }
            }
        }

        // Update the master row
        $fields = [];
        $params = [];
        foreach ($master as $key => $value) {
            if ($key === 'id') continue;
            $fields[] = "$key = ?";
            $params[] = $value;
        }
        $params[] = $master['id'];

        $update_sql = "UPDATE kpi_data SET " . implode(", ", $fields) . " WHERE id = ?";
        $upd_stmt = $pdo->prepare($update_sql);
        $upd_stmt->execute($params);

        // Delete the extra rows
        if (!empty($ids_to_delete)) {
            $in_query = implode(',', array_fill(0, count($ids_to_delete), '?'));
            $del_stmt = $pdo->prepare("DELETE FROM kpi_data WHERE id IN ($in_query)");
            $del_stmt->execute($ids_to_delete);
            echo "✅ Consolidated $year-$month: Kept ID {$master['id']}, deleted IDs: " . implode(', ', $ids_to_delete) . "\n";
        }
    }

    // 2. Remove rows that have no data at all
    // Define KPI columns to check (exclude metadata)
    $stmt = $pdo->query("SHOW COLUMNS FROM kpi_data");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $check_cols = array_diff($columns, ['id', 'year', 'month_id']);
    
    $where_parts = [];
    foreach ($check_cols as $col) {
        $where_parts[] = "($col IS NULL OR $col = 0 OR $col = '')";
    }
    $where_clause = implode(" AND ", $where_parts);
    
    $del_empty = "DELETE FROM kpi_data WHERE $where_clause";
    $deleted_count = $pdo->exec($del_empty);
    echo "🧹 Removed $deleted_count empty rows from kpi_data.\n";

    // 3. Add UNIQUE constraint to prevent future duplicates if it doesn't exist
    try {
        $pdo->exec("ALTER TABLE kpi_data ADD UNIQUE INDEX idx_year_month (year, month_id)");
        echo "🔒 Added UNIQUE constraint to kpi_data (year, month_id).\n";
    } catch (Exception $e) {
        echo "ℹ️ UNIQUE constraint already exists or could not be added.\n";
    }

    echo "🚀 Cleanup complete!";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>
