<?php
/* File: sheener/api/investigations/index.php */

/**
 * Investigations RESTful API
 * Handles all /api/investigations/* endpoints
 */

session_start();
require_once __DIR__ . '/../../php/database.php';

header('Content-Type: application/json; charset=UTF-8');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Check authentication
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Handle simple POST request for creating investigation (most common case)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_GET)) {
        $data = json_decode(file_get_contents("php://input"), true);
        
        // If no JSON data, try POST data
        if (!$data) {
            $data = $_POST;
        }
        
        // Basic validation
        if (!isset($data['event_id']) || !isset($data['investigation_type']) || !isset($data['lead_id'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Missing required fields: event_id, investigation_type, or lead_id'
            ]);
            exit;
        }
        
        // Sanitize and map input variables
        $event_id = (int)$data['event_id'];
        $investigation_type = $data['investigation_type'];
        $lead_id = (int)$data['lead_id'];
        $trigger_reason = $data['trigger_reason'] ?? null;
        $scope_description = $data['scope_description'] ?? null;
        $team_notes = $data['team_notes'] ?? null;
        
        // Validate investigation_type against enum values
        $validTypes = ['Incident', 'Near Miss', 'Breakdown', 'Energy Deviation', 'Quality', 'EHS', 'Other'];
        if (!in_array($investigation_type, $validTypes)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Invalid investigation_type. Must be one of: ' . implode(', ', $validTypes)
            ]);
            exit;
        }
        
        // Validate foreign key constraints before insertion
        // Check if event_id exists in events table (primary table)
        $eventCheck = $pdo->prepare("SELECT event_id FROM events WHERE event_id = :event_id");
        $eventCheck->execute([':event_id' => $event_id]);
        $eventExists = $eventCheck->fetch();
        
        if (!$eventExists) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => "Event ID {$event_id} does not exist in events table"
            ]);
            exit;
        }
        
        // Note: Foreign key constraint has been updated to reference 'events' table
        // The workaround code below is kept as a fallback for edge cases
        // but should no longer be needed after running the migration script
        
        // Optional: Verify foreign key points to correct table (for debugging)
        // This check can be removed in production if FK is confirmed fixed
        try {
            $fkCheck = $pdo->query("
                SELECT REFERENCED_TABLE_NAME 
                FROM information_schema.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = DATABASE() 
                  AND TABLE_NAME = 'investigations' 
                  AND CONSTRAINT_NAME = 'fk_investigation_event' 
                  AND COLUMN_NAME = 'event_id'
            ");
            $fkInfo = $fkCheck->fetch(PDO::FETCH_ASSOC);
            
            // If FK still points to operational_events (shouldn't happen after migration)
            if ($fkInfo && $fkInfo['REFERENCED_TABLE_NAME'] === 'operational_events') {
                error_log("Warning: Foreign key still points to operational_events. Migration may not have been applied.");
                // Fallback: ensure event exists in operational_events
                $opEventCheck = $pdo->prepare("SELECT event_id FROM operational_events WHERE event_id = :event_id");
                $opEventCheck->execute([':event_id' => $event_id]);
                $opEventExists = $opEventCheck->fetch();
                
                if (!$opEventExists) {
                    // Event doesn't exist in operational_events, try to copy it
                    $copyEvent = $pdo->prepare("
                        INSERT INTO operational_events 
                        (event_id, event_type, description, reported_by, department_id, status, created_at)
                        SELECT 
                            event_id,
                            CASE 
                                WHEN event_type = 'OFI' THEN 'OFI'
                                WHEN event_type = 'Adverse Event' THEN 'Incident'
                                WHEN event_type = 'Defects' THEN 'Finding'
                                WHEN event_type = 'NonCompliance' THEN 'Finding'
                                ELSE 'Observation'
                            END as event_type,
                            COALESCE(description, '') as description,
                            reported_by,
                            COALESCE(department_id, 1) as department_id,
                            CASE 
                                WHEN status = 'Open' THEN 'Open'
                                WHEN status = 'Under Investigation' THEN 'In Progress'
                                WHEN status = 'Closed' THEN 'Closed'
                                ELSE 'Open'
                            END as status,
                            COALESCE(reported_date, NOW()) as created_at
                        FROM events
                        WHERE event_id = :event_id
                    ");
                    try {
                        $copyEvent->execute([':event_id' => $event_id]);
                    } catch (PDOException $copyError) {
                        http_response_code(400);
                        echo json_encode([
                            'success' => false,
                            'error' => "Foreign key constraint issue detected. Please ensure the migration script has been run to fix the foreign key constraint.",
                            'details' => $copyError->getMessage()
                        ]);
                        exit;
                    }
                }
            }
        } catch (PDOException $e) {
            // If FK check fails, continue - the INSERT will work if FK is correctly set
            error_log("FK check warning: " . $e->getMessage());
        }
        
        // Check if lead_id exists
        $leadCheck = $pdo->prepare("SELECT people_id FROM people WHERE people_id = :lead_id");
        $leadCheck->execute([':lead_id' => $lead_id]);
        if (!$leadCheck->fetch()) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => "Lead ID {$lead_id} does not exist in people table"
            ]);
            exit;
        }
        
        // Database insertion
        try {
            $sql = "INSERT INTO investigations (
                event_id, 
                investigation_type, 
                lead_id, 
                trigger_reason,
                scope_description, 
                team_notes,
                status,
                opened_at
            ) VALUES (
                :event_id, 
                :investigation_type, 
                :lead_id, 
                :trigger_reason,
                :scope_description, 
                :team_notes,
                'Open',
                NOW()
            )";
            
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':event_id', $event_id, PDO::PARAM_INT);
            $stmt->bindParam(':investigation_type', $investigation_type);
            $stmt->bindParam(':lead_id', $lead_id, PDO::PARAM_INT);
            $stmt->bindParam(':trigger_reason', $trigger_reason);
            $stmt->bindParam(':scope_description', $scope_description);
            $stmt->bindParam(':team_notes', $team_notes);
            
            $stmt->execute();
            
            // Get the ID of the newly created investigation
            $new_id = $pdo->lastInsertId();
            
            // Success response
            http_response_code(201);
            echo json_encode([
                'success' => true,
                'message' => 'Investigation created successfully',
                'investigation_id' => (int)$new_id
            ]);
            exit;
            
        } catch (PDOException $e) {
            // Handle database errors
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Database error creating investigation: ' . $e->getMessage()
            ]);
            exit;
        }
    }
    
    // Handle simple GET request for checking investigations by event_id
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['event_id']) && !isset($_GET['id'])) {
        $event_id = (int)$_GET['event_id'];
        
        // Explicitly list columns to avoid any non-existent column errors
        $sql = "SELECT 
                    i.investigation_id, 
                    i.event_id,
                    i.investigation_type,
                    i.trigger_reason,
                    i.lead_id,
                    i.team_notes,
                    i.scope_description,
                    i.status,
                    i.opened_at,
                    i.closed_at,
                    i.root_cause_summary,
                    i.lessons_learned,
                    e.description as event_description,
                    e.event_type,
                    CONCAT(p.FirstName, ' ', p.LastName) as lead_name
                FROM investigations i
                LEFT JOIN events e ON i.event_id = e.event_id
                LEFT JOIN people p ON i.lead_id = p.people_id
                WHERE i.event_id = :event_id
                ORDER BY i.opened_at DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':event_id' => $event_id]);
        $investigations = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'data' => $investigations
        ]);
        exit;
    }
    
    // Handle simple GET request for single investigation by id
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id']) && !isset($_GET['event_id'])) {
        $investigation_id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
        
        if ($investigation_id === false || $investigation_id <= 0) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Invalid investigation ID format'
            ]);
            exit;
        }
        
        try {
            // SQL Query to fetch investigation details with related data
            // Explicitly list columns to avoid any non-existent column errors
            $sql = "SELECT 
                        i.investigation_id, 
                        i.event_id,
                        i.investigation_type,
                        i.trigger_reason,
                        i.lead_id,
                        i.team_notes,
                        i.scope_description,
                        i.status,
                        i.opened_at,
                        i.closed_at,
                        i.root_cause_summary,
                        i.lessons_learned,
                    e.description as event_description,
                    e.event_type,
                    e.event_subcategory,
                    CONCAT(p.FirstName, ' ', p.LastName) as lead_name,
                    p.Email as lead_email
                    FROM investigations i
                    LEFT JOIN events e ON i.event_id = e.event_id
                    LEFT JOIN people p ON i.lead_id = p.people_id
                    WHERE i.investigation_id = :id";
            
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':id', $investigation_id, PDO::PARAM_INT);
            $stmt->execute();
            
            $investigation = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$investigation) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'error' => 'Investigation not found'
                ]);
                exit;
            }
            
            // Get linked RCAs
            // Note: rca_artefacts table does NOT have completed_at column, only status enum
            $rcaSql = "SELECT rca_id, method, status, created_at
                       FROM rca_artefacts
                       WHERE investigation_id = :id
                       ORDER BY created_at DESC";
            $rcaStmt = $pdo->prepare($rcaSql);
            $rcaStmt->execute([':id' => $investigation_id]);
            $investigation['rca_artefacts'] = $rcaStmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get linked tasks
            // First, get all RCA IDs for this investigation
            $rcaIdsSql = "SELECT rca_id FROM rca_artefacts WHERE investigation_id = :id";
            $rcaIdsStmt = $pdo->prepare($rcaIdsSql);
            $rcaIdsStmt->execute([':id' => $investigation_id]);
            $rcaIds = $rcaIdsStmt->fetchAll(PDO::FETCH_COLUMN);
            
            // Build task query - use placeholders for RCA IDs if any exist
            if (!empty($rcaIds)) {
                $placeholders = implode(',', array_fill(0, count($rcaIds), '?'));
                $taskSql = "SELECT t.task_id, t.task_name, t.status, t.priority, t.due_date,
                                   etl.id as link_id, etl.sourcetype
                            FROM entity_task_links etl
                            JOIN tasks t ON etl.taskid = t.task_id
                            WHERE (etl.sourcetype = 'Investigation' AND etl.sourceid = ?)
                               OR (etl.sourcetype = 'RCA' AND etl.sourceid IN ($placeholders))
                            ORDER BY etl.createdat DESC";
                $taskStmt = $pdo->prepare($taskSql);
                $taskParams = array_merge([$investigation_id], $rcaIds);
                $taskStmt->execute($taskParams);
            } else {
                // No RCAs, only check investigation links
                $taskSql = "SELECT t.task_id, t.task_name, t.status, t.priority, t.due_date,
                                   etl.id as link_id, etl.sourcetype
                            FROM entity_task_links etl
                            JOIN tasks t ON etl.taskid = t.task_id
                            WHERE etl.sourcetype = 'Investigation' AND etl.sourceid = :id
                            ORDER BY etl.createdat DESC";
                $taskStmt = $pdo->prepare($taskSql);
                $taskStmt->execute([':id' => $investigation_id]);
            }
            $investigation['linked_tasks'] = $taskStmt->fetchAll(PDO::FETCH_ASSOC);
            
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'data' => $investigation
            ]);
            exit;
            
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Database error loading investigation: ' . $e->getMessage()
            ]);
            exit;
        }
    }
    
    // Parse the request path
    $requestUri = $_SERVER['REQUEST_URI'];
    $scriptName = $_SERVER['SCRIPT_NAME'];
    
    $path = parse_url($requestUri, PHP_URL_PATH);
    $path = strtok($path, '?');
    
    $scriptDir = dirname($scriptName);
    if (strpos($path, $scriptDir) === 0) {
        $path = substr($path, strlen($scriptDir));
    }
    
    $path = trim($path, '/');
    $pathParts = array_filter(explode('/', $path));
    $pathParts = array_values($pathParts);
    
    // Remove 'api' and 'investigations' if they're in the path
    if (isset($pathParts[0]) && $pathParts[0] === 'api') array_shift($pathParts);
    if (isset($pathParts[0]) && $pathParts[0] === 'investigations') array_shift($pathParts);
    // Remove 'index.php' if it's in the path (when using path-based routing)
    if (isset($pathParts[0]) && $pathParts[0] === 'index.php') array_shift($pathParts);
    
    // Parse path and query parameters
    $pathParts = array_values($pathParts); // Ensure indices start from 0
    
    // If first part is numeric, it's the investigation ID
    if (!empty($pathParts[0]) && is_numeric($pathParts[0])) {
        $id = array_shift($pathParts);
    } else {
        $id = $_GET['id'] ?? null;
    }
    
    // Now the remaining parts start with resource
    $resource = $pathParts[0] ?? '';
    $subResource = $pathParts[1] ?? null;
    $subId = $pathParts[2] ?? null;
    
    // Get request data
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        $input = [];
    }
    
    $method = $_SERVER['REQUEST_METHOD'];
    
    // Route the request
    switch ($resource) {
        case '':
            if ($id) {
                // Check for close action or delete
                if ($method === 'PUT' && isset($input['action']) && $input['action'] === 'close') {
                    handleCloseInvestigation($pdo, $id);
                } elseif ($method === 'DELETE') {
                    handleDeleteInvestigation($pdo, $id);
                } else {
                    handleInvestigation($pdo, $method, $id, $input, $_GET);
                }
            } else {
                handleInvestigationsList($pdo, $method, $input, $_GET);
            }
            break;
            
        case 'rca':
            // For RCA routes, get investigation_id from query or path
            $investigationId = $_GET['id'] ?? $id;
            $subResource = $pathParts[1] ?? null;
            $subId = $_GET['rca_id'] ?? $pathParts[2] ?? null;
            
            if ($investigationId && $subResource) {
                handleRCA($pdo, $method, $investigationId, $subResource, $subId, $input);
            } else {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Invalid RCA endpoint']);
            }
            break;
            
        case 'validation_status':
            if ($id) {
                handleValidationStatus($pdo, $id);
            } else {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Investigation ID required']);
            }
            break;
            
        default:
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Endpoint not found']);
    }
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

// ============================================================================
// Investigation Handlers
// ============================================================================

function handleInvestigationsList($pdo, $method, $input, $query) {
    if ($method === 'GET') {
        $where = ['1=1'];
        $params = [];
        
        if (!empty($query['event_id'])) {
            $where[] = 'i.event_id = :event_id';
            $params[':event_id'] = $query['event_id'];
        }
        
        if (!empty($query['status'])) {
            $where[] = 'i.status = :status';
            $params[':status'] = $query['status'];
        }
        
        // Explicitly list columns to avoid any non-existent column errors
        $sql = "SELECT 
                    i.investigation_id, 
                    i.event_id,
                    i.investigation_type,
                    i.trigger_reason,
                    i.lead_id,
                    i.team_notes,
                    i.scope_description,
                    i.status,
                    i.opened_at,
                    i.closed_at,
                    i.root_cause_summary,
                    i.lessons_learned,
                    e.description as event_description,
                    e.event_type,
                    CONCAT(p.FirstName, ' ', p.LastName) as lead_name
                FROM investigations i
                LEFT JOIN events e ON i.event_id = e.event_id
                LEFT JOIN people p ON i.lead_id = p.people_id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY i.opened_at DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $investigations = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'data' => $investigations
        ]);
    } elseif ($method === 'POST') {
        // Create new investigation
        $event_id = $input['event_id'] ?? null;
        $investigation_type = $input['investigation_type'] ?? null;
        $lead_id = $input['lead_id'] ?? null;
        $trigger_reason = $input['trigger_reason'] ?? null;
        $scope_description = $input['scope_description'] ?? null;
        
        if (!$event_id || !$investigation_type || !$lead_id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Missing required fields: event_id, investigation_type, lead_id']);
            return;
        }
        
        $sql = "INSERT INTO investigations 
                (event_id, investigation_type, lead_id, trigger_reason, scope_description, status, opened_at)
                VALUES (:event_id, :investigation_type, :lead_id, :trigger_reason, :scope_description, 'Open', NOW())";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':event_id' => $event_id,
            ':investigation_type' => $investigation_type,
            ':lead_id' => $lead_id,
            ':trigger_reason' => $trigger_reason,
            ':scope_description' => $scope_description
        ]);
        
        $investigation_id = $pdo->lastInsertId();
        
        echo json_encode([
            'success' => true,
            'investigation_id' => $investigation_id,
            'message' => 'Investigation created successfully'
        ]);
    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    }
}

function handleInvestigation($pdo, $method, $id, $input, $query) {
    if ($method === 'GET') {
        // Get single investigation with related data
        // Explicitly list columns to avoid any non-existent column errors
        $sql = "SELECT 
                    i.investigation_id, 
                    i.event_id,
                    i.investigation_type,
                    i.trigger_reason,
                    i.lead_id,
                    i.team_notes,
                    i.scope_description,
                    i.status,
                    i.opened_at,
                    i.closed_at,
                    i.root_cause_summary,
                    i.lessons_learned,
                    e.description as event_description,
                    e.event_type,
                    e.event_subcategory,
                    CONCAT(p.FirstName, ' ', p.LastName) as lead_name,
                    p.Email as lead_email
                FROM investigations i
                LEFT JOIN events e ON i.event_id = e.event_id
                LEFT JOIN people p ON i.lead_id = p.people_id
                WHERE i.investigation_id = :id";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $investigation = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$investigation) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Investigation not found']);
            return;
        }
        
        // Get linked RCAs
        // Note: rca_artefacts table does NOT have completed_at column, only status enum
        $rcaSql = "SELECT rca_id, method, status, created_at
                   FROM rca_artefacts
                   WHERE investigation_id = :id
                   ORDER BY created_at DESC";
        $rcaStmt = $pdo->prepare($rcaSql);
        $rcaStmt->execute([':id' => $id]);
        $investigation['rca_artefacts'] = $rcaStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get linked tasks
        // First, get all RCA IDs for this investigation
        $rcaIdsSql = "SELECT rca_id FROM rca_artefacts WHERE investigation_id = :id";
        $rcaIdsStmt = $pdo->prepare($rcaIdsSql);
        $rcaIdsStmt->execute([':id' => $id]);
        $rcaIds = $rcaIdsStmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Build task query - use placeholders for RCA IDs if any exist
        if (!empty($rcaIds)) {
            $placeholders = implode(',', array_fill(0, count($rcaIds), '?'));
            $taskSql = "SELECT t.task_id, t.task_name, t.status, t.priority, t.due_date,
                               etl.id as link_id, etl.sourcetype
                        FROM entity_task_links etl
                        JOIN tasks t ON etl.taskid = t.task_id
                        WHERE (etl.sourcetype = 'Investigation' AND etl.sourceid = ?)
                           OR (etl.sourcetype = 'RCA' AND etl.sourceid IN ($placeholders))
                        ORDER BY etl.createdat DESC";
            $taskStmt = $pdo->prepare($taskSql);
            $taskParams = array_merge([$id], $rcaIds);
            $taskStmt->execute($taskParams);
        } else {
            // No RCAs, only check investigation links
            $taskSql = "SELECT t.task_id, t.task_name, t.status, t.priority, t.due_date,
                               etl.id as link_id, etl.sourcetype
                        FROM entity_task_links etl
                        JOIN tasks t ON etl.taskid = t.task_id
                        WHERE etl.sourcetype = 'Investigation' AND etl.sourceid = :id
                        ORDER BY etl.createdat DESC";
            $taskStmt = $pdo->prepare($taskSql);
            $taskStmt->execute([':id' => $id]);
        }
        $investigation['linked_tasks'] = $taskStmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'data' => $investigation
        ]);
    } elseif ($method === 'PUT') {
        // Update investigation
        $allowedFields = ['investigation_type', 'lead_id', 'trigger_reason', 'scope_description', 
                         'root_cause_summary', 'lessons_learned', 'status'];
        $updateFields = [];
        $params = [':id' => $id];
        
        foreach ($allowedFields as $field) {
            if (isset($input[$field])) {
                $updateFields[] = "$field = :$field";
                $params[":$field"] = $input[$field];
            }
        }
        
        if (empty($updateFields)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'No fields to update']);
            return;
        }
        
        $sql = "UPDATE investigations SET " . implode(', ', $updateFields) . " WHERE investigation_id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        echo json_encode([
            'success' => true,
            'message' => 'Investigation updated successfully'
        ]);
    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    }
}

// ============================================================================
// RCA Handlers
// ============================================================================

function handleRCA($pdo, $method, $investigation_id, $subResource, $subId, $input) {
    if ($subResource === 'create') {
        // POST /api/investigations/{id}/rca/create
        if ($method !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
            return;
        }
        
        $method_type = $input['method'] ?? null;
        if (!in_array($method_type, ['FiveWhys', 'Fishbone'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid method. Must be FiveWhys or Fishbone']);
            return;
        }
        
        // Populate required title column to satisfy NOT NULL constraint
        $title = $input['title'] ?? "RCA for Investigation {$investigation_id}";
        
        $sql = "INSERT INTO rca_artefacts (investigation_id, method, status, title, created_at)
                VALUES (:investigation_id, :method, :status, :title, NOW())";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':investigation_id' => $investigation_id,
            ':method' => $method_type,
            ':status' => 'In Progress',
            ':title' => $title
        ]);
        
        $rca_id = $pdo->lastInsertId();
        
        // Initialize method-specific table
        if ($method_type === 'FiveWhys') {
            $initSql = "INSERT INTO rca_5whys (rca_id, problem_statement, root_cause_statement)
                       VALUES (:rca_id, '', '')";
            $initStmt = $pdo->prepare($initSql);
            $initStmt->execute([':rca_id' => $rca_id]);
        } elseif ($method_type === 'Fishbone') {
            // Normalize diagram type to valid enum values in DB
            $diagram_type = $input['diagram_type'] ?? 'SixP';
            if ($diagram_type === 'SixPs') {
                $diagram_type = 'SixP';
            }
            
            $initSql = "INSERT INTO rca_fishbone (rca_id, problem_statement, diagram_type, notes)
                       VALUES (:rca_id, '', :diagram_type, '')";
            $initStmt = $pdo->prepare($initSql);
            $initStmt->execute([
                ':rca_id' => $rca_id,
                ':diagram_type' => $diagram_type
            ]);
        }
        
        echo json_encode([
            'success' => true,
            'rca_id' => $rca_id,
            'message' => 'RCA artefact created successfully'
        ]);
    } elseif ($subResource === 'complete') {
        // PUT /api/investigations/{id}/rca/complete/{rca_id}
        if ($method !== 'PUT' || !$subId) {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
            return;
        }
        
        // Note: rca_artefacts table does NOT have completed_at column, only status enum
        $sql = "UPDATE rca_artefacts 
                SET status = 'Completed'
                WHERE rca_id = :rca_id AND investigation_id = :investigation_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':rca_id' => $subId,
            ':investigation_id' => $investigation_id
        ]);
        
        if ($stmt->rowCount() === 0) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'RCA not found']);
            return;
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'RCA marked as completed'
        ]);
    } elseif ($subResource === '5whys') {
        handleFiveWhys($pdo, $method, $investigation_id, $subId, $input);
    } elseif ($subResource === 'fishbone') {
        handleFishbone($pdo, $method, $investigation_id, $subId, $input);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Invalid RCA resource']);
    }
}

function handleFiveWhys($pdo, $method, $investigation_id, $rca_id, $input) {
    if ($method === 'GET') {
        // Get 5 Whys data
        $sql = "SELECT * FROM rca_5whys WHERE rca_id = :rca_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':rca_id' => $rca_id]);
        $fiveWhys = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$fiveWhys) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => '5 Whys not found']);
            return;
        }
        
        // Get steps
        $stepsSql = "SELECT step_id, rca_id, step_number, question AS why_question, answer, evidence_reference,
                            is_key_cause, created_by, created_at
                     FROM rca_5whys_steps 
                     WHERE rca_id = :rca_id 
                     ORDER BY step_number ASC";
        $stepsStmt = $pdo->prepare($stepsSql);
        $stepsStmt->execute([':rca_id' => $rca_id]);
        $fiveWhys['steps'] = $stepsStmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'data' => $fiveWhys
        ]);
    } elseif ($method === 'PUT') {
        // Update 5 Whys main fields
        $updateFields = [];
        $params = [':rca_id' => $rca_id];
        
        if (isset($input['problem_statement'])) {
            $updateFields[] = "problem_statement = :problem_statement";
            $params[':problem_statement'] = $input['problem_statement'];
        }
        if (isset($input['root_cause_statement'])) {
            $updateFields[] = "root_cause_statement = :root_cause_statement";
            $params[':root_cause_statement'] = $input['root_cause_statement'];
        }
        
        if (empty($updateFields)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'No fields to update']);
            return;
        }
        
        $sql = "UPDATE rca_5whys SET " . implode(', ', $updateFields) . " WHERE rca_id = :rca_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        echo json_encode([
            'success' => true,
            'message' => '5 Whys updated successfully'
        ]);
    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    }
}

function handleFishbone($pdo, $method, $investigation_id, $rca_id, $input) {
    if ($method === 'GET') {
        // Get Fishbone data
        $sql = "SELECT * FROM rca_fishbone WHERE rca_id = :rca_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':rca_id' => $rca_id]);
        $fishbone = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$fishbone) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Fishbone not found']);
            return;
        }
        
        // Get categories
        $catSql = "SELECT category_id, rca_id, name AS category_name, sort_order
                   FROM rca_fishbone_categories 
                   WHERE rca_id = :rca_id 
                   ORDER BY sort_order ASC, category_id ASC";
        $catStmt = $pdo->prepare($catSql);
        $catStmt->execute([':rca_id' => $rca_id]);
        $fishbone['categories'] = $catStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get causes for each category
        foreach ($fishbone['categories'] as &$category) {
            $causeSql = "SELECT * FROM rca_fishbone_causes 
                        WHERE category_id = :category_id 
                        ORDER BY created_at ASC";
            $causeStmt = $pdo->prepare($causeSql);
            $causeStmt->execute([':category_id' => $category['category_id']]);
            $category['causes'] = $causeStmt->fetchAll(PDO::FETCH_ASSOC);
        }
        unset($category);
        
        echo json_encode([
            'success' => true,
            'data' => $fishbone
        ]);
    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    }
}

// ============================================================================
// Validation Status Handler
// ============================================================================

function handleValidationStatus($pdo, $investigation_id) {
    // Check 1: At least one completed RCA
    $rcaSql = "SELECT COUNT(*) as count 
               FROM rca_artefacts 
               WHERE investigation_id = :id AND status = 'Completed'";
    $rcaStmt = $pdo->prepare($rcaSql);
    $rcaStmt->execute([':id' => $investigation_id]);
    $rcaResult = $rcaStmt->fetch(PDO::FETCH_ASSOC);
    $hasCompletedRCA = $rcaResult['count'] > 0;
    
    // Check 2: All linked tasks are closed
    // First, get all RCA IDs for this investigation
    $rcaIdsSql = "SELECT rca_id FROM rca_artefacts WHERE investigation_id = :id";
    $rcaIdsStmt = $pdo->prepare($rcaIdsSql);
    $rcaIdsStmt->execute([':id' => $investigation_id]);
    $rcaIds = $rcaIdsStmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Build task query - use placeholders for RCA IDs if any exist
    if (!empty($rcaIds)) {
        $placeholders = implode(',', array_fill(0, count($rcaIds), '?'));
        $taskSql = "SELECT COUNT(*) as count
                    FROM entity_task_links etl
                    JOIN tasks t ON etl.taskid = t.task_id
                    WHERE ((etl.sourcetype = 'Investigation' AND etl.sourceid = ?)
                       OR (etl.sourcetype = 'RCA' AND etl.sourceid IN ($placeholders)))
                    AND t.status NOT IN ('Completed', 'Archived', 'Cancelled')";
        $taskStmt = $pdo->prepare($taskSql);
        $taskParams = array_merge([$investigation_id], $rcaIds);
        $taskStmt->execute($taskParams);
    } else {
        // No RCAs, only check investigation links
        $taskSql = "SELECT COUNT(*) as count
                    FROM entity_task_links etl
                    JOIN tasks t ON etl.taskid = t.task_id
                    WHERE etl.sourcetype = 'Investigation' AND etl.sourceid = :id
                    AND t.status NOT IN ('Completed', 'Archived', 'Cancelled')";
        $taskStmt = $pdo->prepare($taskSql);
        $taskStmt->execute([':id' => $investigation_id]);
    }
    $taskResult = $taskStmt->fetch(PDO::FETCH_ASSOC);
    $allTasksClosed = $taskResult['count'] == 0;
    
    // Check 3: Summary fields are filled
    $summarySql = "SELECT root_cause_summary, lessons_learned 
                   FROM investigations 
                   WHERE investigation_id = :id";
    $summaryStmt = $pdo->prepare($summarySql);
    $summaryStmt->execute([':id' => $investigation_id]);
    $summaryResult = $summaryStmt->fetch(PDO::FETCH_ASSOC);
    $summariesFilled = !empty($summaryResult['root_cause_summary']) && 
                       !empty($summaryResult['lessons_learned']);
    
    $canClose = $hasCompletedRCA && $allTasksClosed && $summariesFilled;
    
    echo json_encode([
        'success' => true,
        'can_close' => $canClose,
        'checks' => [
            'has_completed_rca' => $hasCompletedRCA,
            'all_tasks_closed' => $allTasksClosed,
            'summaries_filled' => $summariesFilled
        ]
    ]);
}

// ============================================================================
// Close Investigation Handler
// ============================================================================

function handleCloseInvestigation($pdo, $investigation_id) {
    // Validate first
    ob_start();
    handleValidationStatus($pdo, $investigation_id);
    $validationJson = ob_get_clean();
    
    // We expect handleValidationStatus to have echoed valid JSON
    $validation = json_decode($validationJson, true);
    
    if (!$validation || !isset($validation['can_close']) || !$validation['can_close']) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Investigation cannot be closed. Validation checks failed.',
            'checks' => $validation['checks'] ?? []
        ]);
        return;
    }
    
    // Start transaction to ensure both updates succeed
    try {
        $pdo->beginTransaction();

        // Close the investigation
        $sql = "UPDATE investigations 
                SET status = 'Closed', closed_at = NOW() 
                WHERE investigation_id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $investigation_id]);
        
        // Also close the linked event in the primary events table
        $eventSql = "UPDATE events e
                     JOIN investigations i ON e.event_id = i.event_id
                     SET e.status = 'Closed'
                     WHERE i.investigation_id = :id";
        $eventStmt = $pdo->prepare($eventSql);
        $eventStmt->execute([':id' => $investigation_id]);

        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Investigation and linked event closed successfully'
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Database error during closure: ' . $e->getMessage()
        ]);
    }
}

// ============================================================================
// Delete Investigation Handler
// ============================================================================

function handleDeleteInvestigation($pdo, $investigation_id) {
    try {
        $pdo->beginTransaction();

        // 1. Delete linked RCAs
        // First delete from rca_5whys_steps linked to rca_5whys steps
        $pdo->prepare("DELETE FROM rca_5whys_steps WHERE rca_id IN (SELECT rca_id FROM rca_artefacts WHERE investigation_id = :id)")->execute([':id' => $investigation_id]);
        $pdo->prepare("DELETE FROM rca_5whys WHERE rca_id IN (SELECT rca_id FROM rca_artefacts WHERE investigation_id = :id)")->execute([':id' => $investigation_id]);
        
        // Delete fishbone causes and categories
        $pdo->prepare("DELETE FROM rca_fishbone_causes WHERE category_id IN (SELECT category_id FROM rca_fishbone_categories WHERE rca_id IN (SELECT rca_id FROM rca_artefacts WHERE investigation_id = :id))")->execute([':id' => $investigation_id]);
        $pdo->prepare("DELETE FROM rca_fishbone_categories WHERE rca_id IN (SELECT rca_id FROM rca_artefacts WHERE investigation_id = :id)")->execute([':id' => $investigation_id]);
        $pdo->prepare("DELETE FROM rca_fishbone WHERE rca_id IN (SELECT rca_id FROM rca_artefacts WHERE investigation_id = :id)")->execute([':id' => $investigation_id]);
        
        // Delete the artefacts themselves
        $pdo->prepare("DELETE FROM rca_artefacts WHERE investigation_id = :id")->execute([':id' => $investigation_id]);

        // 2. Delete task links
        $pdo->prepare("DELETE FROM entity_task_links WHERE sourcetype = 'Investigation' AND sourceid = :id")->execute([':id' => $investigation_id]);

        // 3. Delete the investigation itself
        $stmt = $pdo->prepare("DELETE FROM investigations WHERE investigation_id = :id");
        $stmt->execute([':id' => $investigation_id]);

        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Investigation and all related data deleted successfully'
        ]);
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Database error during deletion: ' . $e->getMessage()
        ]);
    }
}

?>


