<?php
/* File: sheener/php/email_permit_action.php */
session_start();
header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// Load PHPMailer
require_once 'PHPMailer/Exception.php';
require_once 'PHPMailer/PHPMailer.php';
require_once 'PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Load config
$mailConfig = require_once 'mail_config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

try {
    $permitId = $_POST['permit_id'] ?? null;
    $recipient = $_POST['recipient'] ?? '';
    $subject = $_POST['subject'] ?? 'Permit Document';
    $messageText = $_POST['message'] ?? '';
    
    if (!$recipient) {
        throw new Exception('Recipient email is required');
    }

    if (!filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid recipient email address');
    }

    // Handle PDF Attachment from POST
    if (!isset($_FILES['permit_pdf']) || $_FILES['permit_pdf']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('PDF attachment is missing or upload failed');
    }

    $pdfTmpPath = $_FILES['permit_pdf']['tmp_name'];
    $pdfName = $_FILES['permit_pdf']['name'];

    // Initialize PHPMailer
    $mail = new PHPMailer(true);

    // Server settings
    $mail->isSMTP();
    $mail->Host       = $mailConfig['smtp_host'];
    $mail->SMTPAuth   = $mailConfig['smtp_auth'];
    $mail->Username   = $mailConfig['smtp_username'];
    $mail->Password   = $mailConfig['smtp_password'];
    $mail->SMTPSecure = $mailConfig['smtp_secure'];
    $mail->Port       = $mailConfig['smtp_port'];

    // Recipients
    $mail->setFrom($mailConfig['from_email'], $mailConfig['from_name']);
    $mail->addAddress($recipient);
    $mail->addReplyTo($mailConfig['from_email'], $mailConfig['from_name']);

    // Attachment
    $mail->addAttachment($pdfTmpPath, $pdfName);

    // Content
    $mail->isHTML(false); // Plain text body
    $mail->Subject = $subject;
    $mail->Body    = $messageText;

    $mail->send();
    
    echo json_encode(['success' => true, 'message' => 'Email sent successfully via Gmail with automated attachment!']);

} catch (Exception $e) {
    error_log("PHPMailer Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => "Mail failed: " . $mail->ErrorInfo]);
} catch (\Exception $e) {
    error_log("Email Action Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
