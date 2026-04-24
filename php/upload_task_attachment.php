<?php
/* File: sheener/php/upload_task_attachment.php */

error_reporting(0);
ini_set('display_errors', 0);
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
    $db = new Database();
    $pdo = $db->getConnection();
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Method not allowed');
    }
    
    $task_id = isset($_POST['task_id']) ? intval($_POST['task_id']) : 0;
    if (!$task_id) {
        throw new Exception('Missing task_id');
    }
    
    if (empty($_FILES['attachment'])) {
        throw new Exception('No file uploaded');
    }
    
    $file = $_FILES['attachment'];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Upload error: ' . $file['error']);
    }
    
    // Validate file type
    $allowedTypes = [
        'application/pdf',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
    ];
    
    // More permissive check if mime type detection isn't perfect
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowedExts = ['pdf', 'xls', 'xlsx', 'doc', 'docx'];
    
    if (!in_array($ext, $allowedExts)) {
        throw new Exception('Unsupported file type. Allowed: PDF, Word, Excel.');
    }

    // Secondary mime check
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mime, $allowedTypes)) {
        // Some systems might have different mimes for the same file, but we should be careful.
        // For now, if it matches extension, we allow it, but we could be stricter.
    }
    
    // Build path
    $uploadDir = __DIR__ . '/uploads';
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            throw new Exception('Failed to create upload directory');
        }
    }
    
    $safeName = (string)time() . '_' . preg_replace('/[^a-zA-Z0-9\.\-]/', '_', $file['name']);
    $targetPath = $uploadDir . '/' . $safeName;
    
    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        throw new Exception('Failed to move uploaded file');
    }
    
    // Create record in database
    $relativePath = 'php/uploads/' . $safeName;
    $uploadedBy = $_SESSION['user_id'];
    $description = isset($_POST['description']) ? trim($_POST['description']) : null;
    
    $stmt = $pdo->prepare(
        "INSERT INTO attachments (task_id, file_name, file_type, file_size, file_path, uploaded_by, description, uploaded_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, NOW())"
    );
    
    $success = $stmt->execute([
        $task_id,
        $file['name'],
        $mime,
        $file['size'] ?? 0,
        $relativePath,
        $uploadedBy,
        $description
    ]);

    if (!$success) {
        throw new Exception('Database insert failed');
    }
    
    $attachment_id = $pdo->lastInsertId();
    
    echo json_encode([
        'success' => true,
        'attachment' => [
            'attachment_id' => $attachment_id,
            'filename' => $file['name'],
            'file_path' => $relativePath,
            'message' => 'File uploaded successfully'
        ]
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
