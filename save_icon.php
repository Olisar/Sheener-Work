<?php
/* File: sheener/save_icon.php */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    if ($data) {
        $filename = basename($data['filename']); // prevent path traversal
        $imageData = $data['image'];
        
        // Remove header if present
        $imageData = str_replace('data:image/png;base64,', '', $imageData);
        $imageData = str_replace(' ', '+', $imageData);
        
        $filePath = __DIR__ . '/img/icons/' . $filename;
        
        if (!is_dir(dirname($filePath))) {
            mkdir(dirname($filePath), 0777, true);
        }
        
        if (file_put_contents($filePath, base64_decode($imageData))) {
            echo json_encode(['success' => true, 'file' => $filePath]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to write file']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'No data received']);
    }
}
?>
