<?php
/* File: sheener/php/get_people.php */

error_reporting(0);
ini_set('display_errors', 0);
ob_start(); // Start output buffering

// file name Sheener/php/get_people.php
require_once 'database.php';

ob_clean(); // Clear any output before setting headers
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: http://172.21.10.99");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}


$peopleId = isset($_GET['people_id']) ? intval($_GET['people_id']) : null;

try {
    $database = new Database();
    $pdo = $database->getConnection();

    if ($peopleId) {
        // Fetch a single person by ID with role information
        $query = "
          SELECT
            p.people_id,
            p.FirstName AS first_name,
            p.LastName AS last_name,
            p.DateOfBirth,
            p.Email,
            p.PhoneNumber,
            p.Position,
            p.IsActive,
            r.RoleID,
            r.RoleName,
            r.Description AS RoleDescription
          FROM people p
          LEFT JOIN people_roles pr ON p.people_id = pr.PersonID
          LEFT JOIN roles r ON pr.RoleID = r.RoleID
          WHERE p.people_id = :people_id
        ";
        $stmt = $pdo->prepare($query);
        $stmt->execute([':people_id' => $peopleId]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($results) {
            // Group roles if person has multiple roles
            $person = $results[0];
            if (count($results) > 1 || $results[0]['RoleID'] !== null) {
                $person['roles'] = [];
                foreach ($results as $row) {
                    if ($row['RoleID'] !== null) {
                        $person['roles'][] = [
                            'RoleID' => $row['RoleID'],
                            'RoleName' => $row['RoleName'],
                            'Description' => $row['RoleDescription']
                        ];
                    }
                }
            } else {
                $person['roles'] = [];
            }
            // Remove duplicate fields from main person object
            unset($person['RoleID'], $person['RoleName'], $person['RoleDescription']);
            
            echo json_encode(['success' => true, 'data' => $person]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Person not found']);
        }
    } else {
        // Simplified and optimized query using correlated subqueries to avoid Cartesian products
        $query = "
          SELECT
            p.people_id,
            p.FirstName AS first_name,
            p.LastName AS last_name,
            p.DateOfBirth,
            p.Email,
            p.PhoneNumber,
            p.Position,
            p.IsActive,
            (SELECT GROUP_CONCAT(DISTINCT d.DepartmentName SEPARATOR ', ') 
             FROM people_departments pd 
             JOIN departments d ON pd.DepartmentID = d.department_id 
             WHERE pd.PersonID = p.people_id) AS department_name,
            NULL AS company_name,
            (SELECT GROUP_CONCAT(DISTINCT CONCAT(r.RoleID, ':', r.RoleName) SEPARATOR '|') 
             FROM people_roles pr 
             JOIN roles r ON pr.RoleID = r.RoleID 
             WHERE pr.PersonID = p.people_id) AS roles
          FROM people p
          WHERE p.IsActive = 1
          ORDER BY p.FirstName, p.LastName
        ";

        // If we want to support company_name, we can try a simple LEFT JOIN on vendor if it exists
        // but for now, the subquery approach for roles/departments will be a huge speed boost.
        
        $stmt = $pdo->query($query);
        $people = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Process roles for each person
        foreach ($people as &$person) {
            if (!empty($person['roles'])) {
                $rolesArray = [];
                $rolePairs = explode('|', $person['roles']);
                foreach ($rolePairs as $rolePair) {
                    if (!empty($rolePair)) {
                        $parts = explode(':', $rolePair, 2);
                        if (count($parts) === 2) {
                            $rolesArray[] = [
                                'RoleID' => (int)$parts[0],
                                'RoleName' => $parts[1]
                            ];
                        }
                    }
                }
                $person['roles'] = $rolesArray;
            } else {
                $person['roles'] = [];
            }
        }
        unset($person);

        echo json_encode(['success' => true, 'data' => $people]);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    exit;
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error: ' . $e->getMessage()]);
    exit;
}

?>
