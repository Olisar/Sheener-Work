<?php
/* File: sheener/php/add_event.php */

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

// Check if this is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        "success" => false, 
        "error" => "This endpoint only accepts POST requests.",
        "request_method" => $_SERVER['REQUEST_METHOD'] ?? 'unknown'
    ]);
    exit;
}

try {
    $database = new Database();
    $pdo = $database->getConnection();

    // Get form data from POST
    $event_type = $_POST['event_type'] ?? '';
    $event_subcategory = $_POST['event_subcategory'] ?? '';
    $description = $_POST['description'] ?? '';
    $reported_by = $_POST['reported_by'] ?? null;
    $department_id = $_POST['department_id'] ?? null;
    $likelihood = $_POST['likelihood'] ?? null;
    $severity = $_POST['severity'] ?? null;
    $risk_rating = $_POST['risk_rating'] ?? null;

    // Validate required fields
    $missing_fields = [];
    if (empty($event_type) || trim($event_type) === '') { $missing_fields[] = 'event_type'; }
    if (empty($description) || trim($description) === '') { $missing_fields[] = 'description'; }
    if (empty($reported_by) || !is_numeric($reported_by) || intval($reported_by) <= 0) { $missing_fields[] = 'reported_by'; }

    if (!empty($missing_fields)) {
        echo json_encode(["success" => false, "error" => "Missing required fields: " . implode(', ', $missing_fields)]);
        exit;
    }

    $reported_by = intval($reported_by);
    $pdo->beginTransaction();

    try {
        $valid_db_types = ['OFI', 'Adverse Event', 'Defects', 'NonCompliance'];
        $db_event_type = 'OFI';
        
        switch ($event_type) {
            case 'Audit': $db_event_type = 'NonCompliance'; break;
            case 'Near Miss':
            case 'Accident': $db_event_type = 'Adverse Event'; break;
            case 'Good Catch': $db_event_type = 'Defects'; break;
            case 'Opportunity for Improvement': $db_event_type = 'OFI'; break;
            default:
                if (in_array($event_type, $valid_db_types)) { $db_event_type = $event_type; }
                break;
        }
        
        if (!in_array($db_event_type, $valid_db_types)) {
            throw new Exception("Invalid event type mapping.");
        }

        $sql = "INSERT INTO events (
                    event_type, description, reported_by, status, event_subcategory,
                    likelihood, severity, risk_rating, department_id
                ) VALUES (
                    :event_type, :description, :reported_by, 'Open', :event_subcategory,
                    :likelihood, :severity, :risk_rating, :department_id
                )";

        $likelihood_val = (isset($likelihood) && $likelihood !== '' && is_numeric($likelihood)) ? intval($likelihood) : null;
        $severity_val = (isset($severity) && $severity !== '' && is_numeric($severity)) ? intval($severity) : null;
        $risk_rating_val = (isset($risk_rating) && $risk_rating !== '' && is_numeric($risk_rating)) ? intval($risk_rating) : null;
        $department_id_val = (isset($department_id) && $department_id !== '' && is_numeric($department_id)) ? intval($department_id) : null;
        $event_subcategory_val = (isset($event_subcategory) && trim($event_subcategory) !== '') ? trim($event_subcategory) : null;

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':event_type', $db_event_type);
        $stmt->bindValue(':description', $description);
        $stmt->bindValue(':reported_by', $reported_by, PDO::PARAM_INT);
        $stmt->bindValue(':event_subcategory', $event_subcategory_val);
        $stmt->bindValue(':likelihood', $likelihood_val, $likelihood_val !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue(':severity', $severity_val, $severity_val !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue(':risk_rating', $risk_rating_val, $risk_rating_val !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue(':department_id', $department_id_val, $department_id_val !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
        
        $stmt->execute();
        $event_id = $pdo->lastInsertId();

        // Audit log
        $auditStmt = $pdo->prepare("INSERT INTO auditlog (Action, PerformedBy, PerformedAt, TableAffected, Details, ActionDetails) 
                                   VALUES ('CREATE_EVENT', :performedby, NOW(), 'events', :details, :actiondetails)");
        $auditStmt->execute([
            ':performedby' => $_SESSION['user_id'],
            ':details' => "Created Event #{$event_id}",
            ':actiondetails' => json_encode(['event_id' => $event_id, 'type' => $db_event_type])
        ]);

        // Handle attachments
        if (!empty($_FILES['attachments']['name'][0])) {
            $uploadDir = __DIR__ . '/uploads/';
            if (!is_dir($uploadDir)) { mkdir($uploadDir, 0755, true); }

            $desc = isset($_POST['file_descriptions']) ? json_decode($_POST['file_descriptions'], true) : [];
            $cnt = count($_FILES['attachments']['name']);
            
            $allowedExts = ['pdf', 'xls', 'xlsx', 'doc', 'docx', 'jpg', 'jpeg', 'png'];

            $stmtAtt = $pdo->prepare(
                "INSERT INTO attachments (event_id, file_name, file_type, file_size, file_path, description, uploaded_by)
                 VALUES (?, ?, ?, ?, ?, ?, ?)"
            );
            
            for ($i = 0; $i < $cnt; $i++) {
                if ($_FILES['attachments']['error'][$i] !== UPLOAD_ERR_OK) continue;

                $originalName = $_FILES['attachments']['name'][$i];
                $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
                if (!in_array($ext, $allowedExts)) continue;

                $safeName = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $originalName);
                $filePath = $uploadDir . $safeName;

                if (move_uploaded_file($_FILES['attachments']['tmp_name'][$i], $filePath)) {
                    $relativePath = 'php/uploads/' . $safeName;
                    $stmtAtt->execute([
                        $event_id, $originalName, $_FILES['attachments']['type'][$i],
                        $_FILES['attachments']['size'][$i], $relativePath, $desc[$i] ?? null, $_SESSION['user_id']
                    ]);
                }
            }
        }

        $pdo->commit();
        echo json_encode(["success" => true, "event_id" => $event_id]);

    } catch (Exception $e) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        throw $e;
    }
} catch (Exception $e) {
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}
?>
