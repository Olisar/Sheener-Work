<?php
/* File: sheener/php/update_permit.php */

/*  Sheener / php / update_permit.php  */
session_start();
require_once 'database.php';
header('Content-Type: application/json');

// Check authentication - allow user 32 and other authenticated users
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    die(json_encode(['success' => false, 'error' => 'Unauthorized - Please log in']));
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['success' => false, 'error' => 'Method not allowed']));
}

$db  = new Database();
$pdo = $db->getConnection();
$pdo->beginTransaction();

try {
    /* ---------- 1.  BASIC INPUT ---------- */
    $permit_id = filter_input(INPUT_POST, 'permit_id', FILTER_VALIDATE_INT);
    $task_id   = filter_input(INPUT_POST, 'task_id',   FILTER_VALIDATE_INT);
    if (!$permit_id || !$task_id) {
        throw new Exception('Missing permit_id or task_id');
    }

    /* ---------- 2.  UPDATE PERMIT ---------- */
    // Validate permit status
    $valid_statuses = ['Requested', 'Issued', 'Active', 'Suspended', 'Closed', 'Expired', 'Revoked', 'Cancelled'];
    $status = $_POST['status'] ?? $_POST['permit_status'] ?? null;
    if ($status && !in_array($status, $valid_statuses)) {
        throw new Exception('Invalid permit status: ' . $status);
    }
    
    $dep_owner = filter_input(INPUT_POST, 'Dep_owner', FILTER_VALIDATE_INT) ?? 0;
    
    $stmt = $pdo->prepare(
        "UPDATE permits
         SET task_id       = :task_id,
             permit_type   = :permit_type,
             issued_by     = :issued_by,
             approved_by   = :approved_by,
             issue_date    = :issue_date,
             expiry_date   = :expiry_date,
             conditions    = :conditions,
             status        = :permit_status,
             Dep_owner     = :dep_owner
         WHERE permit_id   = :permit_id"
    );
    // Handle approved_by - convert empty string to null to avoid foreign key constraint violation
    $approved_by = filter_input(INPUT_POST, 'approved_by', FILTER_VALIDATE_INT);
    if ($approved_by === false || $approved_by === null || $approved_by === '') {
        $approved_by = null;
    }
    
    // Handle issued_by - ensure it's valid
    $issued_by = filter_input(INPUT_POST, 'issued_by', FILTER_VALIDATE_INT);
    if (!$issued_by) {
        throw new Exception('Invalid issued_by value');
    }
    
    // Handle Dep_owner - ensure it's valid
    $dep_owner = filter_input(INPUT_POST, 'Dep_owner', FILTER_VALIDATE_INT);
    if (!$dep_owner) {
        throw new Exception('Invalid Dep_owner value');
    }
    
    $stmt->execute([
        ':task_id'    => $task_id,
        ':permit_type'=> $_POST['permit_type']  ?? '',
        ':issued_by'  => $issued_by,
        ':approved_by'=> $approved_by,  // null if empty, otherwise the integer value
        ':issue_date' => $_POST['issue_date']   ?? null,
        ':expiry_date'=> $_POST['expiry_date']  ?? null,
        ':conditions' => $_POST['conditions']  ?? '',
        ':permit_status' => $status ?? null,
        ':dep_owner'  => $dep_owner,
        ':permit_id'  => $permit_id
    ]);

    /* ---------- 3.  STEPS (full replace) ---------- */
    $pdo->prepare("DELETE FROM permit_steps WHERE permit_id = ?")->execute([$permit_id]);
    $steps = isset($_POST['steps']) ? json_decode($_POST['steps'], true) : [];
    if (is_array($steps)) {
        $stmtStep = $pdo->prepare(
            "INSERT INTO permit_steps (permit_id, step_number, step_description, hazard_description, control_description)
             VALUES (?, ?, ?, ?, ?)"
        );
        foreach ($steps as $s) {
            if (empty($s['step_description'])) continue;
            $stmtStep->execute([
                $permit_id,
                $s['step_number'] ?? 0,
                $s['step_description'] ?? '',
                $s['hazard_description']  ?? '',
                $s['control_description'] ?? ''
            ]);
        }
    }

    /* ---------- 4.  DELETED ATTACHMENTS ---------- */
    $deleted = isset($_POST['deleted_attachments']) ? json_decode($_POST['deleted_attachments'], true) : [];
    if (is_array($deleted)) {
        $stmtDel = $pdo->prepare("DELETE FROM attachments WHERE attachment_id = ? AND permit_id = ?");
        foreach ($deleted as $id) $stmtDel->execute([(int)$id, $permit_id]);
    }

    /* ---------- 5.  NEW ATTACHMENTS (Save to disk) ---------- */
    if (!empty($_FILES['attachments']['name'][0])) {
        $uploadDir = __DIR__ . '/uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $desc = isset($_POST['file_descriptions']) ? json_decode($_POST['file_descriptions'], true) : [];
        $cnt  = count($_FILES['attachments']['name']);

        $stmtAtt = $pdo->prepare(
            "INSERT INTO attachments (permit_id, file_name, file_type, file_size, file_path, description, uploaded_by)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        
        for ($i = 0; $i < $cnt; $i++) {
            if ($_FILES['attachments']['error'][$i] !== UPLOAD_ERR_OK) continue;

            $originalName = $_FILES['attachments']['name'][$i];
            $safeName = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $originalName);
            $filePath = $uploadDir . $safeName;

            if (move_uploaded_file($_FILES['attachments']['tmp_name'][$i], $filePath)) {
                $relativePath = 'php/uploads/' . $safeName;
                $stmtAtt->execute([
                    $permit_id,
                    $originalName,
                    $_FILES['attachments']['type'][$i],
                    $_FILES['attachments']['size'][$i],
                    $relativePath,
                    $desc[$i] ?? null,
                    $_SESSION['user_id'] ?? 0
                ]);
            }
        }
    }

    $pdo->commit();
    echo json_encode(['success' => true]);

} catch (Throwable $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
