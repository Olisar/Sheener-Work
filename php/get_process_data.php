<?php
/* File: sheener/php/get_process_data.php */

/**
 * Process Data Export API
 * Exports process_map data in the format expected by SchemScript.js
 * 
 * Endpoint: php/get_process_data.php
 * Returns: JSON with { nodes: [...] } structure matching the JavaScript format
 */

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/database.php';

try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    // Get root node ID from query parameter (default: show all, but can filter by root_id)
    $rootNodeId = isset($_GET['root_id']) ? (int)$_GET['root_id'] : null;
    
    // Get all process_map nodes with all fields
    // Query all columns from process_map table
    $sql = "SELECT 
                id,
                type,
                level,
                status,
                text as name,
                description,
                cost,
                value_add,
                notes,
                parent as parentId,
                `order`,
                owner_id,
                department_id,
                primary_branch_id,
                created_at,
                updated_at
            FROM process_map
            WHERE status = 'Active'
            ORDER BY parent, `order`, id";
    
    $stmt = $pdo->query($sql);
    $allNodes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // If root_id is specified, filter to only show that branch (root node + all descendants)
    if ($rootNodeId !== null) {
        // Build a map of all nodes by ID for quick lookup
        $nodeMap = [];
        foreach ($allNodes as $node) {
            $nodeMap[$node['id']] = $node;
        }
        
        // Find all descendants of the root node (including the root itself)
        $processNodes = [];
        $toProcess = [$rootNodeId];
        $processed = [];
        
        while (!empty($toProcess)) {
            $currentId = array_shift($toProcess);
            
            if (isset($processed[$currentId])) {
                continue; // Already processed
            }
            
            if (isset($nodeMap[$currentId])) {
                $processNodes[] = $nodeMap[$currentId];
                $processed[$currentId] = true;
                
                // Find all children of this node
                foreach ($allNodes as $node) {
                    if ($node['parentId'] == $currentId && !isset($processed[$node['id']])) {
                        $toProcess[] = $node['id'];
                    }
                }
            }
        }
        
        // If root node not found, return empty result
        if (empty($processNodes)) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => "Root node with ID {$rootNodeId} not found or has no active descendants.",
                'nodes' => []
            ]);
            exit;
        }
    } else {
        // No filter, return all nodes
        $processNodes = $allNodes;
    }
    
    // Build nodes array with elements and transformations
    $nodes = [];
    
    foreach ($processNodes as $node) {
        $nodeId = $node['id'];
        
        // Initialize node structure
        // Map type to level if level is not set (backward compatibility)
        $level = $node['level'];
        if (!$level && isset($node['type'])) {
            // Map type enum to level format
            $typeToLevel = [
                'process' => 'L0_Enterprise',
                'step' => 'L1_HighLevel',
                'substep' => 'L2_SubProcess',
                'task' => 'L3_DetailStep',
                'activity' => 'L3_DetailStep'
            ];
            $level = $typeToLevel[$node['type']] ?? null;
        }
        
        // Fix self-referencing parentId (parent cannot be same as id)
        $parentId = isset($node['parentId']) && $node['parentId'] ? (int)$node['parentId'] : null;
        if ($parentId === $nodeId) {
            $parentId = null; // Prevent self-reference
        }
        
        $nodeData = [
            'id' => (int)$nodeId,
            'name' => $node['name'],
            'level' => $level,
            'type' => $node['type'] ?? null, // Include type for reference
            'parentId' => $parentId,
            'description' => $node['description'] ?? null,
            'notes' => $node['notes'] ?? null,
            'order' => isset($node['order']) ? (int)$node['order'] : null,
        ];
        
        // Add cost if available
        if (isset($node['cost']) && $node['cost'] !== null) {
            $nodeData['cost'] = (float)$node['cost'];
        }
        
        // Add value_add if available
        if (isset($node['value_add']) && $node['value_add'] !== null) {
            $nodeData['value_add'] = (bool)$node['value_add'];
        }
        
        // Add metadata fields
        if (isset($node['owner_id']) && $node['owner_id']) {
            $nodeData['owner_id'] = (int)$node['owner_id'];
        }
        if (isset($node['department_id']) && $node['department_id']) {
            $nodeData['department_id'] = (int)$node['department_id'];
        }
        if (isset($node['primary_branch_id']) && $node['primary_branch_id']) {
            $nodeData['primary_branch_id'] = (int)$node['primary_branch_id'];
        }
        
        // Collect elements from all junction tables
        $elements = [];
        
        // Get People elements
        try {
            // Check if usage and fixed columns exist
            $checkCols = $pdo->query("SHOW COLUMNS FROM process_map_people LIKE 'usage'");
            $hasUsage = $checkCols->rowCount() > 0;
            $checkCols = $pdo->query("SHOW COLUMNS FROM process_map_people LIKE 'fixed'");
            $hasFixed = $checkCols->rowCount() > 0;
            
            $selectCols = "p.people_id as id, CONCAT(COALESCE(p.FirstName, ''), ' ', COALESCE(p.LastName, '')) as name";
            if ($hasUsage) $selectCols .= ", pmp.usage";
            if ($hasFixed) $selectCols .= ", pmp.fixed";
            
            $stmt = $pdo->prepare("
                SELECT {$selectCols}
                FROM process_map_people pmp
                INNER JOIN people p ON pmp.people_id = p.people_id
                WHERE pmp.process_map_id = :id
            ");
            $stmt->execute([':id' => $nodeId]);
            $people = $stmt->fetchAll(PDO::FETCH_ASSOC);
            // Remove duplicates by tracking unique person IDs
            $seenPeople = [];
            foreach ($people as $person) {
                $personKey = $person['id'] . '|' . trim($person['name']);
                if (!isset($seenPeople[$personKey])) {
                    $seenPeople[$personKey] = true;
                    $elements[] = [
                        'type' => 'People',
                        'name' => trim($person['name']),
                        'usage' => ($hasUsage && isset($person['usage'])) ? $person['usage'] : null,
                        'fixed' => ($hasFixed && isset($person['fixed'])) ? (bool)$person['fixed'] : false
                    ];
                }
            }
        } catch (PDOException $e) {
            // Table or columns might not exist, skip
            error_log("Error loading people elements: " . $e->getMessage());
        }
        
        // Get Equipment elements
        try {
            $checkCols = $pdo->query("SHOW COLUMNS FROM process_map_equipment LIKE 'usage'");
            $hasUsage = $checkCols->rowCount() > 0;
            $checkCols = $pdo->query("SHOW COLUMNS FROM process_map_equipment LIKE 'fixed'");
            $hasFixed = $checkCols->rowCount() > 0;
            
            $selectCols = "e.equipment_id as id, e.equipment_name as name";
            if ($hasUsage) $selectCols .= ", pme.usage";
            if ($hasFixed) $selectCols .= ", pme.fixed";
            
            $stmt = $pdo->prepare("
                SELECT {$selectCols}
                FROM process_map_equipment pme
                INNER JOIN equipment e ON pme.equipment_id = e.equipment_id
                WHERE pme.process_map_id = :id
            ");
            $stmt->execute([':id' => $nodeId]);
            $equipment = $stmt->fetchAll(PDO::FETCH_ASSOC);
            // Remove duplicates
            $seenEquipment = [];
            foreach ($equipment as $eq) {
                $eqKey = $eq['id'] . '|' . $eq['name'];
                if (!isset($seenEquipment[$eqKey])) {
                    $seenEquipment[$eqKey] = true;
                    $elements[] = [
                        'type' => 'Equipment',
                        'name' => $eq['name'],
                        'usage' => ($hasUsage && isset($eq['usage'])) ? $eq['usage'] : null,
                        'fixed' => ($hasFixed && isset($eq['fixed'])) ? (bool)$eq['fixed'] : false
                    ];
                }
            }
        } catch (PDOException $e) {
            error_log("Error loading equipment elements: " . $e->getMessage());
        }
        
        // Get Material elements
        try {
            $checkCols = $pdo->query("SHOW COLUMNS FROM process_map_material LIKE 'usage'");
            $hasUsage = $checkCols->rowCount() > 0;
            $checkCols = $pdo->query("SHOW COLUMNS FROM process_map_material LIKE 'fixed'");
            $hasFixed = $checkCols->rowCount() > 0;
            
            $selectCols = "m.MaterialID as id, m.MaterialName as name, pmm.quantity, mu.UnitName as unit";
            if ($hasUsage) $selectCols .= ", pmm.usage";
            if ($hasFixed) $selectCols .= ", pmm.fixed";
            
            $stmt = $pdo->prepare("
                SELECT {$selectCols}
                FROM process_map_material pmm
                INNER JOIN materials m ON pmm.material_id = m.MaterialID
                LEFT JOIN measurementunit mu ON pmm.measurement_unit_id = mu.UnitID
                WHERE pmm.process_map_id = :id
            ");
            $stmt->execute([':id' => $nodeId]);
            $materials = $stmt->fetchAll(PDO::FETCH_ASSOC);
            // Remove duplicates
            $seenMaterials = [];
            foreach ($materials as $mat) {
                $matKey = $mat['id'] . '|' . $mat['name'];
                if (!isset($seenMaterials[$matKey])) {
                    $seenMaterials[$matKey] = true;
                    $usage = ($hasUsage && isset($mat['usage'])) ? $mat['usage'] : null;
                    if (!$usage && isset($mat['quantity']) && isset($mat['unit'])) {
                        $usage = $mat['quantity'] . ' ' . $mat['unit'];
                    }
                    $elements[] = [
                        'type' => 'Material',
                        'name' => $mat['name'],
                        'usage' => $usage,
                        'fixed' => ($hasFixed && isset($mat['fixed'])) ? (bool)$mat['fixed'] : false
                    ];
                }
            }
        } catch (PDOException $e) {
            error_log("Error loading material elements: " . $e->getMessage());
        }
        
        // Get Energy elements
        try {
            $checkCols = $pdo->query("SHOW COLUMNS FROM process_map_energy LIKE 'usage'");
            $hasUsage = $checkCols->rowCount() > 0;
            $checkCols = $pdo->query("SHOW COLUMNS FROM process_map_energy LIKE 'fixed'");
            $hasFixed = $checkCols->rowCount() > 0;
            
            $selectCols = "e.EnergyID as id, e.EnergyName as name";
            if ($hasUsage) $selectCols .= ", pme.usage";
            if ($hasFixed) $selectCols .= ", pme.fixed";
            
            $stmt = $pdo->prepare("
                SELECT {$selectCols}
                FROM process_map_energy pme
                INNER JOIN energy e ON pme.energy_id = e.EnergyID
                WHERE pme.process_map_id = :id
            ");
            $stmt->execute([':id' => $nodeId]);
            $energy = $stmt->fetchAll(PDO::FETCH_ASSOC);
            // Remove duplicates
            $seenEnergy = [];
            foreach ($energy as $en) {
                $enKey = $en['id'] . '|' . $en['name'];
                if (!isset($seenEnergy[$enKey])) {
                    $seenEnergy[$enKey] = true;
                    $elements[] = [
                        'type' => 'Energy',
                        'name' => $en['name'],
                        'usage' => ($hasUsage && isset($en['usage'])) ? $en['usage'] : null,
                        'fixed' => ($hasFixed && isset($en['fixed'])) ? (bool)$en['fixed'] : false
                    ];
                }
            }
        } catch (PDOException $e) {
            error_log("Error loading energy elements: " . $e->getMessage());
        }
        
        // Get Area elements
        try {
            $checkCols = $pdo->query("SHOW COLUMNS FROM process_map_area LIKE 'usage'");
            $hasUsage = $checkCols->rowCount() > 0;
            $checkCols = $pdo->query("SHOW COLUMNS FROM process_map_area LIKE 'fixed'");
            $hasFixed = $checkCols->rowCount() > 0;
            
            $selectCols = "a.area_id as id, a.area_name as name";
            if ($hasUsage) $selectCols .= ", pma.usage";
            if ($hasFixed) $selectCols .= ", pma.fixed";
            
            $stmt = $pdo->prepare("
                SELECT {$selectCols}
                FROM process_map_area pma
                INNER JOIN areas a ON pma.area_id = a.area_id
                WHERE pma.process_map_id = :id
            ");
            $stmt->execute([':id' => $nodeId]);
            $areas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            // Remove duplicates
            $seenAreas = [];
            foreach ($areas as $area) {
                $areaKey = $area['id'] . '|' . $area['name'];
                if (!isset($seenAreas[$areaKey])) {
                    $seenAreas[$areaKey] = true;
                    $elements[] = [
                        'type' => 'Area',
                        'name' => $area['name'],
                        'usage' => ($hasUsage && isset($area['usage'])) ? $area['usage'] : null,
                        'fixed' => ($hasFixed && isset($area['fixed'])) ? (bool)$area['fixed'] : false
                    ];
                }
            }
        } catch (PDOException $e) {
            error_log("Error loading area elements: " . $e->getMessage());
        }
        
        // Add Information elements (from process_map_document if exists)
        try {
            $stmt = $pdo->prepare("
                SELECT 
                    d.DocumentID as id,
                    d.DocumentName as name,
                    'Access' as usage,
                    1 as fixed
                FROM process_map_document pmd
                INNER JOIN documents d ON pmd.document_id = d.DocumentID
                WHERE pmd.process_map_id = :id
                LIMIT 5
            ");
            $stmt->execute([':id' => $nodeId]);
            $info = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($info as $inf) {
                $elements[] = [
                    'type' => 'Information',
                    'name' => $inf['name'],
                    'usage' => $inf['usage'] ?? 'Access',
                    'fixed' => (bool)($inf['fixed'] ?? 1)
                ];
            }
        } catch (PDOException $e) {
            // Table might not exist, ignore
        }
        
        // Add elements if any exist
        if (!empty($elements)) {
            $nodeData['elements'] = $elements;
        }
        
        // Get transformation data (input/output)
        $stmt = $pdo->prepare("
            SELECT 
                type,
                material_name,
                quantity,
                unit,
                description,
                `order`
            FROM process_transformation
            WHERE process_map_id = :id
            ORDER BY type, `order`
        ");
        $stmt->execute([':id' => $nodeId]);
        $transformations = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($transformations)) {
            $transformation = [
                'input' => [],
                'output' => []
            ];
            
            foreach ($transformations as $trans) {
                $qty = $trans['quantity'];
                if ($trans['unit']) {
                    $qty = ($qty ? $qty : '') . ($trans['unit'] ? $trans['unit'] : '');
                }
                
                $item = [
                    'material' => $trans['material_name'],
                    'qty' => $qty
                ];
                
                if ($trans['type'] === 'input') {
                    $transformation['input'][] = $item;
                } else {
                    $transformation['output'][] = $item;
                }
            }
            
            if (!empty($transformation['input']) || !empty($transformation['output'])) {
                $nodeData['transformation'] = $transformation;
            }
        }
        
        $nodes[] = $nodeData;
    }
    
    // Return data in the format expected by JavaScript
    echo json_encode([
        'nodes' => $nodes
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    // Log error for debugging (check PHP error log)
    error_log("get_process_data.php error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    echo json_encode([
        'error' => 'Failed to load process data: ' . $e->getMessage(),
        'nodes' => [] // Return empty nodes array on error
    ], JSON_PRETTY_PRINT);
} catch (PDOException $e) {
    http_response_code(500);
    // Log PDO errors
    error_log("get_process_data.php PDO error: " . $e->getMessage());
    
    echo json_encode([
        'error' => 'Database error: ' . $e->getMessage(),
        'nodes' => [] // Return empty nodes array on error
    ], JSON_PRETTY_PRINT);
}
?>

