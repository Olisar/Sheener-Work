<?php
/* File: sheener/php/import_hazards.php */

/**
 * Import Hazards and HIRA Hazard Links
 * 
 * This script imports hazards data and links them to HIRA records.
 * It validates foreign keys and provides detailed error reporting.
 */

require __DIR__ . '/database.php';

// Set headers first, before any output
header('Content-Type: application/json; charset=utf-8');
// Enable error reporting for debugging but don't display to user
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    // Start transaction
    $pdo->beginTransaction();
    
    $errors = [];
    $warnings = [];
    $inserted_hazards = 0;
    $inserted_links = 0;
    $skipped_hazards = 0;
    $skipped_links = 0;
    
    // Check if hazards table has RAID column
    $stmt = $pdo->query("SHOW COLUMNS FROM hazards LIKE 'RAID'");
    $has_raid = $stmt->rowCount() > 0;
    
    // ============================================================================
    // STEP 1: Insert Hazards
    // ============================================================================
    $hazards_data = [
        [1, 101, 1, 'Operator skin contact during weighing of solvent-based API. COMMENT_REF:1', NULL],
        [2, 101, 8, 'Static discharge during powder transfer leading to ignition risk. COMMENT_REF:8', NULL],
        [3, 102, 2, 'Dust generation during blending of powder excipients (DPI). COMMENT_REF:2', NULL],
        [4, 103, 3, 'Propellant leakage during MDI filling — vapour accumulation near equipment. COMMENT_REF:3', NULL],
        [5, 103, 10, 'Over-pressurised canister or burst during crimping/filling. COMMENT_REF:10', NULL],
        [6, 103, 4, 'Aerosolised API during canister filling causing inhalation exposure. COMMENT_REF:4', NULL],
        [7, 104, 2, 'Fine respirable powder cloud during DPI filling — inhalation and dust hazard. COMMENT_REF:2', NULL],
        [8, 104, 5, 'Carryover of other product powder in shared hoppers causing cross-contamination. COMMENT_REF:5', NULL],
        [9, 105, 6, 'Pinch points and hand entrapment during crimping and capping operations. COMMENT_REF:6', NULL],
        [10, 105, 8, 'Static sparks when sealing aluminium/plastic components during packaging. COMMENT_REF:8', NULL],
        [11, 106, 11, 'Chemical exposure from cleaning agents (acid/alkaline) during CIP. COMMENT_REF:11', NULL],
        [12, 106, 9, 'Solvent spill during drum transfer to cleaning station. COMMENT_REF:9', NULL],
        [13, 107, 6, 'Moving parts not isolated during maintenance; risk of crushing. COMMENT_REF:6', NULL],
        [14, 107, 3, 'Uncontrolled hot work during maintenance near flammable residues. COMMENT_REF:3', NULL],
        [15, 107, 17, 'Tools brought into production risk foreign material contamination. COMMENT_REF:17', NULL],
        [16, 108, 13, 'Incorrect labelling of inhaler strength leading to patient safety issue. COMMENT_REF:13', NULL],
        [17, 108, 5, 'Misfeed of packaging materials causing cross-contact between product lines. COMMENT_REF:5', NULL],
        [18, 109, 4, 'Open sampling introduces aerosols into QC area. COMMENT_REF:4', NULL],
        [19, 109, 12, 'Microbial contamination risk if sampling non-sterile techniques used. COMMENT_REF:12', NULL],
        [20, 110, 9, 'Leaking storage drum of spent solvent — environmental release. COMMENT_REF:9', NULL],
        [21, 110, 10, 'Compressed gas cylinder failure in storage area. COMMENT_REF:10', NULL],
        [22, 101, 1, 'Chronic low-level inhalation of solvent vapour in poorly ventilated weighing area. COMMENT_REF:1', NULL],
        [23, 102, 16, 'Low humidity + high powder flow increases static and dust bounce. COMMENT_REF:16', NULL],
        [24, 103, 3, 'Ignition risk from hot surfaces near propellant lines. COMMENT_REF:3', NULL],
        [25, 104, 2, 'Dust explosion potential in enclosed transfer line during powder conveyance. COMMENT_REF:2', NULL],
        [26, 105, 7, 'Repetitive strain from high rate manual packing of inhaler cartons. COMMENT_REF:7', NULL],
        [27, 106, 11, 'Residue neutralisation step omitted in documentation leading to exposure. COMMENT_REF:11', NULL],
        [28, 107, 18, 'Failure of interlocks leads to run with guard removed. COMMENT_REF:18', NULL],
        [29, 108, 13, 'Wrong label applied during high-speed labelling run (no verification). COMMENT_REF:13', NULL],
        [30, 109, 4, 'Operator exposure during aerosol challenge tests if containment not used. COMMENT_REF:4', NULL],
        [31, 101, 1, 'Spill during decanting of concentrated API — dermal contact risk. COMMENT_REF:9', NULL],
        [32, 104, 5, 'Powder cross-contamination via re-circulating filters. COMMENT_REF:5', NULL],
        [33, 103, 14, 'Noise exposure from high-pressure filling pump in MDI line. COMMENT_REF:14', NULL],
        [34, 102, 2, 'Inadequate housekeeping leaves settled powder that becomes airborne during restart. COMMENT_REF:2', NULL],
        [35, 110, 9, 'Incorrect segregation of hazardous waste (solvent vs non-hazardous) causing disposal breach. COMMENT_REF:9', NULL],
        [36, 107, 6, 'Entrapment risk when clearing jams without isolating line. COMMENT_REF:6', NULL],
        [37, 103, 1, 'Allergic sensitisation risk for operators handling certain APIs repeatedly. COMMENT_REF:1', NULL],
        [38, 104, 16, 'High tribocharging during pneumatic conveyance increases deposit and risk of spark. COMMENT_REF:16', NULL],
        [39, 108, 17, 'Packaging material contamination by oily residues from machinery. COMMENT_REF:17', NULL],
        [40, 109, 12, 'Environmental monitoring failure unnoticed leading to out-of-spec microbial levels. COMMENT_REF:12', NULL]
    ];
    
    // Validate task_ids and hazard_type_ids exist
    $task_ids = array_values(array_unique(array_column($hazards_data, 1))); // Re-index to ensure sequential keys
    $hazard_type_ids = array_values(array_unique(array_column($hazards_data, 2))); // Re-index to ensure sequential keys
    
    $valid_task_ids = [];
    if (!empty($task_ids) && count($task_ids) > 0) {
        try {
            $placeholders = str_repeat('?,', count($task_ids) - 1) . '?';
            $stmt = $pdo->prepare("SELECT task_id FROM tasks WHERE task_id IN ($placeholders)");
            $stmt->execute($task_ids);
            $valid_task_ids = array_column($stmt->fetchAll(), 'task_id');
        } catch (PDOException $e) {
            $errors[] = "Error validating task_ids: " . $e->getMessage();
        }
    }
    
    $valid_hazard_type_ids = [];
    if (!empty($hazard_type_ids) && count($hazard_type_ids) > 0) {
        try {
            $placeholders = str_repeat('?,', count($hazard_type_ids) - 1) . '?';
            $stmt = $pdo->prepare("SELECT hazard_type_id FROM hazard_type WHERE hazard_type_id IN ($placeholders)");
            $stmt->execute($hazard_type_ids);
            $valid_hazard_type_ids = array_column($stmt->fetchAll(), 'hazard_type_id');
        } catch (PDOException $e) {
            $errors[] = "Error validating hazard_type_ids: " . $e->getMessage();
        }
    }
    
    // Log validation results
    if (empty($valid_task_ids)) {
        $warnings[] = "Warning: No valid task_ids found. All hazards will be skipped.";
    } else {
        $warnings[] = "Found " . count($valid_task_ids) . " valid task_ids out of " . count($task_ids) . " required.";
    }
    
    if (empty($valid_hazard_type_ids)) {
        $warnings[] = "Warning: No valid hazard_type_ids found. All hazards will be skipped.";
    } else {
        $warnings[] = "Found " . count($valid_hazard_type_ids) . " valid hazard_type_ids out of " . count($hazard_type_ids) . " required.";
    }
    
    // Prepare hazards insert statement
    if ($has_raid) {
        $hazards_sql = "INSERT INTO hazards (hazard_id, task_id, hazard_type_id, hazard_description, RAID) 
                        VALUES (?, ?, ?, ?, ?) 
                        ON DUPLICATE KEY UPDATE 
                          task_id = VALUES(task_id),
                          hazard_type_id = VALUES(hazard_type_id),
                          hazard_description = VALUES(hazard_description),
                          RAID = VALUES(RAID)";
    } else {
        $hazards_sql = "INSERT INTO hazards (hazard_id, task_id, hazard_type_id, hazard_description) 
                        VALUES (?, ?, ?, ?) 
                        ON DUPLICATE KEY UPDATE 
                          task_id = VALUES(task_id),
                          hazard_type_id = VALUES(hazard_type_id),
                          hazard_description = VALUES(hazard_description)";
    }
    
    $hazards_stmt = $pdo->prepare($hazards_sql);
    
    foreach ($hazards_data as $hazard) {
        $hazard_id = $hazard[0];
        $task_id = $hazard[1];
        $hazard_type_id = $hazard[2];
        $description = $hazard[3];
        $raid = $hazard[4];
        
        // Validate foreign keys
        if (!in_array($task_id, $valid_task_ids)) {
            $warnings[] = "Hazard ID $hazard_id: Task ID $task_id does not exist. Skipping.";
            $skipped_hazards++;
            continue;
        }
        
        if (!in_array($hazard_type_id, $valid_hazard_type_ids)) {
            $warnings[] = "Hazard ID $hazard_id: Hazard Type ID $hazard_type_id does not exist. Skipping.";
            $skipped_hazards++;
            continue;
        }
        
        try {
            if ($has_raid) {
                $hazards_stmt->execute([$hazard_id, $task_id, $hazard_type_id, $description, $raid]);
            } else {
                $hazards_stmt->execute([$hazard_id, $task_id, $hazard_type_id, $description]);
            }
            $inserted_hazards++;
        } catch (PDOException $e) {
            $errors[] = "Hazard ID $hazard_id: " . $e->getMessage();
            $skipped_hazards++;
        }
    }
    
    // ============================================================================
    // STEP 2: Insert HIRA Hazard Links
    // ============================================================================
    // First, check if HIRA records exist, create them if needed
    $hira_links_data = [
        [201, 1, 1, NULL, NULL, 3, 2, 'Operator exposure during API weighing'],
        [201, 2, 1, NULL, NULL, 4, 3, 'Static hazard during powder transfer'],
        [202, 3, 2, NULL, NULL, 5, 2, 'Dust hazard during blending'],
        [203, 4, 3, NULL, NULL, 3, 4, 'Propellant vapour hazard during MDI filling']
    ];
    
    // Get unique hira_ids and validate/create them
    $hira_ids = array_unique(array_column($hira_links_data, 0));
    
    foreach ($hira_ids as $hira_id) {
        $stmt = $pdo->prepare("SELECT hira_id FROM hira_register WHERE hira_id = ?");
        $stmt->execute([$hira_id]);
        
        if ($stmt->rowCount() == 0) {
            // HIRA doesn't exist - need to create it
            // We need scope_type, scope_id, and stage_id to create a HIRA
            // For now, we'll use a default or skip
            $warnings[] = "HIRA ID $hira_id does not exist. You need to create it first with scope_type, scope_id, and stage_id.";
            // Optionally create a generic HIRA - but we need more info
            // For safety, we'll skip links for non-existent HIRAs
        }
    }
    
    // Get valid stage_ids
    $stage_ids = array_values(array_unique(array_column($hira_links_data, 2))); // Re-index to ensure sequential keys
    $valid_stage_ids = [];
    if (!empty($stage_ids) && count($stage_ids) > 0) {
        try {
            $placeholders = str_repeat('?,', count($stage_ids) - 1) . '?';
            $stmt = $pdo->prepare("SELECT stage_id FROM lifecycle_stages WHERE stage_id IN ($placeholders)");
            $stmt->execute($stage_ids);
            $valid_stage_ids = array_column($stmt->fetchAll(), 'stage_id');
        } catch (PDOException $e) {
            $errors[] = "Error validating stage_ids: " . $e->getMessage();
        }
    } else {
        $warnings[] = "No stage_ids found in HIRA links data.";
    }
    
    // Prepare links insert statement
    $links_sql = "INSERT INTO hira_hazard_links 
                  (hira_id, hazard_id, stage_id, source_component_id, target_component_id, exposure, detectability, comments) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?) 
                  ON DUPLICATE KEY UPDATE 
                    comments = VALUES(comments),
                    exposure = VALUES(exposure),
                    detectability = VALUES(detectability)";
    
    $links_stmt = $pdo->prepare($links_sql);
    
    foreach ($hira_links_data as $link) {
        $hira_id = $link[0];
        $hazard_id = $link[1];
        $stage_id = $link[2];
        $source_component_id = $link[3];
        $target_component_id = $link[4];
        $exposure = $link[5];
        $detectability = $link[6];
        $comments = $link[7];
        
        // Validate HIRA exists
        $stmt = $pdo->prepare("SELECT hira_id FROM hira_register WHERE hira_id = ?");
        $stmt->execute([$hira_id]);
        if ($stmt->rowCount() == 0) {
            $warnings[] = "HIRA ID $hira_id does not exist. Skipping link for hazard $hazard_id.";
            $skipped_links++;
            continue;
        }
        
        // Validate hazard_id exists
        $stmt = $pdo->prepare("SELECT hazard_id FROM hazards WHERE hazard_id = ?");
        $stmt->execute([$hazard_id]);
        if ($stmt->rowCount() == 0) {
            $warnings[] = "Hazard ID $hazard_id does not exist. Skipping link for HIRA $hira_id.";
            $skipped_links++;
            continue;
        }
        
        // Validate stage_id exists
        if (!in_array($stage_id, $valid_stage_ids)) {
            $warnings[] = "Stage ID $stage_id does not exist. Skipping link for HIRA $hira_id, hazard $hazard_id.";
            $skipped_links++;
            continue;
        }
        
        try {
            $links_stmt->execute([
                $hira_id, 
                $hazard_id, 
                $stage_id, 
                $source_component_id, 
                $target_component_id, 
                $exposure, 
                $detectability, 
                $comments
            ]);
            $inserted_links++;
        } catch (PDOException $e) {
            $errors[] = "Link HIRA $hira_id -> Hazard $hazard_id: " . $e->getMessage();
            $skipped_links++;
        }
    }
    
    // Commit transaction if we inserted something or if there are only warnings (no critical errors)
    // If there are critical errors, rollback. If only warnings, commit what we can.
    $response_data = [
        'success' => empty($errors),
        'message' => empty($errors) ? 'Import completed successfully' : 'Import completed with some errors',
        'errors' => $errors,
        'warnings' => $warnings,
        'summary' => [
            'hazards_inserted' => $inserted_hazards,
            'hazards_skipped' => $skipped_hazards,
            'links_inserted' => $inserted_links,
            'links_skipped' => $skipped_links
        ]
    ];
    
    if (empty($errors) || ($inserted_hazards > 0 || $inserted_links > 0)) {
        if ($pdo->inTransaction()) {
            if (empty($errors)) {
                $pdo->commit();
            } else {
                // If we have errors but also inserted some records, commit anyway
                // (errors might be from validation, not actual insert failures)
                $pdo->commit();
            }
        }
        // Don't set http_response_code for success or partial success
        ob_end_clean(); // Clear output buffer before sending JSON
        try {
            $json = json_encode($response_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if ($json === false) {
                throw new Exception('JSON encoding failed: ' . json_last_error_msg());
            }
            echo $json;
        } catch (Exception $jsonError) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'JSON encoding error: ' . $jsonError->getMessage(),
                'summary' => $response_data['summary']
            ]);
        }
    } else {
        // Only rollback if we have errors AND didn't insert anything
        if ($pdo->inTransaction()) {
            try {
                $pdo->rollBack();
            } catch (PDOException $e) {
                // Ignore rollback errors
            }
        }
        http_response_code(400);
        $response_data['message'] = 'Import failed - no records inserted';
        $response_data['success'] = false;
        ob_end_clean(); // Clear output buffer before sending JSON
        try {
            $json = json_encode($response_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if ($json === false) {
                throw new Exception('JSON encoding failed: ' . json_last_error_msg());
            }
            echo $json;
        } catch (Exception $jsonError) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'JSON encoding error: ' . $jsonError->getMessage()
            ]);
        }
    }
    
} catch (PDOException $e) {
    if (isset($pdo)) {
        try {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
        } catch (PDOException $rollbackError) {
            // Ignore rollback errors - transaction may already be closed
        }
    }
    http_response_code(500);
    try {
        echo json_encode([
            'success' => false,
            'error' => 'Database error: ' . $e->getMessage(),
            'code' => $e->getCode(),
            'file' => basename($e->getFile()),
            'line' => $e->getLine(),
            'summary' => [
                'hazards_inserted' => isset($inserted_hazards) ? $inserted_hazards : 0,
                'hazards_skipped' => isset($skipped_hazards) ? $skipped_hazards : 0,
                'links_inserted' => isset($inserted_links) ? $inserted_links : 0,
                'links_skipped' => isset($skipped_links) ? $skipped_links : 0
            ]
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    } catch (Exception $jsonError) {
        header('Content-Type: text/plain');
        echo "Error: " . $e->getMessage();
    }
} catch (Exception $e) {
    if (isset($pdo)) {
        try {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
        } catch (PDOException $rollbackError) {
            // Ignore rollback errors - transaction may already be closed
        }
    }
    http_response_code(500);
    try {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage(),
            'file' => basename($e->getFile()),
            'line' => $e->getLine(),
            'summary' => [
                'hazards_inserted' => isset($inserted_hazards) ? $inserted_hazards : 0,
                'hazards_skipped' => isset($skipped_hazards) ? $skipped_hazards : 0,
                'links_inserted' => isset($inserted_links) ? $inserted_links : 0,
                'links_skipped' => isset($skipped_links) ? $skipped_links : 0
            ]
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    } catch (Exception $jsonError) {
        header('Content-Type: text/plain');
        echo "Error: " . $e->getMessage();
    }
}

