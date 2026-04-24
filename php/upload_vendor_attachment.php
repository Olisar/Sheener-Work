<?php
/* File: sheener/php/upload_vendor_attachment.php */

/*  Sheener / php / upload_vendor_attachment.php  */
ob_start(); // Buffer output to prevent accidental warnings from breaking JSON
error_reporting(E_ALL);
ini_set('display_errors', 0);
header('Content-Type: application/json');

session_start();
require_once 'database.php';

$debugFile = __DIR__ . '/debug_upload_vendor.txt';
file_put_contents($debugFile, date('[Y-m-d H:i:s] ') . "Starting upload...\n", FILE_APPEND);

try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Method not allowed');
    }
    
    // Check if POST data is missing but Content-Length is high (typical of post_max_size exceeded)
    if (empty($_FILES) && empty($_POST) && isset($_SERVER['CONTENT_LENGTH']) && $_SERVER['CONTENT_LENGTH'] > 0) {
        $limit = ini_get('post_max_size');
        throw new Exception("The file is too large for the server. (Limit: $limit). Please try a smaller file or contact admin.");
    }

    $vendor_id = isset($_POST['vendor_id']) ? intval($_POST['vendor_id']) : 0;
    if (!$vendor_id) {
        throw new Exception('Missing vendor_id');
    }
    
    if (empty($_FILES['attachment'])) {
        throw new Exception('No file uploaded');
    }
    
    $file = $_FILES['attachment'];
    file_put_contents($debugFile, date('[Y-m-d H:i:s] ') . "File: " . $file['name'] . " size: " . $file['size'] . "\n", FILE_APPEND);

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errorMsg = 'Upload error: ' . $file['error'];
        if ($file['error'] === UPLOAD_ERR_INI_SIZE) $errorMsg = 'File exceeds upload_max_filesize in php.ini';
        throw new Exception($errorMsg);
    }
    
    // Validate file extension
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowedExts = ['pdf', 'xls', 'xlsx', 'doc', 'docx'];
    
    if (!in_array($ext, $allowedExts)) {
        throw new Exception('Unsupported file type. Allowed: PDF, Word, Excel.');
    }
    
    // Build path
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
    
    // Create record in database
    $relativePath = 'php/uploads/' . $safeName;
    $uploadedBy = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : (isset($_SESSION['people_id']) ? $_SESSION['people_id'] : 1);
    $description = isset($_POST['description']) ? trim($_POST['description']) : null;
    
    $stmt = $pdo->prepare(
        "INSERT INTO attachments (vendor_id, file_name, file_type, file_size, file_path, uploaded_by, description, uploaded_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, NOW())"
    );
    
    $success = $stmt->execute([
        $vendor_id,
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
    file_put_contents($debugFile, date('[Y-m-d H:i:s] ') . "Success: " . $attachment_id . "\n", FILE_APPEND);
    
    // Clean buffer and send JSON
    ob_clean();
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
    if (ob_get_length()) ob_clean();
    file_put_contents($debugFile, date('[Y-m-d H:i:s] ') . "ERROR: " . $e->getMessage() . "\n", FILE_APPEND);
    echo json_encode([
        'success' => false, 
        'error' => $e->getMessage(),
        'debug' => 'Refer to debug_upload_vendor.txt'
    ]);
}
?>
