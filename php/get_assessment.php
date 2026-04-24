<?php
/* File: sheener/php/get_assessment.php */

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: http://172.21.10.99");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once 'database.php';

$assessmentId = isset($_GET['assessment_id']) ? intval($_GET['assessment_id']) : null;

try {
    $database = new Database();
    $pdo = $database->getConnection();

    if ($assessmentId) {
        // Fetch basic assessment data
        $query = "
            SELECT 
                a.assessment_id,
                a.task_id,
                a.assessment_date,
                a.assessor_name,
                a.comments,
                t.task_name,
                t.task_description
            FROM 
                assessments a
            LEFT JOIN 
                tasks t ON a.task_id = t.task_id
            WHERE 
                a.assessment_id = :assessment_id
        ";
        $stmt = $pdo->prepare($query);
        $stmt->execute([':assessment_id' => $assessmentId]);
        $assessment = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($assessment) {
            // Fetch all hazards for this task
            $hazardsQuery = "
                SELECT 
                    h.hazard_id,
                    h.hazard_description,
                    h.hazard_type_id,
                    ht.type_name as hazard_type_name
                FROM 
                    hazards h
                LEFT JOIN 
                    hazard_type ht ON h.hazard_type_id = ht.hazard_type_id
                WHERE 
                    h.task_id = :task_id
            ";
            $hazardsStmt = $pdo->prepare($hazardsQuery);
            $hazardsStmt->execute([':task_id' => $assessment['task_id']]);
            $hazards = $hazardsStmt->fetchAll(PDO::FETCH_ASSOC);
            
            // For each hazard, get risks and controls
            foreach ($hazards as &$hazard) {
                $risksQuery = "
                    SELECT 
                        r.risk_id,
                        r.risk_description,
                        r.likelihood_before,
                        r.severity_before,
                        r.risk_rate_before,
                        r.likelihood_after,
                        r.severity_after,
                        r.risk_rate_after
                    FROM 
                        risks r
                    WHERE 
                        r.hazard_id = :hazard_id
                ";
                $risksStmt = $pdo->prepare($risksQuery);
                $risksStmt->execute([':hazard_id' => $hazard['hazard_id']]);
                $risks = $risksStmt->fetchAll(PDO::FETCH_ASSOC);
                
                // For each risk, get controls
                foreach ($risks as &$risk) {
                    $controlsQuery = "
                        SELECT 
                            c.control_id,
                            c.control_description,
                            c.status as control_status,
                            c.control_type_id,
                            ct.type_name as control_category
                        FROM 
                            controls c
                        LEFT JOIN 
                            control_types ct ON c.control_type_id = ct.control_type_id
                        WHERE 
                            c.risk_id = :risk_id
                    ";
                    $controlsStmt = $pdo->prepare($controlsQuery);
                    $controlsStmt->execute([':risk_id' => $risk['risk_id']]);
                    $risk['controls'] = $controlsStmt->fetchAll(PDO::FETCH_ASSOC);
                }
                
                $hazard['risks'] = $risks;
            }
            
            $assessment['hazards'] = $hazards;
            
            echo json_encode(["success" => true, "data" => $assessment]);
        } else {
            echo json_encode(["success" => false, "error" => "Assessment not found"]);
        }
    } else {
        // Fetch all assessments
        $query = "
            SELECT
                a.assessment_id,
                a.task_id,
                a.assessment_date,
                a.assessor_name,
                a.comments,
                t.task_name
            FROM 
                assessments a
            LEFT JOIN 
                tasks t ON a.task_id = t.task_id
            ORDER BY 
                a.assessment_id DESC
        ";
        $stmt = $pdo->query($query);
        $assessments = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($assessments) {
            echo json_encode(["success" => true, "data" => $assessments]);
        } else {
            echo json_encode(["success" => true, "data" => []]);
        }
    }
} catch (PDOException $e) {
    echo json_encode(["success" => false, "error" => "Database error: " . $e->getMessage()]);
}
?>
