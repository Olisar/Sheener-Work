<?php
/* File: sheener/php/update_assessment.php */

// php/update_assessment.php
header('Content-Type: application/json');
require_once 'database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $database = new Database();
        $pdo = $database->getConnection();

        // Get assessment_id (null for new assessments)
        $assessment_id = isset($_POST['assessment_id']) && !empty($_POST['assessment_id']) ? intval($_POST['assessment_id']) : null;

        // Retrieve basic form data
        $assessment_title = $_POST['assessment_title'] ?? null;
        $task_id = isset($_POST['task_id']) && !empty($_POST['task_id']) ? intval($_POST['task_id']) : null;
        $assessment_date = $_POST['assessment_date'] ?? null;
        $revision_reference = $_POST['revision_reference'] ?? null;
        $revision_date = $_POST['revision_date'] ?? null;
        $primary_process_id = isset($_POST['primary_process_id']) ? intval($_POST['primary_process_id']) : null;
        $head_of_department_id = isset($_POST['head_of_department_id']) ? intval($_POST['head_of_department_id']) : null;
        $risk_status = $_POST['risk_status'] ?? null;
        $risk_owner_id = isset($_POST['risk_owner_id']) ? intval($_POST['risk_owner_id']) : null;
        $review_frequency = $_POST['review_frequency'] ?? null;
        $next_review_date = $_POST['next_review_date'] ?? null;
        
        // Get assessors array
        $assessors = isset($_POST['assessors']) ? $_POST['assessors'] : [];
        
        // Get hazards array
        $hazards = isset($_POST['hazards']) ? $_POST['hazards'] : [];
        
        // Get affected people categories
        $affected_people_categories = isset($_POST['affected_people_categories']) ? $_POST['affected_people_categories'] : [];
        
        // Start transaction
        $pdo->beginTransaction();
        
        if ($assessment_id) {
            // Update existing assessment
            $query = "UPDATE assessments SET 
                        assessment_date = :assessment_date, 
                        assessor_name = :assessor_name,
                        comments = :comments
                      WHERE assessment_id = :assessment_id";
            
            $stmt = $pdo->prepare($query);
            $stmt->execute([
                ':assessment_date' => $assessment_date,
                ':assessor_name' => 'Multiple Assessors', // Will be updated from assessors array
                ':comments' => $assessment_title,
                ':assessment_id' => $assessment_id
            ]);

            // Get task_id from existing assessment
            $taskQuery = "SELECT task_id FROM assessments WHERE assessment_id = :assessment_id";
            $taskStmt = $pdo->prepare($taskQuery);
            $taskStmt->execute([':assessment_id' => $assessment_id]);
            $assessment = $taskStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($assessment && $assessment['task_id']) {
                $task_id = $assessment['task_id'];
            }
        } else {
            // Create new assessment
            if (!$task_id) {
                throw new PDOException("Task ID is required for new assessments.");
            }
            
            $query = "INSERT INTO assessments (task_id, assessment_date, assessor_name, comments) 
                      VALUES (:task_id, :assessment_date, :assessor_name, :comments)";
            
            $stmt = $pdo->prepare($query);
            $stmt->execute([
                ':task_id' => $task_id,
                ':assessment_date' => $assessment_date,
                ':assessor_name' => 'Multiple Assessors', // Will be updated from assessors array
                ':comments' => $assessment_title
            ]);
            
            $assessment_id = $pdo->lastInsertId();
        }
        
        // Process assessors - for now, just update the assessor_name field
        if (!empty($assessors)) {
            $assessorNames = [];
            foreach ($assessors as $assessor) {
                if (!empty($assessor['person_id'])) {
                    // Get person name
                    $personQuery = "SELECT CONCAT(FirstName, ' ', LastName) as name FROM people WHERE people_id = :id";
                    $personStmt = $pdo->prepare($personQuery);
                    $personStmt->execute([':id' => intval($assessor['person_id'])]);
                    $person = $personStmt->fetch(PDO::FETCH_ASSOC);
                    if ($person) {
                        $assessorNames[] = $person['name'];
                    }
                }
            }
            if (!empty($assessorNames)) {
                $updateAssessorQuery = "UPDATE assessments SET assessor_name = :assessor_name WHERE assessment_id = :assessment_id";
                $updateAssessorStmt = $pdo->prepare($updateAssessorQuery);
                $updateAssessorStmt->execute([
                    ':assessor_name' => implode(', ', $assessorNames),
                    ':assessment_id' => $assessment_id
                ]);
            }
        }
        
        // Process hazards - create hazards linked to the task
        if (!empty($hazards) && $task_id) {
            // Clear existing hazards for this task (be careful - this might affect other assessments)
            // For now, just add new hazards without clearing
            
            foreach ($hazards as $hazardKey => $hazard) {
                if (empty($hazard['description'])) continue;
                
                // Get hazard_type_id (use first available)
                $hazardTypeQuery = "SELECT hazard_type_id FROM hazard_type LIMIT 1";
                $hazardTypeStmt = $pdo->query($hazardTypeQuery);
                $hazardType = $hazardTypeStmt->fetch(PDO::FETCH_ASSOC);
                $hazardTypeId = $hazardType ? $hazardType['hazard_type_id'] : 1;
                
                // Insert hazard
                $hazardInsertQuery = "INSERT INTO hazards (task_id, hazard_type_id, hazard_description) 
                                     VALUES (:task_id, :hazard_type_id, :description)";
                $hazardInsertStmt = $pdo->prepare($hazardInsertQuery);
                $hazardInsertStmt->execute([
                    ':task_id' => $task_id,
                    ':hazard_type_id' => $hazardTypeId,
                    ':description' => $hazard['description']
                ]);
                $hazardId = $pdo->lastInsertId();
                
                // Insert risk for this hazard
                $riskInsertQuery = "INSERT INTO risks (hazard_id, risk_description, likelihood_before, severity_before, risk_rate_before, 
                                                      likelihood_after, severity_after, risk_rate_after) 
                                   VALUES (:hazard_id, :description, :likelihood_before, :severity_before, :risk_rate_before,
                                           :likelihood_after, :severity_after, :risk_rate_after)";
                $riskInsertStmt = $pdo->prepare($riskInsertQuery);
                $riskInsertStmt->execute([
                    ':hazard_id' => $hazardId,
                    ':description' => 'Risk for: ' . $hazard['description'],
                    ':likelihood_before' => intval($hazard['likelihood'] ?? 1),
                    ':severity_before' => intval($hazard['severity'] ?? 1),
                    ':risk_rate_before' => (intval($hazard['likelihood'] ?? 1) * intval($hazard['severity'] ?? 1)),
                    ':likelihood_after' => intval($hazard['residual_likelihood'] ?? 1),
                    ':severity_after' => intval($hazard['residual_severity'] ?? 1),
                    ':risk_rate_after' => (intval($hazard['residual_likelihood'] ?? 1) * intval($hazard['residual_severity'] ?? 1))
                ]);
                $riskId = $pdo->lastInsertId();
                
                // Process controls for this hazard
                if (!empty($hazard['controls'])) {
                    foreach ($hazard['controls'] as $control) {
                        if (!empty($control['description'])) {
                            // Get control_type_id based on category
                            $controlTypeId = 1; // default
                            if (!empty($control['category'])) {
                                $controlTypeQuery = "SELECT control_type_id FROM control_types WHERE type_name = :type_name LIMIT 1";
                                $controlTypeStmt = $pdo->prepare($controlTypeQuery);
                                $controlTypeStmt->execute([':type_name' => $control['category']]);
                                $controlType = $controlTypeStmt->fetch(PDO::FETCH_ASSOC);
                                if ($controlType) {
                                    $controlTypeId = $controlType['control_type_id'];
                                }
                            }
                            
                            $controlInsertQuery = "INSERT INTO controls (risk_id, control_description, control_type_id, status) 
                                                 VALUES (:risk_id, :description, :control_type_id, :status)";
                            $controlInsertStmt = $pdo->prepare($controlInsertQuery);
                            $controlInsertStmt->execute([
                                ':risk_id' => $riskId,
                                ':description' => $control['description'],
                                ':control_type_id' => $controlTypeId,
                                ':status' => $control['status'] ?? 'Pending'
                            ]);
                        }
                    }
                }
            }
        }
        
        // Process affected people categories - append to comments
        if (!empty($affected_people_categories)) {
            $categoriesStr = 'Affected categories: ' . implode(', ', $affected_people_categories);
            $updateCommentsQuery = "UPDATE assessments SET comments = CONCAT(IFNULL(comments, ''), ' ', :categories) WHERE assessment_id = :assessment_id";
            $updateCommentsStmt = $pdo->prepare($updateCommentsQuery);
            $updateCommentsStmt->execute([
                ':categories' => $categoriesStr,
                ':assessment_id' => $assessment_id
            ]);
        }
        
        // Commit transaction
        $pdo->commit();
        if ($assessment_id) {
            echo json_encode(["success" => true, "assessment_id" => $assessment_id]);
        } else {
            echo json_encode(["success" => true]);
        }
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        echo json_encode(["success" => false, "error" => "Database error: " . $e->getMessage()]);
    }
} else {
    echo json_encode(["success" => false, "error" => "Invalid request method."]);
}
?>

