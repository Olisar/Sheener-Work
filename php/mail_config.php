<?php
/* File: sheener/php/mail_config.php */

/**
 * Gmail SMTP Configuration for Sheener
 * Used by PHPMailer to send automated permit emails
 */
return [
    'smtp_host'     => 'smtp.gmail.com',
    'smtp_port'     => 587,
    'smtp_auth'     => true,
    'smtp_username' => 'sheener.info@gmail.com',
    'smtp_password' => 'nipkylegtvdykcxg', // 16-character App Password (sheener1)
    'smtp_secure'   => 'tls',
    'from_email'    => 'sheener.info@gmail.com',
    'from_name'     => 'SHEEner Permit System'
];
?>
