<?php
/* File: sheener/php/update_event.php */

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

    $event_id = $_POST['event_id'] ?? null;
    $event_type = $_POST['event_type'] ?? '';
    $description = $_POST['description'] ?? '';
    $reported_by = $_POST['reported_by'] ?? null;
    $status = $_POST['status'] ?? 'Open';
    $event_subcategory = $_POST['event_subcategory'] ?? null;
    $likelihood = $_POST['likelihood'] ?? null;
    $severity = $_POST['severity'] ?? null;
    $risk_rating = $_POST['risk_rating'] ?? null;
    $department_id = $_POST['department_id'] ?? null;
    $cc_title = $_POST['cc_title'] ?? null;
    $cc_justification = $_POST['cc_justification'] ?? null;
    $cc_change_from = $_POST['cc_change_from'] ?? null;
    $cc_change_to = $_POST['cc_change_to'] ?? null;
    $cc_change_type = $_POST['cc_change_type'] ?? null;
    $cc_logged_ref = $_POST['cc_logged_ref'] ?? null;
    $cc_logged_date = $_POST['cc_logged_date'] ?? null;

    if (empty($event_id) || empty($event_type) || empty($description) || empty($reported_by)) {
        echo json_encode(["success" => false, "error" => "Missing required fields"]);
        exit;
    }

    $valid_types = ['OFI', 'Adverse Event', 'Defects', 'NonCompliance'];
    if (!in_array($event_type, $valid_types)) {
        echo json_encode(["success" => false, "error" => "Invalid event type"]);
        exit;
    }

    $getOrigStmt = $pdo->prepare("SELECT * FROM events WHERE event_id = :event_id");
    $getOrigStmt->execute([':event_id' => $event_id]);
    $oldEvent = $getOrigStmt->fetch(PDO::FETCH_ASSOC);

    if (!$oldEvent) {
        echo json_encode(["success" => false, "error" => "Event not found"]);
        exit;
    }

    $likelihood_val = (isset($likelihood) && $likelihood !== '' && is_numeric($likelihood)) ? intval($likelihood) : null;
    $severity_val = (isset($severity) && $severity !== '' && is_numeric($severity)) ? intval($severity) : null;
    $risk_rating_val = (isset($risk_rating) && $risk_rating !== '' && is_numeric($risk_rating)) ? intval($risk_rating) : null;
    $department_id_val = (isset($department_id) && $department_id !== '' && is_numeric($department_id)) ? intval($department_id) : null;
    $event_subcategory_val = (isset($event_subcategory) && trim($event_subcategory) !== '') ? trim($event_subcategory) : null;

    $query = "UPDATE events SET 
                event_type = :event_type, 
                description = :description, 
                reported_by = :reported_by, 
                status = :status,
                event_subcategory = :event_subcategory,
                likelihood = :likelihood,
                severity = :severity,
                risk_rating = :risk_rating,
                department_id = :department_id,
                cc_title = :cc_title,
                cc_justification = :cc_justification,
                cc_change_from = :cc_change_from,
                cc_change_to = :cc_change_to,
                cc_change_type = :cc_change_type,
                cc_logged_ref = :cc_logged_ref,
                cc_logged_date = :cc_logged_date
              WHERE event_id = :event_id";
              
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(':event_type', $event_type);
    $stmt->bindValue(':description', $description);
    $stmt->bindValue(':reported_by', intval($reported_by), PDO::PARAM_INT);
    $stmt->bindValue(':status', $status);
    $stmt->bindValue(':event_subcategory', $event_subcategory_val);
    $stmt->bindValue(':likelihood', $likelihood_val, $likelihood_val !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
    $stmt->bindValue(':severity', $severity_val, $severity_val !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
    $stmt->bindValue(':risk_rating', $risk_rating_val, $risk_rating_val !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
    $stmt->bindValue(':department_id', $department_id_val, $department_id_val !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
    $stmt->bindValue(':cc_title', $cc_title);
    $stmt->bindValue(':cc_justification', $cc_justification);
    $stmt->bindValue(':cc_change_from', $cc_change_from);
    $stmt->bindValue(':cc_change_to', $cc_change_to);
    $stmt->bindValue(':cc_change_type', $cc_change_type);
    $stmt->bindValue(':cc_logged_ref', $cc_logged_ref);
    $stmt->bindValue(':cc_logged_date', $cc_logged_date && $cc_logged_date !== '' ? $cc_logged_date : null);
    $stmt->bindValue(':event_id', intval($event_id), PDO::PARAM_INT);
    $stmt->execute();

    if (!empty($_FILES['attachments']['name'][0])) {
        $uploadDir = __DIR__ . '/uploads/';
        if (!is_dir($uploadDir)) { mkdir($uploadDir, 0755, true); }
        $cnt = count($_FILES['attachments']['name']);
        $stmtAtt = $pdo->prepare("INSERT INTO attachments (event_id, file_name, file_type, file_size, file_path, uploaded_by) VALUES (?, ?, ?, ?, ?, ?)");
        for ($i = 0; $i < $cnt; $i++) {
            if ($_FILES['attachments']['error'][$i] !== UPLOAD_ERR_OK) continue;
            $safeName = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $_FILES['attachments']['name'][$i]);
            if (move_uploaded_file($_FILES['attachments']['tmp_name'][$i], $uploadDir . $safeName)) {
                $stmtAtt->execute([$event_id, $_FILES['attachments']['name'][$i], $_FILES['attachments']['type'][$i], $_FILES['attachments']['size'][$i], 'php/uploads/' . $safeName, $_SESSION['user_id']]);
            }
        }
    }

    echo json_encode(["success" => true]);
} catch (Exception $e) {
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}
?>
