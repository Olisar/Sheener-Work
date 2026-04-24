<?php
/* File: sheener/php/update_task.php */

header('Content-Type: application/json');
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    die(json_encode(['success' => false, 'error' => 'Unauthorized access.']));
}

// CSRF check
if (empty($_POST['csrf_token']) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    http_response_code(403);
    die(json_encode(['success' => false, 'error' => 'Invalid CSRF token.']));
}

require_once 'database.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();

    $task_id = $_POST['task_id'];
    $task_name = $_POST['task_name'];
    $task_description = $_POST['task_description'];
    $start_date = $_POST['start_date'];
    $finish_date = $_POST['finish_date'] ?: null;
    $due_date = $_POST['due_date'] ?: null;
    $task_type = $_POST['task_type'] ?? 'Project Task';
    $priority = $_POST['priority'];
    $status = $_POST['status'];
    $department_id = $_POST['department_id'] ?: null;
    $assigned_to = $_POST['assigned_to'] ?: null;

    // Normalize empty strings to NULL for nullable FKs
    $finish_date = ($finish_date === '' ? null : $finish_date);
    $due_date = ($due_date === '' ? null : $due_date);
    $department_id = ($department_id === '' ? null : $department_id);
    $assigned_to = ($assigned_to === '' ? null : $assigned_to);

    // Cast numeric IDs when present
    if (!is_null($department_id) && ctype_digit((string) $department_id))
        $department_id = (int) $department_id;
    if (!is_null($assigned_to) && ctype_digit((string) $assigned_to))
        $assigned_to = (int) $assigned_to;

    $query = "UPDATE tasks SET 
                task_name = :task_name, 
                task_description = :task_description, 
                start_date = :start_date, 
                finish_date = :finish_date, 
                due_date = :due_date, 
                task_type = :task_type,
                priority = :priority, 
                status = :status, 
                department_id = :department_id, 
                assigned_to = :assigned_to,
                updated_date = NOW()
              WHERE task_id = :task_id";

    $stmt = $pdo->prepare($query);
    $stmt->execute([
        ':task_name' => $task_name,
        ':task_description' => $task_description,
        ':start_date' => $start_date,
        ':finish_date' => $finish_date,
        ':due_date' => $due_date,
        ':task_type' => $task_type,
        ':priority' => $priority,
        ':status' => $status,
        ':department_id' => $department_id,
        ':assigned_to' => $assigned_to,
        ':task_id' => $task_id
    ]);

    // Process questionnaire data if provided
    if (isset($_POST['questionnaire_data']) && !empty($_POST['questionnaire_data'])) {
        $questionnaire_data = json_decode($_POST['questionnaire_data'], true);

        if ($questionnaire_data) {
            try {
                // Calculate notifiable flag and other derived fields (same logic as add_task.php)
                $notifiable_flag = 0;
                $recommend_hira = 0;
                $estimated_duration_days = null;
                $estimated_person_hours = null;
                $key_hazards = [];
                $permit_recommendations = [];

                // Parse duration
                if (!empty($questionnaire_data['duration'])) {
                    $duration = $questionnaire_data['duration'];
                    if ($duration === '>30') {
                        $notifiable_flag = 1;
                        $estimated_duration_days = 31;
                    } elseif ($duration === '6-30') {
                        $estimated_duration_days = 18;
                    } elseif ($duration === '1-5') {
                        $estimated_duration_days = 3;
                    } else {
                        $estimated_duration_days = 1;
                    }
                }

                // Parse person hours
                if (!empty($questionnaire_data['personhours'])) {
                    $personhours = $questionnaire_data['personhours'];
                    if ($personhours === '>500') {
                        $notifiable_flag = 1;
                        $estimated_person_hours = 501;
                    } elseif ($personhours === '80-500') {
                        $estimated_person_hours = 290;
                    } else {
                        $estimated_person_hours = 40;
                    }
                }

                // Helper function to get or create hazard type
                $getHazardTypeId = function ($typeName, $pdo) {
                    $stmt = $pdo->prepare("SELECT hazard_type_id FROM hazard_type WHERE type_name = ?");
                    $stmt->execute([$typeName]);
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($result) {
                        return $result['hazard_type_id'];
                    }
                    // Create if doesn't exist
                    $stmt = $pdo->prepare("INSERT INTO hazard_type (type_name, description) VALUES (?, ?)");
                    $stmt->execute([$typeName, "Auto-created from questionnaire"]);
                    return $pdo->lastInsertId();
                };

                // Delete existing hazards for this task
                try {
                    $deleteHazards = $pdo->prepare("DELETE FROM hazards WHERE task_id = ?");
                    $deleteHazards->execute([$task_id]);
                } catch (PDOException $e) {
                    error_log("Error deleting existing hazards: " . $e->getMessage());
                }

                // Map questionnaire answers to hazards (same logic as add_task.php)
                if (!empty($questionnaire_data['height']) && $questionnaire_data['height'] === 'yes') {
                    $hazard_type_id = $getHazardTypeId('Work at Height', $pdo);
                    $key_hazards[] = 'Work at Height';
                    $stmt = $pdo->prepare("INSERT INTO hazards (task_id, hazard_type_id, hazard_description) VALUES (?, ?, ?)");
                    $stmt->execute([$task_id, $hazard_type_id, "Work at height, roof access, or scaffolding identified in questionnaire"]);
                    $permit_recommendations[] = 'Work at Height';
                }

                if (!empty($questionnaire_data['confined']) && $questionnaire_data['confined'] === 'yes') {
                    $hazard_type_id = $getHazardTypeId('Confined Space', $pdo);
                    $key_hazards[] = 'Confined Space';
                    $stmt = $pdo->prepare("INSERT INTO hazards (task_id, hazard_type_id, hazard_description) VALUES (?, ?, ?)");
                    $stmt->execute([$task_id, $hazard_type_id, "Confined space work (vessels, pits, tanks) identified in questionnaire"]);
                    $permit_recommendations[] = 'Confined Space';
                }

                if (!empty($questionnaire_data['potent_api']) && $questionnaire_data['potent_api'] === 'yes') {
                    $hazard_type_id = $getHazardTypeId('Chemical Exposure Hazard', $pdo);
                    $key_hazards[] = 'High-hazard Chemicals';
                    $stmt = $pdo->prepare("INSERT INTO hazards (task_id, hazard_type_id, hazard_description) VALUES (?, ?, ?)");
                    $stmt->execute([$task_id, $hazard_type_id, "High-hazard chemicals identified in questionnaire"]);
                }

                if (!empty($questionnaire_data['atex']) && $questionnaire_data['atex'] === 'yes') {
                    $hazard_type_id = $getHazardTypeId('ATEX / Classified Area', $pdo);
                    $key_hazards[] = 'ATEX / Classified Area';
                    $stmt = $pdo->prepare("INSERT INTO hazards (task_id, hazard_type_id, hazard_description) VALUES (?, ?, ?)");
                    $stmt->execute([$task_id, $hazard_type_id, "ATEX or sterile production area work identified in questionnaire"]);
                }

                if (!empty($questionnaire_data['hotwork']) && $questionnaire_data['hotwork'] === 'yes') {
                    $hazard_type_id = $getHazardTypeId('Hot Work', $pdo);
                    $key_hazards[] = 'Hot Work';
                    $stmt = $pdo->prepare("INSERT INTO hazards (task_id, hazard_type_id, hazard_description) VALUES (?, ?, ?)");
                    $stmt->execute([$task_id, $hazard_type_id, "Hot work (welding, cutting, grinding) identified in questionnaire"]);
                    $permit_recommendations[] = 'Hot Work';
                }

                // Handle utilities
                if (!empty($questionnaire_data['utilities']) && is_array($questionnaire_data['utilities'])) {
                    foreach ($questionnaire_data['utilities'] as $utility) {
                        if ($utility === 'electrical') {
                            $hazard_type_id = $getHazardTypeId('Electrical/Static Hazard', $pdo);
                            $key_hazards[] = 'Electrical';
                            $stmt = $pdo->prepare("INSERT INTO hazards (task_id, hazard_type_id, hazard_description) VALUES (?, ?, ?)");
                            $stmt->execute([$task_id, $hazard_type_id, "Electrical work identified in questionnaire"]);
                            $permit_recommendations[] = 'Electrical Work';
                        } elseif ($utility === 'steam' || $utility === 'compressed_air' || $utility === 'gases') {
                            $hazard_type_id = $getHazardTypeId('Energy Isolation', $pdo);
                            $key_hazards[] = 'Energy Isolation';
                            $stmt = $pdo->prepare("INSERT INTO hazards (task_id, hazard_type_id, hazard_description) VALUES (?, ?, ?)");
                            $stmt->execute([$task_id, $hazard_type_id, ucfirst($utility) . " work identified in questionnaire"]);
                            $permit_recommendations[] = 'Clearance';
                        } elseif ($utility === 'other') {
                            $key_hazards[] = 'Other Critical Utilities';
                        }
                    }
                }

                // Set recommend HIRA flag if any hazards were identified
                if (count($key_hazards) > 0) {
                    $recommend_hira = 1;
                }

                // Save/update questionnaire data
                $key_hazards_json = json_encode($key_hazards);

                // Check if taskquestionnaire table exists
                $tableExists = false;
                try {
                    $checkStmt = $pdo->query("SHOW TABLES LIKE 'taskquestionnaire'");
                    $tableExists = $checkStmt->rowCount() > 0;
                } catch (PDOException $e) {
                    // Table doesn't exist, skip questionnaire save
                }

                if ($tableExists) {
                    // Check if questionnaire already exists
                    $checkQ = $pdo->prepare("SELECT taskquestionnaireid FROM taskquestionnaire WHERE taskid = ?");
                    $checkQ->execute([$task_id]);
                    $existing = $checkQ->fetch(PDO::FETCH_ASSOC);

                    if ($existing) {
                        // Update existing
                        $stmt = $pdo->prepare("
                            UPDATE taskquestionnaire SET
                                notifiableflag = ?,
                                estimateddurationdays = ?,
                                estimatedpersonhours = ?,
                                keyhazardsjson = ?,
                                recommendhiraflag = ?,
                                notes = ?
                            WHERE taskid = ?
                        ");
                        $stmt->execute([
                            $notifiable_flag,
                            $estimated_duration_days,
                            $estimated_person_hours,
                            $key_hazards_json,
                            $recommend_hira,
                            $questionnaire_data['notes'] ?? null,
                            $task_id
                        ]);
                    } else {
                        // Insert new
                        $stmt = $pdo->prepare("
                            INSERT INTO taskquestionnaire (
                                taskid, notifiableflag, estimateddurationdays, estimatedpersonhours,
                                keyhazardsjson, recommendhiraflag, notes
                            ) VALUES (?, ?, ?, ?, ?, ?, ?)
                        ");
                        $stmt->execute([
                            $task_id,
                            $notifiable_flag,
                            $estimated_duration_days,
                            $estimated_person_hours,
                            $key_hazards_json,
                            $recommend_hira,
                            $questionnaire_data['notes'] ?? null
                        ]);
                    }

                    // Update or create HIRA register entry if hazards were identified
                    if ($recommend_hira == 1) {
                        try {
                            // Check if HIRA entry exists for this task
                            $checkHira = $pdo->prepare("SELECT hira_id FROM hira_register WHERE task_id = ?");
                            $checkHira->execute([$task_id]);
                            $existingHira = $checkHira->fetch(PDO::FETCH_ASSOC);

                            if (!$existingHira) {
                                // Get default stage_id (usually 1 for initial stage)
                                $stageStmt = $pdo->query("SELECT stage_id FROM lifecycle_stages LIMIT 1");
                                $stage = $stageStmt->fetch(PDO::FETCH_ASSOC);
                                $stage_id = $stage ? $stage['stage_id'] : 1;

                                $hiraStmt = $pdo->prepare("
                                    INSERT INTO hira_register (task_id, stage_id, description, created_date)
                                    VALUES (?, ?, ?, NOW())
                                ");
                                $hiraStmt->execute([
                                    $task_id,
                                    $stage_id,
                                    "Auto-created from questionnaire. Key hazards: " . implode(', ', $key_hazards)
                                ]);
                            }
                        } catch (PDOException $e) {
                            // HIRA table might not exist or have different structure, continue
                            error_log("Could not create/update HIRA register: " . $e->getMessage());
                        }
                    }
                }
            } catch (PDOException $e) {
                error_log("Questionnaire processing error: " . $e->getMessage());
                // Continue with task update even if questionnaire processing fails
            }
        }
    }

    echo json_encode(["success" => true]);
} catch (PDOException $e) {
    echo json_encode(["success" => false, "error" => "Database error: " . $e->getMessage()]);
}
