<?php
/* File: sheener/php/add_permit.php */

// file: Sheener/php/add_permit.php

session_start();
header('Content-Type: application/json');
require_once 'database.php';

// Check authentication - allow user 32 and other authenticated users
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized - Please log in']);
    exit();
}

ini_set('display_errors', 1);
error_reporting(E_ALL);

try {
    $db = new Database();
    $pdo = $db->getConnection();
    $pdo->beginTransaction();

    // --- Collect Input ---
    $task_id      = $_POST["task_id"] ?? null;
    $permit_type  = $_POST["permit_type"] ?? null;
    $issued_by    = $_POST["issued_by"] ?? null;
    $approved_by  = $_POST["approved_by"] ?? null;
    $issue_date   = $_POST["issue_date"] ?? null;
    $expiry_date  = $_POST["expiry_date"] ?? null;
    $conditions   = $_POST["conditions"] ?? null;
    $status       = $_POST["status"] ?? 'Issued';
    $dep_owner    = $_POST["Dep_owner"] ?? 0;

    // --- Validate Permit Type & Status ---
    $valid_types = [
        'Hot Work', 'Cold Work', 'Clearance', 'Work at Height',
        'Confined Space', 'Electrical Work', 'General Work'
    ];
    $valid_statuses = ['Requested', 'Issued', 'Active', 'Suspended', 'Closed', 'Expired', 'Revoked', 'Cancelled'];

    // Permit type whitelist validation (server-side)
    if (!in_array($permit_type, $valid_types)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid permit type submitted']);
        exit;
    }
    if (!in_array($status, $valid_statuses)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid permit status']);
        exit;
    }

    // --- Validate Dates ---
    $issueDateFormats = ['Y-m-d', 'd/m/Y', 'm/d/Y'];
    $issueDate = $expiryDate = false;
    foreach ($issueDateFormats as $format) {
        if (!$issueDate)  $issueDate  = DateTime::createFromFormat($format, $issue_date);
        if (!$expiryDate) $expiryDate = DateTime::createFromFormat($format, $expiry_date);
    }
    if (!$issueDate || !$expiryDate || $expiryDate < $issueDate) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid date range']);
        exit;
    }

    // --- Validate Task ID ---
    if (!$task_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Task ID is required. Please create a task first before creating a Permit to Work. The task is required to store job information and relevant documents that will be linked to this permit.']);
        exit;
    }

    // --- Verify Task Exists ---
    $stmt = $pdo->prepare("SELECT task_id, task_name FROM tasks WHERE task_id = :task_id");
    $stmt->execute([':task_id' => $task_id]);
    $task = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$task) {
        http_response_code(400);
        echo json_encode([
            'success' => false, 
            'error' => 'The selected task does not exist. Please create a task first before creating a Permit to Work. The task is required to store job information and relevant documents that will be linked to this permit.'
        ]);
        exit;
    }

    // --- Insert Permit ---
    $stmt = $pdo->prepare("
        INSERT INTO permits (
            task_id, permit_type, issued_by, approved_by,
            issue_date, expiry_date, status, conditions, Dep_owner
        ) VALUES (
            :task_id, :permit_type, :issued_by, :approved_by,
            :issue_date, :expiry_date, :status, :conditions, :dep_owner
        )
    ");
    $stmt->execute([
        ':task_id'      => $task_id,
        ':permit_type'  => $permit_type,
        ':issued_by'    => $issued_by,
        ':approved_by'  => $approved_by,
        ':issue_date'   => $issue_date,
        ':expiry_date'  => $expiry_date,
        ':status'       => $status,
        ':conditions'   => $conditions,
        ':dep_owner'    => $dep_owner
    ]);
    $permitId = $pdo->lastInsertId();

    // --- Insert Steps ---
    $steps = isset($_POST['steps']) ? json_decode($_POST['steps'], true) : [];
    if (is_array($steps)) {
        $stmtStep = $pdo->prepare("INSERT INTO permit_steps (permit_id, step_number, step_description, hazard_description, control_description) VALUES (?, ?, ?, ?, ?)");
        foreach ($steps as $step) {
            $stmtStep->execute([
                $permitId,
                $step['step_number'],
                $step['step_description'],
                $step['hazard_description'],
                $step['control_description']
            ]);
        }
    }

    // --- Insert Approvers ---
    $approvers = isset($_POST['approvers']) ? json_decode($_POST['approvers'], true) : [];
    if (is_array($approvers)) {
        $stmt = $pdo->prepare("
            INSERT INTO permit_responsibles (
                permit_id, person_id, role, approval_order
            ) VALUES (?, ?, 'Approver', ?)
        ");
        foreach ($approvers as $order => $approverId) {
            $stmt->execute([$permitId, $approverId, $order + 1]);
        }
    }

    // --- Electrical Work Addon ---
    if ($permit_type === 'Electrical Work') {
        $energyType     = filter_input(INPUT_POST, 'energy_type', FILTER_VALIDATE_INT);
        $energyVerifier = filter_input(INPUT_POST, 'energy_verifier', FILTER_VALIDATE_INT);
        if ($energyType && $energyVerifier) {
            $stmt = $pdo->prepare("
                INSERT INTO permit_energies (
                    permit_id, energy_id, isolation_required, isolation_verified_by
                ) VALUES (?, ?, 1, ?)
            ");
            $stmt->execute([$permitId, $energyType, $energyVerifier]);
        }
    }

    // --- Upload Attachments ---
    if (!empty($_FILES['attachments']['name'][0])) {
        $uploadDir = __DIR__ . '/../uploads/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

        $allowedTypes = [
            'application/pdf', 'image/jpeg', 'image/png',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        ];
        $maxSize = 5 * 1024 * 1024; // 5MB

        foreach ($_FILES['attachments']['name'] as $i => $name) {
            $tmpPath = $_FILES['attachments']['tmp_name'][$i];
            $type    = $_FILES['attachments']['type'][$i];
            $size    = $_FILES['attachments']['size'][$i];
            $desc    = null;

            if (!in_array($type, $allowedTypes) || $size > $maxSize) continue;

            $safeName = uniqid() . '_' . basename($name);
            $destPath = $uploadDir . $safeName;

            if (move_uploaded_file($tmpPath, $destPath)) {
                $stmt = $pdo->prepare("
                    INSERT INTO attachments (
                        permit_id, file_name, file_type, file_size,
                        file_path, uploaded_by, description
                    ) VALUES (
                        :permit_id, :file_name, :file_type, :file_size,
                        :file_path, :uploaded_by, :description
                    )
                ");
                $stmt->execute([
                    ':permit_id'   => $permitId,
                    ':file_name'   => $name,
                    ':file_type'   => $type,
                    ':file_size'   => $size,
                    ':file_path'   => 'uploads/' . $safeName,
                    ':uploaded_by' => $issued_by,
                    ':description' => $desc
                ]);
            }
        }
    }

    // --- Final Output ---
    $pdo->commit();
    echo json_encode(['success' => true, 'permit_id' => $permitId]);

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    error_log(date('[Y-m-d H:i:s]') . ' Permit Add Error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Transaction failed: ' . $e->getMessage()]);
}
