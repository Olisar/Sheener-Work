<?php
/* File: sheener/php/get_entity_process_links.php */

// Enable error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors, but log them
ini_set('log_errors', 1);

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Check if database.php exists
if (!file_exists(__DIR__ . '/database.php')) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database configuration file not found'
    ]);
    exit;
}

require_once 'database.php';

// Check if Database class exists
if (!class_exists('Database')) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database class not found'
    ]);
    exit;
}

try {
    $database = new Database();
    if (!$database) {
        throw new Exception("Failed to instantiate Database class");
    }
    
    $pdo = $database->getConnection();
    if (!$pdo) {
        throw new Exception("Failed to establish database connection");
    }
    
    // Check if entity_process_links table exists
    try {
        $tableCheck = $pdo->query("SHOW TABLES LIKE 'entity_process_links'");
        if ($tableCheck === false || $tableCheck->rowCount() === 0) {
            // Table doesn't exist, return empty array
            echo json_encode([
                'success' => true,
                'data' => []
            ]);
            exit;
        }
    } catch (PDOException $e) {
        error_log("Error checking entity_process_links table: " . $e->getMessage());
        echo json_encode([
            'success' => true,
            'data' => []
        ]);
        exit;
    }
    
    $sourcetype = isset($_GET['sourcetype']) ? $_GET['sourcetype'] : null;
    $sourceid = isset($_GET['sourceid']) ? intval($_GET['sourceid']) : null;
    $processid = isset($_GET['processid']) ? intval($_GET['processid']) : null;
    
    // Check if process_map table exists
    $processMapTableExists = false;
    try {
        $processMapTableCheck = $pdo->query("SHOW TABLES LIKE 'process_map'");
        $processMapTableExists = $processMapTableCheck !== false && $processMapTableCheck->rowCount() > 0;
    } catch (PDOException $e) {
        error_log("Error checking process_map table: " . $e->getMessage());
        $processMapTableExists = false;
    }
    
    // Check if people table exists
    $peopleTableExists = false;
    try {
        $peopleTableCheck = $pdo->query("SHOW TABLES LIKE 'people'");
        $peopleTableExists = $peopleTableCheck !== false && $peopleTableCheck->rowCount() > 0;
    } catch (PDOException $e) {
        error_log("Error checking people table: " . $e->getMessage());
        $peopleTableExists = false;
    }
    
    // Build query with conditional joins
    $query = "SELECT 
        epl.id,
        epl.sourcetype,
        epl.sourceid,
        epl.processid,
        epl.createdby,
        epl.createdat";
    
    if ($processMapTableExists) {
        $query .= ",
        pm.id as process_id,
        pm.text as process_name,
        pm.description as process_description,
        pm.type as process_type,
        pm.status as process_status";
    } else {
        $query .= ",
        NULL as process_id,
        NULL as process_name,
        NULL as process_description,
        NULL as process_type,
        NULL as process_status";
    }
    
    if ($peopleTableExists) {
        $query .= ",
        CONCAT(p.FirstName, ' ', p.LastName) as created_by_name";
    } else {
        $query .= ",
        NULL as created_by_name";
    }
    
    $query .= " FROM entity_process_links epl";
    
    if ($processMapTableExists) {
        $query .= " LEFT JOIN process_map pm ON epl.processid = pm.id";
    }
    
    if ($peopleTableExists) {
        $query .= " LEFT JOIN people p ON epl.createdby = p.people_id";
    }
    
    $query .= " WHERE 1=1";
    
    $params = [];
    
    if ($sourcetype && $sourceid) {
        $query .= " AND epl.sourcetype = :sourcetype AND epl.sourceid = :sourceid";
        $params[':sourcetype'] = $sourcetype;
        $params[':sourceid'] = $sourceid;
    }
    
    if ($processid) {
        $query .= " AND epl.processid = :processid";
        $params[':processid'] = $processid;
    }
    
    $query .= " ORDER BY epl.createdat DESC";
    
    try {
        $stmt = $pdo->prepare($query);
        if ($stmt === false) {
            throw new PDOException("Failed to prepare query: " . implode(", ", $pdo->errorInfo()));
        }
        
        $result = $stmt->execute($params);
        if ($result === false) {
            $errorInfo = $stmt->errorInfo();
            throw new PDOException("Query execution failed: " . implode(", ", $errorInfo));
        }
        
        $links = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if ($links === false) {
            $links = [];
        }
    } catch (PDOException $e) {
        error_log("Query error: " . $e->getMessage());
        error_log("Query: " . $query);
        error_log("Params: " . json_encode($params));
        throw $e; // Re-throw to be caught by outer catch block
    }
    
    // Get source details based on sourcetype
    foreach ($links as &$link) {
        try {
            if ($link['sourcetype'] === 'EventFinding' && $link['sourceid']) {
                $source = null;
                
                // First check operational_events table
                $tableCheck = $pdo->query("SHOW TABLES LIKE 'operational_events'");
                if ($tableCheck->rowCount() > 0) {
                    $sourceQuery = "SELECT event_id, event_type, description, status 
                                  FROM operational_events 
                                  WHERE event_id = :id";
                    $sourceStmt = $pdo->prepare($sourceQuery);
                    $sourceStmt->execute([':id' => $link['sourceid']]);
                    $source = $sourceStmt->fetch(PDO::FETCH_ASSOC);
                }
                
                // If not found, check events table
                if (!$source) {
                    $tableCheck = $pdo->query("SHOW TABLES LIKE 'events'");
                    if ($tableCheck->rowCount() > 0) {
                        $sourceQuery = "SELECT event_id, event_type, description, status 
                                      FROM events 
                                      WHERE event_id = :id";
                        $sourceStmt = $pdo->prepare($sourceQuery);
                        $sourceStmt->execute([':id' => $link['sourceid']]);
                        $source = $sourceStmt->fetch(PDO::FETCH_ASSOC);
                    }
                }
                
                if ($source) {
                    $link['source_details'] = [
                        'id' => $source['event_id'],
                        'type' => $source['event_type'],
                        'description' => $source['description'],
                        'status' => $source['status']
                    ];
                }
            } elseif ($link['sourcetype'] === 'TrainingSession' && $link['sourceid']) {
                // Check if training_assignments table exists
                $tableCheck = $pdo->query("SHOW TABLES LIKE 'training_assignments'");
                if ($tableCheck->rowCount() > 0) {
                    $sourceQuery = "SELECT ta.id, ta.status, ta.due_date,
                                  CONCAT(p.FirstName, ' ', p.LastName) as person_name,
                                  dv.Filename
                                  FROM training_assignments ta
                                  LEFT JOIN people p ON ta.person_id = p.people_id
                                  LEFT JOIN documentversions dv ON ta.doc_version_id = dv.VersionID
                                  WHERE ta.id = :id";
                    $sourceStmt = $pdo->prepare($sourceQuery);
                    $sourceStmt->execute([':id' => $link['sourceid']]);
                    $source = $sourceStmt->fetch(PDO::FETCH_ASSOC);
                    if ($source) {
                        $link['source_details'] = [
                            'id' => $source['id'],
                            'person' => $source['person_name'],
                            'document' => $source['Filename'],
                            'status' => $source['status'],
                            'due_date' => $source['due_date']
                        ];
                    }
                }
            }
        } catch (PDOException $e) {
            // Log error but don't break the loop - just skip source details for this link
            error_log("Error fetching source details for link {$link['id']}: " . $e->getMessage());
        }
    }
    
    $response = [
        'success' => true,
        'data' => $links
    ];
    
    $json = json_encode($response);
    if ($json === false) {
        throw new Exception("JSON encoding failed: " . json_last_error_msg());
    }
    
    echo $json;
    
} catch (PDOException $e) {
    http_response_code(500);
    error_log("get_entity_process_links.php PDO Error: " . $e->getMessage());
    error_log("SQL Query: " . (isset($query) ? $query : 'N/A'));
    error_log("Parameters: " . json_encode(isset($params) ? $params : []));
    echo json_encode([
        'success' => false,
        'error' => 'Database error occurred',
        'message' => $e->getMessage(),
        'code' => $e->getCode()
    ], JSON_PRETTY_PRINT);
} catch (Exception $e) {
    http_response_code(500);
    error_log("get_entity_process_links.php Error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ], JSON_PRETTY_PRINT);
}

