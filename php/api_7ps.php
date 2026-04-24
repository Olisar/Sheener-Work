<?php
/* File: sheener/php/api_7ps.php */

/**
 * 7Ps API
 * Handles operations for all 7Ps elements
 */

// Suppress HTML error output
error_reporting(E_ALL);
ini_set('display_errors', 0);

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
    
    $action = $_GET['action'] ?? 'list';
    $type = $_GET['type'] ?? '';
    $id = $_GET['id'] ?? null;
    $processId = $_GET['process_id'] ?? null;
    
    switch ($action) {
        case 'list':
            if (!$type) {
                throw new Exception('Type is required');
            }
            $response = list7Ps($pdo, $type, $processId);
            break;
            
        case 'link':
            $response = link7P($pdo);
            break;
            
        case 'unlink':
            $response = unlink7P($pdo);
            break;
            
        case 'delete':
            if (!$type || !$id) {
                throw new Exception('Type and ID are required');
            }
            $response = delete7P($pdo, $type, $id);
            break;
            
        default:
            throw new Exception('Invalid action');
    }
    
    echo json_encode($response);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

function list7Ps($pdo, $type, $processId = null) {
    $data = [];
    
    switch ($type) {
        case 'people':
            // Check if people_departments junction table exists
            try {
                $checkTable = $pdo->query("SHOW TABLES LIKE 'people_departments'");
                $hasJunctionTable = $checkTable->rowCount() > 0;
            } catch (PDOException $e) {
                $hasJunctionTable = false;
            }
            
            if ($hasJunctionTable) {
                // Optimized query with better grouping
                $stmt = $pdo->query("
                    SELECT 
                        p.people_id as id,
                        p.FirstName,
                        p.LastName,
                        p.Email,
                        p.Position,
                        p.IsActive,
                        (SELECT d.DepartmentName FROM departments d 
                         JOIN people_departments pd ON d.department_id = pd.DepartmentID 
                         WHERE pd.PersonID = p.people_id LIMIT 1) as department_name
                    FROM people p
                    WHERE p.IsActive = 1
                    ORDER BY p.LastName, p.FirstName
                    LIMIT 2000
                ");
            } else {
                // Fallback if junction table doesn't exist
                $stmt = $pdo->query("
                    SELECT 
                        p.people_id as id,
                        p.FirstName,
                        p.LastName,
                        p.Email,
                        p.Position,
                        p.IsActive,
                        'No Department' as department_name
                    FROM people p
                    WHERE p.IsActive = 1
                    ORDER BY p.LastName, p.FirstName
                ");
            }
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;
            
        case 'plant':
        case 'equipment':
            $stmt = $pdo->query("
                SELECT 
                    equipment_id as id,
                    item_name,
                    equipment_type,
                    serial_number,
                    location,
                    status,
                    responsible_person_id
                FROM equipment
                WHERE status = 'Active'
                ORDER BY item_name
                LIMIT 2000
            ");
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;
            
        case 'place':
        case 'areas':
            $stmt = $pdo->query("
                SELECT 
                    area_id as id,
                    area_name,
                    area_type,
                    location_code,
                    description,
                    is_active
                FROM areas
                WHERE is_active = 1
                ORDER BY area_name
            ");
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;
            
        case 'product':
        case 'materials':
            try {
                $stmt = $pdo->query("
                    SELECT 
                        MaterialID as id,
                        MaterialName,
                        MaterialType,
                        COALESCE(UnitOfMeasure, '') as Unit,
                        Description
                    FROM Materials
                    ORDER BY MaterialName
                ");
                $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                // Fallback if Materials table doesn't exist, try lowercase
                try {
                    $stmt = $pdo->query("
                        SELECT 
                            MaterialID as id,
                            MaterialName,
                            MaterialType,
                            COALESCE(UnitOfMeasure, Unit, '') as Unit,
                            Description
                        FROM materials
                        ORDER BY MaterialName
                    ");
                    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                } catch (PDOException $e2) {
                    throw new Exception('Materials table not found: ' . $e2->getMessage());
                }
            }
            break;
            
        case 'energy':
            $stmt = $pdo->query("
                SELECT 
                    e.EnergyID as id,
                    e.EnergyName,
                    e.Description,
                    et.EnergyTypeName as EnergyType
                FROM energy e
                LEFT JOIN energytype et ON e.EnergyTypeID = et.EnergyTypeID
                ORDER BY e.EnergyName
            ");
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;
            
        case 'purpose':
        case 'documents':
            try {
                $stmt = $pdo->query("
                    SELECT 
                        d.DocumentID as id,
                        COALESCE(d.Title, d.DocCode, 'Untitled') as DocumentName,
                        COALESCE(dt.Name, 'Unknown') as DocumentType,
                        COALESCE(dv.VersionNumber, '') as Version,
                        COALESCE(s.StatusName, 'Unknown') as Status,
                        COALESCE(d.EffectiveDate, d.UploadedDate, NULL) as UploadedDate
                    FROM documents d
                    LEFT JOIN documentstatuses s ON d.StatusID = s.StatusID
                    LEFT JOIN document_types dt ON d.DocumentTypeID = dt.DocumentTypeID
                    LEFT JOIN documentversions dv ON d.CurrentVersionID = dv.VersionID
                    ORDER BY d.DocCode, d.Title
                    LIMIT 2000
                ");
                $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                // Fallback to simpler query if joins fail
                try {
                    $stmt = $pdo->query("
                        SELECT 
                            DocumentID as id,
                            COALESCE(Title, DocCode, 'Untitled') as DocumentName,
                            'Unknown' as DocumentType,
                            '' as Version,
                            'Unknown' as Status,
                            EffectiveDate as UploadedDate
                        FROM documents
                        ORDER BY DocCode, Title
                    ");
                    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                } catch (PDOException $e2) {
                    throw new Exception('Documents table not found: ' . $e2->getMessage());
                }
            }
            break;
            
        case 'process':
            $stmt = $pdo->query("
                SELECT 
                    id,
                    type,
                    text,
                    parent
                FROM process_map
                WHERE type = 'process'
                ORDER BY text
            ");
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;
            
        default:
            throw new Exception('Invalid type');
    }
    
    // If process_id is provided, filter to only linked items
    if ($processId) {
        $linkedIds = getLinkedIds($pdo, $type, $processId);
        $data = array_filter($data, function($item) use ($linkedIds, $type) {
            $id = $item['id'] ?? $item[getTypeIdField($type)] ?? null;
            return in_array($id, $linkedIds);
        });
        $data = array_values($data);
    }
    
    return [
        'success' => true,
        'data' => $data
    ];
}

function getLinkedIds($pdo, $type, $processId) {
    $tableMap = [
        'people' => 'process_map_people',
        'equipment' => 'process_map_equipment',
        'plant' => 'process_map_equipment',
        'areas' => 'process_map_area',
        'place' => 'process_map_area',
        'materials' => 'process_map_material',
        'product' => 'process_map_material',
        'energy' => 'process_map_energy',
        'documents' => 'process_map_document',
        'purpose' => 'process_map_document'
    ];
    
    $table = $tableMap[$type] ?? null;
    if (!$table) {
        return [];
    }
    
    try {
        $idField = getTypeIdField($type);
        $stmt = $pdo->prepare("SELECT {$idField} FROM `{$table}` WHERE process_map_id = :id");
        $stmt->execute([':id' => $processId]);
        $results = $stmt->fetchAll(PDO::FETCH_COLUMN);
        return $results;
    } catch (PDOException $e) {
        return [];
    }
}

function getTypeIdField($type) {
    $map = [
        'people' => 'people_id',
        'equipment' => 'equipment_id',
        'plant' => 'equipment_id',
        'areas' => 'area_id',
        'place' => 'area_id',
        'materials' => 'material_id',
        'product' => 'material_id',
        'energy' => 'energy_id',
        'documents' => 'document_id',
        'purpose' => 'document_id'
    ];
    return $map[$type] ?? 'id';
}

function link7P($pdo) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $processId = $input['process_id'] ?? null;
    $type = $input['type'] ?? '';
    $itemId = $input['item_id'] ?? null;
    
    if (!$processId || !$type || !$itemId) {
        throw new Exception('process_id, type, and item_id are required');
    }
    
    $tableMap = [
        'people' => 'process_map_people',
        'equipment' => 'process_map_equipment',
        'plant' => 'process_map_equipment',
        'areas' => 'process_map_area',
        'place' => 'process_map_area',
        'materials' => 'process_map_material',
        'product' => 'process_map_material',
        'energy' => 'process_map_energy',
        'documents' => 'process_map_document',
        'purpose' => 'process_map_document'
    ];
    
    $table = $tableMap[$type] ?? null;
    if (!$table) {
        throw new Exception('Invalid type');
    }
    
    $idField = getTypeIdField($type);
    
    try {
        $stmt = $pdo->prepare("INSERT INTO `{$table}` (process_map_id, {$idField}) VALUES (:process_id, :item_id)");
        $stmt->execute([
            ':process_id' => $processId,
            ':item_id' => $itemId
        ]);
        
        return [
            'success' => true,
            'message' => 'Item linked successfully'
        ];
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
            return [
                'success' => true,
                'message' => 'Item already linked'
            ];
        }
        throw $e;
    }
}

function unlink7P($pdo) {
    $processId = $_GET['process_id'] ?? null;
    $type = $_GET['type'] ?? '';
    $itemId = $_GET['id'] ?? null;
    
    if (!$processId || !$type || !$itemId) {
        throw new Exception('process_id, type, and id are required');
    }
    
    $tableMap = [
        'people' => 'process_map_people',
        'equipment' => 'process_map_equipment',
        'plant' => 'process_map_equipment',
        'areas' => 'process_map_area',
        'place' => 'process_map_area',
        'materials' => 'process_map_material',
        'product' => 'process_map_material',
        'energy' => 'process_map_energy',
        'documents' => 'process_map_document',
        'purpose' => 'process_map_document'
    ];
    
    $table = $tableMap[$type] ?? null;
    if (!$table) {
        throw new Exception('Invalid type');
    }
    
    $idField = getTypeIdField($type);
    
    $stmt = $pdo->prepare("DELETE FROM `{$table}` WHERE process_map_id = :process_id AND {$idField} = :item_id");
    $stmt->execute([
        ':process_id' => $processId,
        ':item_id' => $itemId
    ]);
    
    return [
        'success' => true,
        'message' => 'Item unlinked successfully'
    ];
}

function delete7P($pdo, $type, $id) {
    $tableMap = [
        'people' => 'people',
        'equipment' => 'equipment',
        'plant' => 'equipment',
        'areas' => 'areas',
        'place' => 'areas',
        'materials' => 'materials',
        'product' => 'materials',
        'energy' => 'energy',
        'documents' => 'documents',
        'purpose' => 'documents'
    ];
    
    $table = $tableMap[$type] ?? null;
    if (!$table) {
        throw new Exception('Invalid type');
    }
    
    $idField = getTypeIdField($type);
    
    $stmt = $pdo->prepare("DELETE FROM `{$table}` WHERE {$idField} = :id");
    $stmt->execute([':id' => $id]);
    
    return [
        'success' => true,
        'message' => 'Item deleted successfully'
    ];
}
?>

