<?php
/* File: sheener/php/get_task_questionnaire.php */

header('Content-Type: application/json');
require_once 'database.php';

$task_id = isset($_GET['task_id']) ? intval($_GET['task_id']) : null;

if (!$task_id) {
    echo json_encode(["success" => false, "error" => "Task ID is required"]);
    exit;
}

try {
    $database = new Database();
    $pdo = $database->getConnection();

    // Check if taskquestionnaire table exists
    $tableExists = false;
    try {
        $checkStmt = $pdo->query("SHOW TABLES LIKE 'taskquestionnaire'");
        $tableExists = $checkStmt->rowCount() > 0;
    } catch (PDOException $e) {
        // Table doesn't exist
    }

    $questionnaire = null;
    if ($tableExists) {
        $stmt = $pdo->prepare("SELECT * FROM taskquestionnaire WHERE taskid = ?");
        $stmt->execute([$task_id]);
        $questionnaire = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Get hazards for this task
    $stmt = $pdo->prepare("
        SELECT h.hazard_id, h.hazard_description, ht.type_name, ht.hazard_type_id
        FROM hazards h
        INNER JOIN hazard_type ht ON h.hazard_type_id = ht.hazard_type_id
        WHERE h.task_id = ?
    ");
    $stmt->execute([$task_id]);
    $hazards = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get permit recommendations from questionnaire
    $permit_recommendations = [];
    if ($questionnaire && !empty($questionnaire['keyhazardsjson'])) {
        $key_hazards = json_decode($questionnaire['keyhazardsjson'], true);
        
        // Map hazards to permit recommendations
        $hazard_to_permit = [
            'Work at Height' => 'Work at Height',
            'Confined Space' => 'Confined Space',
            'Hot Work' => 'Hot Work',
            'Electrical' => 'Electrical Work',
            'Energy Isolation' => 'Clearance'
        ];

        foreach ($key_hazards as $hazard) {
            if (isset($hazard_to_permit[$hazard])) {
                $permit_recommendations[] = $hazard_to_permit[$hazard];
            }
        }
    }

    // Get existing permits for this task
    $stmt = $pdo->prepare("
        SELECT permit_id, permit_type, status
        FROM permits
        WHERE task_id = ?
    ");
    $stmt->execute([$task_id]);
    $existing_permits = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $existing_permit_types = array_column($existing_permits, 'permit_type');

    // Filter out recommendations that already have permits
    $suggested_permits = array_diff($permit_recommendations, $existing_permit_types);

    echo json_encode([
        "success" => true,
        "data" => [
            "questionnaire" => $questionnaire,
            "hazards" => $hazards,
            "suggested_permits" => array_values($suggested_permits),
            "existing_permits" => $existing_permits
        ]
    ]);
} catch (PDOException $e) {
    echo json_encode(["success" => false, "error" => "Database error: " . $e->getMessage()]);
}

