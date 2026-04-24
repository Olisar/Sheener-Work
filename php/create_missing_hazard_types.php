<?php
/* File: sheener/php/create_missing_hazard_types.php */

/**
 * Create Missing Hazard Types
 * 
 * This script creates the missing hazard_type_ids that are needed for the hazards import.
 * Run this to create the missing hazard types, then re-run the import.
 */

require __DIR__ . '/database.php';

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    // Start transaction
    $pdo->beginTransaction();
    
    // Missing hazard types based on the warnings
    // Format: [hazard_type_id, type_name, description]
    $missing_hazard_types = [
        [6, 'Mechanical Hazard', 'Physical hazards from machinery, equipment, or moving parts'],
        [7, 'Ergonomic Hazard', 'Hazards related to repetitive motion, awkward postures, or manual handling'],
        [8, 'Electrical/Static Hazard', 'Electrical hazards including static electricity and ignition risks'],
        [9, 'Chemical Spill/Leak Hazard', 'Hazards from chemical spills, leaks, or environmental release'],
        [10, 'Pressure Hazard', 'Hazards from over-pressurization, compressed gases, or burst risks'],
        [11, 'Chemical Exposure Hazard', 'Hazards from exposure to cleaning agents, acids, alkalis, or chemicals'],
        [12, 'Microbial/Biological Hazard', 'Hazards from microbial contamination or biological agents'],
        [13, 'Labeling/Identification Error', 'Hazards from incorrect labeling, misidentification, or documentation errors'],
        [14, 'Noise Hazard', 'Hazards from excessive noise exposure'],
        [16, 'Environmental/Process Condition', 'Hazards from environmental conditions (humidity, temperature, etc.)'],
        [17, 'Contamination Hazard', 'Hazards from foreign material, cross-contamination, or product contamination'],
        [18, 'Safety System Failure', 'Hazards from failure of safety systems, interlocks, or guards']
    ];
    
    $created = 0;
    $existing = 0;
    $errors = [];
    
    // Check which hazard types exist
    $hazard_type_ids = array_column($missing_hazard_types, 0);
    $placeholders = str_repeat('?,', count($hazard_type_ids) - 1) . '?';
    $stmt = $pdo->prepare("SELECT hazard_type_id, type_name FROM hazard_type WHERE hazard_type_id IN ($placeholders)");
    $stmt->execute($hazard_type_ids);
    $existing_types = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $existing_types[$row['hazard_type_id']] = $row['type_name'];
    }
    
    // Check current AUTO_INCREMENT value and set it if needed
    try {
        $stmt = $pdo->query("SHOW TABLE STATUS LIKE 'hazard_type'");
        $table_status = $stmt->fetch(PDO::FETCH_ASSOC);
        $current_auto_increment = $table_status['Auto_increment'] ?? 1;
        $max_required_id = max($hazard_type_ids);
        
        // If we need IDs higher than current auto_increment, set it
        if ($max_required_id >= $current_auto_increment) {
            $pdo->exec("ALTER TABLE hazard_type AUTO_INCREMENT = " . intval($max_required_id + 1));
        }
    } catch (PDOException $e) {
        $errors[] = "Warning: Could not modify AUTO_INCREMENT: " . $e->getMessage();
    }
    
    // Prepare insert statement
    $insert_sql = "INSERT INTO hazard_type (hazard_type_id, type_name, description) 
                   VALUES (?, ?, ?) 
                   ON DUPLICATE KEY UPDATE 
                     type_name = VALUES(type_name),
                     description = VALUES(description)";
    
    $insert_stmt = $pdo->prepare($insert_sql);
    
    foreach ($missing_hazard_types as $hazard_type) {
        $hazard_type_id = $hazard_type[0];
        $type_name = $hazard_type[1];
        $description = $hazard_type[2];
        
        if (isset($existing_types[$hazard_type_id])) {
            $existing++;
            continue;
        }
        
        try {
            $insert_stmt->execute([$hazard_type_id, $type_name, $description]);
            $created++;
        } catch (PDOException $e) {
            $errors[] = "Hazard Type ID $hazard_type_id: " . $e->getMessage();
        }
    }
    
    // Commit transaction
    if ($pdo->inTransaction()) {
        $pdo->commit();
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Hazard type check completed',
        'summary' => [
            'created' => $created,
            'existing' => $existing,
            'total_required' => count($missing_hazard_types)
        ],
        'errors' => $errors
    ]);
    
} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        try {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
        } catch (PDOException $rollbackError) {
            // Ignore rollback errors
        }
    }
    http_response_code(500);
    try {
        echo json_encode([
            'success' => false,
            'error' => 'Database error: ' . $e->getMessage(),
            'code' => $e->getCode(),
            'file' => basename($e->getFile()),
            'line' => $e->getLine()
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    } catch (Exception $jsonError) {
        header('Content-Type: text/plain');
        echo "Error: " . $e->getMessage();
    }
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        try {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
        } catch (PDOException $rollbackError) {
            // Ignore rollback errors
        }
    }
    http_response_code(500);
    try {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage(),
            'file' => basename($e->getFile()),
            'line' => $e->getLine()
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    } catch (Exception $jsonError) {
        header('Content-Type: text/plain');
        echo "Error: " . $e->getMessage();
    }
}

