<?php
/* File: sheener/php/api_process_map.php */

/**
 * Process Map API
 * Handles CRUD operations for process_map table
 */

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once 'database.php';

try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    // Read POST body once (can only be read once per request)
    $rawInput = file_get_contents('php://input');
    $postInput = !empty($rawInput) ? json_decode($rawInput, true) : [];
    
    // Get action from GET or POST body
    $action = $_GET['action'] ?? $postInput['action'] ?? 'list';
    $id = $_GET['id'] ?? $postInput['id'] ?? null;
    
    switch ($action) {
        case 'list':
            $response = getProcessMapList($pdo);
            break;
            
        case 'detail':
            if (!$id) {
                throw new Exception('ID required for detail action');
            }
            $response = getProcessMapDetail($pdo, $id);
            break;
            
        case 'create':
            $response = createProcessMap($pdo, $postInput);
            break;
            
        case 'update':
            if (!$id) {
                throw new Exception('ID required for update action');
            }
            $response = updateProcessMap($pdo, $id, $postInput);
            break;
            
        case 'delete':
            if (!$id) {
                throw new Exception('ID required for delete action');
            }
            $response = deleteProcessMap($pdo, $id);
            break;
            
        // Link management actions
        case 'link':
            $response = linkEntity($pdo, $postInput);
            break;
            
        case 'unlink':
            $response = unlinkEntity($pdo, $postInput);
            break;
            
        case 'get_links':
            if (!$id) {
                throw new Exception('ID required for get_links action');
            }
            $entityType = $_GET['entity_type'] ?? null;
            if (!$entityType) {
                throw new Exception('entity_type required for get_links action');
            }
            $response = getLinkedEntities($pdo, $id, $entityType);
            break;
            
        case 'get_subtree':
            if (!$id) {
                throw new Exception('ID required for get_subtree action');
            }
            $response = getProcessSubtree($pdo, $id);
            break;
            
        case 'reorder':
            if (!$id) {
                throw new Exception('ID required for reorder action');
            }
            $response = reorderProcessNodes($pdo, $id, $postInput);
            break;
            
        case 'bulk_unlink':
            $response = bulkUnlinkEntities($pdo, $postInput);
            break;
            
        // Branch management actions
        case 'list_branches':
            $response = listBranches($pdo);
            break;
            
        case 'get_branch':
            $branchId = $_GET['branch_id'] ?? $postInput['branch_id'] ?? null;
            if (!$branchId) {
                throw new Exception('branch_id required for get_branch action');
            }
            $response = getBranch($pdo, $branchId);
            break;
            
        case 'get_branch_processes':
            $branchId = $_GET['branch_id'] ?? $postInput['branch_id'] ?? null;
            if (!$branchId) {
                throw new Exception('branch_id required for get_branch_processes action');
            }
            $response = getBranchProcesses($pdo, $branchId);
            break;
            
        default:
            throw new Exception('Invalid action');
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

function getProcessMapList($pdo) {
    // Check if branch_id filter is provided
    $branchId = $_GET['branch_id'] ?? null;
    
    if ($branchId) {
        // Get processes filtered by branch
        $stmt = $pdo->prepare("
            SELECT DISTINCT
                pm.id,
                pm.type,
                pm.text,
                pm.parent,
                pm.status,
                pm.description,
                pm.order,
                pm.primary_branch_id,
                pbi.branch_id,
                pbi.is_root AS is_branch_root,
                pbi.order AS branch_order
            FROM process_map pm
            INNER JOIN process_branch_items pbi ON pm.id = pbi.process_map_id
            WHERE pbi.branch_id = :branch_id
            ORDER BY pbi.order, pm.order, pm.id
        ");
        $stmt->execute(['branch_id' => $branchId]);
    } else {
        // Get all processes
        $stmt = $pdo->query("
            SELECT 
                id,
                type,
                text,
                parent,
                status,
                description,
                `order`,
                primary_branch_id
            FROM process_map
            ORDER BY `order`, type, id
        ");
    }
    
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return [
        'success' => true,
        'data' => $data
    ];
}

function getProcessMapDetail($pdo, $id) {
    // Get main node with all new fields in one query
    $stmt = $pdo->prepare("
        SELECT 
            pm.id,
            pm.type,
            pm.text,
            pm.parent,
            pm.status,
            pm.owner_id,
            pm.department_id,
            pm.order,
            pm.description,
            pm.notes,
            pm.created_at,
            pm.updated_at,
            pm.created_by,
            pm.updated_by,
            p.text as parent_text,
            o.FirstName as owner_first_name,
            o.LastName as owner_last_name,
            d.DepartmentName as department_name
        FROM process_map pm
        LEFT JOIN process_map p ON pm.parent = p.id
        LEFT JOIN people o ON pm.owner_id = o.people_id
        LEFT JOIN departments d ON pm.department_id = d.department_id
        WHERE pm.id = :id
    ");
    $stmt->execute([':id' => $id]);
    $node = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$node) {
        throw new Exception('Process node not found');
    }
    
    // Get 7Ps counts - check if junction tables exist
    $node['people_count'] = getLinkedCount($pdo, 'process_map_people', $id);
    $node['equipment_count'] = getLinkedCount($pdo, 'process_map_equipment', $id);
    $node['areas_count'] = getLinkedCount($pdo, 'process_map_area', $id);
    $node['materials_count'] = getLinkedCount($pdo, 'process_map_material', $id);
    $node['energy_count'] = getLinkedCount($pdo, 'process_map_energy', $id);
    $node['documents_count'] = getLinkedCount($pdo, 'process_map_document', $id);
    
    // Get new linkage counts
    $node['events_count'] = getLinkedCount($pdo, 'process_map_event', $id);
    $node['operational_events_count'] = getLinkedCount($pdo, 'process_map_operational_event', $id);
    $node['permits_count'] = getLinkedCount($pdo, 'process_map_permit', $id);
    $node['ofi_count'] = getLinkedCount($pdo, 'process_map_ofi', $id);
    $node['tasks_count'] = getLinkedCount($pdo, 'process_map_task', $id);
    $node['activities_count'] = getLinkedCount($pdo, 'process_map_activity', $id);
    $node['hira_count'] = getLinkedCount($pdo, 'process_map_hira', $id);
    $node['risks_count'] = getLinkedCount($pdo, 'process_map_risk', $id);
    $node['risk_register_count'] = getLinkedCount($pdo, 'process_map_risk_register', $id);
    
    // Get linked entities (limited to 5 each for detail view)
    $node['tasks'] = getLinkedTasks($pdo, $id);
    $node['activities'] = getLinkedActivities($pdo, $id);
    $node['events'] = getLinkedEvents($pdo, $id);
    $node['operational_events'] = getLinkedOperationalEvents($pdo, $id);
    $node['permits'] = getLinkedPermits($pdo, $id);
    $node['ofi'] = getLinkedOFI($pdo, $id);
    $node['hira'] = getLinkedHIRA($pdo, $id);
    
    return [
        'success' => true,
        'data' => $node
    ];
}

function getLinkedCount($pdo, $table, $processMapId) {
    // Check if table exists
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM `{$table}` WHERE process_map_id = :id");
        $stmt->execute([':id' => $processMapId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($result['count'] ?? 0);
    } catch (PDOException $e) {
        // Table might not exist, return 0
        return 0;
    }
}

function getLinkedTasks($pdo, $processMapId) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                t.task_id,
                t.task_name as title,
                t.status,
                t.priority,
                t.due_date,
                pmt.linked_date,
                pmt.notes
            FROM process_map_task pmt
            INNER JOIN tasks t ON pmt.task_id = t.task_id
            WHERE pmt.process_map_id = :id
            ORDER BY pmt.linked_date DESC
            LIMIT 5
        ");
        $stmt->execute([':id' => $processMapId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

function getLinkedEvents($pdo, $processMapId) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                e.event_id,
                e.event_type,
                e.description,
                e.status,
                e.reported_date,
                pmt.linked_date,
                pmt.notes
            FROM process_map_event pmt
            INNER JOIN events e ON pmt.event_id = e.event_id
            WHERE pmt.process_map_id = :id
            ORDER BY pmt.linked_date DESC
            LIMIT 5
        ");
        $stmt->execute([':id' => $processMapId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

function getLinkedOperationalEvents($pdo, $processMapId) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                oe.event_id,
                oe.event_type,
                oe.description,
                oe.status,
                oe.created_at,
                pmt.linked_date,
                pmt.notes
            FROM process_map_operational_event pmt
            INNER JOIN operational_events oe ON pmt.operational_event_id = oe.event_id
            WHERE pmt.process_map_id = :id
            ORDER BY pmt.linked_date DESC
            LIMIT 5
        ");
        $stmt->execute([':id' => $processMapId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

function getLinkedPermits($pdo, $processMapId) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                p.permit_id,
                p.permit_type,
                p.status,
                p.issue_date,
                p.expiry_date,
                pmt.linked_date,
                pmt.notes
            FROM process_map_permit pmt
            INNER JOIN permits p ON pmt.permit_id = p.permit_id
            WHERE pmt.process_map_id = :id
            ORDER BY pmt.linked_date DESC
            LIMIT 5
        ");
        $stmt->execute([':id' => $processMapId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

function getLinkedOFI($pdo, $processMapId) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                ofi.ofi_id,
                ofi.recommended_improvement,
                ofi.implementation_status,
                oe.event_type,
                pmt.linked_date,
                pmt.notes
            FROM process_map_ofi pmt
            INNER JOIN ofi_details ofi ON pmt.ofi_id = ofi.ofi_id
            INNER JOIN operational_events oe ON ofi.event_id = oe.event_id
            WHERE pmt.process_map_id = :id
            ORDER BY pmt.linked_date DESC
            LIMIT 5
        ");
        $stmt->execute([':id' => $processMapId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

function getLinkedHIRA($pdo, $processMapId) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                h.hira_id,
                h.scope_type,
                h.status,
                h.assessment_date,
                pmt.linked_date,
                pmt.notes
            FROM process_map_hira pmt
            INNER JOIN hira_register h ON pmt.hira_id = h.hira_id
            WHERE pmt.process_map_id = :id
            ORDER BY pmt.linked_date DESC
            LIMIT 5
        ");
        $stmt->execute([':id' => $processMapId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

function getLinkedActivities($pdo, $processMapId) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                a.activity_id,
                a.activity_name,
                a.activity_detail,
                a.status,
                a.due_date,
                a.owner,
                pmt.linked_date,
                pmt.notes
            FROM process_map_activity pmt
            INNER JOIN activities a ON pmt.activity_id = a.activity_id
            WHERE pmt.process_map_id = :id
            ORDER BY pmt.linked_date DESC
            LIMIT 5
        ");
        $stmt->execute([':id' => $processMapId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

// Link management functions
function linkEntity($pdo, $input = null) {
    if ($input === null) {
        $rawInput = file_get_contents('php://input');
        $input = !empty($rawInput) ? json_decode($rawInput, true) : [];
    }
    
    if (!isset($input['process_map_id']) || !isset($input['entity_type']) || !isset($input['entity_id'])) {
        throw new Exception('process_map_id, entity_type, and entity_id are required');
    }
    
    $processMapId = $input['process_map_id'];
    $entityType = $input['entity_type'];
    $entityId = $input['entity_id'];
    $notes = $input['notes'] ?? null;
    $linkedBy = $input['linked_by'] ?? null;
    
    // Map entity types to junction tables and foreign key column names
    $entityMappings = [
        'event' => ['table' => 'process_map_event', 'fk' => 'event_id', 'ref_table' => 'events'],
        'operational_event' => ['table' => 'process_map_operational_event', 'fk' => 'operational_event_id', 'ref_table' => 'operational_events'],
        'permit' => ['table' => 'process_map_permit', 'fk' => 'permit_id', 'ref_table' => 'permits'],
        'ofi' => ['table' => 'process_map_ofi', 'fk' => 'ofi_id', 'ref_table' => 'ofi_details'],
        'task' => ['table' => 'process_map_task', 'fk' => 'task_id', 'ref_table' => 'tasks'],
        'hira' => ['table' => 'process_map_hira', 'fk' => 'hira_id', 'ref_table' => 'hira_register'],
        'activity' => ['table' => 'process_map_activity', 'fk' => 'activity_id', 'ref_table' => 'activities'],
        'risk' => ['table' => 'process_map_risk', 'fk' => 'risk_id', 'ref_table' => 'risks'],
        'document' => ['table' => 'process_map_document', 'fk' => 'document_id', 'ref_table' => 'documents'],
        'people' => ['table' => 'process_map_people', 'fk' => 'people_id', 'ref_table' => 'people'],
        'equipment' => ['table' => 'process_map_equipment', 'fk' => 'equipment_id', 'ref_table' => 'equipment'],
        'material' => ['table' => 'process_map_material', 'fk' => 'material_id', 'ref_table' => 'materials'],
        'energy' => ['table' => 'process_map_energy', 'fk' => 'energy_id', 'ref_table' => 'energy'],
        'area' => ['table' => 'process_map_area', 'fk' => 'area_id', 'ref_table' => 'areas'],
    ];
    
    if (!isset($entityMappings[$entityType])) {
        throw new Exception('Invalid entity_type: ' . $entityType);
    }
    
    $mapping = $entityMappings[$entityType];
    $table = $mapping['table'];
    $fkColumn = $mapping['fk'];
    
    // Verify entity exists
    $refTable = $mapping['ref_table'];
    $refIdColumn = $fkColumn === 'hira_id' ? 'hira_id' : (str_replace('_id', '_id', $fkColumn));
    $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM `{$refTable}` WHERE `{$refIdColumn}` = :id");
    $checkStmt->execute([':id' => $entityId]);
    if ($checkStmt->fetchColumn() == 0) {
        throw new Exception("Entity not found in {$refTable}");
    }
    
    // Insert link
    $sql = "INSERT INTO `{$table}` (process_map_id, `{$fkColumn}`, linked_by, notes) 
            VALUES (:process_map_id, :entity_id, :linked_by, :notes)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':process_map_id' => $processMapId,
        ':entity_id' => $entityId,
        ':linked_by' => $linkedBy,
        ':notes' => $notes
    ]);
    
    return [
        'success' => true,
        'message' => 'Entity linked successfully',
        'data' => ['id' => $pdo->lastInsertId()]
    ];
}

function unlinkEntity($pdo, $input = null) {
    if ($input === null) {
        $rawInput = file_get_contents('php://input');
        $input = !empty($rawInput) ? json_decode($rawInput, true) : [];
    }
    
    if (!isset($input['process_map_id']) || !isset($input['entity_type']) || !isset($input['entity_id'])) {
        throw new Exception('process_map_id, entity_type, and entity_id are required');
    }
    
    $processMapId = $input['process_map_id'];
    $entityType = $input['entity_type'];
    $entityId = $input['entity_id'];
    
    $entityMappings = [
        'event' => ['table' => 'process_map_event', 'fk' => 'event_id'],
        'operational_event' => ['table' => 'process_map_operational_event', 'fk' => 'operational_event_id'],
        'permit' => ['table' => 'process_map_permit', 'fk' => 'permit_id'],
        'ofi' => ['table' => 'process_map_ofi', 'fk' => 'ofi_id'],
        'task' => ['table' => 'process_map_task', 'fk' => 'task_id'],
        'hira' => ['table' => 'process_map_hira', 'fk' => 'hira_id'],
        'risk' => ['table' => 'process_map_risk', 'fk' => 'risk_id'],
        'document' => ['table' => 'process_map_document', 'fk' => 'document_id'],
        'people' => ['table' => 'process_map_people', 'fk' => 'people_id'],
        'equipment' => ['table' => 'process_map_equipment', 'fk' => 'equipment_id'],
        'material' => ['table' => 'process_map_material', 'fk' => 'material_id'],
        'energy' => ['table' => 'process_map_energy', 'fk' => 'energy_id'],
        'area' => ['table' => 'process_map_area', 'fk' => 'area_id'],
    ];
    
    if (!isset($entityMappings[$entityType])) {
        throw new Exception('Invalid entity_type: ' . $entityType);
    }
    
    $mapping = $entityMappings[$entityType];
    $table = $mapping['table'];
    $fkColumn = $mapping['fk'];
    
    $sql = "DELETE FROM `{$table}` WHERE process_map_id = :process_map_id AND `{$fkColumn}` = :entity_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':process_map_id' => $processMapId,
        ':entity_id' => $entityId
    ]);
    
    return [
        'success' => true,
        'message' => 'Entity unlinked successfully'
    ];
}

function getLinkedEntities($pdo, $processMapId, $entityType) {
    $entityMappings = [
        'event' => ['table' => 'process_map_event', 'fk' => 'event_id', 'ref_table' => 'events', 'select' => 'e.event_id, e.event_type, e.description, e.status, e.reported_date'],
        'operational_event' => ['table' => 'process_map_operational_event', 'fk' => 'operational_event_id', 'ref_table' => 'operational_events', 'select' => 'oe.event_id, oe.event_type, oe.description, oe.status, oe.created_at'],
        'permit' => ['table' => 'process_map_permit', 'fk' => 'permit_id', 'ref_table' => 'permits', 'select' => 'p.permit_id, p.permit_type, p.status, p.issue_date, p.expiry_date'],
        'ofi' => ['table' => 'process_map_ofi', 'fk' => 'ofi_id', 'ref_table' => 'ofi_details', 'select' => 'ofi.ofi_id, ofi.recommended_improvement, ofi.implementation_status'],
        'task' => ['table' => 'process_map_task', 'fk' => 'task_id', 'ref_table' => 'tasks', 'select' => 't.task_id, t.task_name, t.status, t.priority, t.due_date'],
        'hira' => ['table' => 'process_map_hira', 'fk' => 'hira_id', 'ref_table' => 'hira_register', 'select' => 'h.hira_id, h.scope_type, h.status, h.assessment_date'],
        'activity' => ['table' => 'process_map_activity', 'fk' => 'activity_id', 'ref_table' => 'activities', 'select' => 'a.activity_id, a.activity_name, a.status, a.due_date'],
        'risk' => ['table' => 'process_map_risk', 'fk' => 'risk_id', 'ref_table' => 'risks', 'select' => 'r.risk_id, r.risk_description, r.risk_rate_before, r.risk_rate_after'],
    ];
    
    if (!isset($entityMappings[$entityType])) {
        throw new Exception('Invalid entity_type: ' . $entityType);
    }
    
    $mapping = $entityMappings[$entityType];
    $table = $mapping['table'];
    $fkColumn = $mapping['fk'];
    $refTable = $mapping['ref_table'];
    $select = $mapping['select'];
    $alias = substr($refTable, 0, 1);
    
    try {
        $sql = "SELECT {$select}, pmt.linked_date, pmt.notes, pmt.linked_by
                FROM `{$table}` pmt
                INNER JOIN `{$refTable}` {$alias} ON pmt.`{$fkColumn}` = {$alias}.`{$fkColumn}`
                WHERE pmt.process_map_id = :id
                ORDER BY pmt.linked_date DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $processMapId]);
        return [
            'success' => true,
            'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)
        ];
    } catch (PDOException $e) {
        return [
            'success' => true,
            'data' => []
        ];
    }
}

// Get process subtree (recursive query)
function getProcessSubtree($pdo, $rootId) {
    try {
        // Use recursive CTE if MySQL 8.0+, otherwise use iterative approach
        $stmt = $pdo->prepare("
            WITH RECURSIVE process_tree AS (
                SELECT id, type, text, parent, 0 as level
                FROM process_map
                WHERE id = :root_id
                
                UNION ALL
                
                SELECT pm.id, pm.type, pm.text, pm.parent, pt.level + 1
                FROM process_map pm
                INNER JOIN process_tree pt ON pm.parent = pt.id
            )
            SELECT * FROM process_tree
            ORDER BY level, id
        ");
        $stmt->execute([':root_id' => $rootId]);
        return [
            'success' => true,
            'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)
        ];
    } catch (PDOException $e) {
        // Fallback to iterative approach for older MySQL
        $result = [];
        $queue = [$rootId];
        $processed = [];
        
        while (!empty($queue)) {
            $currentId = array_shift($queue);
            if (in_array($currentId, $processed)) continue;
            $processed[] = $currentId;
            
            $stmt = $pdo->prepare("
                SELECT id, type, text, parent 
                FROM process_map 
                WHERE id = :id OR parent = :id
                ORDER BY parent, id
            ");
            $stmt->execute([':id' => $currentId]);
            $nodes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($nodes as $node) {
                if ($node['id'] == $currentId) {
                    $result[] = $node;
                }
                if ($node['parent'] == $currentId && !in_array($node['id'], $processed)) {
                    $queue[] = $node['id'];
                }
            }
        }
        
        return [
            'success' => true,
            'data' => $result
        ];
    }
}

// Reorder process nodes
function reorderProcessNodes($pdo, $parentId, $input = null) {
    if ($input === null) {
        $rawInput = file_get_contents('php://input');
        $input = !empty($rawInput) ? json_decode($rawInput, true) : [];
    }
    
    if (!isset($input['node_orders']) || !is_array($input['node_orders'])) {
        throw new Exception('node_orders array is required');
    }
    
    $pdo->beginTransaction();
    
    try {
        foreach ($input['node_orders'] as $order => $nodeId) {
            $stmt = $pdo->prepare("UPDATE process_map SET `order` = :order WHERE id = :id AND parent = :parent");
            $stmt->execute([
                ':order' => $order,
                ':id' => $nodeId,
                ':parent' => $parentId
            ]);
            
            // Log audit
            logProcessMapAudit($pdo, $nodeId, 'UPDATE', 'order', null, $order, $input['updated_by'] ?? null);
        }
        
        $pdo->commit();
        
        return [
            'success' => true,
            'message' => 'Nodes reordered successfully'
        ];
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

// Bulk unlink entities
function bulkUnlinkEntities($pdo, $input = null) {
    if ($input === null) {
        $rawInput = file_get_contents('php://input');
        $input = !empty($rawInput) ? json_decode($rawInput, true) : [];
    }
    
    if (!isset($input['process_map_id']) || !isset($input['entity_type']) || !isset($input['entity_ids'])) {
        throw new Exception('process_map_id, entity_type, and entity_ids array are required');
    }
    
    $processMapId = $input['process_map_id'];
    $entityType = $input['entity_type'];
    $entityIds = $input['entity_ids'];
    
    $entityMappings = [
        'event' => ['table' => 'process_map_event', 'fk' => 'event_id'],
        'operational_event' => ['table' => 'process_map_operational_event', 'fk' => 'operational_event_id'],
        'permit' => ['table' => 'process_map_permit', 'fk' => 'permit_id'],
        'ofi' => ['table' => 'process_map_ofi', 'fk' => 'ofi_id'],
        'task' => ['table' => 'process_map_task', 'fk' => 'task_id'],
        'activity' => ['table' => 'process_map_activity', 'fk' => 'activity_id'],
        'hira' => ['table' => 'process_map_hira', 'fk' => 'hira_id'],
        'risk' => ['table' => 'process_map_risk', 'fk' => 'risk_id'],
    ];
    
    if (!isset($entityMappings[$entityType])) {
        throw new Exception('Invalid entity_type: ' . $entityType);
    }
    
    $mapping = $entityMappings[$entityType];
    $table = $mapping['table'];
    $fkColumn = $mapping['fk'];
    
    $placeholders = implode(',', array_fill(0, count($entityIds), '?'));
    $sql = "DELETE FROM `{$table}` WHERE process_map_id = ? AND `{$fkColumn}` IN ({$placeholders})";
    
    $params = array_merge([$processMapId], $entityIds);
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    return [
        'success' => true,
        'message' => 'Entities unlinked successfully',
        'count' => $stmt->rowCount()
    ];
}

function createProcessMap($pdo, $input = null) {
    // Use provided input or read from php://input
    if ($input === null) {
        $rawInput = file_get_contents('php://input');
        $input = !empty($rawInput) ? json_decode($rawInput, true) : [];
    }
    
    if (!isset($input['type']) || !isset($input['text'])) {
        throw new Exception('Type and text are required');
    }
    
    // Build dynamic INSERT query based on available fields
    $fields = ['type', 'text'];
    $values = [':type', ':text'];
    $params = [
        ':type' => $input['type'],
        ':text' => $input['text']
    ];
    
    // Add optional fields if they exist in table
    $optionalFields = ['parent', 'status', 'owner_id', 'department_id', 'order', 'description', 'notes', 'created_by'];
    foreach ($optionalFields as $field) {
        if (isset($input[$field])) {
            $fields[] = $field;
            $values[] = ":{$field}";
            $params[":{$field}"] = $input[$field];
        }
    }
    
    // Set created_by if not provided
    if (!isset($input['created_by']) && isset($_SESSION['user_id'])) {
        $fields[] = 'created_by';
        $values[] = ':created_by';
        $params[':created_by'] = $_SESSION['user_id'];
    }
    
    $sql = "INSERT INTO process_map (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $values) . ")";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    $id = $pdo->lastInsertId();
    
    // Log audit trail if table exists
    try {
        logProcessMapAudit($pdo, $id, 'CREATE', null, null, json_encode($input), $params[':created_by'] ?? null);
    } catch (Exception $e) {
        // Audit table might not exist yet
    }
    
    return [
        'success' => true,
        'data' => ['id' => $id]
    ];
}

function logProcessMapAudit($pdo, $processMapId, $action, $fieldName = null, $oldValue = null, $newValue = null, $changedBy = null) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO process_map_audit 
            (process_map_id, action, field_name, old_value, new_value, changed_by, reason, ip_address, user_agent)
            VALUES 
            (:process_map_id, :action, :field_name, :old_value, :new_value, :changed_by, :reason, :ip_address, :user_agent)
        ");
        $stmt->execute([
            ':process_map_id' => $processMapId,
            ':action' => $action,
            ':field_name' => $fieldName,
            ':old_value' => $oldValue,
            ':new_value' => $newValue,
            ':changed_by' => $changedBy ?? ($_SESSION['user_id'] ?? null),
            ':reason' => null,
            ':ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            ':user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    } catch (PDOException $e) {
        // Audit table might not exist, ignore
    }
}

function updateProcessMap($pdo, $id, $input = null) {
    // Use provided input or read from php://input
    if ($input === null) {
        $rawInput = file_get_contents('php://input');
        $input = !empty($rawInput) ? json_decode($rawInput, true) : [];
    }
    
    // Get old values for audit trail
    $oldValues = [];
    try {
        $stmt = $pdo->prepare("SELECT * FROM process_map WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $oldValues = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Ignore if query fails
    }
    
    $updates = [];
    $params = [':id' => $id];
    $updatedBy = $input['updated_by'] ?? ($_SESSION['user_id'] ?? null);
    
    // Allowed fields for update
    $allowedFields = ['text', 'parent', 'status', 'owner_id', 'department_id', 'order', 'description', 'notes'];
    
    foreach ($allowedFields as $field) {
        if (isset($input[$field])) {
            $updates[] = "{$field} = :{$field}";
            $params[":{$field}"] = $input[$field];
            
            // Log audit for changed fields
            if (isset($oldValues[$field]) && $oldValues[$field] != $input[$field]) {
                logProcessMapAudit($pdo, $id, 'UPDATE', $field, $oldValues[$field], $input[$field], $updatedBy);
            }
        }
    }
    
    // Always update updated_by and updated_at if fields exist
    try {
        $stmt = $pdo->prepare("SHOW COLUMNS FROM process_map LIKE 'updated_by'");
        $stmt->execute();
        if ($stmt->rowCount() > 0) {
            $updates[] = 'updated_by = :updated_by';
            $params[':updated_by'] = $updatedBy;
        }
    } catch (PDOException $e) {
        // Field might not exist
    }
    
    if (empty($updates)) {
        throw new Exception('No fields to update');
    }
    
    $sql = "UPDATE process_map SET " . implode(', ', $updates) . " WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    return [
        'success' => true,
        'message' => 'Process map updated successfully'
    ];
}

function deleteProcessMap($pdo, $id) {
    $stmt = $pdo->prepare("DELETE FROM process_map WHERE id = :id");
    $stmt->execute([':id' => $id]);
    
    return [
        'success' => true,
        'message' => 'Process map deleted successfully'
    ];
}

// ============================================================================
// Branch Management Functions
// ============================================================================

function listBranches($pdo) {
    $stmt = $pdo->query("
        SELECT 
            id,
            name,
            description,
            color,
            icon,
            category,
            is_active,
            created_at,
            updated_at
        FROM process_branches
        WHERE is_active = 1
        ORDER BY category, name
    ");
    
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return [
        'success' => true,
        'data' => $data
    ];
}

function getBranch($pdo, $branchId) {
    $stmt = $pdo->prepare("
        SELECT 
            id,
            name,
            description,
            color,
            icon,
            is_active,
            created_at,
            updated_at,
            created_by
        FROM process_branches
        WHERE id = :id
    ");
    $stmt->execute([':id' => $branchId]);
    
    $branch = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$branch) {
        return [
            'success' => false,
            'error' => 'Branch not found'
        ];
    }
    
    return [
        'success' => true,
        'data' => $branch
    ];
}

function getBranchProcesses($pdo, $branchId) {
    // Get all processes in the branch with their hierarchy
    $stmt = $pdo->prepare("
        SELECT 
            pm.id,
            pm.type,
            pm.text,
            pm.parent,
            pm.status,
            pm.description,
            pm.notes,
            pm.order,
            pm.primary_branch_id,
            pbi.branch_id,
            pbi.is_root AS is_branch_root,
            pbi.order AS branch_order
        FROM process_branch_items pbi
        INNER JOIN process_map pm ON pbi.process_map_id = pm.id
        WHERE pbi.branch_id = :branch_id
        ORDER BY pbi.order, pm.order, pm.id
    ");
    $stmt->execute([':branch_id' => $branchId]);
    
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return [
        'success' => true,
        'data' => $data
    ];
}
?>

