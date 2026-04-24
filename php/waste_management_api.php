<?php
/* File: sheener/php/waste_management_api.php */

/**
 * Waste Management System - API Endpoints
 * Provides RESTful API for waste management data
 */

session_start();
require_once 'database.php';

header('Content-Type: application/json');

// CORS headers (adjust as needed)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if (!isset($_SESSION['user_id']) || !isset($_SESSION['people_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    $method = $_SERVER['REQUEST_METHOD'];
    $path = $_GET['path'] ?? '';
    $path_parts = explode('/', trim($path, '/'));
    $resource = $path_parts[0] ?? '';
    $id = $path_parts[1] ?? null;
    
    // Get request data
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        $input = $_POST;
    }
    
    // Route requests
    switch ($resource) {
        case 'vendors':
            handle_vendors($pdo, $method, $id, $input);
            break;
            
        case 'sites':
            handle_sites($pdo, $method, $id, $input);
            break;
            
        case 'categories':
            handle_categories($pdo, $method, $id, $input);
            break;
            
        case 'collections':
            handle_collections($pdo, $method, $id, $input);
            break;
            
        case 'imports':
            handle_imports($pdo, $method, $id, $input);
            break;
            
        case 'statistics':
            handle_statistics($pdo, $method, $input);
            break;
            
        default:
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Resource not found']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
}

function handle_vendors($pdo, $method, $id, $input) {
    if ($method === 'GET') {
        if ($id) {
            $stmt = $pdo->prepare("SELECT * FROM waste_vendors WHERE vendor_id = ?");
            $stmt->execute([$id]);
            $vendor = $stmt->fetch(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'data' => $vendor]);
        } else {
            $stmt = $pdo->query("SELECT * FROM waste_vendors ORDER BY vendor_name");
            $vendors = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'data' => $vendors]);
        }
    } elseif ($method === 'POST') {
        $stmt = $pdo->prepare("
            INSERT INTO waste_vendors (vendor_name, vendor_code, contact_person, email, phone, address, status)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $input['vendor_name'] ?? '',
            $input['vendor_code'] ?? null,
            $input['contact_person'] ?? null,
            $input['email'] ?? null,
            $input['phone'] ?? null,
            $input['address'] ?? null,
            $input['status'] ?? 'Active'
        ]);
        echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
    } elseif ($method === 'PUT') {
        $stmt = $pdo->prepare("
            UPDATE waste_vendors 
            SET vendor_name = ?, vendor_code = ?, contact_person = ?, email = ?, 
                phone = ?, address = ?, status = ?
            WHERE vendor_id = ?
        ");
        $stmt->execute([
            $input['vendor_name'] ?? '',
            $input['vendor_code'] ?? null,
            $input['contact_person'] ?? null,
            $input['email'] ?? null,
            $input['phone'] ?? null,
            $input['address'] ?? null,
            $input['status'] ?? 'Active',
            $id
        ]);
        echo json_encode(['success' => true]);
    } elseif ($method === 'DELETE') {
        $stmt = $pdo->prepare("DELETE FROM waste_vendors WHERE vendor_id = ?");
        $stmt->execute([$id]);
        echo json_encode(['success' => true]);
    }
}

function handle_sites($pdo, $method, $id, $input) {
    if ($method === 'GET') {
        if ($id) {
            $stmt = $pdo->prepare("SELECT * FROM waste_collection_sites WHERE site_id = ?");
            $stmt->execute([$id]);
            $site = $stmt->fetch(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'data' => $site]);
        } else {
            $stmt = $pdo->query("SELECT * FROM waste_collection_sites ORDER BY site_name");
            $sites = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'data' => $sites]);
        }
    } elseif ($method === 'POST') {
        $stmt = $pdo->prepare("
            INSERT INTO waste_collection_sites (site_name, site_code, address, site_type, status)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $input['site_name'] ?? '',
            $input['site_code'] ?? null,
            $input['address'] ?? null,
            $input['site_type'] ?? 'Facility',
            $input['status'] ?? 'Active'
        ]);
        echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
    }
}

function handle_categories($pdo, $method, $id, $input) {
    if ($method === 'GET') {
        // Get hierarchical categories
        $include_children = isset($_GET['include_children']) && $_GET['include_children'] == '1';
        
        if ($include_children) {
            // Return hierarchical structure
            $stmt = $pdo->query("
                SELECT 
                    c.*,
                    p.category_name as parent_category_name,
                    p.category_code as parent_category_code
                FROM waste_categories c
                LEFT JOIN waste_categories p ON c.parent_category_id = p.category_id
                ORDER BY 
                    COALESCE(c.parent_category_id, c.category_id),
                    c.parent_category_id IS NULL DESC,
                    c.category_name
            ");
            $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Organize into hierarchical structure
            $hierarchical = [];
            foreach ($categories as $cat) {
                if ($cat['parent_category_id'] === null) {
                    $hierarchical[$cat['category_id']] = $cat;
                    $hierarchical[$cat['category_id']]['subcategories'] = [];
                }
            }
            
            foreach ($categories as $cat) {
                if ($cat['parent_category_id'] !== null) {
                    $parent_id = $cat['parent_category_id'];
                    if (isset($hierarchical[$parent_id])) {
                        $hierarchical[$parent_id]['subcategories'][] = $cat;
                    } else {
                        // Find parent in flat structure
                        foreach ($hierarchical as $key => $parent) {
                            if ($parent['category_id'] == $parent_id) {
                                $hierarchical[$key]['subcategories'][] = $cat;
                                break;
                            }
                        }
                    }
                }
            }
            
            echo json_encode(['success' => true, 'data' => array_values($hierarchical)]);
        } else {
            // Return flat list
            $stmt = $pdo->query("
                SELECT 
                    c.*,
                    p.category_name as parent_category_name
                FROM waste_categories c
                LEFT JOIN waste_categories p ON c.parent_category_id = p.category_id
                ORDER BY 
                    COALESCE(c.parent_category_id, c.category_id),
                    c.parent_category_id IS NULL DESC,
                    c.category_name
            ");
            $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'data' => $categories]);
        }
    } elseif ($method === 'POST') {
        $stmt = $pdo->prepare("
            INSERT INTO waste_categories (category_name, category_code, description, hazardous, parent_category_id, unit_of_measure)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $input['category_name'] ?? '',
            $input['category_code'] ?? null,
            $input['description'] ?? null,
            $input['hazardous'] ?? false,
            $input['parent_category_id'] ?? null,
            $input['unit_of_measure'] ?? 'kg'
        ]);
        echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
    }
}

function handle_collections($pdo, $method, $id, $input) {
    if ($method === 'GET') {
        $start_date = $_GET['start_date'] ?? null;
        $end_date = $_GET['end_date'] ?? null;
        $vendor_id = $_GET['vendor_id'] ?? null;
        $site_id = $_GET['site_id'] ?? null;
        
        $where = [];
        $params = [];
        
        if ($start_date) {
            $where[] = "wc.collection_date >= ?";
            $params[] = $start_date;
        }
        if ($end_date) {
            $where[] = "wc.collection_date <= ?";
            $params[] = $end_date;
        }
        if ($vendor_id) {
            $where[] = "wc.vendor_id = ?";
            $params[] = $vendor_id;
        }
        if ($site_id) {
            $where[] = "wc.site_id = ?";
            $params[] = $site_id;
        }
        
        $where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        $query = "
            SELECT 
                wc.*,
                wv.vendor_name,
                wcs.site_name
            FROM waste_collections wc
            JOIN waste_vendors wv ON wc.vendor_id = wv.vendor_id
            JOIN waste_collection_sites wcs ON wc.site_id = wcs.site_id
            $where_clause
            ORDER BY wc.collection_date DESC
            LIMIT 100
        ";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $collections = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'data' => $collections]);
    }
}

function handle_imports($pdo, $method, $id, $input) {
    if ($method === 'GET') {
        $query = "
            SELECT 
                vei.*,
                wv.vendor_name,
                p.FirstName,
                p.LastName
            FROM vendor_excel_imports vei
            LEFT JOIN waste_vendors wv ON vei.vendor_id = wv.vendor_id
            LEFT JOIN people p ON vei.imported_by = p.people_id
            ORDER BY vei.import_date DESC
            LIMIT 50
        ";
        $stmt = $pdo->query($query);
        $imports = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $imports]);
    }
}

function handle_statistics($pdo, $method, $input) {
    if ($method === 'GET') {
        $start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
        $end_date = $_GET['end_date'] ?? date('Y-m-d');
        
        // Total statistics
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(DISTINCT collection_id) as total_collections,
                COUNT(DISTINCT vendor_id) as total_vendors,
                COUNT(DISTINCT site_id) as total_sites,
                SUM(total_weight) as total_weight,
                SUM(total_volume) as total_volume
            FROM waste_collections
            WHERE collection_date BETWEEN ? AND ?
        ");
        $stmt->execute([$start_date, $end_date]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Top categories
        $stmt = $pdo->prepare("
            SELECT 
                wcat.category_name,
                SUM(wci.weight) as total_weight
            FROM waste_collection_items wci
            JOIN waste_collections wc ON wci.collection_id = wc.collection_id
            JOIN waste_categories wcat ON wci.category_id = wcat.category_id
            WHERE wc.collection_date BETWEEN ? AND ?
            GROUP BY wcat.category_id
            ORDER BY total_weight DESC
            LIMIT 5
        ");
        $stmt->execute([$start_date, $end_date]);
        $top_categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'data' => [
                'summary' => $stats,
                'top_categories' => $top_categories,
                'period' => ['start' => $start_date, 'end' => $end_date]
            ]
        ]);
    }
}
?>

