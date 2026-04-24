<?php
/* File: sheener/php/create_permit.php */

/*  Sheener / php / create_permit.php  */
session_start();
require_once 'database.php';
header('Content-Type: application/json');

// Check authentication - allow user 32 and other authenticated users
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    die(json_encode(['success' => false, 'error' => 'Unauthorized - Please log in']));
}

/* ---------- 1.  BASIC SECURITY ---------- */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['success' => false, 'error' => 'Method not allowed']));
}

/* ---------- 2.  INPUT VALIDATION ---------- */
$task_id     = filter_input(INPUT_POST, 'task_id',     FILTER_VALIDATE_INT);
$permit_type = trim(filter_input(INPUT_POST, 'permit_type', FILTER_SANITIZE_STRING));
$issued_by   = filter_input(INPUT_POST, 'issued_by',   FILTER_VALIDATE_INT);
$issue_date  = filter_input(INPUT_POST, 'issue_date',  FILTER_SANITIZE_STRING);
$expiry_date = filter_input(INPUT_POST, 'expiry_date', FILTER_SANITIZE_STRING);
$conditions  = trim(filter_input(INPUT_POST, 'conditions',  FILTER_SANITIZE_STRING));
$dep_owner   = filter_input(INPUT_POST, 'Dep_owner',  FILTER_VALIDATE_INT) ?? 0;

// Handle optional approved_by field - convert empty string to null
$approved_by = filter_input(INPUT_POST, 'approved_by', FILTER_VALIDATE_INT);
$approved_by = ($approved_by === false || $approved_by === null || $approved_by === '') ? null : $approved_by;

$missing = [];
foreach (['task_id','permit_type','issued_by','issue_date','expiry_date'] as $f)
    if (!$$f) $missing[] = $f;

if ($missing) {
    http_response_code(400);
    die(json_encode(['success' => false, 'error' => 'Missing: ' . implode(', ', $missing)]));
}

if (strtotime($issue_date) > strtotime($expiry_date)) {
    http_response_code(400);
    die(json_encode(['success' => false, 'error' => 'Issue date after expiry']));
}

/* ---------- 2.5.  VERIFY TASK EXISTS ---------- */
$db  = new Database();
$pdo = $db->getConnection();

// Verify that the task exists in the database
$stmt = $pdo->prepare("SELECT task_id, task_name FROM tasks WHERE task_id = :task_id");
$stmt->execute([':task_id' => $task_id]);
$task = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$task) {
    http_response_code(400);
    die(json_encode([
        'success' => false, 
        'error' => 'The selected task does not exist. Please create a task first before creating a Permit to Work. The task is required to store job information and relevant documents that will be linked to this permit.'
    ]));
}

/* ---------- 3.  TRANSACTION ---------- */
$pdo->beginTransaction();

try {
    /* 3.a  PERMIT  */
    $stmt = $pdo->prepare(
        "INSERT INTO permits (task_id, permit_type, issued_by, approved_by, issue_date, expiry_date, status, conditions, Dep_owner)
         VALUES (:task_id, :permit_type, :issued_by, :approved_by, :issue_date, :expiry_date, :status, :conditions, :dep_owner)"
    );
    $stmt->execute([
        ':task_id'    => $task_id,
        ':permit_type'=> $permit_type,
        ':issued_by'  => $issued_by,
        ':approved_by'=> $approved_by,
        ':issue_date' => $issue_date,
        ':expiry_date'=> $expiry_date,
        ':status'     => 'Issued',
        ':conditions' => $conditions,
        ':dep_owner'  => $dep_owner
    ]);
    $permit_id = $pdo->lastInsertId();

    /* 3.b  STEPS  */
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

    /* 3.c  ATTACHMENTS (Save to disk)  */
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
    echo json_encode(['success' => true, 'permit_id' => $permit_id]);

} catch (Throwable $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
