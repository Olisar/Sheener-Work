<?php
/* File: sheener/php/get_pha.php */

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
                pha.assessment_id,
                pha.assessment_code,
                pha.process_name,
                pha.process_overview,
                pha.assessment_date,
                pha.assessed_by_id,
                pha.status,
                pha.created_at,
                pha.updated_at,
                CONCAT(p.FirstName, ' ', p.LastName) as assessed_by_name
            FROM 
                process_hazard_assessments pha
            LEFT JOIN 
                people p ON pha.assessed_by_id = p.people_id
            WHERE 
                pha.assessment_id = :assessment_id
        ";
        $stmt = $pdo->prepare($query);
        $stmt->execute([':assessment_id' => $assessmentId]);
        $assessment = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($assessment) {
            // Fetch all hazards for this assessment
            $hazardsQuery = "
                SELECT 
                    h.hazard_id,
                    h.task_id,
                    h.hazard_type_id,
                    h.hazard_description,
                    h.assessment_id,
                    h.process_step,
                    h.existing_controls,
                    h.initial_likelihood,
                    h.initial_severity,
                    h.residual_likelihood,
                    h.residual_severity,
                    ht.type_name as hazard_type_name,
                    t.task_name
                FROM 
                    hazards h
                LEFT JOIN 
                    hazard_type ht ON h.hazard_type_id = ht.hazard_type_id
                LEFT JOIN 
                    tasks t ON h.task_id = t.task_id
                WHERE 
                    h.assessment_id = :assessment_id
                ORDER BY 
                    h.hazard_id
            ";
            $hazardsStmt = $pdo->prepare($hazardsQuery);
            $hazardsStmt->execute([':assessment_id' => $assessmentId]);
            $hazards = $hazardsStmt->fetchAll(PDO::FETCH_ASSOC);

            // For each hazard, fetch controls
            foreach ($hazards as &$hazard) {
                $controlsQuery = "
                    SELECT 
                        c.control_id,
                        c.hazard_id,
                        c.control_description,
                        c.control_type_id,
                        c.status,
                        c.implementation_date,
                        c.review_date,
                        c.responsible_person_id,
                        ct.type_name as control_type_name,
                        CONCAT(p.FirstName, ' ', p.LastName) as responsible_person_name
                    FROM 
                        controls c
                    LEFT JOIN 
                        control_types ct ON c.control_type_id = ct.control_type_id
                    LEFT JOIN 
                        people p ON c.responsible_person_id = p.people_id
                    WHERE 
                        c.hazard_id = :hazard_id
                    ORDER BY 
                        c.control_id
                ";
                $controlsStmt = $pdo->prepare($controlsQuery);
                $controlsStmt->execute([':hazard_id' => $hazard['hazard_id']]);
                $hazard['controls'] = $controlsStmt->fetchAll(PDO::FETCH_ASSOC);

                // For each control, fetch actions
                foreach ($hazard['controls'] as &$control) {
                    $actionsQuery = "
                        SELECT 
                            a.action_id,
                            a.hazard_id,
                            a.control_id,
                            a.description,
                            a.owner_id,
                            a.due_date,
                            a.status,
                            a.completion_date,
                            a.created_at,
                            CONCAT(p.FirstName, ' ', p.LastName) as owner_name
                        FROM 
                            hazard_control_actions a
                        LEFT JOIN 
                            people p ON a.owner_id = p.people_id
                        WHERE 
                            a.control_id = :control_id
                        ORDER BY 
                            a.action_id
                    ";
                    $actionsStmt = $pdo->prepare($actionsQuery);
                    $actionsStmt->execute([':control_id' => $control['control_id']]);
                    $control['actions'] = $actionsStmt->fetchAll(PDO::FETCH_ASSOC);
                }
            }

            
            $assessment['hazards'] = $hazards;

            // Fetch assessors from ra_assessorlinkt
            $assessorsQuery = "
                SELECT 
                    al.RAAssessorLinkID,
                    al.RAID as assessment_id,
                    al.AssessorID as person_id,
                    al.AssessDate as assess_date,
                    CONCAT(p.FirstName, ' ', p.LastName) as assessor_name
                FROM 
                    ra_assessorlinkt al
                LEFT JOIN 
                    people p ON al.AssessorID = p.people_id
                WHERE 
                    al.RAID = :assessment_id
            ";
            $assessorsStmt = $pdo->prepare($assessorsQuery);
            $assessorsStmt->execute([':assessment_id' => $assessmentId]);
            $assessment['assessors'] = $assessorsStmt->fetchALL(PDO::FETCH_ASSOC);


            // Fetch signoffs
            $signoffsQuery = "
                SELECT 
                    s.signoff_id,
                    s.assessment_id,
                    s.signer_role,
                    s.signer_id,
                    s.signature_date,
                    CONCAT(p.FirstName, ' ', p.LastName) as signer_name
                FROM 
                    hazard_assessment_signoffs s
                LEFT JOIN 
                    people p ON s.signer_id = p.people_id
                WHERE 
                    s.assessment_id = :assessment_id
                ORDER BY 
                    s.signoff_id
            ";
            $signoffsStmt = $pdo->prepare($signoffsQuery);
            $signoffsStmt->execute([':assessment_id' => $assessmentId]);
            $assessment['signoffs'] = $signoffsStmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode(['success' => true, 'data' => $assessment]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Assessment not found']);
        }
    } else {
        // Fetch all assessments for list
        $query = "
            SELECT 
                pha.assessment_id,
                pha.assessment_code,
                pha.process_name,
                pha.assessment_date,
                pha.status,
                CONCAT(p.FirstName, ' ', p.LastName) as assessed_by_name
            FROM 
                process_hazard_assessments pha
            LEFT JOIN 
                people p ON pha.assessed_by_id = p.people_id
            ORDER BY 
                pha.created_at DESC
        ";
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        $assessments = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'data' => $assessments]);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
