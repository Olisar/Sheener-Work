<?php
/* File: sheener/php/upload_material_attachment.php */

/*  Sheener / php / upload_material_attachment.php  */
error_reporting(E_ALL);
ini_set('display_errors', 0);
header('Content-Type: application/json');

session_start();
require_once 'database.php';

try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Method not allowed');
    }
    
    $material_id = isset($_POST['material_id']) ? intval($_POST['material_id']) : 0;
    if (!$material_id) {
        throw new Exception('Missing material_id');
    }
    
    if (empty($_FILES['attachment'])) {
        throw new Exception('No file uploaded');
    }
    
    $file = $_FILES['attachment'];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Upload error: ' . $file['error']);
    }
    
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowedExts = ['pdf', 'xls', 'xlsx', 'doc', 'docx'];
    
    if (!in_array($ext, $allowedExts)) {
        throw new Exception('Unsupported file type. Allowed: PDF, Word, Excel.');
    }
    
    $uploadDir = __DIR__ . '/uploads';
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0777, true)) {
            throw new Exception('Failed to create upload directory');
        }
    }
    
    $safeName = (string)time() . '_' . preg_replace('/[^a-zA-Z0-9\.\-]/', '_', $file['name']);
    $targetPath = $uploadDir . '/' . $safeName;
    
    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        throw new Exception('Failed to move uploaded file');
    }
    
    $relativePath = 'php/uploads/' . $safeName;
    $uploadedBy = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : (isset($_SESSION['people_id']) ? $_SESSION['people_id'] : 1);
    $description = isset($_POST['description']) ? trim($_POST['description']) : null;
    
    $stmt = $pdo->prepare(
        "INSERT INTO attachments (material_id, file_name, file_type, file_size, file_path, uploaded_by, description, uploaded_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, NOW())"
    );
    
    $success = $stmt->execute([
        $material_id,
        $file['name'],
        $file['type'] ?? '',
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
